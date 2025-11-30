<?php

namespace App\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class MakeMongoMigration extends GeneratorCommand
{
    /**
     * Command signature.
     *
     * Multiple collection names can be given:
     * php artisan mongo:migration users agencies vendors
     *
     * @var string
     */
    protected $signature = 'mongo:migration {collections* : One or more collection names}';

    /**
     * Command description.
     *
     * @var string
     */
    protected $description = 'Create one or more MongoDB collection migration files';

    /**
     * Execute the command.
     */
    public function handle()
    {
        $collections = $this->argument('collections');

        if (empty($collections)) {
            $this->error("✘ No collection names provided.");
            return;
        }

        $stub = $this->files->get($this->getStub());
        $migrationFolder = base_path('database/MongoMigrations');

        // Ensure folder exists
        if (!File::exists($migrationFolder)) {
            File::makeDirectory($migrationFolder, 0755, true);
        }

        foreach ($collections as $collection) {

            $collection = trim(Str::snake($collection));

            if ($collection === '') {
                $this->warn("⚠  Ignored empty collection name.");
                continue;
            }

            $timestamp = date('Y_m_d_His');

            $fileName = "{$timestamp}_create_{$collection}_collection.php";
            $filePath = "{$migrationFolder}/{$fileName}";

            // Prevent accidental overwrite
            if (File::exists($filePath)) {
                $this->warn("⚠  Migration already exists: {$fileName}");
                continue;
            }

            // Build class name
            $className = $this->makeClassName($collection);

            // Build file content
            $content = str_replace(
                ['{{ class }}', '{{ collection }}'],
                [$className, $collection],
                $stub
            );

            // Write file
            File::put($filePath, $content);

            $this->info("✔  Migration created: {$fileName}");

            // Sleep 1 second so each file has a unique timestamp
            sleep(1);
        }

        $this->line("<fg=green>✔  All MongoDB migration(s) generated successfully.</>");
    }

    /**
     * Get stub path.
     */
    protected function getStub(): string
    {
        return base_path('stubs/mongo-migration.stub');
    }

    /**
     * Create a proper class name.
     */
    protected function makeClassName(string $collection): string
    {
        return Str::studly($collection) . 'CollectionMigration';
    }
}
