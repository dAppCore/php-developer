# TODO.md - core-developer

Last reviewed: 2026-01-29

## P1 - Critical / Security

### Completed
- [x] **Server model has no migration** - FIXED: Migration created at `src/Migrations/0001_01_01_000001_create_developer_tables.php`
- [x] **Inconsistent Hades authorization in DevController** - FIXED: Now uses `$user->isHades()` method
- [x] **SetHadesCookie uses env() directly** - FIXED: Now uses `config('developer.hades_token')`
- [x] **HorizonServiceProvider gate is empty** - FIXED: `viewHorizon` gate now checks `$user->isHades()`
- [x] **TelescopeServiceProvider gate emails empty** - FIXED: Telescope gate now checks `$user->isHades()`

### Open

_No open P1 items._

### Recently Fixed (Jan 2026)

- [x] **Database component SQL injection hardening** - FIXED: `isReadOnlyQuery()` now blocks stacked queries by checking for semicolons followed by non-whitespace content using regex `/;\s*\S/`.
  - File: `src/View/Modal/Admin/Database.php`

- [x] **DevController missing strict types declaration** - FIXED: Added `declare(strict_types=1);` at top of file.
  - File: `src/Controllers/DevController.php`

- [x] **Servers component writes private key to temp file** - FIXED: Now uses `tempnam()` for atomic file creation and sets 0600 permissions before writing sensitive data.
  - File: `src/View/Modal/Admin/Servers.php`

- [x] **CopyDeviceFrames command lacks config validation** - FIXED: Added validation for config existence and required keys (source_path, public_path, devices) with proper error messages.
  - File: `src/Console/Commands/CopyDeviceFrames.php`

## P2 - High Priority

### Completed
- [x] **Unify authorization pattern** - Created `RequireHades` middleware for consistent authorization
- [x] **Add rate limiting to API routes** - Added rate limiters in `Boot.php` for all endpoints
- [x] **Log clear action should be audited** - `clearLogs()` now logs with user_id, email, previous_size, IP
- [x] **Remove duplicate log reading logic** - Created `LogReaderService` used by both DevController and Logs component
- [x] **RemoteServerManager timeout is hardcoded** - Added configurable timeouts via config

### Open

- [ ] **Tests pass without Hades tier set** - Tests in `DevToolsBasic.php` create a user without Hades tier but tests pass, suggesting authorization may not be enforced correctly in test environment
  - File: `src/Tests/UseCase/DevToolsBasic.php`
  - Acceptance: Tests should fail when user is not Hades; add trait/helper to set Hades status in tests

- [ ] **Clear logs button has no confirmation** - Unlike cache management, the logs clear button executes immediately without confirmation
  - File: `src/View/Modal/Admin/Logs.php` and corresponding blade
  - Acceptance: Add confirmation modal similar to Cache component

- [ ] **Activity log component shows all workspaces** - ActivityLog queries all Activity records without workspace scoping
  - File: `src/View/Modal/Admin/ActivityLog.php`
  - Acceptance: Either scope to current workspace or document that this is intentional for Hades users

### Recently Fixed (Jan 2026)

- [x] **Missing developer config file** - FIXED: Config file exists at `src/config.php` with hades_token, ssh.connection_timeout, ssh.command_timeout, and horizon notification settings. Published via `mergeConfigFrom()` in Boot.php.

- [x] **Livewire pages have no route middleware** - FIXED: RequireHades middleware applied to the `/hub/dev/*` route group in `src/Routes/admin.php` line 15. Authorization now enforced at route level.

## P3 - Medium Priority

### Completed
- [x] **Multi-log file support** - Added `getAvailableLogFiles()` and `getCurrentLogPath()` to LogReaderService
- [x] **Command registration** - CopyDeviceFrames now registered via `onConsole()` handler

### Open

- [ ] **Server model missing table name specification** - Relies on Laravel convention; should explicitly set `$table = 'servers'`
  - File: `src/Models/Server.php`
  - Acceptance: Add protected `$table` property

- [ ] **LogReaderService redaction patterns need review** - IP redaction pattern may miss IPv6 addresses
  - File: `src/Services/LogReaderService.php` line 42
  - Acceptance: Add IPv6 support or document limitation

- [ ] **RouteTestService environment check is permissive** - `isTestingAllowed()` returns true for 'testing' environment which could be used in CI
  - File: `src/Services/RouteTestService.php` line 47
  - Acceptance: Consider adding config flag to explicitly enable route testing

- [ ] **Database query tool lacks export functionality** - Users can view results but cannot download/export them
  - Acceptance: Add CSV/JSON export button for query results

