<?php

namespace Sushi\Exceptions;

class MigrationException extends \Exception
{
    public static function modelRowsNotFound($class)
    {
        return new static('Sushi: $rows property not found on model: ' . $class);
    }
}
