<?php

/*
 * Plugin Name: UBC Augmented Reality (UBCAR)
 * Plugin URI: http://sidl.es/
 *
 * Description: UBCAR allows users to upload locations, tours, media, and layers. These can be displayed geographically with the [ubcar-map] shortcode. Any logged-in user is allowed to upload media and comment on existing points. The UBCAR API can also retrieve the plugin's data.
 *
 * Version: 1.0.0
 * Author: Nathan Sidles, Eduardo Jovel
 * Author URI: http://sidl.es/
 * LICENSE: GPL2
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

	if ( !defined( 'ABSPATH' ) ) {
		exit;
	}

	/**
	 * Requires the UBCAR_Admin, UBCAR_View, and UBCAR_Data superclasses.
	 */
	require_once( plugin_dir_path( __FILE__ ).'ubcar-admin/class-ubcar-admin.php' );
	require_once( plugin_dir_path( __FILE__ ).'ubcar-view/class-ubcar-view.php' );
	require_once( plugin_dir_path( __FILE__ ).'ubcar-data/class-ubcar-data.php' );

	new UBCAR_Admin();
	new UBCAR_View();
	new UBCAR_Data();

?>
