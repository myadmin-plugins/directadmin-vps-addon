<?php

namespace Detain\MyAdminVpsDirectadmin;

use Symfony\Component\EventDispatcher\GenericEvent;

class Plugin {

	public function __construct() {
	}

	public static function Load(GenericEvent $event) {
		$service = $event->getSubject();
		function_requirements('class.Addon');
		$addon = new \Addon();
		$addon->set_module('vps')->set_text('DirectAdmin')->set_cost(VPS_DA_COST)
			->set_require_ip(true)->set_enable(function() {
				$service_info = $service_order->get_service_info();
				$settings = get_module_settings($service_order->get_module());
				require_once 'include/licenses/license.functions.inc.php';
				$pass = vps_get_password($service_info[$settings['PREFIX'].'_id']);
				function_requirements('directadmin_get_best_type');
				function_requirements('activate_directadmin');
				$ostype = directadmin_get_best_type($module, $service_info[$settings['PREFIX'] . '_type'], $service_info, $service_extra);
				$result = activate_directadmin($service_info[$settings['PREFIX'].'_ip'], $ostype, $pass, $GLOBALS['tf']->accounts->cross_reference($service_info[$settings['PREFIX'] . '_custid']), $module . $service_info[$settings['PREFIX'].'_id']);
			})->set_disable(function() {
			})->register();
		$service->add_addon($addon);
	}

	public static function Settings(GenericEvent $event) {
		$module = 'vps';
		$settings = $event->getSubject();
		$settings->add_text_setting($module, 'Addon Costs', 'vps_da_cost', 'VPS DirectAdmin License:', 'This is the cost for purchasing a direct admin license on top of a VPS.', $settings->get_setting('VPS_DA_COST'));
	}
}
