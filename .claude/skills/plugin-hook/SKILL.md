---
name: plugin-hook
description: Implements the MyAdmin plugin hook registration pattern for `src/Plugin.php`. Generates `getHooks()` returning event-to-handler map, `getRequirements()` with `add_page_requirement`, `getAddon()` with `AddonHandler`, and `getSettings()` with `add_text_setting`. Use when user says 'add a hook', 'register event', 'new plugin method', or modifies `Plugin::getHooks()`. Do NOT use for modifying existing hook handlers (doEnable/doDisable logic) or for non-Plugin.php files.
---
# Plugin Hook Registration

## Critical

- All hook handler methods MUST be `public static` — never instance methods
- `getHooks()` keys use dot notation: `'function.requirements'` (global) and `self::$module.'.hookname'` (module-prefixed)
- Every value in `getHooks()` MUST be `[__CLASS__, 'methodName']` — never a closure or string
- Module-prefixed keys (all except `function.requirements`) MUST start with `self::$module.'.'`
- Handler methods receiving `GenericEvent` MUST type-hint the parameter as `GenericEvent $event`
- Never add protected or private methods — all methods must be public
- Tabs for indentation (enforced by `.scrutinizer.yml`)

## Instructions

1. **Add the event key to `getHooks()`** in `src/Plugin.php`.
   - Global hooks (framework-level): `'function.requirements' => [__CLASS__, 'getRequirements']`
   - Module hooks: `self::$module.'.load_addons' => [__CLASS__, 'getAddon']` and `self::$module.'.settings' => [__CLASS__, 'getSettings']`
   - Verify the new key follows dot-separator format before proceeding.

2. **Implement a `getRequirements` handler** when registering a page/function dependency:
   ```php
   public static function getRequirements(GenericEvent $event)
   {
       /** @var \MyAdmin\Plugins\Loader $this->loader */
       $loader = $event->getSubject();
       $loader->add_page_requirement('function_name', '/../vendor/detain/PACKAGE/src/file.php');
   }
   ```
   - Verify `add_page_requirement` first arg matches the function name used in `function_requirements()` calls elsewhere.

3. **Implement a `getAddon` handler** when registering an `AddonHandler`:
   ```php
   public static function getAddon(GenericEvent $event)
   {
       /** @var \ServiceHandler $service */
       $service = $event->getSubject();
       function_requirements('class.AddonHandler');
       $addon = new \AddonHandler();
       $addon->setModule(self::$module)
           ->set_text('AddonLabel')
           ->set_cost(COST_CONSTANT)
           ->set_require_ip(true)
           ->setEnable([__CLASS__, 'doEnable'])
           ->setDisable([__CLASS__, 'doDisable'])
           ->register();
       $service->addAddon($addon);
   }
   ```
   - Verify `COST_CONSTANT` is defined (e.g., `VPS_DA_COST`) and matches the setting key registered in `getSettings()`.

4. **Implement a `getSettings` handler** when adding an admin-panel setting:
   ```php
   public static function getSettings(GenericEvent $event)
   {
       /** @var \MyAdmin\Settings $settings **/
       $settings = $event->getSubject();
       $settings->setTarget('module');
       $settings->add_text_setting(self::$module, _('Addon Costs'), 'setting_key', _('Label'), _('Description'), $settings->get_setting('SETTING_CONSTANT'));
       $settings->setTarget('global');
   }
   ```
   - `setting_key` must be lowercase with underscores (e.g., `vps_da_cost`); `SETTING_CONSTANT` is the uppercase equivalent (`VPS_DA_COST`).
   - Always call `setTarget('global')` after adding settings to reset scope.
   - Verify `$settings->setTarget('module')` precedes `add_text_setting` and `setTarget('global')` follows it.

5. **Run tests** to verify the hook registration:
   ```bash
   vendor/bin/phpunit tests/PluginTest.php -v
   ```
   - `testGetHooksCount` will fail if you added a key without a handler — fix before committing.
   - `testExpectedPublicMethods` will fail if a new method was added — update the `$expected` array in `tests/PluginTest.php` to include the new method name.

## Examples

**User says:** "Add a hook to register a new page requirement for `vps_add_cpanel`"

**Actions taken:**
1. Add to `getHooks()`: `'function.requirements' => [__CLASS__, 'getRequirements']` (already present — this key is shared)
2. Add inside `getRequirements()`: `$loader->add_page_requirement('vps_add_cpanel', '/../vendor/detain/myadmin-cpanel-vps-addon/src/vps_add_cpanel.php');`
3. Run `vendor/bin/phpunit tests/PluginTest.php -v`

**Result:**
```php
public static function getRequirements(GenericEvent $event)
{
    $loader = $event->getSubject();
    $loader->add_page_requirement('vps_add_directadmin', '/../vendor/detain/myadmin-directadmin-vps-addon/src/vps_add_directadmin.php');
    $loader->add_page_requirement('vps_add_cpanel', '/../vendor/detain/myadmin-cpanel-vps-addon/src/vps_add_cpanel.php');
}
```

## Common Issues

- **`testGetHooksCount` fails with count mismatch:** You added a key to `getHooks()` without updating the test's expected count, or you forgot to add the corresponding handler method. Check `tests/PluginTest.php:233` and update `assertCount(3, ...)` to the new total.
- **`testExpectedPublicMethods` fails:** New handler method not listed in `$expected` array at `tests/PluginTest.php:358`. Add the method name to that array.
- **`testHookCallbackMethodsArePublicStatic` fails:** Handler method is not declared `static`. All hook handlers in `Plugin.php` must be `public static`.
- **`testModulePrefixedHookKeys` fails:** A module-level hook key doesn't start with `Plugin::$module.'.'`. Use `self::$module.'.hookname'` not a hardcoded string like `'vps.hookname'`.
- **`testGetSettingsRegisters*` fails:** `setTarget('module')` or `setTarget('global')` missing around `add_text_setting()`. Both calls are required — see Step 4.
