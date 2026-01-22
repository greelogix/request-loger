# Greelogix Package Structure & Naming Conventions

This document outlines the organizational structure and naming conventions used in Greelogix Laravel packages. Follow these guidelines when creating new packages or contributing to existing ones.

## Table of Contents

1. [Package Structure](#package-structure)
2. [Naming Conventions](#naming-conventions)
3. [Namespace Structure](#namespace-structure)
4. [Configuration Files](#configuration-files)
5. [Database Tables](#database-tables)
6. [Routes](#routes)
7. [Views](#views)
8. [Artisan Commands](#artisan-commands)
9. [Environment Variables](#environment-variables)
10. [Publishing Tags](#publishing-tags)
11. [Example Package Structure](#example-package-structure)

---

## Package Structure

### Standard Directory Layout

```
package-name/
├── composer.json
├── config/
│   └── gl-{package-name}.php
├── database/
│   └── migrations/
│       └── create_{table_name}_table.php
├── resources/
│   └── views/
│       └── {view-files}.blade.php
├── routes/
│   └── web.php (or api.php)
├── src/
│   ├── Console/
│   │   └── {Command}Command.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── {Controller}Controller.php
│   │   └── Middleware/
│   │       └── {Middleware}.php
│   ├── Models/
│   │   └── {Model}.php
│   └── {PackageName}ServiceProvider.php
└── README.md
```

---

## Naming Conventions

### Package Name (Composer)

- **Format**: `greelogix/{package-name}`
- **Case**: Lowercase, kebab-case
- **Example**: `greelogix/request-logger`

```json
{
  "name": "greelogix/request-logger"
}
```

### Namespace

- **Format**: `GreeLogix\{PackageName}`
- **Case**: PascalCase, no hyphens (convert kebab-case to PascalCase)
- **Example**: `GreeLogix\RequestLogger`

```php
namespace GreeLogix\RequestLogger;
```

**Note**: The namespace uses `GreeLogix` (capital G, L, and X) - not `Greelogix` or `GreenLogix`.

### Service Provider Class

- **Format**: `{PackageName}ServiceProvider`
- **Location**: `src/{PackageName}ServiceProvider.php`
- **Example**: `RequestLoggerServiceProvider`

```php
namespace GreeLogix\RequestLogger;

class RequestLoggerServiceProvider extends ServiceProvider
{
    // ...
}
```

---

## Configuration Files

### Config File Naming

- **Format**: `gl-{package-name}.php`
- **Case**: Lowercase, kebab-case with `gl-` prefix
- **Location**: `config/gl-{package-name}.php`
- **Example**: `config/gl-request-logger.php`

### Config Key

- **Format**: `gl-{package-name}`
- **Example**: `config('gl-request-logger.enabled')`

```php
// In ServiceProvider
$this->mergeConfigFrom(
    __DIR__.'/../config/gl-request-logger.php', 
    'gl-request-logger'
);

// Usage
config('gl-request-logger.enabled');
```

### Publishing Config

```php
$this->publishes([
    __DIR__.'/../config/gl-request-logger.php' => config_path('gl-request-logger.php'),
], 'gl-request-logger-config');
```

---

## Database Tables

### Table Naming

- **Format**: `gl_{table_name}`
- **Case**: Lowercase, snake_case with `gl_` prefix
- **Example**: `gl_request_logs`

```php
// In Migration
Schema::create('gl_request_logs', function (Blueprint $table) {
    // ...
});

// In Model
protected $table = 'gl_request_logs';
```

### Migration File Naming

- **Format**: `create_{table_name}_table.php`
- **Published Format**: `{timestamp}_create_gl_{table_name}_table.php`
- **Example**: `create_request_logs_table.php` → `2024_01_01_000000_create_gl_request_logs_table.php`

```php
$this->publishes([
    __DIR__.'/../database/migrations/create_request_logs_table.php' => 
        database_path('migrations/'.date('Y_m_d_His').'_create_gl_request_logs_table.php'),
], 'gl-request-logger-migrations');
```

### Database Connection Support

If your package needs to support a separate database connection, you can configure it in your config file and update your model and migration accordingly.

**In Config File** (`config/gl-{package-name}.php`):
```php
'connection' => env('GL_{PACKAGE_NAME_UPPER}_CONNECTION', null),
```

**In Model** (`src/Models/{Model}.php`):
```php
public function getConnectionName()
{
    return config('gl-{package-name}.connection') ?: parent::getConnectionName();
}
```

**In Migration** (`database/migrations/create_{table_name}_table.php`):
```php
public function up(): void
{
    $connection = config('gl-{package-name}.connection');
    
    if ($connection) {
        Schema::connection($connection)->create('gl_{table_name}', function (Blueprint $table) {
            // ...
        });
    } else {
        Schema::create('gl_{table_name}', function (Blueprint $table) {
            // ...
        });
    }
}

public function down(): void
{
    $connection = config('gl-{package-name}.connection');
    
    if ($connection) {
        Schema::connection($connection)->dropIfExists('gl_{table_name}');
    } else {
        Schema::dropIfExists('gl_{table_name}');
    }
}
```

**Note:** When `connection` is `null`, the default database connection will be used.

---

## Routes

### Route Prefix

- **Format**: `gl/`
- **Example**: All routes should be prefixed with `gl/`

```php
Route::middleware(['web'])->prefix('gl')->group(function () {
    Route::get('/request-logs', [LogViewerController::class, 'index']);
});
```

### Route Names

- **Format**: `gl.{package-name}.{action}`
- **Case**: Lowercase, kebab-case
- **Example**: `gl.request-logger.index`, `gl.request-logger.show`

```php
Route::get('/request-logs', [LogViewerController::class, 'index'])
    ->name('gl.request-logger.index');
```

### Ignored Routes in Config

When excluding routes from package functionality, use the `gl/` prefix:

```php
'ignored_routes' => [
    'gl/request-logs*',
    'gl/request-logs-check-new',
],
```

---

## Views

### View Namespace

- **Format**: `gl-{package-name}`
- **Case**: Lowercase, kebab-case
- **Example**: `gl-request-logger`

```php
// In ServiceProvider
$this->loadViewsFrom(__DIR__.'/../resources/views', 'gl-request-logger');

// Usage in Controller
return view('gl-request-logger::index', ['data' => $data]);
```

### View Publishing

- **Published Location**: `resources/views/vendor/gl-{package-name}/`
- **Example**: `resources/views/vendor/gl-request-logger/`

```php
$this->publishes([
    __DIR__.'/../resources/views' => resource_path('views/vendor/gl-request-logger'),
], 'gl-request-logger-views');
```

---

## Artisan Commands

### Command Signature

- **Format**: `gl-{package-name}:{command}`
- **Case**: Lowercase, kebab-case
- **Example**: `gl-request-logger:install`

```php
protected $signature = 'gl-request-logger:install';
```

### Command Description

- **Format**: Should mention "GL {Package Name}" for clarity
- **Example**: `Install GL Request Logger and guide middleware registration.`

```php
protected $description = 'Install GL Request Logger and guide middleware registration.';
```

---

## Environment Variables

### Environment Variable Naming

- **Format**: `GL_{PACKAGE_NAME}_{SETTING}`
- **Case**: Uppercase, underscores (convert kebab-case to UPPER_SNAKE_CASE)
- **Example**: `GL_REQUEST_LOGGER_ENABLED`, `GL_REQUEST_LOGGER_DRIVER`

```php
'enabled' => env('GL_REQUEST_LOGGER_ENABLED', env('REQUEST_LOGGER_ENABLED', true)),
'driver' => env('GL_REQUEST_LOGGER_DRIVER', env('REQUEST_LOGGER_DRIVER', 'database')),
```

**Note**: It's common to provide a fallback to a non-prefixed version for backward compatibility:
```php
env('GL_REQUEST_LOGGER_ENABLED', env('REQUEST_LOGGER_ENABLED', true))
```

---

## Publishing Tags

### Publishing Tag Format

- **Format**: `gl-{package-name}-{asset-type}`
- **Case**: Lowercase, kebab-case
- **Asset Types**: `config`, `migrations`, `views`, `assets`, etc.

### Examples

```php
// Config
'gl-request-logger-config'

// Migrations
'gl-request-logger-migrations'

// Views
'gl-request-logger-views'

// Assets (if applicable)
'gl-request-logger-assets'
```

### Usage

```bash
php artisan vendor:publish --tag=gl-request-logger-config
php artisan vendor:publish --tag=gl-request-logger-migrations
php artisan vendor:publish --tag=gl-request-logger-views
```

---

## Example Package Structure

Here's a complete example based on the `request-logger` package:

### Package: `greelogix/request-logger`

```
request-logger/
├── composer.json                    # "name": "greelogix/request-logger"
├── config/
│   └── gl-request-logger.php       # Config key: 'gl-request-logger'
├── database/
│   └── migrations/
│       └── create_request_logs_table.php  # Creates: gl_request_logs
├── resources/
│   └── views/
│       ├── index.blade.php
│       └── show.blade.php
├── routes/
│   └── web.php                     # Routes prefixed with 'gl/'
├── src/
│   ├── Console/
│   │   └── InstallCommand.php      # Command: gl-request-logger:install
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── LogViewerController.php
│   │   └── Middleware/
│   │       └── LogRequests.php
│   ├── Models/
│   │   └── RequestLog.php          # Table: gl_request_logs
│   └── RequestLoggerServiceProvider.php
└── README.md
```

### Key Identifiers

- **Composer**: `greelogix/request-logger`
- **Namespace**: `GreeLogix\RequestLogger`
- **Config**: `gl-request-logger`
- **Table**: `gl_request_logs`
- **Routes**: `gl/request-logs`, `gl.request-logger.index`
- **Views**: `gl-request-logger::index`
- **Command**: `gl-request-logger:install`
- **Env Vars**: `GL_REQUEST_LOGGER_*`
- **Publish Tags**: `gl-request-logger-{type}`

---

## Quick Reference Checklist

When creating a new Greelogix package, ensure:

- [ ] Composer name: `greelogix/{package-name}` (kebab-case)
- [ ] Namespace: `GreeLogix\{PackageName}` (PascalCase, no hyphens)
- [ ] Config file: `gl-{package-name}.php` (kebab-case)
- [ ] Config key: `gl-{package-name}`
- [ ] Database tables: `gl_{table_name}` (snake_case)
- [ ] Route prefix: `gl/`
- [ ] Route names: `gl.{package-name}.{action}`
- [ ] View namespace: `gl-{package-name}`
- [ ] Artisan commands: `gl-{package-name}:{command}`
- [ ] Environment variables: `GL_{PACKAGE_NAME}_{SETTING}` (uppercase)
- [ ] Publishing tags: `gl-{package-name}-{asset-type}`

---

## Important Notes

1. **Namespace Capitalization**: Always use `GreeLogix` (capital G, L, and X), not `Greelogix` or `GreenLogix`.

2. **Consistency**: The `gl_` prefix is used consistently across:
   - Database tables
   - Config files (as `gl-`)
   - Route prefixes (as `gl/`)
   - Publishing tags (as `gl-`)

3. **Backward Compatibility**: When introducing new environment variables, consider providing fallback to non-prefixed versions for existing projects.

4. **Case Conversion**:
   - Package name (kebab-case) → Namespace (PascalCase)
   - Package name (kebab-case) → Env vars (UPPER_SNAKE_CASE)
   - Package name (kebab-case) → Table names (snake_case with `gl_` prefix)

---

## Summary

The Greelogix structure follows a consistent pattern where:
- **`gl_`** prefix is used for database tables
- **`gl-`** prefix is used for config files, view namespaces, and publishing tags
- **`gl/`** prefix is used for routes
- **`GL_`** prefix is used for environment variables
- **`GreeLogix`** namespace is used for all PHP classes
- **`greelogix/`** vendor prefix is used in Composer

This ensures all Greelogix packages are easily identifiable and don't conflict with other packages or application code.
