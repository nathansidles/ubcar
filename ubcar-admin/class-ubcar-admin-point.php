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
     *     this point (updated in the UBCAR_Admin_Medium class)
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
            wp_register_script( 'ubcar_control_panel_point_updater_script', plugins_url( 'js/ubcar-point-updater.js', dirname(__FILE__) ) );
            wp_enqueue_script( 'ubcar_control_panel_script', array( 'jquery' ) );
            wp_enqueue_script( 'ubcar_control_panel_point_updater_script', array( 'jquery' ) );
            wp_localize_script( 'ubcar_control_panel_point_updater_script', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
            ?>
            <h2>UBCAR Point Page</h2>
            <p></p>
            <hr />
            <h3 id="ubcar-add-new-toggle">Add New Point<span class="ubcar-menu-toggle" id="ubcar-add-toggle-arrow">&#9660</span></h3>

            <form method="POST" action="" style="width: 100%;" id="ubcar-add-new-form">
                <?php
                    wp_nonce_field('ubcar_nonce_check','ubcar_nonce_field');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="ubcar_point_title">Point Title</label></th>
                        <td><input name="ubcar_point_title" type="text" id="ubcar_point_title" value="" class="regular-text ltr" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ubcar_point_description">Point Description Text</label></th>
                        <td>
                            <textarea name="ubcar_point_description" rows="5" type="textfield" id="ubcar_point_description" value="" class="regular-text ltr" /></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ubcar_point_latitude">Point Latitude</label></th>
                        <td><input name="ubcar_point_latitude" type="number" id="ubcar_point_latitude" value="" class="regular-text ltr" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ubcar_point_longitude">Point Longitude</label></th>
                        <td><input name="ubcar_point_longitude" type="number" id="ubcar_point_longitude" value="" class="regular-text ltr" /> <div class="button" id="ubcar_point_latlng_check">Check Location</div></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ubcar_point_latlng">Click to select Lat/Long:</label></th>
                        <td>
                            <div id="ubcar-map-canvas"></div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ubcar_point_tags">Tags (comma-separated)</label></th>
                        <td>
                            <input name="ubcar_point_tags" type="text" id="ubcar_point_tags" value="" class="regular-text ltr" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <div class="button button-primary" name="ubcar_point_submit" id="ubcar_point_submit">Upload point</div>
                        </th>
                    </tr>
                </table>
            </form>
            <hr />
            <h3>Manage Existing points</h3>
            <table class="ubcar-table" id="ubcar_point_table">
            </table>
            <div class="ubcar-forward-back">
                <a class="ubcar-forward-back-control" id="ubcar_point_back">Prev</a>
                <span id="ubcar_point_display_count">1</span>
                <a class="ubcar-forward-back-control" id="ubcar_point_forward">Next</a>
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
            if ( !isset($_POST['ubcar_nonce_field']) || !wp_verify_nonce($_POST['ubcar_nonce_field'],'ubcar_nonce_check') ) {
                echo 'Sorry, WordPress has rejected your submission - specifically, your nonce did not verify. Please reload the form page and try again. This message may occur if you took more than a day to complete your form, if you do not have the appropriate privileges to submit data points but nonetheless try, or if the ubcar coding team made an error.';
            } else {
                $ubcar_point_post = array(
                    'post_title' => $_POST['ubcar_point_title'],
                    'post_content' => $_POST['ubcar_point_description'],
                    'post_status' => 'publish',
                    'post_type' => 'ubcar_point'
                );
                
                $ubcar_point_id = wp_insert_post( $ubcar_point_post );
                add_post_meta( $ubcar_point_id, 'ubcar_point_latitude', $_POST['ubcar_point_latitude'] );
                add_post_meta( $ubcar_point_id, 'ubcar_point_longitude', $_POST['ubcar_point_longitude'] );
                add_post_meta( $ubcar_point_id, 'ubcar_point_tags', $_POST['ubcar_point_tags'] );
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
            $ubcar_points = get_posts( array( 'posts_per_page' => 10, 'offset' => $ubcar_point_offset, 'order' => 'DESC', 'post_type' => 'ubcar_point' ) );
            $response = array();
            foreach ($ubcar_points as $ubcar_point) {
                $tempArray = $this->ubcar_get_point( $ubcar_point->ID );
                array_push( $response, $tempArray );
            }
            wp_send_json( $response );
            die();
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
            $tempArray["date"] = get_the_date( 'Y-m-d', $ubcar_point->ID);
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
            $this->ubcar_point_get_points( intval( $_POST['ubcar_point_offset'] ) * 10 );
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
            $back_point = ($_POST['ubcar_point_offset'] - 2 ) * 10;
            if( $back_point < 0 ) {
                $back_point = 0;
            }
            $this->ubcar_point_get_points( $back_point );
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
            if ( !isset($_POST['ubcar_nonce_field']) || !wp_verify_nonce($_POST['ubcar_nonce_field'],'ubcar_nonce_check')  ) {
                echo 0;
            } else {
                if( get_current_user_id() != $delete_post->post_author && !current_user_can( 'edit_pages' )) {
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
            if ( !isset($_POST['ubcar_nonce_field']) || !wp_verify_nonce($_POST['ubcar_nonce_field'],'ubcar_nonce_check')  ) {
                echo 0;
            } else {
                if( get_current_user_id() != $edit_post->post_author && !current_user_can( 'edit_pages' ) ) {
                    echo 0;
                } else {
                    echo wp_send_json( $this->ubcar_get_point( $edit_post->ID ) );
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
            if ( !isset($_POST['ubcar_nonce_field']) || !wp_verify_nonce($_POST['ubcar_nonce_field'],'ubcar_nonce_check')  ) {
                echo 0;
            } else {
                if( get_current_user_id() != $edit_post->post_author && !current_user_can( 'edit_pages' ) ) {
                    echo 0;
                } else {
                    $update_array = array(
                        'ID' => $_POST['ubcar_point_edit_id'],
                        'post_title' => $_POST['ubcar_point_title'],
                        'post_content' => $_POST['ubcar_point_description']
                    );
                    wp_update_post( $update_array );
                    update_post_meta( $_POST['ubcar_point_edit_id'], 'ubcar_point_latitude', $_POST['ubcar_point_latitude']  );
                    update_post_meta( $_POST['ubcar_point_edit_id'], 'ubcar_point_longitude', $_POST['ubcar_point_longitude']  );
                    update_post_meta( $_POST['ubcar_point_edit_id'], 'ubcar_point_tags', $_POST['ubcar_point_tags']  );
                    echo wp_send_json( $this->ubcar_get_point( $_POST['ubcar_point_edit_id'] ) );
                }
            }
        }

    }
    
?>
