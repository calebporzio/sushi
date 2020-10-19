<?php

namespace Tests;

use Sushi\Sushi;
use Sushi\Rules\SushiExists;
use Orchestra\Testbench\TestCase;
use Illuminate\Database\Eloquent\Model;

class SushiExistsTest extends TestCase
{
    /** @test */
    public function it_returns_true_if_the_passed_value_exists_on_the_sushi_model()
    {
        $rule = new SushiExists(AwesomeSushiModel::class);

        $this->assertTrue($rule->passes('awesome_id', 1));

        $this->assertFalse($rule->passes('awesome_id', 9999));
    }

    /** @test */
    public function it_returns_false_if_the_model_doesnt_have_the_sushi_trait_implemented()
    {
        $rule = new SushiExists(BoringNormalModel::class);

        $this->assertFalse($rule->passes('awesome_id', 1));
    }

    /** @test */
    public function it_accepts_an_existing_column_value()
    {
        $rule = new SushiExists(AwesomeSushiModel::class, 'name');

        $this->assertTrue($rule->passes('awesome_name', 'second_row'));
    }
}

class AwesomeSushiModel extends Model
{
    use Sushi;

    /**
     * @return array
     */
    protected $rows = [
        [
            'id' => 1,
            'name' => 'first_row',
            'display_name' => 'The first row',
        ],
        [
            'id' => 2,
            'name' => 'second_row',
            'display_name' => 'The second row',
        ],
        [
            'id' => 3,
            'name' => 'third_row',
            'display_name' => 'The third row',
        ],
    ];
}

class BoringNormalModel extends Model
{
}
