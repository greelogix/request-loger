# Request Logger

A Laravel package for logging HTTP requests with automatic sensitive data masking and a beautiful web UI for viewing logs.

## Features

- 📝 Automatic request/response logging
- 🔒 Automatic masking of sensitive data (passwords, tokens, etc.)
- 🎨 Beautiful dark-themed web UI for viewing logs
- ⚡ Configurable logging driver (database or file)
- 🚫 Configurable route exclusions
- 📊 Request duration tracking
- 👤 User tracking
- 🗄️ Database and file logging support

## Requirements

- PHP ^8.1
- Laravel ^10.0 or ^11.0

## Installation

### 1. Install the Package

Add the package to your Laravel project via Composer:

```bash
composer require composite/request-logger
```

### 2. Run the Install Command

This will publish the configuration file and provide setup instructions:

```bash
php artisan request-logger:install
```

### 3. Publish and Run Migrations

Publish the migration file:

```bash
php artisan vendor:publish --tag=request-logger-migrations
```

Run the migrations:

```bash
php artisan migrate
```

### 4. Register the Middleware

The middleware needs to be registered to start logging requests.

#### For Laravel 11

Add the middleware to `bootstrap/app.php`:

```php
use Gl\RequestLogger\Http\Middleware\LogRequests;

->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        LogRequests::class,
    ]);
})
```

#### For Laravel 10

Add the middleware to `app/Http/Kernel.php` in the `$middlewareGroups['web']` array:

```php
use Gl\RequestLogger\Http\Middleware\LogRequests;

protected $middlewareGroups = [
    'web' => [
        // ... other middleware
        LogRequests::class,
    ],
];
```

## Usage

### Accessing the Log Viewer

Once the middleware is registered and you've made some requests, visit:

```
http://your-app-url/request-logs
```

The route is automatically registered by the package and requires the `web` middleware group.

### Viewing Logs

The web UI displays:
- Request method and path
- HTTP status code
- User ID (if authenticated)
- IP address
- Request headers
- Request body
- Response body
- Request duration

Click "View Payload" on any log entry to see the full request/response details.

## Configuration

### Publishing Configuration

The configuration file is automatically published when you run `php artisan request-logger:install`. You can also publish it manually:

```bash
php artisan vendor:publish --tag=request-logger-config
```

The configuration file will be published to `config/request-logger.php`.

### Configuration Options

#### `enabled`

Enable or disable request logging globally.

```php
'enabled' => true,
```

#### `driver`

Choose the logging driver: `database` or `file`.

```php
'driver' => env('REQUEST_LOGGER_DRIVER', 'database'),
```

#### `table`

Database table name for storing logs (when using database driver).

```php
'table' => 'request_logs',
```

#### `file_channel`

Log channel to use when using file driver.

```php
'file_channel' => env('REQUEST_LOGGER_CHANNEL', env('LOG_CHANNEL', 'stack')),
```

#### `masked_keys`

Array of keys to automatically mask in request/response data. Keys are matched case-insensitively and can use partial matching.

```php
'masked_keys' => [
    'password',
    'password_confirmation',
    'authorization',
    'token',
    'api_key',
    'apikey',
    'secret',
    'session',
    'cookie',
],
```

#### `ignored_routes`

Array of path/URI patterns to exclude from logging. Uses Laravel's `Str::is()` matching with wildcard support.

```php
'ignored_routes' => [
    'request-logs*',
    'admin/*',
    'api/health*',
],
```

Examples:
- `'admin/*'` - Matches all routes starting with `admin/`
- `'api/users*'` - Matches routes like `api/users`, `api/users/123`, etc.
- `'request-logs*'` - Matches all request logger UI routes

#### `ignored_urls`

Array of full URL patterns to exclude from logging. Supports wildcards and regex patterns.

```php
'ignored_urls' => [
    'https://example.com/webhook*',
    '/^https?:\/\/.*\.example\.com\/api\/.*$/',
],
```

