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
