<?php
/**
 * VPS Functionality
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2017
 * @package MyAdmin
 * @category VPS
 */

/**
 * Adds DirectAdmin to a VPS
 * @return void
 */
function vps_add_directadmin() {
	function_requirements('class.AddServiceAddon');
	$addon = new AddServiceAddon();
	$addon->load(__FUNCTION__, 'DirectAdmin', 'vps', VPS_DA_COST, 'da');
	$addon->process();
}
