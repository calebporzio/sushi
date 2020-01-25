# Sushi
Eloquent's missing "array" driver.

Sometimes you just want to use eloquent, but without all the hastle.

## Install
`composer require calebporzio/sushi`

## Use

Using this package consists of two steps:
1. Add the `Sushi` trait to a model.
2. Add a `$rows` property to the model.

That's it.

```php
use Sushi\Sushi as TheMissingEloquentArrayDriver;

class State extends Model
{
    use TheMissingEloquentArrayDriver;

    protected $rows = [
        [
            'abbr' => 'NY',
            'name' => 'New York',
        ],
        [
            'abbr' => 'CA',
            'name' => 'California',
        ],
    ];
}
```

Now, you can use this model anywhere you like, and it will behave as if you created a table with the rows you provided.
```php
$stateName = State::whereAbbr('NY')->first()->name;
```

This is really useful for "Fixture" data, like states, contries, zip codes, user_roles, sites_settings, etc...

## How It Works
Under the hood, this package creates a SQLite in-memory database JUST for this model. It addes a table and populates the rows, all within each request.

SQLite is really fast, and my quick little benchmark with a 200 row model added a .009 second overhead to my application. "That's a number I can live with!" -- Penguins
