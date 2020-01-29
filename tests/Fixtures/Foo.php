<?php

namespace Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

class Foo extends Model
{
    use Sushi;

    protected $rows = [
        [
            'foo' => 'bar',
            'bob' => 'lob',
        ],
        [
            'foo' => 'baz',
            'bob' => 'law',
        ],
    ];

    public static function resetStatics()
    {
        static::setSushiConnection(null);
        static::clearBootedModels();
    }

    public static function setSushiConnection($connection)
    {
        static::$sushiConnection = $connection;
    }
}
