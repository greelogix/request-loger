<?php

namespace GreeLogix\RequestLogger\Console;

use GreeLogix\RequestLogger\Http\Middleware\LogRequests;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gl-request-logger:install {--force : Force overwrite existing config file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install GL Request Logger and guide middleware registration.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Publishing configuration...');
        
        $configPath = config_path('gl-request-logger.php');
        $configExists = File::exists($configPath);
        
        if ($configExists && !$this->option('force')) {
            // Check if config has the new options
            $existingConfig = require $configPath;
            $hasNewOptions = isset($existingConfig['ui_middleware']) && isset($existingConfig['allowed_emails']);
            
            if (!$hasNewOptions) {
                $this->warn('Config file exists but is missing new options (ui_middleware, allowed_emails).');
                $this->info('Updating config file with new options...');
                $this->updateConfigFile($configPath, $existingConfig);
            } else {
                $this->info('Config file already exists and is up to date.');
            }
        } else {
            // Publish config (will overwrite if --force is used)
            $this->callSilent('vendor:publish', [
                '--tag' => 'gl-request-logger-config',
                '--force' => $this->option('force'),
            ]);
        }

        $bootstrapApp = base_path('bootstrap/app.php');

        if (file_exists($bootstrapApp)) {
            $this->line('Laravel 11 detected:');
            $this->line(" - Add ".LogRequests::class." via ->withMiddleware(".'"'.LogRequests::class.'"'.") in bootstrap/app.php.");
        } else {
            $this->line('Laravel 10 detected:');
            $this->line(" - Add ".LogRequests::class.' to the $middleware (or a group) in app/Http/Kernel.php.');
        }

        $this->line('');
        $this->line('Route: GET /gl/request-logs (web middleware) to view logs.');

        return self::SUCCESS;
    }

    /**
     * Update existing config file with new options.
     */
    protected function updateConfigFile(string $configPath, array $existingConfig): void
    {
        // Read the package's default config file
        $packageConfigPath = __DIR__.'/../../config/gl-request-logger.php';
        $packageConfigLines = file($packageConfigPath, FILE_IGNORE_NEW_LINES);
        
        // Find the line number where UI Middleware comment starts
        $startLine = null;
        for ($i = 0; $i < count($packageConfigLines); $i++) {
            if (strpos($packageConfigLines[$i], 'UI Middleware') !== false) {
                $startLine = $i;
                break;
            }
        }
        
        if ($startLine === null) {
            $this->warn('Could not find new options in package config.');
            $this->line('Please run: php artisan vendor:publish --tag=gl-request-logger-config --force');
            return;
        }
        
        // Extract lines from UI Middleware to the end (before closing bracket)
        $newOptionsLines = array_slice($packageConfigLines, $startLine, -1);
        $newOptions = implode("\n", $newOptionsLines);
        
        // Read existing config file
        $existingContent = File::get($configPath);
        
        // Remove the closing bracket and any trailing whitespace/newlines
        $existingContent = rtrim($existingContent);
        
        // Remove closing ]; if present
        if (preg_match('/\];?\s*$/', $existingContent)) {
            $existingContent = preg_replace('/\];?\s*$/', '', $existingContent);
            $existingContent = rtrim($existingContent);
        }
        
        // Ensure there's a comma at the end if the last line doesn't have one
        if (substr($existingContent, -1) !== ',') {
            $existingContent .= ',';
        }
        
        // Append new options before the closing bracket
        $updatedContent = $existingContent . "\n\n" . $newOptions . "\n];\n";
        
        File::put($configPath, $updatedContent);
        $this->info('Config file updated successfully with new options (ui_middleware, allowed_emails)!');
    }
}
