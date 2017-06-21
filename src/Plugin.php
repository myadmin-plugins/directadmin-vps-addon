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
			'vps.load_addons' => [__CLASS__, 'Load'],
			'vps.settings' => [__CLASS__, 'getSettings'],
		];
	}

	public static function Load(GenericEvent $event) {
		$service = $event->getSubject();
		function_requirements('class.Addon');
		$addon = new \Addon();
		$addon->set_module('vps')
			->set_text('DirectAdmin')
			->set_cost(VPS_DA_COST)
			->set_require_ip(TRUE)
			->set_enable([__CLASS__, 'Enable'])
			->set_disable([__CLASS__, 'Disable'])
			->register();
		$service->add_addon($addon);
	}

	public static function Enable(\Service_Order $serviceOrder) {
		$serviceInfo = $serviceOrder->getServiceInfo();
		$settings = get_module_settings($serviceOrder->get_module());
		require_once 'include/licenses/license.functions.inc.php';
		$pass = vps_get_password($serviceInfo[$settings['PREFIX'].'_id']);
		function_requirements('directadmin_get_best_type');
		function_requirements('activate_directadmin');
		$ostype = directadmin_get_best_type($module, $serviceInfo[$settings['PREFIX'].'_type'], $serviceInfo, $serviceExtra);
		$result = activate_directadmin($serviceInfo[$settings['PREFIX'].'_ip'], $ostype, $pass, $GLOBALS['tf']->accounts->cross_reference($serviceInfo[$settings['PREFIX'].'_custid']), $module.$serviceInfo[$settings['PREFIX'].'_id']);
	}

	public static function Disable(\Service_Order $serviceOrder) {
		$serviceInfo = $serviceOrder->getServiceInfo();
		$settings = get_module_settings($serviceOrder->get_module());
		require_once 'include/licenses/license.functions.inc.php';
		function_requirements('deactivate_directadmin');
		deactivate_directadmin($serviceInfo[$settings['PREFIX'].'_ip']);
	}

	public static function getSettings(GenericEvent $event) {
		$module = 'vps';
		$settings = $event->getSubject();
		$settings->add_text_setting($module, 'Addon Costs', 'vps_da_cost', 'VPS DirectAdmin License:', 'This is the cost for purchasing a direct admin license on top of a VPS.', $settings->get_setting('VPS_DA_COST'));
	}
}
