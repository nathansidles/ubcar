<?php
	/**
	 * The UBCAR_Admin_Tour subclass
	 *
	 * This file defines the UBCAR_Admin_Tour subclass. The UBCAR_Admin_Tour
	 * class manages ubcar_tour-type posts. ubcar_tour-type posts have one extra
	 * piece of metadata:
	 *
	 * - ubcar_tour_locations: an array of ubcar_point-type post IDs associated
	 *	 with this tour. Their order determines the order of the tour.
	 *
	 * @package UBCAR
	 */

	/*
	 * The UBCAR_Admin_Tour subclass
	 */
	class UBCAR_Admin_Tour extends UBCAR_Admin {

		/**
		 * The UBCAR_Admin_Tour constructor.
		 *
		 * @access public
		 * @return void
		 */
		public function __construct() {
			$this->add_actions();
		}

		/**
		 * This function adds the UBCAR_Admin_Tour actions, including its AJAX
		 * callback hooks.
		 *
		 * @access public
		 * @return void
		 */
		function add_actions() {
			add_action( 'wp_ajax_tour_updater', array( $this, 'ubcar_tour_updater_callback' ) );
			add_action( 'wp_ajax_tour_initial', array( $this, 'ubcar_tour_initial' ) );
			add_action( 'wp_ajax_tour_forward', array( $this, 'ubcar_tour_forward' ) );
			add_action( 'wp_ajax_tour_backward', array( $this, 'ubcar_tour_backward' ) );
			add_action( 'wp_ajax_tour_delete', array( $this, 'ubcar_tour_delete' ) );
			add_action( 'wp_ajax_tour_goto', array( $this, 'ubcar_tour_goto' ) );
			add_action( 'wp_ajax_tour_edit', array( $this, 'ubcar_tour_edit' ) );
			add_action( 'wp_ajax_tour_edit_submit', array( $this, 'ubcar_tour_edit_submit' ) );
		}

		/**
		 * This function initializes the main UBCAR Tour menu page.
		 *
		 * @access public
		 * @return void
		 */
		function menu_initializer() {
			wp_register_script( 'ubcar_control_panel_tour_updater_script', plugins_url( 'js/ubcar-tour-updater.js', dirname( __FILE__ ) ) );
			wp_enqueue_script( 'ubcar_control_panel_script', array( 'jquery' ) );
			wp_enqueue_script( 'ubcar_control_panel_tour_updater_script', array( 'jquery', 'ubcar_control_panel_script' ) );
			wp_localize_script( 'ubcar_control_panel_tour_updater_script', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
			wp_enqueue_script( 'jquery-ui-sortable', array( 'jquery' ) );
			$ubcar_locations = get_posts( array( 'posts_per_page' => -1, 'order' => 'ASC', 'post_type' => 'ubcar_point' ) );
			?>
			<h2>UBCAR Tour Page</h2>
			<p></p>
			<hr />
			<h3 id="ubcar-add-new-toggle">Add New Tour<span class="ubcar-menu-toggle" id="ubcar-add-toggle-arrow">&#9660</span></h3>

			<form method="POST" action="" style="width: 100%;" id="ubcar-add-new-form">
				<?php
					wp_nonce_field( 'ubcar_nonce_check','ubcar-nonce-field' );
				?>
				<table class="form-table">
					<tr>
						<th scope="row"><label for="ubcar-tour-title">Tour Title</label></th>
						<td><input name="ubcar-tour-title" type="text" id="ubcar-tour-title" value="" class="regular-text ltr" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="ubcar-tour-description">Tour Description Text</label></th>
						<td>
							<textarea name="ubcar-tour-description" rows="5" type="textfield" id="ubcar-tour-description" value="" class="regular-text ltr" /></textarea>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="ubcar-tour-locations">Tour Locations</label></th>
						<td>
							<div class="ubcar-tour-locations">
								<h4>Available Points</h4>
								<ul id="ubcar-tour-locations-complete-list" class="ubcar-tour-order-locations">
								<?php
									foreach ( $ubcar_locations as $ubcar_location ) {
										echo '<li>' . $ubcar_location->post_title . ' ( #' . $ubcar_location->ID . ' )<input type="hidden" value="' . $ubcar_location->ID . '"></li>';
									}
								?>
								</ul>
							</div>
							<div class="ubcar-tour-locations">
								<h4>Selected Points</h4>
								<ul id="ubcar-tour-locations-selected-list" class="ubcar-tour-order-locations">
								</ul>
							</div>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<div class="button button-primary" name="ubcar-tour-submit" id="ubcar-tour-submit">Upload Tour</div>
						</th>
					</tr>
				</table>
			</form>
			<hr />
			<h3>Manage Existing tours</h3>
			<div>
				<table class="ubcar-table" id="ubcar-tour-table">
				</table>
				<div class="ubcar-goto">
					<input type="text" id="ubcar-tour-choose-count" style="width:40px;">
					<input type="submit" id="ubcar-tour-goto" value="Go to Page">
				</div>
				<div class="ubcar-forward-back">
					<a class="ubcar-forward-back-control" id="ubcar-tour-back">Prev</a>
					<span id="ubcar-tour-display-count">1</span> of <span id="ubcar-tour-max-count"><?php echo max( ( ceil( wp_count_posts('ubcar_tour')->publish / 25 ) ), 1 ); ?></span>
					<a class="ubcar-forward-back-control" id="ubcar-tour-forward">Next</a>
				</div>
			</div>
			<?php
		}

		/**
		 * This is the callback function for ubcar-tour-updater.js's
		 * update_tours() AJAX request, adding a new tour post.
		 *
		 * @access public
		 * @global object $wpdb
		 * @return void
		 */
		function ubcar_tour_updater_callback() {
			global $wpdb;
			if ( !isset( $_POST['ubcar_nonce_field'] ) || !wp_verify_nonce( $_POST['ubcar_nonce_field'],'ubcar_nonce_check' ) ) {
				echo 'Sorry, WordPress has rejected your submission - specifically, your nonce did not verify. Please reload the form page and try again. This message may occur if you took more than a day to complete your form, if you do not have the appropriate privileges to submit data points but nonetheless try, or if the ubcar coding team made an error.';
			} else {
				$ubcar_tour_post = array(
					'post_title' => sanitize_text_field( $_POST['ubcar_tour_title'] ),
					'post_content' => sanitize_text_field( $_POST['ubcar_tour_description'] ),
					'post_status' => 'publish',
					'post_type' => 'ubcar_tour'
				);

				$ubcar_tour_id = wp_insert_post( $ubcar_tour_post );
				if( isset( $_POST['ubcar_tour_locations'] ) ) {
					$ubcar_tour_locations = array_map( 'esc_attr', $_POST['ubcar_tour_locations']);
					add_post_meta( $ubcar_tour_id, 'ubcar_tour_locations', $ubcar_tour_locations );
				}
				echo 'Submission uploaded!';
			}

			die();
		}

		/**
		 * This is the helper function for retrieving a set of ubcar_tour data
		 * from the database, converting it to JSON, and echoing it.
		 *
		 * @param int $ubcar_tour_offset
		 *
		 * @access public
		 * @global object $wpdb
		 * @return void
		 */
		function ubcar_tour_get_tours( $ubcar_tour_offset ) {
			global $wpdb;
			$ubcar_get_tours_parameters = array( 'posts_per_page' => 25, 'offset' => $ubcar_tour_offset, 'order' => 'DESC', 'post_type' => 'ubcar_tour' );
			$response = array();
			if ( !current_user_can( 'edit_pages' ) ) {
				$ubcar_current_user = wp_get_current_user();
				$ubcar_get_tours_parameters['author_name'] = $ubcar_current_user->user_login;
			}
			$ubcar_tours = get_posts( $ubcar_get_tours_parameters );
			foreach ( $ubcar_tours as $ubcar_tour ) {
				$temp_array = $this->ubcar_get_tour( $ubcar_tour->ID );
				array_push( $response, $temp_array );
			}
			wp_send_json( $response );
		}

		/**
		 * This is a helper function for retrieving a single ubcar_tour datum
		 * and metadata from the database.
		 *
		 * @param int $tour_ubcar_id
		 *
		 * @access public
		 * @return array
		 */
		function ubcar_get_tour( $tour_ubcar_id ) {
			$ubcar_tour = get_post( $tour_ubcar_id );
			$ubcar_tour_meta = get_post_meta( $ubcar_tour->ID, 'ubcar_tour_locations', true );
			$ubcar_tour_author = get_user_by( 'id', $ubcar_tour->post_author );
			$temp_array = array();
			$temp_array["ID"] = $ubcar_tour->ID;
			$temp_array["uploader"] = $ubcar_tour_author->first_name . ' ' . $ubcar_tour_author->last_name . ' (' . $ubcar_tour_author->user_login . ')';
			$temp_array["title"] = $ubcar_tour->post_title;
			$temp_array["date"] = get_the_date( 'Y-m-d', $ubcar_tour->ID );
			$temp_array["description"] = $ubcar_tour->post_content;
			$ubcar_tour_meta_names = array();
			if( $ubcar_tour_meta != null ) {
				foreach( $ubcar_tour_meta as $ubcar_tour_meta_point ) { // retrieving the tour points' data
					$ubcar_tour_meta_point_title = get_post( $ubcar_tour_meta_point );
					if( $ubcar_tour_meta_point_title != null ) {
						$inner_temp_array = array();
						$inner_temp_array["ID"] = $ubcar_tour_meta_point_title->ID;
						$inner_temp_array["title"] = $ubcar_tour_meta_point_title->post_title;
						array_push( $ubcar_tour_meta_names, $inner_temp_array );
					}
				}
			}
			$temp_array["locations"] = $ubcar_tour_meta_names;
			return $temp_array;
		}

		/**
		 * This is the callback function for ubcar-tour-updater.js's
		 * initial AJAX request, displaying a set of ubcar_tour posts.
		 *
		 * @access public
		 * @return void
		 */
		function ubcar_tour_initial() {
			$this->ubcar_tour_get_tours( 0 );
		}

		/**
		 * This is the callback function for ubcar-point-updater.js's
		 * forward_tours() AJAX request, displaying the next set of
		 * ubcar_tour posts.
		 *
		 * @access public
		 * @return void
		 */
		function ubcar_tour_forward() {
			$this->ubcar_tour_get_tours( intval( $_POST['ubcar_tour_offset'] ) * 25 );
		}

		/**
		 * This is the callback function for ubcar-tour-updater.js's
		 * backward_tours() AJAX request, displaying the previous set of
		 * ubcar_tour posts.
		 *
		 * @access public
		 * @return void
		 */
		function ubcar_tour_backward() {
			$back_tour = ( intval( $_POST['ubcar_tour_offset'] ) - 2 ) * 25;
			if( $back_tour < 0 ) {
				$back_tour = 0;
			}
			$this->ubcar_tour_get_tours( $back_tour );
		}

		/**
		 * This is the callback function for ubcar-tour-updater.js's
		 * goto_tours() AJAX request, displaying the selected set of
		 * ubcar_tour posts.
		 *
		 * @access public
		 * @return void
		 */
		function ubcar_tour_goto() {
			$goto_tour = ( intval( $_POST['ubcar_tour_offset'] ) - 1 ) * 25;
			if( $goto_tour < 0 ) {
				$goto_tour = 0;
			}
			$this->ubcar_tour_get_tours( $goto_tour );
		}


		/**
		 * This is the callback function for ubcar-tour-updater.js's
		 * delete_tours() AJAX request, deleting an ubcar_tour post
		 *
		 * @access public
		 * @global object $wpdb
		 * @return void
		 */
		function ubcar_tour_delete() {
			global $wpdb;
			$delete_post = get_post( $_POST['ubcar_tour_delete_id'] );
			if ( !isset( $_POST['ubcar_nonce_field'] ) || !wp_verify_nonce( $_POST['ubcar_nonce_field'],'ubcar_nonce_check' )  ) {
				echo 0;
			} else {
				if( get_current_user_id() != $delete_post->post_author && !current_user_can( 'edit_pages' ) ) {
					echo 0;
				} else {
					wp_delete_post( $_POST['ubcar_tour_delete_id'] );
					$this->ubcar_tour_get_tours( 0 );
				}
			}
		}

		/**
		 * This is the callback function for ubcar-tour-updater.js's
		 * edit_tours() AJAX request, retrieving a single tour.
		 *
		 * @access public
		 * @global object $wpdb
		 * @return void
		 */
		function ubcar_tour_edit() {
			global $wpdb;
			$edit_post = get_post( $_POST['ubcar_tour_edit_id'] );
			if ( !isset( $_POST['ubcar_nonce_field'] ) || !wp_verify_nonce( $_POST['ubcar_nonce_field'],'ubcar_nonce_check' )  ) {
				echo 0;
			} else {
				if( get_current_user_id() != $edit_post->post_author && !current_user_can( 'edit_pages' ) ) {
					echo 0;
				} else {
					$ubcar_all_points = get_posts( array( 'posts_per_page' => -1, 'order' => 'ASC', 'post_type' => 'ubcar_point' ) );
					$ubcar_all_points_pared = array();
					foreach( $ubcar_all_points as $ubcar_point ) {
						$inner_temp_array = array();
						$inner_temp_array["ID"] = $ubcar_point->ID;
						$inner_temp_array["title"] = $ubcar_point->post_title;
						array_push( $ubcar_all_points_pared, $inner_temp_array );
					}
					$ubcar_tour_to_return = $this->ubcar_get_tour( $edit_post->ID );
					$ubcar_tour_to_return["all_locations"] = $ubcar_all_points_pared;
					wp_send_json( $ubcar_tour_to_return );
				}
			}
		}

		/**
		 * This is the callback function for ubcar-tour-updater.js's
		 * edit_tours_submit() AJAX request, updating the ubcar_tour post.
		 *
		 * @access public
		 * @global object $wpdb
		 * @return void
		 */
		function ubcar_tour_edit_submit() {
			global $wpdb;
			$edit_post = get_post( $_POST['ubcar_tour_edit_id'] );
			if ( !isset( $_POST['ubcar_nonce_field'] ) || !wp_verify_nonce( $_POST['ubcar_nonce_field'],'ubcar_nonce_check' )  ) {
				echo 0;
			} else {
				if( get_current_user_id() != $edit_post->post_author && !current_user_can( 'edit_pages' ) ) {
					echo 0;
				} else {
					$update_array = array(
						'ID' => sanitize_text_field( $_POST['ubcar_tour_edit_id'] ),
						'post_title' => sanitize_text_field( $_POST['ubcar_tour_title'] ),
						'post_content' => sanitize_text_field( $_POST['ubcar_tour_description'] )
					);
					wp_update_post( $update_array );
					if( isset( $_POST['ubcar_tour_locations'] ) ) {
						$ubcar_tour_locations = array_map( 'esc_attr', $_POST['ubcar_tour_locations']);
						update_post_meta( $_POST['ubcar_tour_edit_id'], 'ubcar_tour_locations',  $ubcar_tour_locations );
					}
					wp_send_json( $this->ubcar_get_tour( $_POST['ubcar_tour_edit_id'] ) );
				}
			}
		}

	}

?>