Examples:
- `'https://example.com/webhook*'` - Matches all webhook URLs on example.com
- `'/^https?:\/\/.*\.example\.com\/.*/'` - Regex pattern matching any subdomain

#### `ignored_paths_regex`

Array of regular expression patterns for path/URI matching. Must be valid regex patterns.

```php
'ignored_paths_regex' => [
    '/^\/api\/v\d+\/health$/',
    '/^\/admin\/.*$/',
],
```

Examples:
- `'/^\/api\/v\d+\/health$/'` - Matches `/api/v1/health`, `/api/v2/health`, etc.
- `'/^\/admin\/.*$/'` - Matches all routes starting with `/admin/`

#### `slow_request_threshold_ms`

Threshold in milliseconds for marking requests as "slow". Requests exceeding this duration will be marked with a red "SLOW" badge.

```php
'slow_request_threshold_ms' => env('REQUEST_LOGGER_SLOW_THRESHOLD', 1000),
```

#### `log_html_responses`

Whether to log HTML response bodies. If set to `false`, HTML responses will be replaced with "HTML response" text to reduce database size.

```php
'log_html_responses' => env('REQUEST_LOGGER_LOG_HTML', true),
```

### Environment Variables

You can configure the package using environment variables:

```env
REQUEST_LOGGER_DRIVER=database
REQUEST_LOGGER_CHANNEL=stack
REQUEST_LOGGER_SLOW_THRESHOLD=1000
REQUEST_LOGGER_LOG_HTML=true
```

## Logging Drivers

### Database Driver

Stores logs in the `request_logs` database table. This is the default driver and provides the best performance for viewing logs through the web UI.

### File Driver

Stores logs in Laravel's log files. Useful for high-traffic applications or when you don't want to store logs in the database.

## What Gets Logged

For each request, the following information is captured:

- HTTP method (GET, POST, etc.)
- Request path
- HTTP status code
- IP address
- User ID (if authenticated)
- Request headers (with sensitive data masked)
- Request body (with sensitive data masked)
- Response body (with sensitive data masked)
- Request duration in milliseconds
- Timestamps

## Security

### Sensitive Data Masking

The package automatically masks sensitive data in:
- Request headers
- Request body
- Response body

Sensitive keys are matched case-insensitively and can use partial matching. For example, `password` will match `password`, `Password`, `user_password`, etc.

Masked values are replaced with `***` in the logs.

### Route Exclusions

You can exclude specific routes, URLs, or endpoints from being logged using multiple configuration options:

- **`ignored_routes`** - Path/URI patterns using wildcard matching (e.g., `'admin/*'`, `'api/users*'`)
- **`ignored_urls`** - Full URL patterns with wildcard or regex support (e.g., `'https://example.com/webhook*'`)
- **`ignored_paths_regex`** - Regular expression patterns for advanced path matching (e.g., `'/^\/api\/v\d+\/.*$/'`)

By default, the request logger UI routes are excluded to prevent logging the log viewer itself.

## Publishing Assets

### Views

To customize the log viewer UI, publish the views:

```bash
php artisan vendor:publish --tag=request-logger-views
```

Views will be published to `resources/views/vendor/request-logger/`.

## Troubleshooting

### Migration Already Exists

If you get an error about the migration already existing, you can:

1. Rollback and re-run:
   ```bash
   php artisan migrate:rollback --step=1
   php artisan migrate
   ```

2. Or manually drop the table and re-run migrations:
   ```bash
   php artisan tinker
   >>> Schema::dropIfExists('request_logs');
   >>> exit
   php artisan migrate
   ```

### No Logs Appearing

1. Check that the middleware is registered correctly
2. Verify that `enabled` is set to `true` in the config
3. Check that the route you're testing isn't in the `ignored_routes` list
4. If using database driver, ensure the migration has run successfully
5. If using file driver, check your Laravel log files

### Class Not Found Errors

If you encounter class not found errors:

1. Run `composer dump-autoload`
2. Clear Laravel's cache: `php artisan config:clear` and `php artisan cache:clear`

## License

MIT

## Support

For issues, questions, or contributions, please open an issue on the repository.
