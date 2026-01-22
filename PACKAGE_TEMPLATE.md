# Greelogix Package Template

Use this template as a checklist when creating a new Greelogix package.

## Package Information

Replace `{package-name}` and `{PackageName}` throughout:

- **Package Name (kebab-case)**: `{package-name}`
- **Package Name (PascalCase)**: `{PackageName}`
- **Example**: `request-logger` → `RequestLogger`

---

## 1. Composer Configuration

**File**: `composer.json`

```json
{
  "name": "greelogix/{package-name}",
  "description": "Description of your package",
  "type": "library",
  "license": "MIT",
  "require": {
    "php": "^8.1",
    "illuminate/support": "^10.0|^11.0"
  },
  "autoload": {
    "psr-4": {
      "GreeLogix\\{PackageName}\\": "src/"
    }
  },
  "extra": {
    "laravel": {
      "providers": [
        "GreeLogix\\{PackageName}\\{PackageName}ServiceProvider"
      ]
    }
  }
}
```

**Checklist**:
- [ ] Name is `greelogix/{package-name}` (kebab-case)
- [ ] Namespace is `GreeLogix\{PackageName}` (PascalCase)
- [ ] Service provider class name matches

---

## 2. Service Provider

**File**: `src/{PackageName}ServiceProvider.php`

```php
<?php

namespace GreeLogix\{PackageName};

use Illuminate\Support\ServiceProvider;

class {PackageName}ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/gl-{package-name}.php', 
            'gl-{package-name}'
        );
    }

    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../config/gl-{package-name}.php' => config_path('gl-{package-name}.php'),
        ], 'gl-{package-name}-config');

        // Publish migrations (if applicable)
        $this->publishes([
            __DIR__.'/../database/migrations/create_{table_name}_table.php' => 
                database_path('migrations/'.date('Y_m_d_His').'_create_gl_{table_name}_table.php'),
        ], 'gl-{package-name}-migrations');

        // Publish views (if applicable)
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/gl-{package-name}'),
        ], 'gl-{package-name}-views');

        // Load views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'gl-{package-name}');
        
        // Load routes (if applicable)
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        
        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
```

**Checklist**:
- [ ] Namespace is `GreeLogix\{PackageName}`
- [ ] Config key is `gl-{package-name}`
- [ ] Publishing tags use `gl-{package-name}-{type}` format
- [ ] View namespace is `gl-{package-name}`

---

## 3. Configuration File

**File**: `config/gl-{package-name}.php`

```php
<?php

return [
    'enabled' => env('GL_{PACKAGE_NAME_UPPER}_ENABLED', true),
    
    // Add your configuration options here
];
```

**Checklist**:
- [ ] File name is `gl-{package-name}.php` (kebab-case)
- [ ] Environment variables use `GL_{PACKAGE_NAME_UPPER}_{SETTING}` format
- [ ] Consider fallback to non-prefixed env vars for backward compatibility

---

## 4. Database Tables

**File**: `database/migrations/create_{table_name}_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gl_{table_name}', function (Blueprint $table) {
            $table->id();
            // Add your columns here
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gl_{table_name}');
    }
};
```

**Model File**: `src/Models/{Model}.php`

```php
<?php

namespace GreeLogix\{PackageName}\Models;

use Illuminate\Database\Eloquent\Model;

class {Model} extends Model
{
    protected $table = 'gl_{table_name}';
    
    /**
     * Get the database connection name for the model.
     *
     * @return string|null
     */
    public function getConnectionName()
    {
        return config('gl-{package-name}.connection') ?: parent::getConnectionName();
    }
    
    // Add your model code here
}
```

**Optional: Database Connection Support**

If you want to support a separate database connection, add the `connection` option to your config file:

```php
// In config/gl-{package-name}.php
'connection' => env('GL_{PACKAGE_NAME_UPPER}_CONNECTION', null),
```

And update your migration to use the connection:

```php
public function up(): void
{
    $connection = config('gl-{package-name}.connection');
    
    if ($connection) {
        Schema::connection($connection)->create('gl_{table_name}', function (Blueprint $table) {
            $table->id();
            // Add your columns here
            $table->timestamps();
        });
    } else {
        Schema::create('gl_{table_name}', function (Blueprint $table) {
            $table->id();
            // Add your columns here
            $table->timestamps();
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

**Checklist**:
- [ ] Table name is `gl_{table_name}` (snake_case with `gl_` prefix)
- [ ] Migration file creates table with `gl_` prefix
- [ ] Model specifies `$table = 'gl_{table_name}'`
- [ ] (Optional) Model supports custom database connection via config
- [ ] (Optional) Migration supports custom database connection via config

---

## 5. Routes

**File**: `routes/web.php` (or `api.php`)

```php
<?php

use GreeLogix\{PackageName}\Http\Controllers\{Controller}Controller;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->prefix('gl')->group(function () {
    Route::get('/{package-name}', [{Controller}Controller::class, 'index'])
        ->name('gl.{package-name}.index');
    
    Route::get('/{package-name}/{id}', [{Controller}Controller::class, 'show'])
        ->name('gl.{package-name}.show');
});
```

**Checklist**:
- [ ] Routes are prefixed with `gl/`
- [ ] Route names use `gl.{package-name}.{action}` format
- [ ] Middleware is appropriate (`web` or `api`)

---

## 6. Controllers

**File**: `src/Http/Controllers/{Controller}Controller.php`

```php
<?php

