<?php

namespace Gl\RequestLogger\Console;

use Gl\RequestLogger\Http\Middleware\LogRequests;
use Illuminate\Console\Command;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'request-logger:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install RequestLogger and guide middleware registration.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Publishing configuration...');
        $this->callSilent('vendor:publish', ['--tag' => 'request-logger-config']);

        $bootstrapApp = base_path('bootstrap/app.php');

        if (file_exists($bootstrapApp)) {
            $this->line('Laravel 11 detected:');
            $this->line(" - Add ".LogRequests::class." via ->withMiddleware(".'"'.LogRequests::class.'"'.") in bootstrap/app.php.");
        } else {
            $this->line('Laravel 10 detected:');
            $this->line(" - Add ".LogRequests::class.' to the $middleware (or a group) in app/Http/Kernel.php.');
        }

        $this->line('');
        $this->line('Route: GET /request-logs (web middleware) to view logs.');

        return self::SUCCESS;
    }
}
