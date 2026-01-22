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
composer require greelogix/request-logger
```

### 2. Run the Install Command

This will publish the configuration file and provide setup instructions:

```bash
php artisan gl-request-logger:install
```

**Note:** If you already have a config file, the install command will automatically add any missing new configuration options (like `ui_middleware` and `allowed_emails`) without overwriting your existing settings. To force overwrite the entire config file, use the `--force` flag:

```bash
php artisan gl-request-logger:install --force
```

### 3. Publish and Run Migrations

Publish the migration file:

```bash
php artisan vendor:publish --tag=gl-request-logger-migrations
```

Run the migrations:

```bash
php artisan migrate
```

### 4. Register the Middleware

The middleware needs to be registered to start logging requests.

#### For Laravel 11

Add the middleware to `bootstrap/app.php`:

**Option 1: Using `use` statement (recommended):**
```php
use GreeLogix\RequestLogger\Http\Middleware\LogRequests;

->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        LogRequests::class,
    ]);
})
```

**Option 2: Using fully qualified class name:**
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \GreeLogix\RequestLogger\Http\Middleware\LogRequests::class,
    ]);
})
```

#### For Laravel 10

Add the middleware to `app/Http/Kernel.php` in the `$middlewareGroups['web']` array:

**Option 1: Using `use` statement (recommended):**
```php
use GreeLogix\RequestLogger\Http\Middleware\LogRequests;

protected $middlewareGroups = [
    'web' => [
        // ... other middleware
        LogRequests::class,
    ],
];
```

**Option 2: Using fully qualified class name:**
```php
protected $middlewareGroups = [
    'web' => [
        // ... other middleware
        \GreeLogix\RequestLogger\Http\Middleware\LogRequests::class,
    ],
];
```

**Note:** The namespace is `GreeLogix` (with capital G, L, and X). Make sure the casing is correct to avoid class not found errors.

## Usage

### Accessing the Log Viewer

Once the middleware is registered and you've made some requests, visit:

```
http://your-app-url/gl/request-logs
```

