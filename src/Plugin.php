<?php

namespace Detain\MyAdminVpsDirectadmin;

use Symfony\Component\EventDispatcher\GenericEvent;

class Plugin {

	public static $name = 'DirectAdmin VPS Addon';
	public static $description = 'Allows selling of DirectAdmin Licenses as a VPS Addon.  DirectAdmin is a graphical web-based web hosting control panel designed to make administration of websites easier. DirectAdmin is often called DA for short.  More info at https://www.directadmin.com/';
	public static $help = '';
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
		myadmin_log(self::$module, 'info', self::$name.' Activation', __LINE__, __FILE__);
		$pass = vps_get_password($serviceInfo[$settings['PREFIX'].'_id']);
		function_requirements('directadmin_get_best_type');
		function_requirements('activate_directadmin');
		$serviceExtra = run_event('parse_service_extra', $serviceInfo[$settings['PREFIX'].'_extra'], self::$module);
		$ostype = directadmin_get_best_type(self::$module, $serviceInfo[$settings['PREFIX'].'_type'], $serviceInfo, $serviceExtra);
		activate_directadmin($serviceInfo[$settings['PREFIX'].'_ip'], $ostype, $pass, $GLOBALS['tf']->accounts->cross_reference($serviceInfo[$settings['PREFIX'].'_custid']), self::$module.$serviceInfo[$settings['PREFIX'].'_id']);
	}

	public static function doDisable(\Service_Order $serviceOrder, $repeatInvoiceId, $regexMatch = FALSE) {
		$serviceInfo = $serviceOrder->getServiceInfo();
		$settings = get_module_settings(self::$module);
		require_once __DIR__.'/../../../../include/licenses/license.functions.inc.php';
		myadmin_log(self::$module, 'info', self::$name.' Deactivation', __LINE__, __FILE__);
		function_requirements('deactivate_directadmin');
		deactivate_directadmin($serviceInfo[$settings['PREFIX'].'_ip']);
		$email = $settings['TBLNAME'].' ID: '.$serviceInfo[$settings['PREFIX'].'_id'].'<br>'.$settings['TBLNAME'].' Hostname: '.$serviceInfo[$settings['PREFIX'].'_hostname'].'<br>Repeat Invoice: '.$repeatInvoiceId.'<br>Description: '.self::$name.'<br>';
		$subject = $settings['TBLNAME'].' '.$serviceInfo[$settings['PREFIX'].'_id'].' Canceled '.self::$name;
		$headers = '';
		$headers .= 'MIME-Version: 1.0'.EMAIL_NEWLINE;
		$headers .= 'Content-type: text/html; charset=UTF-8'.EMAIL_NEWLINE;
		$headers .= 'From: '.$settings['TITLE'].' <'.$settings['EMAIL_FROM'].'>'.EMAIL_NEWLINE;
		admin_mail($subject, $email, $headers, FALSE, 'admin_email_vps_da_canceled.tpl');
	}

	public static function getSettings(GenericEvent $event) {
		$settings = $event->getSubject();
		$settings->add_text_setting(self::$module, 'Addon Costs', 'vps_da_cost', 'VPS DirectAdmin License:', 'This is the cost for purchasing a direct admin license on top of a VPS.', $settings->get_setting('VPS_DA_COST'));
	}
}
