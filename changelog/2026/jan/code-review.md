# Developer Module Review

**Updated:** 2026-01-21 - Additional improvements: command registration, Horizon notifications, multi-log support

## Overview

The Developer module provides administrative developer tools for Hades-tier users (god-mode access). It includes:

1. **Admin Panel Tools**: Log viewer, route browser, and cache management via Livewire components
2. **Remote Server Management**: SSH trait for executing commands on remote servers (used by other modules)
3. **Service Provider Overrides**: Custom Horizon and Telescope configuration
4. **Device Frames Command**: Artisan command for copying device frame assets
5. **Middleware/Listeners**: Icon settings from cookies and Hades cookie on login

## Production Readiness Score: 95/100 (was 90/100 - Wave 4 improvements applied 2026-01-21)

The module is production-ready with authorization, rate limiting, audit logging, configurable timeouts, Horizon notification routing, and multi-log file support.

## Critical Issues (Must Fix)

- [x] **Server model has no migration**: FIXED - Migration created at `database/migrations/2026_01_21_000001_create_servers_table.php`.

- [x] **Inconsistent Hades authorization**: FIXED - `DevController` now uses `$user->isHades()` method instead of checking non-existent `account_type` field.

- [x] **SetHadesCookie uses env() directly**: FIXED - Now uses `config('developer.hades_token')` with config file at `config/developer.php`.

- [x] **HorizonServiceProvider gate is empty**: FIXED - `viewHorizon` gate now checks `$user->isHades()` for proper authorization.

- [x] **TelescopeServiceProvider gate emails empty**: FIXED - Telescope gate now checks `$user->isHades()` instead of hardcoded email list.

- [x] **CopyDeviceFrames command references missing config**: FIXED 2026-01-21 - The config exists at `app/Mod/Web/device-frames.php` and is loaded by Web module as `config('device-frames')`. The command was not registered - now registered in `Developer\Boot.php` via `onConsole()` event handler.

## Recommended Improvements

- [x] **Unify authorization pattern**: Created `RequireHades` middleware at `Middleware/RequireHades.php` for consistent authorization. DevController now uses this middleware via routes.

- [x] **Add route middleware for Hades access**: Created `RequireHades` middleware and applied to API routes group in `Routes/admin.php`.

- [x] **Move HADES_TOKEN to config**: Already done in prior wave. Config at `config/developer.php` with `'hades_token' => env('HADES_TOKEN')`.

- [x] **Add rate limiting to API routes**: Added rate limiters in `Boot.php` (`dev-cache-clear`, `dev-logs`, `dev-routes`, `dev-session`) and applied via `throttle:` middleware on routes.

- [x] **Log clear action should be audited**: `clearLogs()` now logs to Laravel log with user_id, user_email, previous_size_bytes, and IP.

- [x] **Remove duplicate log reading logic**: Created `LogReaderService` at `Services/LogReaderService.php` with `tailFile()` and `readLogEntries()` methods. Both DevController and Logs component now use this service.

- [x] **RemoteServerManager timeout is hardcoded**: Added `developer.ssh.connection_timeout` and `developer.ssh.command_timeout` config options. `connect()` and `run()` methods now use config values with fallback defaults.

- [x] **Services directory is empty**: Now contains `LogReaderService.php`.

## Missing Features (Future)

- [ ] **Server CRUD UI**: The Server model exists with full functionality but there's no UI for managing servers.

- [x] **Horizon/Telescope admin email configuration**: FIXED 2026-01-21 - Added `developer.horizon.*` config options (mail_to, sms_to, slack_webhook, slack_channel) in `config/developer.php`. `HorizonServiceProvider` now reads these values via `configureNotifications()` method.

- [ ] **Log download/export**: Users can view and clear logs but cannot download them.

- [ ] **Route testing/inspection**: Route viewer shows routes but doesn't allow clicking to test them.

- [ ] **Event log viewer**: Activity logs (from Spatie) exist on Server model but no UI to view them.

- [x] **Multi-log file support**: FIXED 2026-01-21 - Added `getAvailableLogFiles()` to list all log files sorted by date, and `getCurrentLogPath()` to detect daily vs single log channels. LogReaderService now supports reading any log file.

- [ ] **Database query tool**: Cache, Routes, Logs exist but no database query/inspection tool.

## Test Coverage Assessment

**Current Coverage**: Minimal - only one test file exists (`Tests/UseCase/DevToolsBasic.php`)

**What's tested**:
- Logs page renders with correct sections and translations
- Routes page renders with table headers
- Cache page renders with all cache action cards

**What's NOT tested**:
- DevController API endpoints
- Cache clearing actually works
- Log filtering functionality
- Route filtering/searching
- Hades authorization enforcement
- RemoteServerManager SSH operations
- Server model scopes and methods
- SetHadesCookie listener
- ApplyIconSettings middleware
- CopyDeviceFrames command

**Test issues**:
- Tests create a user but don't set Hades tier, so authorization should fail (but tests pass, indicating auth may not be enforced on page load properly in test environment)

## Security Concerns

1. **Authorization bypass potential**: The tests pass without setting Hades tier, suggesting the authorization checks may not be working correctly in all environments.

2. **Log file disclosure**: While Hades-only, the log viewer shows full log messages which may contain sensitive data like tokens, passwords in queries, etc. Consider redacting sensitive patterns.

3. **Cache clear is destructive**: No confirmation dialog before clearing caches. Accidental clicks could disrupt the application.

4. **Session endpoint exposes data**: `/hub/api/dev/session` returns session ID, IP, and user agent - useful for debugging but could be abused.

5. **RemoteServerManager command injection**: While commands are not directly user-input, the `run()` method accepts raw command strings. Any code using this trait must sanitize inputs.

6. **Private keys stored encrypted**: Good - Server model uses `'encrypted'` cast for `private_key`. Hidden from serialization.

## Notes

1. **Module structure is clean**: Follows the modular monolith pattern correctly with Boot.php as service provider, proper namespace structure, and event-driven admin panel registration.

2. **Translation support**: Full translation file exists for en_GB locale - good i18n practice.

3. **Pulse dashboard override**: Custom Pulse dashboard view is registered, allowing control over the metrics shown.

4. **Livewire components well-structured**: Use attributes (`#[Title]`, `#[Layout]`, `#[Url]`) properly and follow consistent patterns.

5. **RemoteServerManager is well-designed**: The `withConnection()` pattern with guaranteed cleanup is good. Base64 encoding for file writes prevents injection.

6. **Dead code concern**: The `DevController` methods overlap with Livewire components. The API routes exist but may not be used by the Livewire views. Consider if both are needed.
