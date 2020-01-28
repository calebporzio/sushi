<?php

namespace Sushi;

use DateTime;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Arr;
use Illuminate\Support\LazyCollection;

/** @mixin Model */
trait Sushi
{
    protected static SQLiteConnection $sushiConnection;

    protected static function bootSushi(): void
    {
        static::migrate();
    }

    public function getConnection(): SQLiteConnection
    {
        static::$sushiConnection ??= $this->getConnectionFactory()->make([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ], 'sushi');

        return static::$sushiConnection;
    }

    protected function getConnectionFactory(): ConnectionFactory
    {
        return app(ConnectionFactory::class);
    }

    public static function migrate(): void
    {
        $firstRow = static::getRows()->first();
        $model = new static();

        $model->getConnection()->getSchemaBuilder()->create(
            $model->getTable(),
            static function (Blueprint $table) use ($model, $firstRow): void {
                forward_static_call([static::class, 'getSchema'], $table, $model, $firstRow);
            }
        );

        static::getRows()->each(static function (array $row): void {
            static::create($row);
        });
    }

    public static function getRows(): LazyCollection
    {
        return LazyCollection::make(static::ROWS);
    }

    public static function getSchema(Blueprint $table, Model $model, array $row): void
    {
        if ($model->getIncrementing()) {
            $table->bigIncrements($model->getKeyName());
        }

        if ($model->usesTimestamps()) {
            $table->timestamps();
        }

        if (method_exists(static::class, 'tapSchema')) {
            forward_static_call([static::class, 'tapSchema'], $table, $model, $row);

            return;
        }

        foreach (Arr::except($row, ['id', 'created_at', 'updated_at']) as $column => $value) {
            if (is_int($value)) {
                $type = 'integer';
            } elseif (is_float($value)) {
                $type = 'float';
            } elseif (is_array($value)) {
                $type = 'json';
            } elseif (is_object($value) && $value instanceof DateTime) {
                $type = 'timestamp';
            }

            $type = $type ?? 'string';

            $table->{$type}($column)->nullable();
        }
    }
}
