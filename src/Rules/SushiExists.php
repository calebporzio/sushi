<?php

namespace Sushi\Rules;

use Sushi\Sushi;
use Illuminate\Contracts\Validation\Rule;

class SushiExists implements Rule
{
    /**
     * Model instance.
     *
     * @var \Illuminate\Database\Eloquent\Model
     * @var \Sushi\Sushi
     */
    protected $model;

    /**
     * Column to check if the passed value exists.
     *
     * @var string
     */
    protected $column;

    /**
     * Create a new rule instance.
     *
     * @param string $model
     * @param string $column
     * @return void
     */
    public function __construct(string $model, string $column = 'id')
    {
        $this->model = new $model;
        $this->column = $column;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return $this->hasSushiTrait() && collect($this->model->getRows())->pluck($this->column)->contains($value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The selected :attribute is invalid.';
    }

    /**
     * Checks if the given model has the sushi trait implemented.
     *
     * @return bool
     */
    private function hasSushiTrait()
    {
        return in_array(Sushi::class, array_keys(class_uses_recursive($this->model)));
    }
}