namespace GreeLogix\{PackageName}\Http\Controllers;

use Illuminate\Routing\Controller;

class {Controller}Controller extends Controller
{
    public function index()
    {
        return view('gl-{package-name}::index', [
            // Pass data here
        ]);
    }
}
```

**Checklist**:
- [ ] Namespace is `GreeLogix\{PackageName}\Http\Controllers`
- [ ] Views use `gl-{package-name}::` namespace
- [ ] Config access uses `config('gl-{package-name}.key')`

---

## 7. Views

**Location**: `resources/views/`

**Usage in Controller**:
```php
return view('gl-{package-name}::index', ['data' => $data]);
```

**Checklist**:
- [ ] Views are referenced with `gl-{package-name}::` namespace
- [ ] Published to `resources/views/vendor/gl-{package-name}/`

---

## 8. Artisan Commands

**File**: `src/Console/{Command}Command.php`

```php
<?php

namespace GreeLogix\{PackageName}\Console;

use Illuminate\Console\Command;

class {Command}Command extends Command
{
    protected $signature = 'gl-{package-name}:{command}';
    
    protected $description = 'Description of GL {PackageName} command.';
    
    public function handle(): int
    {
        // Command logic here
        return self::SUCCESS;
    }
}
```

**Registration in ServiceProvider**:
```php
if ($this->app->runningInConsole()) {
    $this->commands([
        {Command}Command::class,
    ]);
}
```

**Checklist**:
- [ ] Command signature is `gl-{package-name}:{command}`
- [ ] Description mentions "GL {PackageName}"
- [ ] Command is registered in ServiceProvider

---

## 9. Environment Variables

**Format**: `GL_{PACKAGE_NAME_UPPER}_{SETTING}`

**Examples**:
- `GL_REQUEST_LOGGER_ENABLED`
- `GL_REQUEST_LOGGER_DRIVER`
- `GL_REQUEST_LOGGER_SLOW_THRESHOLD`

**Usage in Config**:
```php
'enabled' => env('GL_{PACKAGE_NAME_UPPER}_ENABLED', env('{PACKAGE_NAME_UPPER}_ENABLED', true)),
```

**Checklist**:
- [ ] Environment variables use `GL_` prefix
- [ ] Convert kebab-case to UPPER_SNAKE_CASE
- [ ] Consider fallback for backward compatibility

---

## 10. Publishing Tags

**Format**: `gl-{package-name}-{asset-type}`

**Common Asset Types**:
- `config`
- `migrations`
- `views`
- `assets`

**Examples**:
- `gl-request-logger-config`
- `gl-request-logger-migrations`
- `gl-request-logger-views`

**Checklist**:
- [ ] All publishing tags use `gl-{package-name}-` prefix
- [ ] Asset type is descriptive (config, migrations, views, etc.)

---

## Quick Conversion Reference

| Type | Format | Example |
|------|--------|---------|
| Package Name | kebab-case | `request-logger` |
| Namespace | PascalCase | `RequestLogger` |
| Config File | `gl-` + kebab-case | `gl-request-logger.php` |
| Config Key | `gl-` + kebab-case | `gl-request-logger` |
| Table Name | `gl_` + snake_case | `gl_request_logs` |
| Route Prefix | `gl/` | `gl/request-logs` |
| Route Name | `gl.` + kebab-case | `gl.request-logger.index` |
| View Namespace | `gl-` + kebab-case | `gl-request-logger` |
| Command | `gl-` + kebab-case | `gl-request-logger:install` |
| Env Var | `GL_` + UPPER_SNAKE_CASE | `GL_REQUEST_LOGGER_ENABLED` |
| Publish Tag | `gl-` + kebab-case + `-` + type | `gl-request-logger-config` |

---

## Final Checklist

Before publishing your package, verify:

- [ ] All namespaces use `GreeLogix\{PackageName}` (capital G, L, X)
- [ ] All config files use `gl-{package-name}.php` format
- [ ] All database tables use `gl_{table_name}` format
- [ ] All routes are prefixed with `gl/`
- [ ] All route names use `gl.{package-name}.{action}` format
- [ ] All views use `gl-{package-name}::` namespace
- [ ] All commands use `gl-{package-name}:{command}` format
- [ ] All env vars use `GL_{PACKAGE_NAME_UPPER}_{SETTING}` format
- [ ] All publishing tags use `gl-{package-name}-{type}` format
- [ ] README.md documents the package structure
- [ ] All examples in documentation follow the conventions

---

## Example: Complete Package Structure

```
{package-name}/
├── composer.json                    ✅ greelogix/{package-name}
├── config/
│   └── gl-{package-name}.php       ✅ gl- prefix
├── database/
│   └── migrations/
│       └── create_{table}_table.php ✅ Creates gl_{table}
├── resources/
│   └── views/
│       └── index.blade.php
├── routes/
│   └── web.php                     ✅ Routes with gl/ prefix
├── src/
│   ├── Console/
│   │   └── InstallCommand.php      ✅ gl-{package-name}:install
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── {Controller}Controller.php
│   │   └── Middleware/
│   │       └── {Middleware}.php
│   ├── Models/
│   │   └── {Model}.php             ✅ Table: gl_{table}
│   └── {PackageName}ServiceProvider.php ✅ GreeLogix namespace
└── README.md
```

---

**Remember**: Consistency is key! All Greelogix packages should follow these conventions to ensure easy identification and avoid conflicts.
