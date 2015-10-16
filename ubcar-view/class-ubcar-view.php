<?php

	/**
	 * The UBCAR_View superclass
	 *
	 * This file defines the UBCAR_View superclass and requires its subclasses,
	 * allowing UBCAR data to be viewed on its frontend.
	 *
	 * UBCAR_View depends on Google Maps, jQuery, and (optionally) UBC CTLT's
	 * Wiki-Embed plugin.
	 *
	 * @package UBCAR
	 */

	/**
	 * Requires the View Map subclass.
	 */
	require_once( plugin_dir_path(__FILE__).'class-ubcar-view-map.php' );
    require_once( plugin_dir_path(__FILE__).'class-ubcar-view-point.php' );

	/**
	 * The UBCAR_View superclass.
	 */
	class UBCAR_View {

		var $ubcar_view_map;
        var $ubcar_view_point;

		/**
		 * The UBCAR_View constructor
		 *
		 * @access public
		 * @return void
		 */
		public function __construct() {
			$this->ubcar_view_map = new UBCAR_View_Map();
            $this->ubcar_view_point = new UBCAR_View_Point();
		}

		/**
		 * This function adds the UBCAR_View actions.
		 *
		 * @access public
		 * @return void
		 */
		function add_actions() {
		}

		/**
		 * This function adds the UBCAR_View shortcodes.
		 *
		 * @access public
		 * @return void
		 */
		function add_shortcodes() {
		}

		/**
			 * This is the helper function for cleaning a set of ubcar_medium data
			 * sent by a user.
			 *
			 * @param string $ubcar_string_to_be_cleaned
			 *
			 * @access public
			 * @return string
			 */
			function ubcar_media_data_cleaner( $ubcar_string_to_be_cleaned ) {

			$bad_characters  = array( "&",	 "<",	">",	'"',	  "'",	 "/",	  "\n" );
			$good_characters = array( "&amp;", "&lt;", "&gt;", '&quot;', '&#39;', '&#x2F;', '<br />' );

			return str_replace( $bad_characters, $good_characters, $ubcar_string_to_be_cleaned );
			}

	}

?>
