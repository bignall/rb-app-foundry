# Testing Guide — RB App Foundry

This guide covers the automated test suite (PHPUnit, PHPStan) and the manual checks that are required before cutting a release or merging a PR that touches the framework core.

---

## Automated Tests

### Running the PHP test suite

```bash
composer test          # PHPUnit (no WordPress install needed)
composer analyse       # PHPStan static analysis (level 5)
composer phpcs         # WordPress Coding Standards lint
```

The suite uses **PHPUnit 10** with **Brain\Monkey 2.6** to stub WordPress functions. It runs without a live WordPress site or database.

### Test directory layout

```
tests/
├── Unit/
│   ├── Admin/                 # SettingsManagerTest.php
│   ├── Connection/            # AuthTypeTest, ConnectionResponseTest,
│   │                          #   ConnectionAbstractTest, ConnectionManagerTest
│   └── Addon/                 # AddonManagerTest.php
├── Stubs/
│   └── WordPress.php          # Lightweight WP_Error stub
├── WPTestCase.php             # Base class — sets up Brain\Monkey
└── bootstrap.php              # Autoloader + constants (ABSPATH, AUTH_KEY, ...)
```

### What to test

| Type | Approach |
|---|---|
| Pure logic (ConnectionResponse, AuthType) | Standard PHPUnit assertions — no mocking needed |
| Classes that call WP functions (SettingsManager, ConnectionAbstract, AddonManager) | Extend `WPTestCase`; stub WP functions with `Brain\Monkey\Functions\when()` or `expect()` |
| Classes that make HTTP calls | Use the **testable-subclass pattern** (see below) |
| Classes that need private state set up | Use `ReflectionClass` to access private properties |

### What NOT to automate

Cover these in the PR description instead:

- OAuth flows that require a live browser session
- Actual API calls to third-party platforms
- WordPress plugin activation/deactivation on a real site
- Visual admin panel behaviour

---

## Testable-Subclass Pattern

`ConnectionAbstract::httpRequest()`, `storeCredentials()`, and `getStoredCredentials()` are all `protected`. Create a concrete subclass inside the test file that overrides them to avoid real HTTP and database calls:

```php
class TestableMyConnection extends MyConnection
{
    private array $fakeCredentials = [];
    private ?ConnectionResponse $nextResponse = null;

    public function setFakeCredentials(array $creds): void
    {
        $this->fakeCredentials = $creds;
    }

    public function queueResponse(ConnectionResponse $response): void
    {
        $this->nextResponse = $response;
    }

    protected function getStoredCredentials(): array
    {
        return $this->fakeCredentials;
    }

    protected function storeCredentials(array $credentials): void
    {
        $this->fakeCredentials = $credentials;
    }

    protected function httpRequest(string $method, string $url, array $options = []): ConnectionResponse
    {
        return $this->nextResponse ?? ConnectionResponse::success([]);
    }
}
```

---

## Writing Tests for a New Connection

If you are adding a new connection class (e.g. `InstagramConnection`):

1. Create `tests/Unit/Connection/InstagramConnectionTest.php`
2. Define `TestableInstagramConnection` inside the file (as above)
3. Test at minimum:
   - `getId()` and `getName()` return expected values
   - `isConnected()` returns false when no credentials
   - `authenticate()` success path (queue a 200 response, assert credentials stored)
   - `authenticate()` failure path (queue a 4xx response, assert returns false)
   - The publish/post method: success path and error path
   - Any non-trivial helper methods (token refresh, account listing, etc.)

---

## Manual Testing Checklist

Run this checklist before every release and before merging any PR that changes framework behaviour.

### 1. Installation

- [ ] Activate RB App Foundry on a clean WordPress install (no other plugins)
- [ ] No PHP errors or warnings in the debug log
- [ ] The **RB App Foundry** admin menu item appears
- [ ] Deactivate and reactivate — no errors

### 2. Admin Panel

- [ ] The admin panel loads at **WP Admin → RB App Foundry**
- [ ] The Settings tab saves and reloads values correctly (dot-notation keys round-trip)
- [ ] The Connections tab lists all registered connections with correct auth-type labels
- [ ] The Add-ons tab shows discovered add-ons with accurate active/inactive status

### 3. Add-on Lifecycle

- [ ] Activating an add-on shows it as active immediately (no page reload required)
- [ ] Deactivating an add-on removes it from the active list
- [ ] Attempting to deactivate an add-on that another active add-on depends on is rejected with a clear error
- [ ] Reloading the page after activate/deactivate preserves the state

### 4. Connections

- [ ] Each connection's settings fields appear in the UI
- [ ] Saving OAuth credentials (if applicable) persists across page reloads
- [ ] The connection status indicator updates correctly after authenticate/disconnect

### 5. With a Dependent Plugin Active

- [ ] Install and activate **RB SocialPillar** alongside RB App Foundry
- [ ] SocialPillar add-ons appear in the Add-ons tab
- [ ] No conflicts or duplicate admin menu items
- [ ] Both plugins deactivate cleanly without errors

### 6. Plugin Check (before release)

Run the official WordPress Plugin Check tool:

```
WP Admin → Tools → Plugin Check → select RB App Foundry → Run Tests
```

- [ ] Zero errors (warnings are acceptable if documented)
- [ ] No deprecated function usage flagged

### 7. Upgrade Path

- [ ] Install the previous released version, then overwrite with the new version
- [ ] Existing settings are preserved
- [ ] Existing active add-ons remain active
- [ ] No migration errors in the debug log