The route is automatically registered by the package and requires authentication by default. You can configure which middleware to use and restrict access to specific user emails (see [UI Security Configuration](#ui-security-configuration) below).

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

The configuration file is automatically published when you run `php artisan gl-request-logger:install`. You can also publish it manually:

```bash
php artisan vendor:publish --tag=gl-request-logger-config
```

The configuration file will be published to `config/gl-request-logger.php`.

### Configuration Options

#### `enabled`

Enable or disable request logging globally.

```php
'enabled' => true,
```

#### `driver`

Choose the logging driver: `database` or `file`.

```php
'driver' => env('GL_REQUEST_LOGGER_DRIVER', 'database'),
```

#### `connection`

**Optional.** Database connection name to use for storing logs. 

- **Default:** `null` (uses your application's default database connection)
- **No configuration needed:** The package works out of the box using your default database connection
- **Optional feature:** Only set this if you want to use a separate database for logging

```php
'connection' => env('GL_REQUEST_LOGGER_CONNECTION', null),
```

**By default, you don't need to configure anything** - the package will automatically use your default database connection. You only need to set this if you want to use a separate database.

**Example (Optional):** To use a separate database connection named `logs`:

1. First, configure the connection in `config/database.php`:
```php
'connections' => [
    'logs' => [
        'driver' => 'mysql',
        'host' => env('DB_LOGS_HOST', '127.0.0.1'),
        'port' => env('DB_LOGS_PORT', '3306'),
        'database' => env('DB_LOGS_DATABASE', 'logs'),
        'username' => env('DB_LOGS_USERNAME', 'root'),
        'password' => env('DB_LOGS_PASSWORD', ''),
        // ... other connection settings
    ],
],
```

2. Then set the connection in your `.env` file:
```env
GL_REQUEST_LOGGER_CONNECTION=logs
```

3. When running migrations, make sure to specify the connection:
```bash
php artisan migrate --database=logs
```

Or update the migration file to use the configured connection (the package migration already handles this automatically).

**Important Notes:**
- **This is completely optional** - you can use the package without setting this value
- By default, logs are stored in your main application database (the default connection)
- Only configure a separate connection if you specifically want to isolate logs in a different database
- If you change the connection after the initial setup, you'll need to run migrations on the new connection to create the table

#### `table`

Database table name for storing logs (when using database driver).

```php
'table' => 'gl_request_logs',
```

#### `file_channel`

Log channel to use when using file driver.

```php
'file_channel' => env('GL_REQUEST_LOGGER_CHANNEL', env('LOG_CHANNEL', 'stack')),
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
    'gl/request-logs*',
    'admin/*',
    'api/health*',
],
```

Examples:
- `'admin/*'` - Matches all routes starting with `admin/`
- `'api/users*'` - Matches routes like `api/users`, `api/users/123`, etc.
- `'gl/request-logs*'` - Matches all request logger UI routes

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
'slow_request_threshold_ms' => env('GL_REQUEST_LOGGER_SLOW_THRESHOLD', 1000),
```

#### `log_html_responses`

Whether to log HTML response bodies. If set to `false`, HTML responses will be replaced with "HTML response" text to reduce database size.

```php
'log_html_responses' => env('GL_REQUEST_LOGGER_LOG_HTML', true),
```

#### `per_page`

Number of log entries to display per page in the log viewer UI. Default is 50.

```php
'per_page' => env('GL_REQUEST_LOGGER_PER_PAGE', 50),
```

#### `ui_middleware`

Array of middleware to apply to the log viewer UI routes. This allows you to protect the UI with authentication, authorization, or other middleware.

**Default:** `['auth']` (requires authentication)

**Examples:**
- `['auth']` - Requires authentication (default)
- `['auth', 'verified']` - Requires authentication and email verification
- `['auth:sanctum']` - Requires Sanctum authentication
- `['auth', 'role:admin']` - Requires authentication and admin role (if using a role package)
- `[]` - No additional middleware (only `web` middleware is always applied)

```php
'ui_middleware' => env('GL_REQUEST_LOGGER_UI_MIDDLEWARE') 
    ? explode(',', env('GL_REQUEST_LOGGER_UI_MIDDLEWARE'))
    : ['auth'],
```

**Note:** The `web` middleware group is always applied automatically. This configuration allows you to add additional middleware on top of that.

#### `allowed_emails`

Array of user email addresses that are allowed to access the log viewer UI. If this array is empty, all authenticated users can access the UI (subject to the `ui_middleware` configuration).

If this array contains emails, only users with those email addresses will be allowed to access the UI, even if they pass the middleware checks.

**Default:** `[]` (all authenticated users can access)

**Examples:**
- `[]` - All authenticated users can access (default)
- `['admin@example.com', 'developer@example.com']` - Only these specific emails can access
- `['admin@example.com']` - Only the admin email can access

```php
'allowed_emails' => env('GL_REQUEST_LOGGER_ALLOWED_EMAILS')
    ? explode(',', env('GL_REQUEST_LOGGER_ALLOWED_EMAILS'))
    : [],
```

**Note:** When `allowed_emails` is configured, the user must be authenticated and their email must be in the allowed list. If the user is not authenticated or their email is not in the list, they will receive a 403 Forbidden error.

### Environment Variables

You can configure the package using environment variables:

```env
GL_REQUEST_LOGGER_DRIVER=database
GL_REQUEST_LOGGER_CONNECTION=null
GL_REQUEST_LOGGER_CHANNEL=stack
GL_REQUEST_LOGGER_SLOW_THRESHOLD=1000
GL_REQUEST_LOGGER_LOG_HTML=true
GL_REQUEST_LOGGER_PER_PAGE=50
GL_REQUEST_LOGGER_UI_MIDDLEWARE=auth
GL_REQUEST_LOGGER_ALLOWED_EMAILS=admin@example.com,developer@example.com
```

**Note:** 
- `GL_REQUEST_LOGGER_UI_MIDDLEWARE` should be a comma-separated list of middleware names (e.g., `auth,verified`)
- `GL_REQUEST_LOGGER_ALLOWED_EMAILS` should be a comma-separated list of email addresses (e.g., `admin@example.com,developer@example.com`)
- Leave `GL_REQUEST_LOGGER_ALLOWED_EMAILS` empty or unset to allow all authenticated users

**Note:** Set `GL_REQUEST_LOGGER_CONNECTION` to a connection name (e.g., `logs`) to use a separate database, or leave it unset/empty to use the default connection.

## Logging Drivers

### Database Driver

Stores logs in the `gl_request_logs` database table. This is the default driver and provides the best performance for viewing logs through the web UI.

By default, logs are stored in your application's default database connection. **No additional configuration is required.**

**Optional:** You can configure the package to use a separate database connection by setting the `connection` option. This is useful for:
- Separating logs from your main application database
- Using a dedicated database server for logging
- Improving performance by isolating log queries

**Note:** Using a separate database is completely optional. The package works perfectly fine with your default database connection.

See the [Connection Configuration](#connection) section above for setup instructions (only if you want to use a separate database).

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

### UI Security

The log viewer UI is protected by default with authentication middleware. You can further restrict access using two configuration options:

#### Middleware Protection

Configure which middleware should be applied to the UI routes using the `ui_middleware` configuration option. By default, the `auth` middleware is applied, requiring users to be authenticated.

**Example:** Require authentication and email verification:
```php
'ui_middleware' => ['auth', 'verified'],
```

**Example:** Use Sanctum authentication:
```php
'ui_middleware' => ['auth:sanctum'],
```

**Example:** No additional middleware (only `web` middleware):
```php
'ui_middleware' => [],
```

#### Email-Based Access Control

Restrict access to specific user emails using the `allowed_emails` configuration option. When configured, only users with email addresses in the allowed list can access the UI, even if they pass the middleware checks.

**Example:** Allow only specific admin emails:
```php
'allowed_emails' => [
    'admin@example.com',
    'developer@example.com',
],
```

**Example:** Allow all authenticated users (default):
```php
'allowed_emails' => [],
```

**Important:** 
- Users must be authenticated to access the UI (unless you remove `auth` from `ui_middleware`)
- If `allowed_emails` is configured, the user's email must be in the list
- Users who don't meet the requirements will receive a 403 Forbidden error

## Publishing Assets

### Views

To customize the log viewer UI, publish the views:

```bash
php artisan vendor:publish --tag=gl-request-logger-views
```

Views will be published to `resources/views/vendor/gl-request-logger/`.

## Troubleshooting

### Package Installation Conflicts

If you encounter dependency conflicts during installation:

**Error:** `Your requirements could not be resolved to an installable set of packages`

**Solution:** Ensure you're using Laravel 10 or 11. If you're on an older version, upgrade Laravel first:

```bash
composer require laravel/framework:^10.0
# or for Laravel 11
composer require laravel/framework:^11.0
```

Then try installing the package again:

```bash
composer require greelogix/request-logger
```

### Migration Already Exists / Table Already Exists

If you get an error about the table already existing (e.g., `Base table or view already exists: 1050 Table 'gl_request_logs' already exists`):

**This usually happens when:**
- The migration runs twice (once from published migrations and once from package auto-loaded migrations)
- You're using a custom database connection and the table exists on that connection

**Solutions:**

1. **If using a custom connection**, make sure the table exists on that connection. Check your connection:
   ```bash
   php artisan tinker
   >>> config('gl-request-logger.connection')
   >>> exit
   ```

2. **If the table exists on the wrong connection**, you can either:
   - Drop and recreate on the correct connection:
     ```bash
     php artisan tinker
     >>> Schema::connection('your-connection-name')->dropIfExists('gl_request_logs');
     >>> exit
     php artisan migrate
     ```
   - Or manually move the table to the correct database/connection

3. **If migrations are running twice**, the package automatically checks if the table exists before creating it (as of the latest version). If you still see this error:
   ```bash
   php artisan migrate:rollback --step=1
   php artisan migrate
   ```

4. **Manual cleanup** (if needed):
   ```bash
   php artisan tinker
   >>> Schema::dropIfExists('gl_request_logs');
   >>> # Or for custom connection:
   >>> Schema::connection('your-connection')->dropIfExists('gl_request_logs');
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

## Greelogix Package Structure

This package follows the Greelogix organizational structure and naming conventions. For detailed information about:

- **Package structure and naming conventions**: See [GREELOGIX_STRUCTURE.md](./GREELOGIX_STRUCTURE.md)
- **Template for creating new packages**: See [PACKAGE_TEMPLATE.md](./PACKAGE_TEMPLATE.md)

These documents outline the consistent patterns used across all Greelogix packages, including:
- Namespace structure (`GreeLogix\{PackageName}`)
- Configuration file naming (`gl-{package-name}.php`)
- Database table naming (`gl_{table_name}`)
- Route prefixes and naming (`gl/` prefix, `gl.{package-name}.{action}`)
- Environment variables (`GL_{PACKAGE_NAME}_{SETTING}`)
- Publishing tags (`gl-{package-name}-{type}`)

## License

MIT

## Support

For issues, questions, or contributions, please open an issue on the repository.
