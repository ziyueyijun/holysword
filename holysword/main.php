<?php

namespace HolySword;

use Dotenv;

class main
{
    public static function init(): void
    {
        self::env();
    }

    private static function env(): void
    {
        $dotenv = Dotenv\Dotenv::createImmutable(ROOT_PATH);
        $dotenv->safeLoad();
    }

}