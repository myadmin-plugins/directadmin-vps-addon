<?php
/* TODO:
 - service type, category, and services  adding
 - dealing with the SERVICE_TYPES_directadmin define
 - add way to call/hook into install/uninstall
*/
return [
	'name' => 'Directadmin Licensing VPS Addon',
	'description' => 'Allows selling of Directadmin Server and VPS License Types.  More info at https://www.netenberg.com/directadmin.php',
	'help' => 'It provides more than one million end users the ability to quickly install dozens of the leading open source content management systems into their web space.  	Must have a pre-existing cPanel license with cPanelDirect to purchase a directadmin license. Allow 10 minutes for activation.',
	'module' => 'vps',
	'author' => 'detain@interserver.net',
	'home' => 'https://github.com/detain/myadmin-directadmin-vps-addon',
	'repo' => 'https://github.com/detain/myadmin-directadmin-vps-addon',
	'version' => '1.0.0',
	'type' => 'addon',
	'hooks' => [
		'vps.load_addons' => ['Detain\MyAdminVpsDirectadmin', 'Load'],
		/* 'function.requirements' => ['Detain\MyAdminVpsDirectadmin\Plugin', 'Requirements'],
		'licenses.settings' => ['Detain\MyAdminVpsDirectadmin\Plugin', 'Settings'],
		'licenses.activate' => ['Detain\MyAdminVpsDirectadmin\Plugin', 'Activate'],
		'licenses.change_ip' => ['Detain\MyAdminVpsDirectadmin\Plugin', 'ChangeIp'],
		'ui.menu' => ['Detain\MyAdminVpsDirectadmin\Plugin', 'Menu'] */
	],
];
