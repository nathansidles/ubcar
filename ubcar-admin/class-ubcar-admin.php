<?php

	/**
	 * The UBCAR_Admin superclass
	 *
	 * This file defines the UBCAR_Admin superclass and requires its subclasses,
	 * allowing users to administer the UBCAR backend.
	 *
	 * It is also directly responsible for managing some UBCAR options. There
	 * are four UBCAR options:
	 *
	 * - ubcar_css_choice: choice of CSS, two-column or pseudo-full-screen
	 *	 ( see class-ubcar-view-map.php )
	 * - ubcar_google_maps_api_key: Google Maps API key for Maps dispay
	 * - ubcar_app_title: display title for an UBCAR-associated app
	 * - ubcar_app_introduction: introductory text for an UBCAR-associated app
	 *
	 * It also defines four types of WordPress posts:
	 *
	 * - ubcar_layer
	 * - ubcar_point
	 * - ubcar_medium
	 * - ubcar_tour
	 *
	 * Each of these types is controlled by its own UBCAR_Admin subclass. These
	 * types interact in the UBCAR_View and UBCAR_Data classes.
	 *
	 * UBCAR_Admin depends on jQuery and ( optionally ) Google Maps.
	 *
	 * @package UBCAR
	 */

	/**
	 * Requires the Admin Layer, Point, Tour, and Medium subclasses.
	 */
	require_once( plugin_dir_path( __FILE__ ).'class-ubcar-admin-layer.php' );
	require_once( plugin_dir_path( __FILE__ ).'class-ubcar-admin-point.php' );
	require_once( plugin_dir_path( __FILE__ ).'class-ubcar-admin-tour.php' );
	require_once( plugin_dir_path( __FILE__ ).'class-ubcar-admin-medium.php' );

	/**
	 * The UBCAR_Admin superclass.
	 */
	class UBCAR_Admin {

		var $ubcar_admin_layers;
		var $ubcar_admin_media;
		var $ubcar_admin_points;
		var $ubcar_admin_tours;

		/**
		 * The UBCAR_Admin constructor.
		 *
		 * @access public
		 * @return void
		 */
		public function __construct() {
			$this->ubcar_admin_layers = new UBCAR_Admin_Layer();
			$this->ubcar_admin_media = new UBCAR_Admin_Medium();
			$this->ubcar_admin_points = new UBCAR_Admin_Point();
			$this->ubcar_admin_tours = new UBCAR_Admin_Tour();
			$this->add_actions();
		}

		/**
		 * This function adds the UBCAR_Admin actions, including its AJAX
		 * callback hook.
		 *
		 * @access public
		 * @return void
		 */
		function add_actions() {
			add_action( 'admin_init', array( $this, 'menu_init' ) );
			add_action( 'admin_menu', array( $this, 'menu_page' ) );
			add_action( 'init', array( $this, 'register_post_types' ) );
			add_action( 'wp_ajax_options_updater', array( $this, 'ubcar_options_updater_callback' ) );
		}

		/**
		 *
		 * This function registers a UI script and registers and enqueues the
		 * Dashboard style.
		 *
		 * @access public
		 * @return void
		 */
		function menu_init() {
			wp_register_script( 'ubcar_control_panel_script', plugins_url( 'js/ubcar-control-panel.js', dirname( __FILE__ ) ) );
			wp_register_style( 'ubcar_control_panel_style', plugins_url( '/css/ubcar-admin-style.css', dirname( __FILE__ ) ) );
			wp_enqueue_style( 'ubcar_control_panel_style' );
		}

		/**
		 * This function registers the new UBCAR post types.
		 *
		 * @access public
		 * @return void
		 */
		function register_post_types() {
			register_post_type( 'ubcar_layer' );
			register_post_type( 'ubcar_media' );
			register_post_type( 'ubcar_tour' );
			register_post_type( 'ubcar_point' );
		}

		/**
		 * This function creates the UBCAR menu pages and helps determine who
		 * can access them.
		 *
		 * @access public
		 * @return void
		 */
		function menu_page() {
			if ( !current_user_can( 'edit_pages' ) ) {
				add_menu_page( 'UBCAR', 'UBCAR', 'read', 'ubcar-media', array( $this->ubcar_admin_media, 'menu_initializer' ) );
				if( get_option( 'ubcar_point_choice' ) == '1' ) {
					add_submenu_page( 'ubcar-media', 'UBCAR Points', 'Points', 'read', 'ubcar-points', array( $this->ubcar_admin_points, 'menu_initializer' )  );
					add_submenu_page( 'ubcar-media', 'UBCAR Tours', 'Tours', 'read', 'ubcar-tours', array( $this->ubcar_admin_tours, 'menu_initializer' ) );
				}
			} else {
				add_menu_page( 'UBCAR', 'UBCAR', 'edit_pages', 'ubcar', array( $this, 'menu_initializer' ) );
				add_submenu_page( 'ubcar', 'UBCAR Layers', 'Layers', 'manage_options', 'ubcar-layers', array( $this->ubcar_admin_layers, 'menu_initializer' ) );
				add_submenu_page( 'ubcar', 'ubcar Media', 'Media', 'edit_pages', 'ubcar-media', array( $this->ubcar_admin_media, 'menu_initializer' ) );
				add_submenu_page( 'ubcar', 'UBCAR Points', 'Points', 'edit_pages', 'ubcar-points', array( $this->ubcar_admin_points, 'menu_initializer' )  );
				add_submenu_page( 'ubcar', 'UBCAR Tours', 'Tours', 'edit_pages', 'ubcar-tours', array( $this->ubcar_admin_tours, 'menu_initializer' ) );
			}
		}

		/**
		 * This function initializes the main UBCAR menu page.
		 *
		 * @access public
		 * @return void
		 */

		function menu_initializer() {
			wp_register_script( 'ubcar_control_panel_updater_script', plugins_url( 'js/ubcar-options-updater.js', dirname( __FILE__ ) ) );
			wp_enqueue_script( 'ubcar_control_panel_script', array( 'jquery' ) );
			wp_enqueue_script( 'ubcar_control_panel_updater_script', array( 'jquery', 'ubcar_control_panel_script' ) );
			wp_localize_script( 'ubcar_control_panel_updater_script', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
			?>
				<h2>UBCAR Settings Page</h2>
				<p>This page allows you to determine the settings for the UBCAR plugin. They will help determine what data are displayed in the app.</p>
				<p>If you are seeing this page, you are an administrator. Other administrators can see and modify the information on this page.
				<hr />
				<h3>UBCAR Plugin/Data Settings</h3>
				<table class="form-table">
					<tr>
						<th scope="row"><label for="ubcar_app_google_key">Google Maps API Key</label></th>
						<td>
							<?php
								echo '<input name="ubcar-app-google-key" type="text" id="ubcar-google-maps-api-key" value="' . get_option( 'ubcar_google_maps_api_key' ) . '" class="regular-text ltr" />';
							?>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="ubcar_css_choice">UBCAR CSS Choice</label></th>
						<td>
							<?php
								echo '<input name="ubcar-css-choice" type="radio" id="ubcar-css-choice-1" ';
								if( get_option( 'ubcar_css_choice' ) == 'responsive' || get_option( 'ubcar_css_choice' ) == '' ) {
									echo 'checked ';
								}
								echo '/> One-Column/Two-Column Responsive<br />';
								echo '<input name="ubcar-css-choice" type="radio" id="ubcar-css-choice-2" ';
								if( get_option( 'ubcar_css_choice' ) == 'full' ) {
									echo 'checked ';
								}
								echo '/> Full Page';
							?>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="ubcar_display_choice">UBCAR Data Display Choice</label></th>
						<td>
							<?php
								echo '<input name="ubcar-display-choice" type="radio" id="ubcar-display-choice-1" ';
								if( get_option( 'ubcar_display_choice' ) == 'separate' || get_option( 'ubcar_display_choice' ) == '' ) {
									echo 'checked ';
								}
								echo '/> Separate Media/Point Information Panes<br />';
								echo '<input name="ubcar-display-choice" type="radio" id="ubcar-display-choice-2" ';
								if( get_option( 'ubcar_display_choice' ) == 'unified' ) {
									echo 'checked ';
								}
								echo '/> Unified Data Pane';
							?>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="ubcar_point_choice">UBCAR Subscriber Point Creation/Editing</label></th>
						<td>
							<?php
								echo '<input name="ubcar-point-choice" type="radio" id="ubcar-point-choice-1" ';
								if( get_option( 'ubcar_point_choice' ) == '0' || get_option( 'ubcar_point_choice' ) == '' ) {
									echo 'checked ';
								}
								echo '/> Not Allowed<br />';
								echo '<input name="ubcar-point-choice" type="radio" id="ubcar-point-choice-2" ';
								if( get_option( 'ubcar_point_choice' ) == '1' ) {
									echo 'checked ';
								}
								echo '/> Allowed';
							?>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="ubcar_tour_choice">UBCAR Subscriber Tour Creation/Editing</label></th>
						<td>
							<?php
								echo '<input name="ubcar-tour-choice" type="radio" id="ubcar-tour-choice-1" ';
								if( get_option( 'ubcar_tour_choice' ) == '0' || get_option( 'ubcar_tour_choice' ) == '' ) {
									echo 'checked ';
								}
								echo '/> Not Allowed<br />';
								echo '<input name="ubcar-tour-choice" type="radio" id="ubcar-tour-choice-2" ';
								if( get_option( 'ubcar_tour_choice' ) == '1' ) {
									echo 'checked ';
								}
								echo '/> Allowed';
							?>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="ubcar_control_display_choice">Tour/Layer Selection for Tours and Layers View</label></th>
						<td>
							<?php
								echo '<input name="ubcar-control-display-choice" type="radio" id="ubcar-control-display-choice-1" ';
								if( get_option( 'ubcar_control_display_choice' ) == '0' || get_option( 'ubcar_control_display_choice' ) == '' ) {
									echo 'checked ';
								}
								echo '/> Visible<br />';
								echo '<input name="ubcar-control-display-choice" type="radio" id="ubcar-control-display-choice-2" ';
								if( get_option( 'ubcar_control_display_choice' ) == '1' ) {
									echo 'checked ';
								}
								echo '/> Invisible';
							?>
						</td>
					</tr>
				</table>
				<div class="button button-primary" id="ubcar-options-submit">Submit</div>
			<?php
		}

		/**
		 * This is the callback function for ubcar-options-updater.js's AJAX
		 * requests, updating the UBCAR options.
		 *
		 * @access public
		 * @return void
		 */
		function ubcar_options_updater_callback() {
			if( current_user_can( 'edit_pages' ) ) {
				update_option( 'ubcar_css_choice', esc_attr( $_POST['ubcar_css_choice'] ) );
				update_option( 'ubcar_display_choice', esc_attr( $_POST['ubcar_display_choice'] ) );
				update_option( 'ubcar_point_choice', esc_attr( $_POST['ubcar_point_choice'] ) );
				update_option( 'ubcar_tour_choice', esc_attr( $_POST['ubcar_tour_choice'] ) );
				update_option( 'ubcar_control_display_choice', esc_attr( $_POST['ubcar_control_display_choice'] ) );
				update_option( 'ubcar_google_maps_api_key', esc_attr( $_POST['ubcar_google_maps_api_key'] ) );
				echo 'UBCAR options updated!';
			} else {
				echo 'You do not have privileges to update these options.';
			}
			die();
		}

	}

?>
