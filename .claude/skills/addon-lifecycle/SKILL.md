---
name: addon-lifecycle
description: Implements doEnable/doDisable lifecycle methods following the pattern in src/Plugin.php. Handles get_module_settings(), myadmin_log(), function_requirements(), vps_get_password(), run_event('parse_service_extra'), and admin email via \MyAdmin\Mail()->adminMail(). Use when user says 'add enable logic', 'implement disable', 'provision addon', or works on activation/deactivation. Do NOT use for hook registration or AddonHandler setup.
---
# Addon Lifecycle

## Critical

- Always call `require_once __DIR__.'/../../../../include/licenses/license.functions.inc.php'` **before** any license or activation functions in both `doEnable` and `doDisable`.
- Always resolve the service ID and customer ID through `$settings['PREFIX']` — never hardcode column names.
- Call `myadmin_log()` immediately after `get_module_settings()` as the first observable action in both methods.
- All external functions (`activate_*`, `deactivate_*`, `directadmin_get_best_type`) must be loaded via `function_requirements()`, not `require_once`.

## Instructions

1. **Declare the method signature** — both lifecycle methods share the same signature:
   ```php
   public static function doEnable(\ServiceHandler $serviceOrder, $repeatInvoiceId, $regexMatch = false)
   public static function doDisable(\ServiceHandler $serviceOrder, $repeatInvoiceId, $regexMatch = false)
   ```
   Verify `$serviceOrder` is typed as `\ServiceHandler` before proceeding.

2. **Extract service info and settings** — always the first two lines of the method body:
   ```php
   $serviceInfo = $serviceOrder->getServiceInfo();
   $settings = get_module_settings(self::$module);
   ```
   All subsequent column references use `$settings['PREFIX']` as the key prefix.

3. **Load license functions** — immediately after step 2, before any other calls:
   ```php
   require_once __DIR__.'/../../../../include/licenses/license.functions.inc.php';
   ```

4. **Log the lifecycle event:**
   ```php
   myadmin_log(self::$module, 'info', self::$name.' Activation', __LINE__, __FILE__, self::$module, $serviceInfo[$settings['PREFIX'].'_id']);
   // or '... Deactivation' for doDisable
   ```
   Use `self::$name` (not a string literal) as the message prefix.

5. **`doEnable` only — fetch password and parse extra:**
   ```php
   $pass = vps_get_password($serviceInfo[$settings['PREFIX'].'_id'], $serviceInfo[$settings['PREFIX'].'_custid']);
   function_requirements('directadmin_get_best_type');
   function_requirements('activate_directadmin'); // replace with your activation function
   $serviceExtra = run_event('parse_service_extra', $serviceInfo[$settings['PREFIX'].'_extra'], self::$module);
   $ostype = directadmin_get_best_type(self::$module, $serviceInfo[$settings['PREFIX'].'_type'], $serviceInfo, $serviceExtra);
   ```
   Verify `$pass` and `$ostype` are populated before calling the activation function.

6. **`doEnable` only — call the provisioning function:**
   ```php
   activate_directadmin(
       $serviceInfo[$settings['PREFIX'].'_ip'],
       $ostype,
       $pass,
       $GLOBALS['tf']->accounts->cross_reference($serviceInfo[$settings['PREFIX'].'_custid']),
       self::$module.$serviceInfo[$settings['PREFIX'].'_id']
   );
   ```

7. **`doDisable` only — call the deprovisioning function then send admin email:**
   ```php
   function_requirements('deactivate_directadmin'); // replace with your deactivation function
   deactivate_directadmin($serviceInfo[$settings['PREFIX'].'_ip']);
   $email = $settings['TBLNAME'].' ID: '.$serviceInfo[$settings['PREFIX'].'_id'].'<br>'
       .$settings['TBLNAME'].' Hostname: '.$serviceInfo[$settings['PREFIX'].'_hostname'].'<br>'
       .'Repeat Invoice: '.$repeatInvoiceId.'<br>'
       .'Description: '.self::$name.'<br>';
   $subject = $settings['TBLNAME'].' '.$serviceInfo[$settings['PREFIX'].'_id'].' Canceled '.self::$name;
   (new \MyAdmin\Mail())->adminMail($subject, $email, false, 'admin/vps_da_canceled.tpl');
   ```
   Verify the `.tpl` path exists under `include/templates/email/admin/` before committing.

## Examples

**User says:** "Implement doEnable and doDisable for a new Imunify addon"

**Actions taken:**
1. Add `public static $name = 'Imunify VPS Addon';` and `public static $module = 'vps';` to the Plugin class.
2. Implement `doEnable` following steps 1–6, replacing `activate_directadmin` → `activate_imunify` and loading it via `function_requirements('activate_imunify')`.
3. Implement `doDisable` following step 7, replacing `deactivate_directadmin` → `deactivate_imunify` and template `admin/vps_da_canceled.tpl` → `admin/vps_imunify_canceled.tpl`.

**Result:**
```php
public static function doEnable(\ServiceHandler $serviceOrder, $repeatInvoiceId, $regexMatch = false)
{
    $serviceInfo = $serviceOrder->getServiceInfo();
    $settings = get_module_settings(self::$module);
    require_once __DIR__.'/../../../../include/licenses/license.functions.inc.php';
    myadmin_log(self::$module, 'info', self::$name.' Activation', __LINE__, __FILE__, self::$module, $serviceInfo[$settings['PREFIX'].'_id']);
    $pass = vps_get_password($serviceInfo[$settings['PREFIX'].'_id'], $serviceInfo[$settings['PREFIX'].'_custid']);
    function_requirements('activate_imunify');
    $serviceExtra = run_event('parse_service_extra', $serviceInfo[$settings['PREFIX'].'_extra'], self::$module);
    activate_imunify($serviceInfo[$settings['PREFIX'].'_ip'], $pass, $GLOBALS['tf']->accounts->cross_reference($serviceInfo[$settings['PREFIX'].'_custid']), self::$module.$serviceInfo[$settings['PREFIX'].'_id']);
}
```

## Common Issues

- **`Call to undefined function activate_*`**: You skipped `function_requirements('activate_*')` before calling it. Add the call immediately before the activation function call.
- **`Undefined index: PREFIX_id`**: `get_module_settings()` was not called, or `self::$module` is wrong. Verify `public static $module = 'vps';` matches the registered module name.
- **`require_once` path resolves to wrong location**: `__DIR__` is relative to `src/`. The path `__DIR__.'/../../../../include/licenses/license.functions.inc.php'` assumes four directory levels up to the MyAdmin root. If the plugin is not installed under `vendor/detain/myadmin-*/src/`, adjust the `../` count.
- **Admin email not sent after disable**: Ensure `(new \MyAdmin\Mail())` is instantiated with `new` and the template path is relative to `include/templates/email/`. Missing template file will silently fail — confirm the `.tpl` exists.
- **`vps_get_password` returns empty**: The VPS record may lack a stored password. Log `$pass` with `myadmin_log()` immediately after the call to confirm before passing to the activation function.