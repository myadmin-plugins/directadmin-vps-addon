<?php

namespace Detain\MyAdminVpsDirectadmin\Tests;

use Detain\MyAdminVpsDirectadmin\Plugin;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Tests for the Plugin class.
 *
 * Covers class structure, static properties, hook registration,
 * method signatures, and event handler behaviour using reflection
 * and static analysis rather than mocking heavy external services.
 */
class PluginTest extends TestCase
{
    /**
     * @var ReflectionClass<Plugin>
     */
    private ReflectionClass $ref;

    protected function setUp(): void
    {
        $this->ref = new ReflectionClass(Plugin::class);
    }

    // ------------------------------------------------------------------
    //  Class structure
    // ------------------------------------------------------------------

    /**
     * Verify that the Plugin class exists and lives in the expected namespace.
     */
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(Plugin::class));
    }

    /**
     * Verify the fully-qualified class name.
     */
    public function testFullyQualifiedClassName(): void
    {
        $this->assertSame('Detain\\MyAdminVpsDirectadmin\\Plugin', $this->ref->getName());
    }

    /**
     * The class must not be abstract or an interface.
     */
    public function testClassIsInstantiable(): void
    {
        $this->assertTrue($this->ref->isInstantiable());
        $this->assertFalse($this->ref->isAbstract());
        $this->assertFalse($this->ref->isInterface());
    }

    /**
     * The class must not extend any parent class.
     */
    public function testClassHasNoParent(): void
    {
        $this->assertFalse($this->ref->getParentClass());
    }

    /**
     * The class must not implement any interfaces.
     */
    public function testClassImplementsNoInterfaces(): void
    {
        $this->assertEmpty($this->ref->getInterfaceNames());
    }

    // ------------------------------------------------------------------
    //  Static properties
    // ------------------------------------------------------------------

    /**
     * The $name property must be a public static string.
     */
    public function testNameProperty(): void
    {
        $prop = $this->ref->getProperty('name');
        $this->assertTrue($prop->isPublic());
        $this->assertTrue($prop->isStatic());
        $this->assertSame('DirectAdmin VPS Addon', Plugin::$name);
    }

    /**
     * The $description property must be a public static string containing key info.
     */
    public function testDescriptionProperty(): void
    {
        $prop = $this->ref->getProperty('description');
        $this->assertTrue($prop->isPublic());
        $this->assertTrue($prop->isStatic());
        $this->assertStringContainsString('DirectAdmin', Plugin::$description);
        $this->assertStringContainsString('VPS', Plugin::$description);
        $this->assertStringContainsString('https://www.directadmin.com/', Plugin::$description);
    }

    /**
     * The $help property must be a public static string (may be empty).
     */
    public function testHelpProperty(): void
    {
        $prop = $this->ref->getProperty('help');
        $this->assertTrue($prop->isPublic());
        $this->assertTrue($prop->isStatic());
        $this->assertIsString(Plugin::$help);
    }

    /**
     * The $module property must equal 'vps'.
     */
    public function testModuleProperty(): void
    {
        $prop = $this->ref->getProperty('module');
        $this->assertTrue($prop->isPublic());
        $this->assertTrue($prop->isStatic());
        $this->assertSame('vps', Plugin::$module);
    }

    /**
     * The $type property must equal 'addon'.
     */
    public function testTypeProperty(): void
    {
        $prop = $this->ref->getProperty('type');
        $this->assertTrue($prop->isPublic());
        $this->assertTrue($prop->isStatic());
        $this->assertSame('addon', Plugin::$type);
    }

    /**
     * Verify that exactly five static properties are declared.
     */
    public function testStaticPropertyCount(): void
    {
        $statics = array_filter(
            $this->ref->getProperties(),
            static fn(\ReflectionProperty $p) => $p->isStatic()
        );
        $this->assertCount(5, $statics);
    }

    // ------------------------------------------------------------------
    //  Constructor
    // ------------------------------------------------------------------

    /**
     * The constructor should accept zero arguments and be public.
     */
    public function testConstructor(): void
    {
        $ctor = $this->ref->getConstructor();
        $this->assertNotNull($ctor);
        $this->assertTrue($ctor->isPublic());
        $this->assertSame(0, $ctor->getNumberOfRequiredParameters());
    }

    /**
     * Instantiation must succeed without errors.
     */
    public function testInstantiation(): void
    {
        $plugin = new Plugin();
        $this->assertInstanceOf(Plugin::class, $plugin);
    }

    // ------------------------------------------------------------------
    //  getHooks()
    // ------------------------------------------------------------------

    /**
     * getHooks must be public and static.
     */
    public function testGetHooksIsPublicStatic(): void
    {
        $method = $this->ref->getMethod('getHooks');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
    }

    /**
     * getHooks must return an array.
     */
    public function testGetHooksReturnsArray(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertIsArray($hooks);
    }

    /**
     * getHooks must contain the 'function.requirements' key.
     */
    public function testGetHooksContainsFunctionRequirements(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertArrayHasKey('function.requirements', $hooks);
        $this->assertSame([Plugin::class, 'getRequirements'], $hooks['function.requirements']);
    }

    /**
     * getHooks must contain the module load_addons key.
     */
    public function testGetHooksContainsLoadAddons(): void
    {
        $hooks = Plugin::getHooks();
        $key = Plugin::$module . '.load_addons';
        $this->assertArrayHasKey($key, $hooks);
        $this->assertSame([Plugin::class, 'getAddon'], $hooks[$key]);
    }

    /**
     * getHooks must contain the module settings key.
     */
    public function testGetHooksContainsSettings(): void
    {
        $hooks = Plugin::getHooks();
        $key = Plugin::$module . '.settings';
        $this->assertArrayHasKey($key, $hooks);
        $this->assertSame([Plugin::class, 'getSettings'], $hooks[$key]);
    }

    /**
     * getHooks must contain exactly three entries.
     */
    public function testGetHooksCount(): void
    {
        $this->assertCount(3, Plugin::getHooks());
    }

    /**
     * Every value in getHooks must be a valid callable array [class, method].
     */
    public function testGetHooksValuesAreCallableArrays(): void
    {
        foreach (Plugin::getHooks() as $eventName => $callback) {
            $this->assertIsArray($callback, "Hook '$eventName' must be an array callback");
            $this->assertCount(2, $callback, "Hook '$eventName' callback must have two elements");
            $this->assertSame(Plugin::class, $callback[0]);
            $this->assertTrue(
                $this->ref->hasMethod($callback[1]),
                "Method '{$callback[1]}' referenced in hook '$eventName' must exist"
            );
        }
    }

    /**
     * All hook callback methods must be public static.
     */
    public function testHookCallbackMethodsArePublicStatic(): void
    {
        foreach (Plugin::getHooks() as $eventName => $callback) {
            $method = $this->ref->getMethod($callback[1]);
            $this->assertTrue($method->isPublic(), "Hook method '{$callback[1]}' must be public");
            $this->assertTrue($method->isStatic(), "Hook method '{$callback[1]}' must be static");
        }
    }

    // ------------------------------------------------------------------
    //  Method signatures
    // ------------------------------------------------------------------

    /**
     * getRequirements must accept exactly one parameter typed as GenericEvent.
     */
    public function testGetRequirementsSignature(): void
    {
        $method = $this->ref->getMethod('getRequirements');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());
        $this->assertNotNull($params[0]->getType());
        $this->assertSame(GenericEvent::class, $params[0]->getType()->getName());
    }

    /**
     * getAddon must accept exactly one parameter typed as GenericEvent.
     */
    public function testGetAddonSignature(): void
    {
        $method = $this->ref->getMethod('getAddon');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());
        $this->assertNotNull($params[0]->getType());
        $this->assertSame(GenericEvent::class, $params[0]->getType()->getName());
    }

    /**
     * getSettings must accept exactly one parameter typed as GenericEvent.
     */
    public function testGetSettingsSignature(): void
    {
        $method = $this->ref->getMethod('getSettings');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());
        $this->assertNotNull($params[0]->getType());
        $this->assertSame(GenericEvent::class, $params[0]->getType()->getName());
    }

    /**
     * doEnable must accept three parameters with proper types and defaults.
     */
    public function testDoEnableSignature(): void
    {
        $method = $this->ref->getMethod('doEnable');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
        $params = $method->getParameters();
        $this->assertCount(3, $params);

        // First param: ServiceHandler (not type-hinted to a namespaced class we control)
        $this->assertSame('serviceOrder', $params[0]->getName());

        // Second param: $repeatInvoiceId (no type hint)
        $this->assertSame('repeatInvoiceId', $params[1]->getName());
        $this->assertFalse($params[1]->isOptional());

        // Third param: $regexMatch = false
        $this->assertSame('regexMatch', $params[2]->getName());
        $this->assertTrue($params[2]->isOptional());
        $this->assertFalse($params[2]->getDefaultValue());
    }

    /**
     * doDisable must accept three parameters with proper types and defaults.
     */
    public function testDoDisableSignature(): void
    {
        $method = $this->ref->getMethod('doDisable');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
        $params = $method->getParameters();
        $this->assertCount(3, $params);

        $this->assertSame('serviceOrder', $params[0]->getName());
        $this->assertSame('repeatInvoiceId', $params[1]->getName());
        $this->assertSame('regexMatch', $params[2]->getName());
        $this->assertTrue($params[2]->isOptional());
        $this->assertFalse($params[2]->getDefaultValue());
    }

    // ------------------------------------------------------------------
    //  Method inventory
    // ------------------------------------------------------------------

    /**
     * The Plugin class must declare exactly these public methods.
     */
    public function testExpectedPublicMethods(): void
    {
        $expected = [
            '__construct',
            'getHooks',
            'getRequirements',
            'getAddon',
            'getSettings',
            'doEnable',
            'doDisable',
        ];

        $actual = array_map(
            static fn(ReflectionMethod $m) => $m->getName(),
            $this->ref->getMethods(ReflectionMethod::IS_PUBLIC)
        );

        sort($expected);
        sort($actual);

        $this->assertSame($expected, $actual);
    }

    /**
     * There must be no protected or private methods.
     */
    public function testNoNonPublicMethods(): void
    {
        $nonPublic = $this->ref->getMethods(
            ReflectionMethod::IS_PROTECTED | ReflectionMethod::IS_PRIVATE
        );
        $this->assertCount(0, $nonPublic);
    }

    // ------------------------------------------------------------------
    //  Static analysis of source file contents
    // ------------------------------------------------------------------

    /**
     * The Plugin source file must use the correct namespace declaration.
     */
    public function testSourceFileNamespace(): void
    {
        $source = file_get_contents((new ReflectionClass(Plugin::class))->getFileName());
        $this->assertStringContainsString('namespace Detain\\MyAdminVpsDirectadmin;', $source);
    }

    /**
     * The Plugin source file must import GenericEvent.
     */
    public function testSourceFileImportsGenericEvent(): void
    {
        $source = file_get_contents((new ReflectionClass(Plugin::class))->getFileName());
        $this->assertStringContainsString('use Symfony\\Component\\EventDispatcher\\GenericEvent;', $source);
    }

    /**
     * getRequirements must reference vps_add_directadmin page requirement.
     */
    public function testGetRequirementsReferencesPageRequirement(): void
    {
        $source = file_get_contents((new ReflectionClass(Plugin::class))->getFileName());
        $this->assertStringContainsString('vps_add_directadmin', $source);
        $this->assertStringContainsString('add_page_requirement', $source);
    }

    /**
     * doEnable must call activate_directadmin.
     */
    public function testDoEnableCallsActivation(): void
    {
        $source = file_get_contents((new ReflectionClass(Plugin::class))->getFileName());
        $this->assertStringContainsString('activate_directadmin', $source);
    }

    /**
     * doDisable must call deactivate_directadmin.
     */
    public function testDoDisableCallsDeactivation(): void
    {
        $source = file_get_contents((new ReflectionClass(Plugin::class))->getFileName());
        $this->assertStringContainsString('deactivate_directadmin', $source);
    }

    /**
     * doDisable must send admin mail notification.
     */
    public function testDoDisableSendsAdminMail(): void
    {
        $source = file_get_contents((new ReflectionClass(Plugin::class))->getFileName());
        $this->assertStringContainsString('adminMail', $source);
        $this->assertStringContainsString('vps_da_canceled.tpl', $source);
    }

    /**
     * doEnable must reference license.functions.inc.php.
     */
    public function testDoEnableRequiresLicenseFunctions(): void
    {
        $source = file_get_contents((new ReflectionClass(Plugin::class))->getFileName());
        $this->assertStringContainsString('license.functions.inc.php', $source);
    }

    /**
     * doEnable must call myadmin_log for audit logging.
     */
    public function testDoEnableCallsLogging(): void
    {
        $source = file_get_contents((new ReflectionClass(Plugin::class))->getFileName());
        $this->assertStringContainsString('myadmin_log', $source);
    }

    /**
     * getSettings must register a text setting for VPS_DA_COST.
     */
    public function testGetSettingsRegistersVpsDaCost(): void
    {
        $source = file_get_contents((new ReflectionClass(Plugin::class))->getFileName());
        $this->assertStringContainsString('add_text_setting', $source);
        $this->assertStringContainsString('VPS_DA_COST', $source);
        $this->assertStringContainsString('vps_da_cost', $source);
    }

    /**
     * getAddon source must set require_ip to true.
     */
    public function testGetAddonRequiresIp(): void
    {
        $source = file_get_contents((new ReflectionClass(Plugin::class))->getFileName());
        $this->assertStringContainsString('set_require_ip(true)', $source);
    }

    /**
     * getAddon source must register enable and disable callbacks.
     */
    public function testGetAddonRegistersCallbacks(): void
    {
        $source = file_get_contents((new ReflectionClass(Plugin::class))->getFileName());
        $this->assertStringContainsString("setEnable([__CLASS__, 'doEnable'])", $source);
        $this->assertStringContainsString("setDisable([__CLASS__, 'doDisable'])", $source);
    }

    /**
     * getAddon source must set cost to VPS_DA_COST constant.
     */
    public function testGetAddonSetsCost(): void
    {
        $source = file_get_contents((new ReflectionClass(Plugin::class))->getFileName());
        $this->assertStringContainsString('VPS_DA_COST', $source);
    }

    // ------------------------------------------------------------------
    //  Hook key format
    // ------------------------------------------------------------------

    /**
     * All hook keys must be non-empty strings containing a dot separator.
     */
    public function testHookKeysFormat(): void
    {
        foreach (array_keys(Plugin::getHooks()) as $key) {
            $this->assertIsString($key);
            $this->assertNotEmpty($key);
            $this->assertStringContainsString('.', $key);
        }
    }

    /**
     * Module-prefixed hook keys must start with the module name.
     */
    public function testModulePrefixedHookKeys(): void
    {
        $hooks = Plugin::getHooks();
        unset($hooks['function.requirements']);

        foreach (array_keys($hooks) as $key) {
            $this->assertStringStartsWith(Plugin::$module . '.', $key);
        }
    }
}
