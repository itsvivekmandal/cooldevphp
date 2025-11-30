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