- [ ] **Route inspector history not persisted** - History is lost on page refresh
  - File: `src/View/Modal/Admin/RouteInspector.php`
  - Acceptance: Consider storing history in session or localStorage

- [ ] **Missing translations for some UI elements** - Servers, Database, and ActivityLog pages have hardcoded English strings instead of using translation keys
  - Files: Multiple blade files and components
  - Acceptance: Add translation keys to `src/Lang/en_GB/developer.php` and use them consistently

## P4 - Low Priority / Improvements

### Open

- [ ] **DevController has redundant authorize() calls** - The `routes()` and `session()` methods call `$this->authorize()` but API routes already have `RequireHades` middleware
  - File: `src/Controllers/DevController.php`
  - Acceptance: Remove redundant authorization checks or document the defence-in-depth approach

- [ ] **LogReaderService could use generators** - For very large log files, using generators instead of arrays would reduce memory usage
  - File: `src/Services/LogReaderService.php`
  - Acceptance: Refactor `readLogEntries()` to optionally yield entries

- [ ] **RouteTestResult getFormattedResponseTime has edge case** - Times under 1ms are converted to microseconds incorrectly (multiplied by 1000 instead of keeping as sub-millisecond)
  - File: `src/Data/RouteTestResult.php` line 93
  - Acceptance: Fix calculation or clarify the intended behaviour

- [ ] **Server status enum should be a proper PHP enum** - Currently uses string values ('pending', 'connected', 'failed')
  - File: `src/Models/Server.php`
  - Acceptance: Create `ServerStatus` backed enum and use it consistently

- [ ] **ApplyIconSettings middleware has hardcoded defaults** - Default values should come from config
  - File: `src/Middleware/ApplyIconSettings.php`
  - Acceptance: Move defaults to config file

- [ ] **Pulse dashboard override lacks documentation** - The custom Pulse view is registered but not documented
  - File: `src/View/Blade/vendor/pulse/dashboard.blade.php`
  - Acceptance: Add comment in Boot.php explaining the override purpose

## P5 - Nice to Have / Future

### From code-review.md (documented features)
- [ ] **Server CRUD UI improvements** - Add bulk actions, SSH key validation, connection health checks
- [ ] **Log download/export** - FIXED: `downloadLogs()` added, but could add format options (JSON, filtered export)
- [ ] **Event log viewer** - Activity logs exist on Server model but no dedicated UI to view activity per model

### New Ideas
- [ ] **Add log search functionality** - Currently only filter by level, add full-text search within log messages
- [ ] **Database saved queries** - Allow saving frequently used queries with names
- [ ] **Route documentation viewer** - Parse DocBlocks from controllers and display in route inspector
- [ ] **SSH terminal emulator** - Interactive terminal for connected servers (complex, security considerations)
- [ ] **Cache statistics dashboard** - Show cache hit/miss rates, memory usage, key counts
- [ ] **Config diff viewer** - Compare current config values against defaults

## P6+ - Backlog / Someday

- [ ] **Real-time log streaming** - WebSocket/SSE for live log tailing
- [ ] **Query explain plan visualisation** - Parse and display EXPLAIN output graphically
- [ ] **Route performance profiling** - Track response times over time, identify slow routes
- [ ] **Deployment integration** - Trigger deployments from server management UI
- [ ] **Multi-database support** - Query tool for multiple database connections
- [ ] **Scheduled task monitoring** - View and manage Laravel scheduled tasks

## Test Coverage Gaps

### Currently Tested
- Logs page renders with correct sections and translations
- Routes page renders with table headers
- Cache page renders with all cache action cards

### Missing Tests
- [ ] DevController API endpoints (logs, routes, session, clear)
- [ ] Cache clearing actually executes and clears caches
- [ ] Log filtering by level
- [ ] Route filtering/searching
- [ ] Hades authorization enforcement (both allow and deny cases)
- [ ] RemoteServerManager SSH operations (mock phpseclib)
- [ ] Server model scopes and methods
- [ ] SetHadesCookie listener
- [ ] ApplyIconSettings middleware
- [ ] CopyDeviceFrames command
- [ ] LogReaderService redaction patterns
- [ ] RouteTestService route testing logic
- [ ] Database component query execution and validation
- [ ] ActivityLog filtering and pagination
- [ ] RouteInspector request building and execution

## Notes

- All Hades-only features require the user's `isHades()` method to return true
- The module depends on `host-uk/core` and `host-uk/core-admin`
- UK English spellings must be used (colour, organisation, centre)
- All PHP files should have `declare(strict_types=1);`
- Testing uses Pest syntax, not PHPUnit
