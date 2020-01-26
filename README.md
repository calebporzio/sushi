# Sushi 🍣
Eloquent's missing "array" driver.

Sometimes you want to use Eloquent, but without the database.

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

### Relationships
Let's say you created a `Role` model, based on an array using Sushi, that looked like this:
```php
class Role extends Model
{
    use \Sushi\Sushi;

    protected $rows = [
        ['id' => 1, 'label' => 'admin'],
        ['id' => 2, 'label' => 'manager'],
        ['id' => 3, 'label' => 'user'],
    ];
}
```

You can add a relationship to another standard model, just like you normally would:
```php
class User extends Model
{
    ...

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
```

Assuming the `users` table has a `role_id` column, you can do things like this:
```php
// Grab a User.
$user = User::first();
// Grab a Role.
$role = Role::whereLabel('admin')->first();

// Associate them.
$user->role()->associate($role);

// Access like normal.
$user->role;

// Eager load.
$user->load('role');
User::with('role')->first();
```

> Note: There is one cavaet when dealing with Sushi model relationships. The `whereHas` method will NOT work. This is because the two models are spread across two separate databases.

## How It Works
Under the hood, this package creates and caches a SQLite database JUST for this model. It addes a table and populates the rows. If, for whatever reason, it can't cache a .sqlite file, it will default to using an in-memory sqlite database.
