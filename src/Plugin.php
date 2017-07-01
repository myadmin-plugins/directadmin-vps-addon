<?php

namespace Detain\MyAdminVpsDirectadmin;

use Symfony\Component\EventDispatcher\GenericEvent;

class Plugin {

	public static $name = 'Directadmin Licensing VPS Addon';
	public static $description = 'Allows selling of Directadmin Server and VPS License Types.  More info at https://www.netenberg.com/directadmin.php';
	public static $help = 'It provides more than one million end users the ability to quickly install dozens of the leading open source content management systems into their web space.  	Must have a pre-existing cPanel license with cPanelDirect to purchase a directadmin license. Allow 10 minutes for activation.';
	public static $module = 'vps';
	public static $type = 'addon';


	public function __construct() {
	}

	public static function getHooks() {
		return [
			self::$module.'.load_addons' => [__CLASS__, 'getAddon'],
			self::$module.'.settings' => [__CLASS__, 'getSettings'],
		];
	}

	public static function getAddon(GenericEvent $event) {
		$service = $event->getSubject();
		function_requirements('class.Addon');
		$addon = new \Addon();
		$addon->setModule(self::$module)
			->set_text('DirectAdmin')
			->set_cost(VPS_DA_COST)
			->set_require_ip(TRUE)
			->set_enable([__CLASS__, 'doEnable'])
			->set_disable([__CLASS__, 'doDisable'])
			->register();
		$service->addAddon($addon);
	}

	public static function doEnable(\Service_Order $serviceOrder, $repeatInvoiceId, $regexMatch = FALSE) {
		$serviceInfo = $serviceOrder->getServiceInfo();
		$settings = get_module_settings(self::$module);
		require_once __DIR__.'/../../../../include/licenses/license.functions.inc.php';
		$pass = vps_get_password($serviceInfo[$settings['PREFIX'].'_id']);
		function_requirements('directadmin_get_best_type');
		function_requirements('activate_directadmin');
		$serviceExtra = run_event('parse_service_extra', $serviceInfo[$settings['PREFIX'].'_extra'], self::$module);
		$ostype = directadmin_get_best_type(self::$module, $serviceInfo[$settings['PREFIX'].'_type'], $serviceInfo, $serviceExtra);
		$result = activate_directadmin($serviceInfo[$settings['PREFIX'].'_ip'], $ostype, $pass, $GLOBALS['tf']->accounts->cross_reference($serviceInfo[$settings['PREFIX'].'_custid']), self::$module.$serviceInfo[$settings['PREFIX'].'_id']);
	}

	public static function doDisable(\Service_Order $serviceOrder) {
		$serviceInfo = $serviceOrder->getServiceInfo();
		$settings = get_module_settings(self::$module);
		require_once __DIR__.'/../../../../include/licenses/license.functions.inc.php';
		function_requirements('deactivate_directadmin');
		deactivate_directadmin($serviceInfo[$settings['PREFIX'].'_ip']);
	}

	public static function getSettings(GenericEvent $event) {
		$settings = $event->getSubject();
		$settings->add_text_setting(self::$module, 'Addon Costs', 'vps_da_cost', 'VPS DirectAdmin License:', 'This is the cost for purchasing a direct admin license on top of a VPS.', $settings->get_setting('VPS_DA_COST'));
	}
}
