# MongoDB Package Installation
* Check compatibility of php, laravel or mongodb version
### Install the MongoDB PHP extension
```
    sudo apt update
    sudo apt install php8.2-mongodb // Install the MongoDB PHP extension
    sudo phpenmod mongodb           // Enable the extension
    sudo systemctl restart apache2  // Restart PHP / Web Server
    php -m | grep mongodb           // Verify the extension is installed // Output - mongodb
```
### Install MongoDB package
```
composer require mongodb/laravel-mongodb
```
### Add MongoDB connection in config/database.php
```
'mongodb' => [
    'driver' => 'mongodb',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', 27017),
    'database' => env('DB_DATABASE'),
    'username' => env('DB_USERNAME'),
    'password' => env('DB_PASSWORD'),
    'options' => [
        'database' => env('DB_AUTHENTICATION_DATABASE', 'admin'),
    ],
],

```
### Create First model app/Models/User.php
* Paste the following code in User.php
```
<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class User extends Model
{
    // protected $connection = 'mongodb'; // MongoDB connection
    // protected $collection = 'users';   // Collection name

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}

```


# Migrations Command
### Create databse/MongoMigration Folder 
* All the migrations files goes here.

### Create stubs/mongo-migration.stub in root directory
* Add the following code in stub file (Template File)
```
    <?php

    use Illuminate\Support\Facades\DB;

    return new class
    {
        /**
        * Run the migrations.
        *
        * This creates the collection and optional indexes.
        */
        public function up()
        {
            $collectionName = '{{ collection }}';
            $db = DB::connection('mongodb')->getMongoDB();

            // Check if collection exists
            $collections = iterator_to_array($db->listCollections());
            $collectionNames = array_map(fn($c) => $c->getName(), $collections);

            if (!in_array($collectionName, $collectionNames)) {
                $db->createCollection($collectionName);
            }

            // Example: create indexes (optional)
            // $db->selectCollection($collectionName)->createIndex(['email' => 1], ['unique' => true]);
            // $db->selectCollection($collectionName)->createIndex(['username' => 1]);
        }

        /**
        * Reverse the migrations.
        *
        * Drops the collection.
        */
        public function down()
        {
            $collectionName = '{{ collection }}';
            DB::connection('mongodb')->getMongoDB()->dropCollection($collectionName);
        }
    };

```
### Create MakeMongoMigration Command
* Create this file Console/Commands/MakeMongoMigration.php
* Paste the following code in MakeMongoMigration.php
* command => php artisan make:mongo-migration collection_name
```
    <?php

    namespace App\Console\Commands;

    // use Illuminate\Console\Command;
    use Illuminate\Console\GeneratorCommand;
    use Illuminate\Support\Str;

    class MakeMongoMigration extends GeneratorCommand
    {
        /**
        * The name and signature of the console command.
        *
        * @var string
        */
        protected $signature = 'make:mongo-migration {name}';

        /**
        * The console command description.
        *
        * @var string
        */
        protected $description = 'Create a new MongoDB collection migration file';

        /**
        * Execute the console command.
        */
        public function handle()
        {
            $name = $this->argument('name');

            // Generate timestamp
            $timestamp = date('Y_m_d_His');

            // File path inside MongoMigrations
            $path = base_path("/database/MongoMigrations/{$timestamp}_create_{$name}_collection.php");

            // Put file content using stub
            $this->files->put($path, $this->buildClass($name));

            $this->info("MongoDB migration created: {$path}");
        }

        protected function getStub()
        {
            return base_path('stubs/mongo-migration.stub');
        }

        protected function buildClass($name)
        {
            $stub = $this->files->get($this->getStub());

            // Replace placeholder inside stub
            return str_replace(
                ['{{ class }}', '{{ collection }}'],
                [$this->makeClassName($name), $name],
                $stub
            );
        }

        protected function makeClassName($name)
        {
            return Str::studly($name) . 'Migration';
        }
    }

```

# Migrate Command
### Create MongoMigrate Command
* Create this file Console/Commands/MongoMigrate.php
* Paste the following code in MongoMigrate.php
* command => php artisan migrate:mongo
```
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MongoMigrate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:mongo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run all MongoDB migrations in database/MongoMigrations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $migrationsPath = base_path('database/MongoMigrations');

        if (!File::exists($migrationsPath)) {
            $this->error("MongoMigrations folder does not exist: {$migrationsPath}");
            return;
        }

        $files = File::files($migrationsPath);

        if (empty($files)) {
            $this->info("No MongoDB migration files found in {$migrationsPath}");
            return;
        }

        foreach ($files as $file) {
            $this->info("Running migration: " . $file->getFilename());

            // Include the file and run up() method
            $migration = include $file->getRealPath();

            if (method_exists($migration, 'up')) {
                $migration->up();
            }
        }

        $this->info("All MongoDB migrations executed successfully.");
    }
}

```