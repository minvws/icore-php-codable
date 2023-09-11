<?php

declare(strict_types=1);

namespace MinVWS\Tests\Codable\Traits;

use Faker\Factory;
use Faker\Generator;

trait WithFaker
{
    private static ?Generator $faker = null;

    protected static function createFaker(): Generator
    {
        return Factory::create();
    }

    protected static function faker(): Generator
    {
        if (self::$faker === null) {
            self::$faker = static::createFaker();
        }

        return self::$faker;
    }
}
