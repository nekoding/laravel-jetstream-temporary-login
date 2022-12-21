<?php

namespace App\Services\TempLogin;

class BasicTempLogin implements TempLoginContract
{

    public function generate(int $size = 4): string
    {
        $code = [];

        for ($i=0; $i < $size; ++$i) {
            $code[] = $this->randomWords(rand(4, 5));
        }

        return implode('-', $code);
    }

    public function randomWords(int $length): string
    {
        $words = range('a', 'z');
        $string = '';

        for ($i=0; $i<$length; ++$i) {
            $string .= $words[rand(0, count($words) - 1)];
        }

        return $string;
    }

}
