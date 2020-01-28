<?php

namespace Tests;

use Carbon\Carbon;
use DateTime;
use Faker\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Support\LazyCollection;
use Orchestra\Testbench\TestCase;
use Sushi\Sushi;

class SushiTest extends TestCase
{
    protected function tearDown(): void
    {
        (new Maki())->getConnection()->getSchemaBuilder()->dropAllTables();

        parent::tearDown();
    }

    /** @test */
    public function it_generates_correct_schema()
    {
        $table = new Blueprint('table');

        Maki::getSchema($table, new Maki(), Maki::getRows()->first());

        $columns = collect($table->getColumns());

        $id = $columns->firstWhere('name', 'id');
        $this->assertEqualsColumnDefinition([
            'type' => 'bigInteger',
            'name' => 'id',
            'autoIncrement' => true,
            'unsigned' => true,
        ], $id);

        $name = $columns->firstWhere('name', 'name');
        $this->assertEqualsColumnDefinition([
            'type' => 'string',
            'name' => 'name',
            'nullable' => true,
        ], $name);

        $age = $columns->firstWhere('name', 'age');
        $this->assertEqualsColumnDefinition([
            'type' => 'integer',
            'name' => 'age',
            'nullable' => true,
        ], $age);

        $price = $columns->firstWhere('name', 'price');
        $this->assertEqualsColumnDefinition([
            'type' => 'float',
            'name' => 'price',
            'nullable' => true,
        ], $price);

        $properties = $columns->firstWhere('name', 'properties');
        $this->assertEqualsColumnDefinition([
            'type' => 'json',
            'name' => 'properties',
            'nullable' => true,
        ], $properties);

        $publishedAt = $columns->firstWhere('name', 'published_at');
        $this->assertEqualsColumnDefinition([
            'type' => 'timestamp',
            'name' => 'published_at',
            'nullable' => true,
        ], $publishedAt);

        $createdAt = $columns->firstWhere('name', 'created_at');
        $this->assertEqualsColumnDefinition([
            'type' => 'timestamp',
            'name' => 'created_at',
            'nullable' => true,
        ], $createdAt);

        $updatedAt = $columns->firstWhere('name', 'updated_at');
        $this->assertEqualsColumnDefinition([
            'type' => 'timestamp',
            'name' => 'updated_at',
            'nullable' => true,
        ], $updatedAt);
    }

    /** @test */
    public function it_inserts_all_rows()
    {
        $this->assertSame(101, Maki::count());
    }

    /** @test */
    public function it_uses_correct_attributes()
    {
        $model = Maki::whereKey(1)->first();

        $this->assertSame(1, $model->id);
        $this->assertSame('foobar', $model->name);
        $this->assertSame(10, $model->age);
        $this->assertSame(9.99, $model->price);
        $this->assertSame(['lorem', 'ipsum'], $model->properties);
        $this->assertInstanceOf(DateTime::class, $model->published_at);
        $this->assertSame('2020-01-25 19:45:30', $model->published_at->format('Y-m-d H:i:s'));
        $this->assertInstanceOf(DateTime::class, $model->created_at);
        $this->assertInstanceOf(DateTime::class, $model->updated_at);
    }

    protected function assertEqualsColumnDefinition(array $expected, $actual)
    {
        $this->assertInstanceOf(ColumnDefinition::class, $actual);
        foreach($expected as $key => $value) {
            $this->assertSame($value, $actual[$key]);
        }
    }
}

class Maki extends Model
{
    use Sushi;

    protected $guarded = [];

    public $casts = [
        'age' => 'int',
        'price' => 'float',
        'properties' => 'array',
        'published_at' => 'datetime',
    ];

    protected const ROWS = [
        ['id' => 1, 'name' => 'foobar', 'age' => 10, 'price' => 9.99, 'properties' => ['lorem', 'ipsum'], 'published_at' => '2020-01-25 19:45:30'],
        ['id' => 2, 'name' => 'minion', 'age' => 20, 'price' => 19.99, 'properties' => ['minion', 'banana'], 'published_at' => '2020-01-28 08:15:20'],
    ];

    public static function getRows(): LazyCollection
    {
        $faker = Factory::create();

        return LazyCollection::range(0, 100)->map(function (int $i) use ($faker): array {
            $row = self::ROWS[$i] ?? [
                'name' => $faker->word,
                'age' => $faker->numberBetween(1, 99),
                'price' => $faker->randomFloat(2, 1, 1000),
                'properties' => $faker->words(),
                'published_at' => $faker->dateTimeThisDecade,
            ];

            $row['published_at'] = Carbon::make($row['published_at']);

            return $row;
        });
    }
}
