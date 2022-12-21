<?php

namespace App\Services\TempLogin;

interface TempLoginContract
{

    /**
     * generate random temporal code
     *
     * @param  int $size
     * @return string
     */
    public function generate(int $size = 4): string;

    /**
     * seed random words
     *
     * @param  mixed $length
     * @return string
     */
    public function randomWords(int $length): string;

}
