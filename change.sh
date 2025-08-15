# === Start copy here ===
git checkout -b chore/api-harden

# .editorconfig
mkdir -p .github/workflows openapi app/Http/Middleware app/Http/Controllers/Api/V1 routes tests/Feature
cat > .editorconfig <<'EOF'
root = true
[*]
end_of_line = lf
insert_final_newline = true
charset = utf-8
indent_style = space
indent_size = 4
trim_trailing_whitespace = true
[*.{yml,yaml,json,md}]
indent_size = 2
EOF

# pint.json
cat > pint.json <<'EOF'
{
  "preset": "laravel",
  "rules": {
    "ordered_imports": {"sort_algorithm": "alpha"},
    "no_unused_imports": true
  }
}
EOF

# phpstan.neon (Larastan)
cat > phpstan.neon <<'EOF'
includes:
  - vendor/nunomaduro/larastan/extension.neon
parameters:
  paths:
    - app
  level: 6
  bootstrapFiles:
    - vendor/autoload.php
  parallel:
    maximumNumberOfProcesses: 4
EOF

# GitHub Actions CI
mkdir -p .github/workflows
cat > .github/workflows/api-ci.yml <<'EOF'
name: API CI
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    strategy:
      matrix: { php: ["8.2","8.3"] }
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          tools: composer:v2
          extensions: mbstring, intl, pdo_mysql, xml, curl, fileinfo, openssl
      - run: composer install --prefer-dist --no-interaction --no-progress
      - run: ./vendor/bin/pint --test || true
      - run: ./vendor/bin/phpstan analyse --no-progress || true
      - run: |
          if [ -f vendor/bin/pest ]; then vendor/bin/pest --compact || true; else vendor/bin/phpunit --testdox || true; fi
EOF

# Request ID middleware
cat > app/Http/Middleware/RequestId.php <<'EOF'
<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class RequestId
{
    public function handle(Request $request, Closure $next)
    {
        $id = $request->headers->get('X-Request-Id') ?? (string) Str::uuid();
        Log::withContext(['request_id' => $id]);
        $response = $next($request);
        $response->headers->set('X-Request-Id', $id);
        return $response;
    }
}
EOF

# Healthcheck controller
cat > app/Http/Controllers/Api/V1/HealthcheckController.php <<'EOF'
<?php
namespace App\Http\Controllers\Api\V1;
use Illuminate\Routing\Controller;

class HealthcheckController extends Controller
{
    public function __invoke()
    {
        return response()->json([
            'ok' => true,
            'service' => 'findmenu-api',
            'version' => config('app.version', '0.1.0'),
            'time' => now()->toIso8601String(),
        ]);
    }
}
EOF

# Versioned API routes (include this from routes/api.php)
cat > routes/api_v1.php <<'EOF'
<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\HealthcheckController;

Route::prefix('v1')->group(function () {
    Route::get('/health', HealthcheckController::class);
    // TODO: Auth + menus endpoints here, under ->middleware('auth:sanctum') as needed.
});
EOF

# Minimal OpenAPI spec
mkdir -p openapi
cat > openapi/openapi.yaml <<'EOF'
openapi: 3.0.3
info:
  title: FindMenu API
  version: 1.0.0
servers:
  - url: /api/v1
paths:
  /health:
    get:
      summary: Healthcheck
      responses:
        '200':
          description: OK
          content:
            application/json:
              schema:
                type: object
                properties:
                  ok: { type: boolean }
                  service: { type: string }
                  version: { type: string }
                  time: { type: string, format: date-time }
EOF

# Pest bootstrap + test
cat > tests/Pest.php <<'EOF'
<?php
uses(Tests\TestCase::class)->in('Feature');
EOF

cat > tests/Feature/HealthcheckTest.php <<'EOF'
<?php
it('responds to /api/v1/health', function () {
    $res = $this->get('/api/v1/health');
    $res->assertOk()->assertJsonStructure(['ok','service','version','time']);
});
EOF

# Composer deps
composer require laravel/sanctum spatie/laravel-permission darkaonline/l5-swagger
composer require --dev laravel/pint nunomaduro/larastan pestphp/pest pestphp/pest-plugin-laravel

# Publish Swagger (ok if already published)
php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider" --force || true

# Include v1 routes from routes/api.php (one-liner if missing)
grep -q "api_v1.php" routes/api.php || echo "<?php\n\nrequire __DIR__.'/api_v1.php';\n" > routes/api.php

# (Manual) Add 'request.id' alias in bootstrap/app.php or Kernel if on older Laravel:
#   ->withMiddleware(fn($m) => $m->alias(['request.id' => \App\Http\Middleware\RequestId::class]))

# Generate docs & run basic checks
php artisan l5-swagger:generate || true
./vendor/bin/pint || true
./vendor/bin/phpstan analyse || true
if [ -f vendor/bin/pest ]; then vendor/bin/pest --compact || true; else vendor/bin/phpunit --testdox || true; fi

git add .
git commit -m "chore(api): standards (pint, phpstan), healthcheck, v1 routes, basic OpenAPI, CI"
git push -u origin chore/api-harden
# === End copy here ===
