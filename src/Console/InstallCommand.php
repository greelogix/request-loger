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
        // Read the package's default config file as a string
        $packageConfigPath = __DIR__.'/../../config/gl-request-logger.php';
        $packageConfigContent = File::get($packageConfigPath);
        
        // Extract the new options section (UI Middleware and Allowed Emails)
        // Match from the comment to the closing bracket and comma
        if (preg_match('/(\/\*\s*UI Middleware.*?\*\/\s*\'ui_middleware\'.*?\[.*?\],\s*\/\*\s*Allowed Emails.*?\*\/\s*\'allowed_emails\'.*?\[.*?\],)/s', $packageConfigContent, $matches)) {
            $newOptions = $matches[1];
            
            // Read existing config file
            $existingContent = File::get($configPath);
            
            // Remove the closing bracket and any trailing whitespace
            $existingContent = rtrim($existingContent);
            if (substr($existingContent, -2) === '];') {
                $existingContent = rtrim(substr($existingContent, 0, -2));
            }
            
            // Append new options before the closing bracket
            $updatedContent = $existingContent . ",\n\n" . $newOptions . "\n];\n";
            
            File::put($configPath, $updatedContent);
            $this->info('Config file updated successfully with new options (ui_middleware, allowed_emails)!');
        } else {
            $this->warn('Could not extract new options from package config. Please add them manually.');
            $this->line('Add these options to your config file:');
            $this->line('  - ui_middleware');
            $this->line('  - allowed_emails');
        }
    }
}
