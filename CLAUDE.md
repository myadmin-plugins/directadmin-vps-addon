# DirectAdmin VPS Addon — MyAdmin Plugin

## Overview
PHP plugin selling DirectAdmin licenses as a VPS addon in MyAdmin billing.

- **Namespace:** `Detain\MyAdminVpsDirectadmin` → `src/`
- **Test Namespace:** `Detain\MyAdminVpsDirectadmin\Tests` → `tests/`
- **Module:** `vps` · **Type:** `addon`
- **Key deps:** `symfony/event-dispatcher ^5.0`

## Commands

```bash
composer install
vendor/bin/phpunit tests/ -v
vendor/bin/phpunit tests/ --coverage-clover coverage.xml --whitelist src/
```

Config: `phpunit.xml.dist`

## Architecture

**CI/CD:** `.github/` contains `workflows/tests.yml` for automated testing pipelines. `.idea/` contains IDE configuration including `inspectionProfiles/Project_Default.xml`, `deployment.xml`, and `encodings.xml`.

**Plugin class:** `src/Plugin.php` → `Detain\MyAdminVpsDirectadmin\Plugin`
**Addon function:** `src/vps_add_directadmin.php` → `vps_add_directadmin()`

**Hooks** registered in `Plugin::getHooks()`:
- `function.requirements` → `getRequirements()` — registers `vps_add_directadmin` page requirement
- `vps.load_addons` → `getAddon()` — registers `AddonHandler` with cost `VPS_DA_COST`
- `vps.settings` → `getSettings()` — adds `vps_da_cost` text setting to admin panel

**Hook registration pattern:**
```php
public static function getHooks()
{
    return [
        'function.requirements'      => [__CLASS__, 'getRequirements'],
        self::$module.'.load_addons' => [__CLASS__, 'getAddon'],
        self::$module.'.settings'    => [__CLASS__, 'getSettings'],
    ];
}
```

**Lifecycle:**
- `doEnable(\ServiceHandler, $repeatInvoiceId)` — calls `activate_directadmin()` after `function_requirements('activate_directadmin')`
- `doDisable(\ServiceHandler, $repeatInvoiceId)` — calls `deactivate_directadmin()`, sends admin email via `\MyAdmin\Mail()->adminMail()` using `admin/vps_da_canceled.tpl`

**MyAdmin helpers:**
- `get_module_settings(self::$module)` → `PREFIX`, `TABLE`, `TBLNAME`
- `myadmin_log($module, 'info', $msg, __LINE__, __FILE__, $module, $serviceId)`
- `function_requirements('name')` — lazy-loads function/class files
- `run_event('parse_service_extra', $extra, $module)` — hook dispatch
- `vps_get_password($id, $custid)` — get VPS root password
- `directadmin_get_best_type($module, $type, $serviceInfo, $serviceExtra)`

License functions: `require_once __DIR__.'/../../../../include/licenses/license.functions.inc.php'`

## Conventions

- Tabs for indentation (enforced in `.scrutinizer.yml`)
- camelCase properties and parameters; `UPPERCASE` constants (e.g. `VPS_DA_COST`)
- All logging via `myadmin_log()` with `__LINE__, __FILE__`
- Load external functions via `function_requirements()`, not direct `require_once` except license functions
- Static class methods for all plugin handlers; constructor is empty

## Tests

- `tests/PluginTest.php` — plugin class structure
- `tests/VpsAddDirectadminTest.php` — source-reading tests on `src/vps_add_directadmin.php`
- Pattern: `file_get_contents()` + `assertStringContains` / `assertMatchesRegularExpression` on raw PHP source
- PHPUnit 9

```bash
vendor/bin/phpunit tests/PluginTest.php -v
vendor/bin/phpunit tests/VpsAddDirectadminTest.php -v
```

<!-- caliber:managed:pre-commit -->
## Before Committing

**IMPORTANT:** Before every git commit, you MUST ensure Caliber syncs agent configs with the latest code changes.

First, check if the pre-commit hook is already installed:
```bash
grep -q "caliber" .git/hooks/pre-commit 2>/dev/null && echo "hook-active" || echo "no-hook"
```

- If **hook-active**: the hook handles sync automatically — just commit normally. Tell the user: "Caliber will sync your agent configs automatically via the pre-commit hook."
- If **no-hook**: run Caliber manually before committing:
  1. Tell the user: "Caliber: Syncing agent configs with your latest changes..."
  2. Run: `caliber refresh && git add CLAUDE.md .claude/ .cursor/ .cursorrules .github/copilot-instructions.md .github/instructions/ AGENTS.md CALIBER_LEARNINGS.md 2>/dev/null`
  3. After it completes, briefly tell the user what Caliber updated. Then proceed with the commit.

If `caliber` is not found, tell the user: "This project uses Caliber for agent config sync. Run /setup-caliber to get set up."
<!-- /caliber:managed:pre-commit -->

<!-- caliber:managed:learnings -->
## Session Learnings

Read `CALIBER_LEARNINGS.md` for patterns and anti-patterns learned from previous sessions.
These are auto-extracted from real tool usage — treat them as project-specific rules.
<!-- /caliber:managed:learnings -->
