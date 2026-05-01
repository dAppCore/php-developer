# AGENTS.md — php-developer

CorePHP package contract:

- `declare(strict_types=1);` in every PHP file
- Type hints on every parameter + return type
- Pest test suite (not PHPUnit)
- PSR-12 via Laravel Pint (`pint.json`)
- PHPStan static analysis (`phpstan.neon`)
- EUPL-1.2 licence

## CI

- `.github/workflows/ci.yml` runs Pest + Pint + PHPStan
- `.woodpecker.yml` mirrors for forge.lthn.sh
