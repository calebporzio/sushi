# Sushi 🍣
Eloquent's missing "array" driver.

Sometimes you want to use Eloquent, but without dealing with a database.

## This Package Is Sponsorware 💰💰💰
Originally, this package was only available to my sponsors on GitHub Sponsors until I reached 75 sponsors.

Now that we've reached the goal, the package is fully open source.

Enjoy, and thanks for the support! ❤️

Learn more about **Sponsorware** at [github.com/sponsorware/docs](https://github.com/sponsorware/docs) 💰.


## Requirements

The [`pdo-sqlite` PHP extension](https://www.php.net/manual/en/ref.pdo-sqlite.php) must be installed on your system to use this package.

## Install
```
composer require calebporzio/sushi
```

## Use

Using this package consists of two steps:
1. Add the `Sushi` trait to a model.
2. Add a `$rows` property to the model.

That's it.

```php
class State extends Model
{
    use \Sushi\Sushi;

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

This is really useful for "Fixture" data, like states, countries, zip codes, user_roles, sites_settings, etc...

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

> Note: There is one caveat when dealing with Sushi model relationships. The `whereHas` method will NOT work. This is because the two models are spread across two separate databases.

### Using database-checking validation rules
You can even use Laravel's `exists:table,column` database checking request validation rule.

```php
$data = request()->validate([
    'state' => ['required', 'exists:App\Models\State,abbr'],
]);
```

> Note: Be aware that you must use the fully-qualified namespace of the model instead of a table name. This ensures that Laravel will correctly resolve the model's connection.

### Custom Schema
If Sushi's schema auto-detection system doesn't meet your specific requirements for the supplied row data, you can customize them with the `$schema` property or the `getSchema()` method.

```php
class Products extends Model
{
    use \Sushi\Sushi;

    protected $rows = [
        ['name' => 'Lawn Mower', 'price' => '226.99'],
        ['name' => 'Leaf Blower', 'price' => '134.99'],
        ['name' => 'Rake', 'price' => '9.99'],
    ];

    protected $schema = [
        'price' => 'float',
    ];
}
```

## Advanced Usage

When you need more flexibility, you can implement the `afterMigrate(BluePrint $table)` method, allowing you to customize the table after it has been created. This might be useful for adding indexes to certain columns.

```php
class Products extends Model
{
    use \Sushi\Sushi;

    protected $rows = [
        ['name' => 'Lawn Mower', 'price' => '226.99'],
        ['name' => 'Leaf Blower', 'price' => '134.99'],
        ['name' => 'Rake', 'price' => '9.99'],
    ];

    protected function afterMigrate(Blueprint $table)
    {
        $table->index('name');
    }
}
```

## How It Works
Under the hood, this package creates and caches a SQLite database JUST for this model. It creates a table and populates the rows. If, for whatever reason, it can't cache a .sqlite file, it will default to using an in-memory sqlite database.

## Using ->getRows()
You can optionally opt out of using the `protected $rows` property, and directly implement your own `getRows()` method.

This will allow you to determine the rows for the model at runtime. You can even generate the model's rows from an external source like a third-party API.


```php
class Role extends Model
{
    use \Sushi\Sushi;

    public function getRows()
    {
        return [
            ['id' => 1, 'label' => 'admin'],
            ['id' => 2, 'label' => 'manager'],
            ['id' => 3, 'label' => 'user'],
        ];
    }
}
```

### Caching ->getRows()

If you choose to use your own ->getRows() method, the rows will NOT be cached between requests by default.

You can force Sushi to cache your dataset with the following method: `sushiShouldCache()`.

Let's look at a configuration where `->getRows()` datasets would be cached as an example:

```php
class Role extends Model
{
    use \Sushi\Sushi;

    public function getRows()
    {
        return [
            ['id' => 1, 'label' => 'admin'],
            ['id' => 2, 'label' => 'manager'],
            ['id' => 3, 'label' => 'user'],
        ];
    }

    protected function sushiShouldCache()
    {
        return true;
    }
}
```

By default, Sushi looks at the "last modified" timestamp of your model PHP file and compares it with its internal `.sqlite` cache file. If the model file has been changed more recently than the `.sqlite` cache file, then Sushi will destroy and rebuild the `.sqlite` cache.
Additionally, you can configure an external file for Sushi to reference when determining if the cache is up to date or needs to be refreshed.

If, for example, you are using Sushi to provide an Eloquent model for an external data source file like an `.csv` file, you can use `sushiCacheReferencePath` to force Sushi to reference the `.csv` file when determining if the cache is stale.

For example:

```php
class Role extends Model
{
    use \Sushi\Sushi;

    public function getRows()
    {
        return CSV::fromFile(__DIR__.'/roles.csv')->toArray();
    }

    protected function sushiShouldCache()
    {
        return true;
    }

    protected function sushiCacheReferencePath()
    {
        return __DIR__.'/roles.csv';
    }
}
```

Now, Sushi will only "bust" its internal cache if `roles.csv` changes, rather than looking at the `Role.php` model.

### Handling Empty Datasets
Sushi reads the first row in your dataset to work out the scheme of the SQLite table. If you are using `getRows()` and this returns an empty array (e.g an API returns nothing back) then Sushi would throw an error.

If you would like Sushi to work even if the dataset is empty, you can define your schema in the optional `protected $schema` array.

> Note: If you choose to use your own ->getRows() method, the rows will NOT be cached between requests.

```php
class Currency extends Model
{
    use \Sushi\Sushi;

    protected $schema = [
        'id' => 'integer',
        'name' => 'string',
        'symbol' => 'string',
        'precision' => 'float'
    ];

    public function getRows()
    {
        return [];
    }
}
```

### Handling String-based Primary Keys
Sushi requires you to add two properties to your model, if it uses a string-based primary key - `$incrementing` and `$keyType`:

```php
class Role extends Model
{
    use \Sushi\Sushi;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $rows = [
        ['id' => 'admin', 'label' => 'Admin'],
        ['id' => 'manager', 'label' => 'Manager'],
        ['id' => 'user', 'label' => 'User'],
    ];
}
```

### Troubleshoot

**ERROR:** `SQLSTATE[HY000]: General error: 1 too many SQL variables`

By default Sushi uses chunks of `100` to insert your data in the SQLite database. In some scenarios this might hit some SQLite limits.
You can configure the chunk size in the model: `public $sushiInsertChunkSize = 50;`
