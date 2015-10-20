<?php
	/**
	 * The UBCAR_Admin_Layer subclass
	 *
	 * This file defines the UBCAR_Admin_Layer subclass. The UBCAR_Admin_Layer
	 * class manages ubcar_layer-type posts. ubcar_layer-type posts have three
	 * extra pieces of metadata:
	 *
	 *  - ubcar_password: determines if a layer is visible to subscribers when
	 *	  adding media ( used in the UBCAR_Admin_Medium class )
	 *  - ubcar_layer_media: an array of ubcar_media-type post IDs associated
	 *	  with this layer ( updated in the UBCAR_Admin_Medium class )
	 *  - ubcar_layer_points: an array of ubcar_point-type post IDs associated
	 *	  with this layer ( updated in the UBCAR_Admin_Medium class )
	 *
	 * @package UBCAR
	 */

	/**
	 * The UBCAR_Admin_Layer subclass
	 */
class UBCAR_Admin_Layer extends UBCAR_Admin {

		/**
		 * The UBCAR_Admin_Layer constructor.
		 *
		 * @access public
		 * @return void
		 */
		public function __construct() {
			$this->add_actions();
		}

		/**
		 * This function adds the UBCAR_Admin_Layer actions,including its AJAX
		 * callback hooks.
		 *
		 * @access public
		 * @return void
		 */
		function add_actions() {
			add_action( 'wp_ajax_layer_updater', array( $this, 'ubcar_layer_updater_callback' ) );
			add_action( 'wp_ajax_layer_initial', array( $this, 'ubcar_layer_initial' ) );
			add_action( 'wp_ajax_layer_forward', array( $this, 'ubcar_layer_forward' ) );
			add_action( 'wp_ajax_layer_backward', array( $this, 'ubcar_layer_backward' ) );
			add_action( 'wp_ajax_layer_goto', array( $this, 'ubcar_layer_goto' ) );
			add_action( 'wp_ajax_layer_delete', array( $this, 'ubcar_layer_delete' ) );
			add_action( 'wp_ajax_layer_edit', array( $this, 'ubcar_layer_edit' ) );
			add_action( 'wp_ajax_layer_edit_submit', array( $this, 'ubcar_layer_edit_submit' ) );
		}

