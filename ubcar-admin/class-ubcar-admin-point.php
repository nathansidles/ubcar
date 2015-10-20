<?php
	/**
	 * The UBCAR_Admin_Point subclass
	 *
	 * This file defines the UBCAR_Admin_Point subclass. The UBCAR_Admin_Point
	 * class manages ubcar_point-type posts. ubcar_point-type posts have four
	 * extra pieces of metadata:
	 *
	 * - ubcar_point_latitude: the latitude of the point
	 * - ubcar_point_longitude: the longitude of the point
	 * - ubcar_point_tags:  text tags of the point, separated by comma
	 * - ubcar_point_media: an array of ubcar_media-type post IDs associated with
	 *	 this point ( updated in the UBCAR_Admin_Medium class )
	 *
	 * @package UBCAR
	 */

	/*
	 * The UBCAR_Admin_Point subclass
	 */
	class UBCAR_Admin_Point extends UBCAR_Admin {

		/**
		 * The UBCAR_Admin_Point constructor.
		 *
		 * @access public
		 * @return void
		 */
		public function __construct() {
			$this->add_actions();
		}

		/**
		 * This function adds the UBCAR_Admin_Point actions,including its AJAX
		 * callback hooks.
		 *
		 * @access public
		 * @return void
		 */
		function add_actions() {
			add_action( 'wp_ajax_point_updater', array( $this, 'ubcar_point_updater_callback' ) );
			add_action( 'wp_ajax_point_initial', array( $this, 'ubcar_point_initial' ) );
			add_action( 'wp_ajax_point_forward', array( $this, 'ubcar_point_forward' ) );
			add_action( 'wp_ajax_point_backward', array( $this, 'ubcar_point_backward' ) );
			add_action( 'wp_ajax_point_delete', array( $this, 'ubcar_point_delete' ) );
			add_action( 'wp_ajax_point_goto', array( $this, 'ubcar_point_goto' ) );
			add_action( 'wp_ajax_point_edit', array( $this, 'ubcar_point_edit' ) );
			add_action( 'wp_ajax_point_edit_submit', array( $this, 'ubcar_point_edit_submit' ) );
		}

		/**
		 * This function initializes the main UBCAR Point menu page.
		 *
		 * @access public
		 * @return void
		 */
		function menu_initializer() {
			wp_enqueue_script( 'ubcar_map_display_google_script', 'https://maps.googleapis.com/maps/api/js?v=3.exp&key=' . get_option( 'ubcar_google_maps_api_key' ) );
			wp_register_script( 'ubcar_control_panel_point_updater_script', plugins_url( 'js/ubcar-point-updater.js', dirname( __FILE__ ) ) );
			wp_enqueue_script( 'ubcar_control_panel_script', array( 'jquery' ) );
			wp_enqueue_script( 'ubcar_control_panel_point_updater_script', array( 'jquery', 'ubcar_control_panel_script' ) );
			wp_localize_script( 'ubcar_control_panel_point_updater_script', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
			?>
			<h2>UBCAR Point Page</h2>
			<p></p>
			<hr />
			<h3 id="ubcar-add-new-toggle">Add New Point<span class="ubcar-menu-toggle" id="ubcar-add-toggle-arrow">&#9660</span></h3>

			<form method="POST" action="" style="width: 100%;" id="ubcar-add-new-form">
				<?php
					wp_nonce_field( 'ubcar_nonce_check','ubcar-nonce-field' );
				?>
				<table class="form-table">
					<tr>
						<th scope="row"><label for="ubcar-point-title">Point Title</label></th>
						<td><input name="ubcar-point-title" type="text" id="ubcar-point-title" value="" class="regular-text ltr" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="ubcar-point-description">Point Description Text</label></th>
						<td>
							<textarea name="ubcar-point-description" rows="5" type="textfield" id="ubcar-point-description" value="" class="regular-text ltr" /></textarea>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="ubcar-point-latitude">Point Latitude</label></th>
						<td><input name="ubcar-point-latitude" type="number" id="ubcar-point-latitude" value="" class="regular-text ltr" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="ubcar-point-longitude">Point Longitude</label></th>
						<td><input name="ubcar-point-longitude" type="number" id="ubcar-point-longitude" value="" class="regular-text ltr" /> <div class="button" id="ubcar-point-latlng-check">Check Location</div></td>
					</tr>
					<tr>
						<th scope="row"><label for="ubcar-point-latlng">Click to select Lat/Long:</label></th>
						<td>
							<div id="ubcar-map-canvas"></div>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="ubcar-point-tags">Tags ( comma-separated )</label></th>
						<td>
							<input name="ubcar-point-tags" type="text" id="ubcar-point-tags" value="" class="regular-text ltr" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<div class="button button-primary" name="ubcar-point-submit" id="ubcar-point-submit">Upload point</div>
						</th>
					</tr>
				</table>
			</form>
			<hr />
			<h3>Manage Existing points</h3>
			<table class="ubcar-table" id="ubcar-point-table">
			</table>
			<div class="ubcar-goto">
				<input type="text" id="ubcar-point-choose-count" style="width:40px;">
				<input type="submit" id="ubcar-point-goto" value="Go to Page">
			</div>
			<div class="ubcar-forward-back">
				<a class="ubcar-forward-back-control" id="ubcar-point-back">Prev</a>
				<span id="ubcar-point-display-count">1</span> of <span id="ubcar-point-max-count"><?php echo max( ( ceil( wp_count_posts('ubcar_point')->publish / 25 ) ), 1 ); ?></span>
				<a class="ubcar-forward-back-control" id="ubcar-point-forward">Next</a>
			</div>
			<?php
		}

		/**
		 * This is the callback function for ubcar-point-updater.js's
		 * update_points() AJAX request, adding a new point post.
		 *
		 * @access public
		 * @global object $wpdb
		 * @return void
		 */
		function ubcar_point_updater_callback() {
			global $wpdb;
			if ( !isset( $_POST['ubcar_nonce_field'] ) || !wp_verify_nonce( $_POST['ubcar_nonce_field'],'ubcar_nonce_check' ) ) {
				echo 'Sorry, WordPress has rejected your submission - specifically, your nonce did not verify. Please reload the form page and try again. This message may occur if you took more than a day to complete your form, if you do not have the appropriate privileges to submit data points but nonetheless try, or if the ubcar coding team made an error.';
			} else {
				$ubcar_point_post = array(
					'post_title' => sanitize_text_field( $_POST['ubcar_point_title'] ),
					'post_content' => sanitize_text_field( $_POST['ubcar_point_description'] ),
					'post_status' => 'publish',
					'post_type' => 'ubcar_point'
				);

				$ubcar_point_id = wp_insert_post( $ubcar_point_post );
				add_post_meta( $ubcar_point_id, 'ubcar_point_latitude', sanitize_text_field( $_POST['ubcar_point_latitude'] ) );
				add_post_meta( $ubcar_point_id, 'ubcar_point_longitude', sanitize_text_field( $_POST['ubcar_point_longitude'] ) );
				add_post_meta( $ubcar_point_id, 'ubcar_point_tags', sanitize_text_field( $_POST['ubcar_point_tags'] ) );
				add_post_meta( $ubcar_point_id, 'ubcar_point_media', array() );
				echo 'Submission uploaded!';
			}
			die();
		}

		/**
		 * This is the helper function for retrieving a set of ubcar_point data
		 * from the database, converting it to JSON, and echoing it.
		 *
		 * @param int $ubcar_point_offset
		 *
		 * @access public
		 * @global object $wpdb
		 * @return void
		 */
		function ubcar_point_get_points( $ubcar_point_offset ) {
			global $wpdb;
			$ubcar_get_points_parameters = array( 'posts_per_page' => 25, 'offset' => $ubcar_point_offset, 'order' => 'DESC', 'post_type' => 'ubcar_point' );
			$response = array();
			if ( !current_user_can( 'edit_pages' ) ) {
				$ubcar_current_user = wp_get_current_user();
				$ubcar_get_points_parameters['author_name'] = $ubcar_current_user->user_login;
			}
			$ubcar_points = get_posts( $ubcar_get_points_parameters );
			foreach ( $ubcar_points as $ubcar_point ) {
				$tempArray = $this->ubcar_get_point( $ubcar_point->ID );
				array_push( $response, $tempArray );
			}
			wp_send_json( $response );
		}

		/**
		 * This is a helper function for retrieving a single ubcar_point datum
		 * and metadata from the database.
		 *
		 * @param int $point_ubcar_id
		 *
		 * @access public
		 * @return array
		 */
		function ubcar_get_point( $point_ubcar_id ) {
			$ubcar_point = get_post( $point_ubcar_id );
			$ubcar_point_meta_latitude = get_post_meta( $ubcar_point->ID, 'ubcar_point_latitude', true );
			$ubcar_point_meta_longitude = get_post_meta( $ubcar_point->ID, 'ubcar_point_longitude', true );
			$ubcar_point_meta_tags = get_post_meta( $ubcar_point->ID, 'ubcar_point_tags', true );
			$ubcar_point_author = get_user_by( 'id', $ubcar_point->post_author );
			$tempArray = array();
			$tempArray["ID"] = $ubcar_point->ID;
			$tempArray["uploader"] = $ubcar_point_author->first_name . ' ' . $ubcar_point_author->last_name . ' (' . $ubcar_point_author->user_login . ')';
			$tempArray["title"] = $ubcar_point->post_title;
			$tempArray["date"] = get_the_date( 'Y-m-d', $ubcar_point->ID );
			$tempArray["description"] = $ubcar_point->post_content;
			$tempArray["latitude"] = $ubcar_point_meta_latitude;
			$tempArray["longitude"] = $ubcar_point_meta_longitude;
			$tempArray["tags"] = $ubcar_point_meta_tags;
			return $tempArray;
		}

		/**
		 * This is the callback function for ubcar-point-updater.js's
		 * initial AJAX request, displaying a set of ubcar_point posts.
		 *
		 * @access public
		 * @return void
		 */
		function ubcar_point_initial() {
			$this->ubcar_point_get_points( 0 );
		}

		/**
		 * This is the callback function for ubcar-point-updater.js's
		 * forward_points() AJAX request, displaying the next set of
		 * ubcar_point posts.
		 *
		 * @access public
		 * @return void
		 */
		function ubcar_point_forward() {
			$this->ubcar_point_get_points( intval( $_POST['ubcar_point_offset'] ) * 25 );
		}

		/**
		 * This is the callback function for ubcar-point-updater.js's
		 * backward_points() AJAX request, displaying the previous set of
		 * ubcar_point posts.
		 *
		 * @access public
		 * @return void
		 */
		function ubcar_point_backward() {
			$back_point = ( intval( $_POST['ubcar_point_offset'] ) - 2 ) * 25;
			if( $back_point < 0 ) {
				$back_point = 0;
			}
			$this->ubcar_point_get_points( $back_point );
		}

		/**
		 * This is the callback function for ubcar-point-updater.js's
		 * goto_points() AJAX request, displaying the selected set of
		 * ubcar_point posts.
		 *
		 * @access public
		 * @return void
		 */
		function ubcar_point_goto() {
			$goto_point = ( intval( $_POST['ubcar_point_offset'] ) - 1 ) * 25;
			if( $goto_point < 0 ) {
				$goto_point = 0;
			}
			$this->ubcar_point_get_points( $goto_point );
		}


		/**
		 * This is the callback function for ubcar-point-updater.js's
		 * delete_points() AJAX request, deleting an ubcar_point post
		 *
		 * @access public
		 * @global object $wpdb
		 * @return void
		 */
		function ubcar_point_delete() {
			global $wpdb;
			$delete_post = get_post( $_POST['ubcar_point_delete_id'] );
			if ( !isset( $_POST['ubcar_nonce_field'] ) || !wp_verify_nonce( $_POST['ubcar_nonce_field'],'ubcar_nonce_check' )  ) {
				echo 0;
			} else {
				if( get_current_user_id() != $delete_post->post_author && !current_user_can( 'edit_pages' ) ) {
					echo 0;
				} else {
					wp_delete_post( $_POST['ubcar_point_delete_id'] );
					$this->ubcar_point_get_points( 0 );
				}
			}
		}

		/**
		 * This is the callback function for ubcar-point-updater.js's
		 * edit_points() AJAX request, retrieving a single layer.
		 *
		 * @access public
		 * @global object $wpdb
		 * @return void
		 */
		function ubcar_point_edit() {
			global $wpdb;
			$edit_post = get_post( $_POST['ubcar_point_edit_id'] );
			if ( !isset( $_POST['ubcar_nonce_field'] ) || !wp_verify_nonce( $_POST['ubcar_nonce_field'],'ubcar_nonce_check' )  ) {
				echo 0;
			} else {
				if( get_current_user_id() != $edit_post->post_author && !current_user_can( 'edit_pages' ) ) {
					echo 0;
				} else {
					wp_send_json( $this->ubcar_get_point( $edit_post->ID ) );
				}
			}
		}

		/**
		 * This is the callback function for ubcar-point-updater.js's
		 * edit_points_submit() AJAX request, updating the ubcar_point post.
		 *
		 * @access public
		 * @global object $wpdb
		 * @return void
		 */
		function ubcar_point_edit_submit() {
			global $wpdb;
			$edit_post = get_post( $_POST['ubcar_point_edit_id'] );
			if ( !isset( $_POST['ubcar_nonce_field'] ) || !wp_verify_nonce( $_POST['ubcar_nonce_field'],'ubcar_nonce_check' )  ) {
				echo 0;
			} else {
				if( get_current_user_id() != $edit_post->post_author && !current_user_can( 'edit_pages' ) ) {
					echo 0;
				} else {
					$update_array = array(
						'ID' => sanitize_text_field( $_POST['ubcar_point_edit_id'] ),
						'post_title' => sanitize_text_field( $_POST['ubcar_point_title'] ),
						'post_content' => sanitize_text_field( $_POST['ubcar_point_description'] )
					);
					wp_update_post( $update_array );
					update_post_meta( $_POST['ubcar_point_edit_id'], 'ubcar_point_latitude', sanitize_text_field( $_POST['ubcar_point_latitude'] ) );
					update_post_meta( $_POST['ubcar_point_edit_id'], 'ubcar_point_longitude', sanitize_text_field( $_POST['ubcar_point_longitude'] ) );
					update_post_meta( $_POST['ubcar_point_edit_id'], 'ubcar_point_tags', sanitize_text_field( $_POST['ubcar_point_tags'] ) );
					wp_send_json( $this->ubcar_get_point( $_POST['ubcar_point_edit_id'] ) );
				}
			}
		}

	}

?>
