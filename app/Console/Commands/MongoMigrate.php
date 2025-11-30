<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MongoMigrate extends Command
{
    /**
     * The console command signature.
     *
     * action: migrate|refresh|reset
     * collection: optional specific collection migration file
     */
    protected $signature = 'mongo:migrate
        {action? : migrate|refresh|reset}
        {collection? : Specific collection name}';

    /**
     * The console command description.
     */
    protected $description = 'Run MongoDB migrations stored inside database/MongoMigrations';

    /**
     * Execute the console command.
     *
     * This method parses the action & collection arguments,
     * validates them, loads migration files,
     * and delegates to the appropriate handler.
     */
    public function handle()
    {
        $action     = $this->argument('action');
        $collection = $this->argument('collection');

        // Available valid actions
        $validActions = ['migrate', 'refresh', 'reset'];

        /**
         * If "action" is not one of the valid actions:
         * - Treat it as a collection name instead.
         * - And default action becomes "migrate".
         */
        if (!in_array($action, $validActions)) {
            if (!$collection) {
                $collection = $action;
            }
            $action = 'migrate';
        }

        // Folder where migration files exist
        $migrationsPath = base_path('database/MongoMigrations');

        // Ensure folder exists
        if (!File::exists($migrationsPath)) {
            $this->error("✘ MongoMigrations folder missing: {$migrationsPath}");
            return;
        }

        // Load all migration files
        $files = File::files($migrationsPath);

        if (empty($files)) {
            $this->warn("⚠  No MongoDB migration files found.");
            return;
        }

        // Call the appropriate method dynamically
        $result = $this->{$action}(files: $files, collection: $collection);

        if ($result) {
            $this->line("<bg=blue;fg=white>✔  MongoDB migration '{$action}' executed successfully. </>");
        } else {
            $this->error("✘ No matching migration file found for '{$collection}'.");
        }
    }

    /**
     * Run all migrations (equivalent to "up").
     *
     * @param array $files
     * @param string|null $collection
     */
    private function migrate(array $files, ?string $collection): bool
    {
        $this->line("<fg=cyan>▶ Running MongoDB migrations...</>");
        return $this->executeAction(files: $files, collection: $collection, action: 'up');
    }

    /**
     * Rollback and then re-run migrations (reset → migrate).
     *
     * @param array $files
     * @param string|null $collection
     */
    private function refresh(array $files, ?string $collection): bool
    {
        $this->line("<fg=yellow>↺ Refreshing MongoDB migrations...</>");

        $this->reset(files: $files, collection: $collection);
        return $this->migrate(files: $files, collection: $collection);
    }

    /**
     * Rollback (equivalent to running "down").
     *
     * @param array $files
     * @param string|null $collection
     */
    private function reset(array $files, ?string $collection): bool
    {
        $this->line("<fg=red>⤵ Rolling back MongoDB migrations...</>");
        return $this->executeAction(files: $files, collection: $collection, action: 'down');
    }

    /**
     * Core executor that runs up/down on matching migration files.
     *
     * @param array $files
     * @param string $action ("up" or "down")
     * @param string|null $collection
     *
     * @return bool Whether any migration file matched
     */
    private function executeAction(array $files, string $action, ?string $collection): bool
    {
        $found = false;

        foreach ($files as $file) {
            $fileName = $file->getFilename();

            // If specific collection is given, filter file names
            if ($collection && !str_contains($fileName, "create_{$collection}_collection")) {
                continue;
            }

            $found = true;
            $this->info("→ {$fileName}");

            // Include the migration file and get its returned class
            $migration = include $file->getRealPath();

            // Run the appropriate function
            if (method_exists($migration, $action)) {
                $migration->$action();
            } else {
                $this->warn("⚠  Method '{$action}' not found in {$fileName}");
            }
        }

        return $found;
    }
}