		/**
		 * This function initializes the UBCAR Layer menu page.
		 *
		 * @access public
		 * @return void
		 */
		function menu_initializer() {
			wp_register_script( 'ubcar_control_panel_layer_updater_script', plugins_url( 'js/ubcar-layer-updater.js', dirname( __FILE__ ) ) );
			wp_enqueue_script( 'ubcar_control_panel_script', array( 'jquery' ) );
			wp_enqueue_script( 'ubcar_control_panel_layer_updater_script', array( 'jquery', 'ubcar_control_panel_script' ) );
			wp_localize_script( 'ubcar_control_panel_layer_updater_script', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
			?>
			<h2>UBCAR Layer Page</h2>
			<p></p>
			<hr />
			<h3 id="ubcar-add-new-toggle">Add New layer<span class="ubcar-menu-toggle" id="ubcar-add-toggle-arrow">&#9660</span></h3>

			<form method="POST" action="" style="width: 100%;" id="ubcar-add-new-form">
				<?php
					wp_nonce_field( 'ubcar_nonce_check','ubcar-nonce-field' );
				?>
				<table class="form-table">
					<tr>
						<th scope="row"><label for="ubcar-layer-title">Layer Title</label></th>
						<td><input name="ubcar-layer-title" type="text" id="ubcar-layer-title" value="" class="regular-text ltr" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="ubcar_layer_description">Layer Description Text</label></th>
						<td>
							<textarea name="ubcar-layer-description" rows="5" type="textfield" id="ubcar-layer-description" value="" class="regular-text ltr" /></textarea>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="ubcar-layer-password">Block Subscriber-Level Media Access:</label></th>
						<td>
						<input name="ubcar-layer-password" id="ubcar-layer-password" type="checkbox"></td>
					</tr>
					<tr>
						<th scope="row">
							<div class="button button-primary" name="ubcar-layer-submit" id="ubcar-layer-submit">Upload Layer</div>
						</th>
					</tr>
				</table>
			</form>
			<hr />
			<h3>Manage Existing Layers</h3>
			<table class="ubcar-table" id="ubcar-layer-table">
			</table>
			<div class="ubcar-goto">
				<input type="text" id="ubcar-layer-choose-count" style="width:40px;">
				<input type="submit" id="ubcar-layer-goto" value="Go to Page">
			</div>
			<div class="ubcar-forward-back">
				<a class="ubcar-forward-back-control" id="ubcar-layer-back">Prev</a>
				<span id="ubcar-layer-display-count">1</span> of <span id="ubcar-layer-max-count"><?php echo max( ( ceil( wp_count_posts('ubcar_layer')->publish / 25 ) ), 1 ); ?></span>
				<a class="ubcar-forward-back-control" id="ubcar-layer-forward">Next</a>
			</div>
			<?php
		}

		/**
		 * This is the callback function for ubcar-layer-updater.js's
		 * update_layers() AJAX request, adding a new layer post.
		 *
		 * @access public
		 * @global object $wpdb
		 * @return void
		 */
		function ubcar_layer_updater_callback() {
			global $wpdb;
			if ( !isset( $_POST['ubcar_nonce_field'] ) || !wp_verify_nonce( $_POST['ubcar_nonce_field'],'ubcar_nonce_check' ) ) {
				echo 'Sorry, WordPress has rejected your submission - specifically, your nonce did not verify. Please reload the form page and try again. This message may occur if you took more than a day to complete your form, if you do not have the appropriate privileges to submit data points but nonetheless try, or if the ubcar coding team made an error.';
			} else {
				$ubcar_layer_post = array(
					'post_title' => sanitize_text_field( $_POST['ubcar_layer_title'] ),
					'post_content' => sanitize_text_field( $_POST['ubcar_layer_description'] ),
					'post_status' => 'publish',
					'post_type' => 'ubcar_layer'
				 );

				$ubcar_layer_id = wp_insert_post( $ubcar_layer_post );
				add_post_meta( $ubcar_layer_id, 'ubcar_password', sanitize_text_field( $_POST['ubcar_layer_password'] ) );
				add_post_meta( $ubcar_layer_id, 'ubcar_layer_media', array() );
				add_post_meta( $ubcar_layer_id, 'ubcar_layer_points', array() );
				echo 'Submission uploaded!';
			}

			die();
		}

		/**
		 * This is the helper function for retrieving a set of ubcar_layer data
		 * from the database, converting it to JSON, and echoing it.
		 *
		 * @param int $ubcar_layer_offset
		 *
		 * @access public
		 * @global object $wpdb
		 * @return void
		 */
		function ubcar_layer_get_layers( $ubcar_layer_offset ) {
			global $wpdb;
			$ubcar_layers = get_posts( array( 'posts_per_page' => 25, 'offset' => $ubcar_layer_offset, 'order' => 'DESC', 'post_type' => 'ubcar_layer' ) );
			$response = array();
			foreach ( $ubcar_layers as $ubcar_layer ) {
				$tempArray = $this->ubcar_get_layer( $ubcar_layer->ID );
				array_push( $response, $tempArray );
			}
			wp_send_json( $response );
		}

		/**
		 * This is a helper function for retrieving a single ubcar_layer datum
		 * and metadatum from the database.
		 *
		 * @param int $layer_ubcar_id
		 *
		 * @access public
		 * @return array
		 */
		function ubcar_get_layer( $layer_ubcar_id ) {
			$ubcar_layer = get_post( $layer_ubcar_id );
			$ubcar_layer_meta = get_post_meta( $ubcar_layer->ID, 'ubcar_password', true );
			$ubcar_layer_author = get_user_by( 'id', $ubcar_layer->post_author );
			$tempArray = array();
			$tempArray["ID"] = $ubcar_layer->ID;
			$tempArray["uploader"] = $ubcar_layer_author->first_name . ' ' . $ubcar_layer_author->last_name . ' (' . $ubcar_layer_author->user_login . ')';
			$tempArray["title"] = $ubcar_layer->post_title;
			$tempArray["date"] = get_the_date( 'Y-m-d', $ubcar_layer->ID );
			$tempArray["description"] = $ubcar_layer->post_content;
			$tempArray["password"] = $ubcar_layer_meta;
			return $tempArray;
		}

		/**
		 * This is the callback function for ubcar-layer-updater.js's
		 * initial AJAX request, displaying a set of ubcar_layer posts.
		 *
		 * @access public
		 * @return void
		 */
		function ubcar_layer_initial() {
			$this->ubcar_layer_get_layers( 0 );
		}

		/**
		 * This is the callback function for ubcar-layer-updater.js's
		 * forward_layers() AJAX request, displaying the next set of
		 * ubcar_layer posts.
		 *
		 * @access public
		 * @return void
		 */
		function ubcar_layer_forward() {
			$this->ubcar_layer_get_layers( intval( $_POST['ubcar_layer_offset'] ) * 25 );
		}

		/**
		 * This is the callback function for ubcar-layer-updater.js's
		 * backward_layers() AJAX request, displaying the previous set of
		 * ubcar_layer posts.
		 *
		 * @access public
		 * @return void
		 */
		function ubcar_layer_backward() {
			$back_layer = ( intval( $_POST['ubcar_layer_offset'] ) - 2 ) * 25;
			if( $back_layer < 0 ) {
				$back_layer = 0;
			}
			$this->ubcar_layer_get_layers( $back_layer );
		}

		/**
		 * This is the callback function for ubcar-layer-updater.js's
		 * goto_layers() AJAX request, displaying the selected set of
		 * ubcar_layer posts.
		 *
		 * @access public
		 * @return void
		 */
		function ubcar_layer_goto() {
			$goto_layer = ( intval( $_POST['ubcar_layer_offset'] ) - 1 ) * 25;
			if( $goto_layer < 0 ) {
				$goto_layer = 0;
			}
			$this->ubcar_layer_get_layers( $goto_layer );
		}

		/**
		 * This is the callback function for ubcar-layer-updater.js's
		 * delete_layers() AJAX request, deleting an ubcar_layer post
		 *
		 * @access public
		 * @global object $wpdb
		 * @return void
		 */
		function ubcar_layer_delete() {
			global $wpdb;
			$delete_post = get_post( sanitize_text_field( $_POST['ubcar_layer_delete_id'] ) );
			if ( !isset( $_POST['ubcar_nonce_field'] ) || !wp_verify_nonce( $_POST['ubcar_nonce_field'],'ubcar_nonce_check' )  ) {
				echo 0;
			} else {
				if( get_current_user_id() != $delete_post->post_author && !current_user_can( 'edit_pages' ) ) {
					echo 0;
				} else {
					wp_delete_post( sanitize_text_field( $_POST['ubcar_layer_delete_id'] ) );
					$this->ubcar_layer_get_layers( 0 );
				}
			}
		}

		/**
		 * This is the callback function for ubcar-layer-updater.js's
		 * edit_layers() AJAX request, retrieving a single layer.
		 *
		 * @access public
		 * @global object $wpdb
		 * @return void
		 */
		function ubcar_layer_edit() {
			global $wpdb;
			$edit_post = get_post( sanitize_text_field( $_POST['ubcar_layer_edit_id'] ) );
			if ( !isset( $_POST['ubcar_nonce_field'] ) || !wp_verify_nonce( $_POST['ubcar_nonce_field'],'ubcar_nonce_check' )  ) {
				echo 0;
			} else {
				if( get_current_user_id() != $edit_post->post_author && !current_user_can( 'edit_pages' ) ) {
					echo 0;
				} else {
					wp_send_json( $this->ubcar_get_layer( $edit_post->ID ) );
				}
			}
		}

		/**
		 * This is the callback function for ubcar-layer-updater.js's
		 * edit_layers_submit() AJAX request, updating the ubcar_layer post.
		 *
		 * @access public
		 * @global object $wpdb
		 * @return void
		 */
		function ubcar_layer_edit_submit() {
			global $wpdb;
			$edit_post = get_post( sanitize_text_field( $_POST['ubcar_layer_edit_id'] ) );
			if ( !isset( $_POST['ubcar_nonce_field'] ) || !wp_verify_nonce( $_POST['ubcar_nonce_field'],'ubcar_nonce_check' )  ) {
				echo 0;
			} else {
				if( get_current_user_id() != $edit_post->post_author && !current_user_can( 'edit_pages' ) ) {
					echo 0;
				} else {
					$update_array = array(
						'ID' => sanitize_text_field( $_POST['ubcar_layer_edit_id'] ),
						'post_title' => sanitize_text_field( $_POST['ubcar_layer_title'] ),
						'post_content' => sanitize_text_field( $_POST['ubcar_layer_description'] )
					 );
					wp_update_post( $update_array );
					update_post_meta( sanitize_text_field( $_POST['ubcar_layer_edit_id'] ), 'ubcar_password', sanitize_text_field( $_POST['ubcar_layer_password'] ) );
					wp_send_json( $this->ubcar_get_layer( sanitize_text_field( $_POST['ubcar_layer_edit_id'] ) ) );
				}
			}
		}

	}

?>
