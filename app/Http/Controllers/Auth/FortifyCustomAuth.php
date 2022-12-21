<?php

namespace App\Http\Controllers\Auth;

use App\Models\TemporaryLoginCode;
use App\Services\TempLogin\TempLoginContract;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Actions\AttemptToAuthenticate;
use Laravel\Fortify\Actions\EnsureLoginIsNotThrottled;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;

class FortifyCustomAuth extends AuthenticatedSessionController
{

    public function __construct(
        StatefulGuard $statefulGuard,
        protected readonly TempLoginContract $tempLoginContract
    ) {
        parent::__construct($statefulGuard);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'email'     => 'required|email|exists:users,email',
            'loginCode' => [
                'nullable',
                'string'
            ]
        ]);

        if (!isset($data['loginCode'])) {
            $state = [
                'email'      => $data['email'],
                'action'     => 'login',
                'timestamp'  => now()->timestamp,
            ];

            $tempCode = $this->tempLoginContract->generate();
            $encryptState = Crypt::encrypt(json_encode($state));

            TemporaryLoginCode::create([
                'state'         => $encryptState,
                'temp_code'     => $tempCode,
                'expired_at'    => now()->addMinutes(10)
            ]);

            return Inertia::render("Auth/Login", [
                'showLoginCodeInput' => true,
                'status'             => 'We just sent you a temporary login code. Please check your inbox.',
            ]);
        }

        $tempLoginCode = \App\Models\TemporaryLoginCode::where('temp_code', $data['loginCode'])->first();

        if (
            !$tempLoginCode ||
            $tempLoginCode->expired_at->lte(now()) ||
            json_decode(Crypt::decrypt($tempLoginCode->state))->email != $request->email
        ) {
            return inertia('Auth/Login', [
                'showLoginCodeInput' => true,
                'errors.loginCode' => 'Your login code was incorrect. Please try again.'
            ]);
        }

        // delete temp login code
        $tempLoginCode->delete();

        return $this->loginPipeline($request)->then(function ($request) {
            return app(LoginResponse::class);
        });
    }

    /**
     * Get the authentication pipeline instance.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Pipeline\Pipeline
     */
    protected function loginPipeline(Request $request)
    {
        if (Fortify::$authenticateThroughCallback) {
            return (new Pipeline(app()))->send($request)->through(array_filter(
                call_user_func(Fortify::$authenticateThroughCallback, $request)
            ));
        }

        if (is_array(config('fortify.pipelines.login'))) {
            return (new Pipeline(app()))->send($request)->through(array_filter(
                config('fortify.pipelines.login')
            ));
        }

        return (new Pipeline(app()))->send($request)->through(array_filter([
            config('fortify.limiters.login') ? null : EnsureLoginIsNotThrottled::class,
            Features::enabled(Features::twoFactorAuthentication()) ? RedirectIfTwoFactorAuthenticatable::class : null,
            AttemptToAuthenticate::class,
            PrepareAuthenticatedSession::class,
        ]));
    }
}
