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
        if (static::$faker === null) {
            static::$faker = static::createFaker();
        }

        return static::$faker;
    }
}