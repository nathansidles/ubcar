<?php
    /**
     * The UBCAR_Admin_Medium subclass
     * 
     * This file defines the UBCAR_Admin_Medium subclass. The UBCAR_Admin_Medium
     * class manages ubcar_medium-type posts. ubcar_medium-type posts have one
     * extra piece of metadata:
     * 
     * - ubcar_media_meta: an array of metadata for this ubcar_medium post:
     *   - type: the type of media uploaded.
     *     - image: the ID of an image uploaded to the WordPress gallery
     *     - imagewp: the ID of an image from the WordPress gallery (treated as
     *         an image type)
     *     - audio: the ID of a public-facing audio file to embed
     *     - video: the ID of a public-facing video file to embed
     *     - eternal: a URL of an external webpage to be displayed as a link
     *     - wiki: a URL to a wiki page to be embedded, dependent on UBC CTLT's
     *         Wiki-Embed plugin
     *   - audio_type: Optional. the type of audio media uploaded. Only
     *       SoundCloud is supported currently. Adding new types requires adding
     *       audio_type checking and display code in ubcar-map-view.js.
     *   - video_type: Optional. the type of video media uploaded. Only YouTube
     *       is supported currently. Adding new types requires adding audio_type
     *       checking and display code in ubcar-map-view.js.
     *   - url: the WordPress ID, external video/audio ID, or URL of the media
     *   - location: the ubcar_point post ID of the media's associated location
     *   - layers: an array of the ubcar_layer post IDs associated with this
     *       point
     *   - hidden: determines if the media file is displayed on the front-end
     * 
     * Additionally, the UBCAR_Admin_Medium class manages the following metadata
     * for other classes:
     * 
     * 1. (UBCAR_Admin_Layer) ubcar_layer_media 
     * 2. (UBCAR_Admin_Layer) ubcar_layer_points 
     * 3. (UBCAR_Admin_Point) ubcar_point_media
     * 
     * These metadata allow WordPress to quickly determine all media associated
     * with a point (#3), all points that contain media of a layer (#2), and all
     * media within a single layer (#1).
     * 
     * UBCAR_Admin_Medium does not use AJAX to upload media files because it was
     * a pain to try and implement. It instead uses the PRG design pattern.
     * 
     * @package UBCAR
     */    

    /*
     * The UBCAR_Admin_Tour subclass
     */
    class UBCAR_Admin_Medium extends UBCAR_Admin {
    
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
         * This function adds the UBCAR_Admin_Medium actions,including its AJAX
         * callback hooks and upload detection hooks.
         * 
         * @access public
         * @return void
         */
        function add_actions() {
            add_action( 'wp_ajax_media_initial', array( $this, 'ubcar_media_initial' ) );
            add_action( 'wp_ajax_media_forward', array( $this, 'ubcar_media_forward' ) );
            add_action( 'wp_ajax_media_backward', array( $this, 'ubcar_media_backward' ) );
            add_action( 'wp_ajax_media_delete', array( $this, 'ubcar_media_delete' ) );
            add_action( 'wp_ajax_media_edit', array( $this, 'ubcar_media_edit' ) );
            add_action( 'wp_ajax_media_edit_submit', array( $this, 'ubcar_media_edit_submit' ) );
            add_action( 'admin_init', array( $this, 'ubcar_media_data_handler' ) );
        }
        
        /**
         * This function initializes the main UBCAR Media menu page.
         * 
         * @access public
         * @return void
         */
        function menu_initializer() {
            wp_register_script( 'ubcar_control_panel_media_updater_script', plugins_url( 'js/ubcar-media-updater.js', dirname(__FILE__) ) );
            wp_enqueue_script( 'ubcar_control_panel_script', array( 'jquery' ) );
            wp_enqueue_script( 'ubcar_control_panel_media_updater_script', array( 'jquery' ) );
            wp_localize_script( 'ubcar_control_panel_media_updater_script', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
            $ubcar_locations = get_posts( array( 'posts_per_page' => -1, 'order' => 'ASC', 'post_type' => 'ubcar_point' ) );
            $ubcar_layers = get_posts( array( 'posts_per_page' => -1, 'order' => 'ASC', 'post_type' => 'ubcar_layer' ) );
            ?>
            <h2>UBCAR Media Page</h2>
            <p></p>
            <hr />
            <h3 id="ubcar-add-new-toggle">Add New Media<span class="ubcar-menu-toggle" id="ubcar-add-toggle-arrow">&#9660</span></h3>

            <form method="POST" action="" style="width: 100%;" id="ubcar-add-new-form" enctype="multipart/form-data">
                <?php
                    wp_nonce_field('ubcar_nonce_check','ubcar_nonce_field');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="ubcar_media_type">Type</label></th>
                        <td>
                            <select id="ubcar_media_type" name="ubcar_media_type" class="">
                                <option value="image">Image from Computer</option>
                                <option value="imagewp">Image from Gallery</option>
                                <option value="video">Video</option>
                                <option value="audio">Audio</option>
                                <option value="external">External Site Link</option>
                                <?php
                                if( current_user_can( 'edit_pages' ) ) {
                                    echo '<option value="wiki">Wiki Page</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr class="ubcar-add-media-image">
                        <th scope="row"><label for="ubcar_media_upload">Image Upload</label></th>
                        <td><input name="ubcar_media_upload" type="file" id="ubcar_media_upload" class="regular-text ltr" multiple="false" /></td>
                    </tr>
                    <tr class="ubcar-add-media-imagewp">
                        <th scope="row"><label for="ubcar_media">WordPress Gallery #</label></th>
                        <td>
                            <select id="ubcar_wp_image_url" name="ubcar_wp_image_url">
                                <?php
                                    $gallery_images = get_posts( array( 'posts_per_page' => -1, 'order' => 'ASC', 'post_type' => 'attachment', 'post_mime_type' => 'image/png, image/jpeg' ) );
                                    foreach( $gallery_images as $gallery_image ) {
                                        echo '<option value="' . $gallery_image->ID. '">' . $gallery_image->post_title . ' (#' . $gallery_image->ID . ')</option>';
                                    }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr class="ubcar-add-media-external">
                        <th scope="row"><label for="ubcar_external">External Web Address</label></th>
                        <td><input name="ubcar_external_url" type="text" id="ubcar_external_url" value="" class="regular-text ltr" /></td>
                    </tr>
                    <tr class="ubcar-add-media-wiki">
                        <th scope="row"><label for="ubcar_wiki">Wiki Page URL</label></th>
                        <td><input name="ubcar_wiki_url" type="text" id="ubcar_wiki_url" value="" class="regular-text ltr" /></td>
                    </tr>
                    <tr class="ubcar-add-media-video">
                        <th scope="row"><label for="ubcar_video_type">Video Type</label></th>
                        <td>
                            <select id="ubcar_video_type" name="ubcar_video_type" class="">
                                <option value="youtube">YouTube</option>
                            </select>
                        </td>
                    </tr>
                    <tr class="ubcar-add-media-video">
                        <th scope="row"><label for="ubcar_video_url">Video URL</label></th>
                        <td><input name="ubcar_video_url" type="text" id="ubcar_video_url" value="" class="regular-text ltr" /></td>
                    </tr>
                    <tr class="ubcar-add-media-audio">
                        <th scope="row"><label for="ubcar_audio_type">Audio Type</label></th>
                        <td>
                            <select id="ubcar_audio_type" name="ubcar_audio_type" class="">
                                <option value="soundcloud">SoundCloud</option>
                            </select>
                        </td>
                    </tr>
                    <tr class="ubcar-add-media-audio">
                        <th scope="row"><label for="ubcar_audio_url">SoundCloud ID#</label></th>
                        <td><input name="ubcar_audio_url" type="text" id="ubcar_audio_url" value="" class="regular-text ltr" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ubcar_media_title">Media Title</label></th>
                        <td><input name="ubcar_media_title" type="text" id="ubcar_media_title" value="" class="regular-text ltr" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ubcar_media_description">Media Description Text</label><br /><span id="ubcar_media_wiki_warning">(n/a for Wiki Pages)</span></th>
                        <td>
                            <textarea name="ubcar_media_description" rows="5" type="textfield" id="ubcar_media_description" value="" class="regular-text ltr" /></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ubcar_media_location">Associated Location</label></th>
                        <td>
                            <select id="ubcar_media_location" name="ubcar_media_location" class="">
                                <option value="0">---</option>
                                <?php
                                foreach ($ubcar_locations as $ubcar_location) {
                                    echo '<option value="' . $ubcar_location->ID . '">' . $ubcar_location->post_title . ' (#' . $ubcar_location->ID . ')</option>';
                                }
                            ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ubcar_media_layers">Associated Layers</label></th>
                        <td><select multiple name="ubcar_media_layers[]" id="ubcar_media_layers[]" size="10">
                            <?php
                                foreach ($ubcar_layers as $ubcar_layer) {
                                    $ubcar_layer_password = get_post_meta( $ubcar_layer->ID, 'ubcar_password', true );
                                    if( $ubcar_layer_password == 'false' || $ubcar_layer_password == '' || current_user_can( 'edit_pages' ) ) {
                                        echo '<option value="' . $ubcar_layer->ID . '">' . $ubcar_layer->post_title . ' (#' . $ubcar_layer->ID . ')</option>';
                                    }
                                }
                            ?>
                        </select></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ubcar_media_visibility">Hidden</label></th>
                        <td><input name="ubcar_media_visibility" type="checkbox" id="ubcar_media_visibility" /></td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <input class="button button-primary" name="ubcar_media_submit" id="ubcar_media_submit" type="submit" value="Upload">
                        </th>
                    </tr>
                </table>
            </form>
            <hr />
            <?php
                if( isset( $_GET['load'] ) && $_GET['load'] == 'failure' ) {
                    ?>
                        <h3>Image failed to load or no image selected. Please try again.</h3>
                        <hr />
                    <?php
                }
            ?>
            <h3>Manage Existing Media</h3>
            <div class="ubcar-filter">
            <?php
                if ( current_user_can( 'edit_pages' ) ) {
                    echo '<form method="GET" action="' . menu_page_url( 'ubcar-media', 0 ) . '" style="width: 100%;" id="ubcar-add-new-form">';
                    echo '<input type="hidden" name="page" value="' . $_REQUEST['page'] . '" />';
                    echo '<input id="ubcar-media-filter-value" name="author">';
                    echo '<input class="button button-primary" type="submit" value="Filter by Username">';
                    echo '</form>';
                    echo '<a href="' . menu_page_url( 'ubcar-media', 0 ) . '" id="ubcar-media-filter-none">See All</a>';
                }
                ?>
            </div>
            <table class="ubcar-table" id="ubcar_media_table">
            </table>
            <div class="ubcar-forward-back">
                <a class="ubcar-forward-back-control" id="ubcar_media_back">Prev</a>
                <span id="ubcar_media_display_count">1</span>
                <a class="ubcar-forward-back-control" id="ubcar_media_forward">Next</a>
            </div>
            <?php
            if( isset( $_GET['author'] ) && current_user_can( 'edit_pages' ) ) {
                echo '<input type="hidden" id="ubcar-author-name" value="' . $_GET['author'] . '">';
            } else {
                echo '<input type="hidden" id="ubcar-author-name" value="">';
            }
        }
        
        /**
         * This is function detects if a media file upload is being request,
         * performs the upload, and redirects to ubcar-data/
         * ubcar-post-redirect-get.php.
         * 
         * @access public
         * @global object $wpdb
         * @return void
         */
        function ubcar_media_data_handler() {
            global $wpdb;
            if( isset( $_POST['ubcar_nonce_field']) && isset( $_POST['ubcar_media_type'] ) ) {
                if ( !isset($_POST['ubcar_nonce_field']) || !wp_verify_nonce($_POST['ubcar_nonce_field'],'ubcar_nonce_check') ) {
                    die();
                } else {
                    $ubcar_url = "";
                    $ubcar_media_post_meta = array();
                    $ubcar_media_post = array(
                        'post_title' => $_POST['ubcar_media_title'],
                        'post_content' => $_POST['ubcar_media_description'],
                        'post_status' => 'publish',
                        'post_type' => 'ubcar_media'
                    );
                    if( $_POST['ubcar_media_type'] == 'image' ) {
                        $ubcar_url = media_handle_upload( 'ubcar_media_upload', 0 );
                        if( is_wp_error( $ubcar_url ) ) {
                            wp_redirect( menu_page_url( 'ubcar-media', 0 ) . '&load=failure' );
                            exit;
                        }
                    } else if( $_POST['ubcar_media_type'] == 'audio' ) {
                        $ubcar_url = $_POST['ubcar_audio_url'];
                        $ubcar_media_post_meta['audio_type'] = $_POST['ubcar_audio_type'];
                    } else if( $_POST['ubcar_media_type'] == 'video' ) {
                        $ubcar_url = substr( $_POST['ubcar_video_url'], strrpos( $_POST['ubcar_video_url'], "=" )  );
                        $ubcar_media_post_meta['video_type'] = $_POST['ubcar_video_type'];
                    } else if( $_POST['ubcar_media_type'] == 'external' || $_POST['ubcar_media_type'] == 'wiki' ) {
                        if( $_POST['ubcar_media_type'] == 'external' ) {
                            $ubcar_url_string = $_POST['ubcar_external_url'];
                        } else if( $_POST['ubcar_media_type'] == 'wiki' ) {
                            $ubcar_url_string = $_POST['ubcar_wiki_url'];
                            $ubcar_media_post['post_content'] = 'n/a';
                        }
                        $ubcar_url_array = parse_url( $ubcar_url_string );
                        if( isset( $ubcar_url_array['scheme'] ) ) {
                            $ubcar_url .= $ubcar_url_string;
                        } else {
                            $ubcar_url .= 'http://' . $ubcar_url_string;
                        }
                    } else if( $_POST['ubcar_media_type'] == 'imagewp' ) {
                        $ubcar_url = $_POST['ubcar_wp_image_url']; 
                    }
                    $ubcar_media_post_meta['type'] = $_POST['ubcar_media_type'];
                    if( $_POST['ubcar_media_type'] == 'imagewp' ) {
                        $ubcar_media_post_meta['type'] = 'image';
                    }
                    $ubcar_media_post_meta['url'] = $ubcar_url;
                    $ubcar_media_post_meta['location'] = $_POST['ubcar_media_location'];
                    $ubcar_media_post_meta['layers'] = array();
                    if( isset( $_POST['ubcar_media_layers'] ) ) {
                        $ubcar_media_post_meta['layers'] = $_POST['ubcar_media_layers'];
                    }
                    if( isset( $_POST['ubcar_media_visibility'] ) ) {
                        $ubcar_media_post_meta['hidden'] = 'on';
                    } else {
                        $ubcar_media_post_meta['hidden'] = 'off';
                    }
                    $ubcar_media_id = wp_insert_post( $ubcar_media_post );
                    add_post_meta( $ubcar_media_id, 'ubcar_media_meta', $ubcar_media_post_meta );
                    foreach( $ubcar_media_post_meta['layers'] as $layer ) {
                        $layer_media = get_post_meta( $layer, 'ubcar_layer_media', true );
                        if( $layer_media == null ) {
                            $layer_media = array();
                        }
                        array_push( $layer_media, $ubcar_media_id );
                        update_post_meta( $layer, 'ubcar_layer_media',  $layer_media );
                        $layer_points = get_post_meta( $layer, 'ubcar_layer_points', true );
                        if( $layer_points == null ) {
                            $layer_points = array();
                        }
                        array_push( $layer_points, array( $ubcar_media_id, $_POST['ubcar_media_location'] ) );
                        update_post_meta( $layer, 'ubcar_layer_points',  $layer_points );
                    }
                    $ubcar_location_media = get_post_meta( $_POST['ubcar_media_location'], 'ubcar_point_media', true );
                    if( $ubcar_location_media == null ) {
                        $ubcar_location_media = array();
                    }
                    array_push( $ubcar_location_media, $ubcar_media_id );
                    update_post_meta( $_POST['ubcar_media_location'], 'ubcar_point_media', $ubcar_location_media );
                    
                }
                $return_url = plugins_url( 'ubcar-data/ubcar-post-redirect-get.php', dirname(__FILE__) ) . '?return=' . menu_page_url( 'ubcar-media', 0 );
                wp_redirect( $return_url );
                exit;
            }
        }
    
        /**
         * This is the helper function for retrieving a set of ubcar_medium data
         * from the database, converting it to JSON, and echoing it.
         * 
         * @param int $ubcar_media_offset
         * @param string $ubcar_author_name
         * 
         * @access public
         * @global object $wpdb
         * @return void
         */
        function ubcar_media_get_medias( $ubcar_media_offset, $ubcar_author_name ) {
            global $wpdb;
            $ubcar_get_medias_parameters = array( 'posts_per_page' => 10, 'offset' => $ubcar_media_offset, 'order' => 'DESC', 'post_type' => 'ubcar_media' );
            if( $ubcar_author_name != '' ) {
                $ubcar_get_medias_parameters['author_name'] = $ubcar_author_name;
            }
            if ( !current_user_can( 'edit_pages' ) ) {
                $ubcar_current_user = wp_get_current_user();
                $ubcar_get_medias_parameters['author_name'] = $ubcar_current_user->user_login;
            }
            $ubcar_medias = get_posts( $ubcar_get_medias_parameters );
            $response = array();
            foreach ($ubcar_medias as $ubcar_media) {
                $tempArray = $this->ubcar_get_media( $ubcar_media->ID );
                array_push( $response, $tempArray );
            }
            wp_send_json( $response );
            die();
        }
        
        /**
         * This is a helper function for retrieving a single ubcar_medium datum
         * and metadata from the database.
         * 
         * @param int $ubcar_media_id
         * 
         * @access public
         * @return array
         */
        function ubcar_get_media( $ubcar_media_id ) {
            $ubcar_media = get_post( $ubcar_media_id );
            $ubcar_media_meta = get_post_meta( $ubcar_media->ID, 'ubcar_media_meta', true );
            $ubcar_media_author = get_user_by( 'id', $ubcar_media->post_author );
            $tempArray = array();
            $tempArray["ID"] = $ubcar_media->ID;
            if( $ubcar_media_meta['type'] == 'image' || $ubcar_media_meta['type'] == 'imagewp' ) {
                $tempArray["url"] = wp_get_attachment_thumb_url( $ubcar_media_meta['url'] );
                $tempArray["full_size_url"] = wp_get_attachment_url( $ubcar_media_meta['url'] );
                $tempArray["type"] = 'image';
            } else {
                $tempArray["url"] = $ubcar_media_meta['url'];
                $tempArray["type"] = $ubcar_media_meta['type'];
            }
            $tempArray["uploader"] = $ubcar_media_author->first_name . ' ' . $ubcar_media_author->last_name . ' (' . $ubcar_media_author->user_login . ')';
            $tempArray["title"] = $ubcar_media->post_title;
            $tempArray["date"] = get_the_date( 'Y-m-d', $ubcar_media->ID);
            $tempArray["description"] = $ubcar_media->post_content;
            $location = get_post( $ubcar_media_meta['location'] );
            $location_name = array();
            if( $location != null ) {
                $location_name["ID"] = $location->ID;
                $location_name["title"] = $location->post_title;
            } else {
                $location_name["ID"] = "?";
                $location_name["title"] = "Deleted location";
            }
            $tempArray["location"] = $location_name;
            $ubcar_media_layer_names = array();
            if( $ubcar_media_meta['layers'] != null ) {
                foreach( $ubcar_media_meta['layers'] as $ubcar_media_layer ) {
                    $layer = get_post( $ubcar_media_layer );
                    if($layer != null) {
                        $inner_temp_array = array();
                        $inner_temp_array["ID"] = $layer->ID;
                        $inner_temp_array["title"] = $layer->post_title;
                        array_push( $ubcar_media_layer_names, $inner_temp_array );
                    }
                }
            }
            $tempArray["hidden"] = $ubcar_media_meta['hidden'];
            $tempArray["layers"] = $ubcar_media_layer_names;
            return $tempArray;
        }
        
        /**
         * This is the callback function for ubcar-media_updater.js's
         * initial AJAX request, displaying a set of ubcar_medium posts.
         * 
         * @access public
         * @return void
         */
        function ubcar_media_initial() {
            $this->ubcar_media_get_medias( 0, $_POST['ubcar_author_name']  );
        }
        
        /**
         * This is the callback function for ubcar-medium-updater.js's
         * forward_medias() AJAX request, displaying the next set of
         * ubcar_medium posts.
         * 
         * @access public
         * @return void
         */
        function ubcar_media_forward() {
            $this->ubcar_media_get_medias( intval( $_POST['ubcar_media_offset'] ) * 10, $_POST['ubcar_author_name']  );
        }
        
        /**
         * This is the callback function for ubcar-medium-updater.js's
         * backward_medias() AJAX request, displaying the previous set of
         * ubcar_medium posts.
         * 
         * @access public
         * @return void
         */
        function ubcar_media_backward() {
            $back_media = ($_POST['ubcar_media_offset'] - 2 ) * 10;
            if( $back_media < 0 ) {
                $back_media = 0;
            }
            $this->ubcar_media_get_medias( $back_media, $_POST['ubcar_author_name'] );
        }
        
        /**
         * This is the callback function for ubcar-media-updater.js's
         * delete_medias() AJAX request, deleting an ubcar_medium post
         * 
         * @access public
         * @global object $wpdb
         * @return void
         */
        function ubcar_media_delete() {
            global $wpdb;
            $delete_post = get_post( $_POST['ubcar_media_delete_id'] );
            if ( !isset($_POST['ubcar_nonce_field']) || !wp_verify_nonce($_POST['ubcar_nonce_field'],'ubcar_nonce_check')  ) {
                echo 1;
            } else {
                if( get_current_user_id() != $delete_post->post_author && !current_user_can( 'edit_pages' )) {
                    echo 1;
                } else {
                    $ubcar_media_meta = get_post_meta( $_POST['ubcar_media_delete_id'], 'ubcar_media_meta', true );
                    $ubcar_media_point = $ubcar_media_meta['location'];
                    $ubcar_point_media = get_post_meta( $ubcar_media_point, 'ubcar_point_media', true );
                    if( $ubcar_point_media != null ) {
                        $ubcar_point_media_index = array_search( $_POST['ubcar_media_delete_id'], $ubcar_point_media );
                        if( $ubcar_point_media_index !== FALSE) {
                            array_splice( $ubcar_point_media, $ubcar_point_media_index, 1 );
                            update_post_meta( $ubcar_media_point, 'ubcar_point_media',  $ubcar_point_media );
                        }
                    }
                    if( $ubcar_media_meta['layers'] != null ) {
                        foreach( $ubcar_media_meta['layers'] as $ubcar_media_layer ) {
                            $ubcar_layer_media = get_post_meta( $ubcar_media_layer, 'ubcar_layer_media', true );
                            $ubcar_layer_media_index = array_search( $_POST['ubcar_media_delete_id'], $ubcar_layer_media );
                            if( $ubcar_layer_media_index !== FALSE) {
                                array_splice( $ubcar_layer_media, $ubcar_layer_media_index, 1 );
                                update_post_meta( $ubcar_media_layer, 'ubcar_layer_media',  $ubcar_layer_media );
                            }
                            $ubcar_layer_points = get_post_meta( $ubcar_media_layer, 'ubcar_layer_points', true );
                            $ubcar_layer_points_length = count( $ubcar_layer_points );
                            for( $i = 0; $i < $ubcar_layer_points_length; $i++) {
                                if( $ubcar_layer_points[$i][0] == $_POST['ubcar_media_delete_id']) {
                                    array_splice( $ubcar_layer_points, $i, 1 );
                                    update_post_meta( $ubcar_media_layer, 'ubcar_layer_points',  $ubcar_layer_points );
                                }
                            }
                        }
                    }
                    
                    wp_delete_post( $_POST['ubcar_media_delete_id'] );
                    $this->ubcar_media_get_medias( 0, $_POST['ubcar_author_name']  );
                }
            }
            die();
        }
        
        /**
         * This is the callback function for ubcar-media-updater.js's
         * edit_medias() AJAX request, retrieving a single medium.
         * 
         * @access public
         * @global object $wpdb
         * @return void
         */
        function ubcar_media_edit() {
            global $wpdb;
            $edit_post = get_post( $_POST['ubcar_media_edit_id'] );
            if ( !isset($_POST['ubcar_nonce_field']) || !wp_verify_nonce($_POST['ubcar_nonce_field'],'ubcar_nonce_check')  ) {
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
                    $ubcar_all_layers = get_posts( array( 'posts_per_page' => -1, 'order' => 'ASC', 'post_type' => 'ubcar_layer' ) );
                    $ubcar_all_layers_pared = array();
                    foreach( $ubcar_all_layers as $ubcar_layer ) {
                        $ubcar_layer_password = get_post_meta( $ubcar_layer->ID, 'ubcar_password', true );
                        if( $ubcar_layer_password == 'false' || $ubcar_layer_password == '' || current_user_can( 'edit_pages' ) ) {
                            $inner_temp_array = array();
                            $inner_temp_array["ID"] = $ubcar_layer->ID;
                            $inner_temp_array["title"] = $ubcar_layer->post_title;
                            array_push( $ubcar_all_layers_pared, $inner_temp_array );
                        }
                    }
                    $ubcar_media_to_return = $this->ubcar_get_media( $edit_post->ID );
                    $ubcar_media_to_return["all_locations"] = $ubcar_all_points_pared;
                    $ubcar_media_to_return["all_layers"] = $ubcar_all_layers_pared;
                    echo wp_send_json( $ubcar_media_to_return );
                }
            }
        }
        
        /**
         * This is the callback function for ubcar-media-updater.js's
         * edit_medias_submit() AJAX request, updating the ubcar_medium post.
         * 
         * @access public
         * @global object $wpdb
         * @return void
         */
        function ubcar_media_edit_submit() {
            global $wpdb;
            $edit_post = get_post( $_POST['ubcar_media_edit_id'] );
            if ( !isset($_POST['ubcar_nonce_field']) || !wp_verify_nonce($_POST['ubcar_nonce_field'],'ubcar_nonce_check')  ) {
                echo 0;
            } else {
                if( get_current_user_id() != $edit_post->post_author && !current_user_can( 'edit_pages' ) ) {
                    echo 0;
                } else {
                    $update_array = array(
                        'ID' => $_POST['ubcar_media_edit_id'],
                        'post_title' => $_POST['ubcar_media_title'],
                        'post_content' => $_POST['ubcar_media_description']
                    );
                    $update_array_meta = get_post_meta( $_POST['ubcar_media_edit_id'], 'ubcar_media_meta', true );
                    $update_array_meta['location'] = $_POST['ubcar_media_location'];
                    $update_array_meta['layers'] = $_POST['ubcar_media_layers'];
                    if( $_POST['ubcar_media_hidden'] == 'true' ) {
                        $update_array_meta['hidden'] = 'on';
                    } else {
                        $update_array_meta['hidden'] = 'off';
                    }
                    wp_update_post( $update_array );
                    update_post_meta( $_POST['ubcar_media_edit_id'], 'ubcar_media_meta',  $update_array_meta  );
                    
                    // check if layers have been added; update ubcar_layer_media and ubcar_layer_points metadata
                    if( isset ( $_POST['ubcar_media_added_layers'] ) ) {
                        foreach( $_POST['ubcar_media_added_layers'] as $layer ) {
                            if( $layer != null ) {
                                $layer_media = get_post_meta( $layer, 'ubcar_layer_media', true );
                                if( $layer_media == null ) {
                                    $layer_media = array();
                                }
                                array_push( $layer_media, $_POST['ubcar_media_edit_id'] );
                                update_post_meta( $layer, 'ubcar_layer_media',  $layer_media );
                                $layer_points = get_post_meta( $layer, 'ubcar_layer_points', true );
                                if( $layer_points == null ) {
                                    $layer_points = array();
                                }
                                array_push( $layer_points, array( $_POST['ubcar_media_edit_id'], $_POST['ubcar_media_location'] ) );
                                update_post_meta( $layer, 'ubcar_layer_points',  $layer_points );
                            }
                        }
                    }
                    
                    // check if layers have been removed; update ubcar_layer_media and upcar_layer_points metadata
                    if( isset( $_POST['ubcar_media_removed_layers'] ) ) {
                        foreach( $_POST['ubcar_media_removed_layers'] as $layer ) {
                            if( $layer != null ) {
                                $layer_media = get_post_meta( $layer, 'ubcar_layer_media', true );
                                if( $layer_media != null ) {
                                    $layer_media_index = array_search( $_POST['ubcar_media_edit_id'], $layer_media );
                                    if( $layer_media_index !== FALSE) {
                                        array_splice( $layer_media, $layer_media_index, 1 );
                                        update_post_meta( $layer, 'ubcar_layer_media',  $layer_media );
                                    }
                                }
                                $layer_points = get_post_meta( $layer, 'ubcar_layer_points', true );
                                if( $layer_points != null ) {
                                    $i = 0;
                                    foreach( $layer_points as $layer_point) {
                                        if( $layer_point[0] == $_POST['ubcar_media_edit_id'] ) {
                                            array_splice( $layer_points, $i, 1 );
                                            update_post_meta( $layer, 'ubcar_layer_points',  $layer_points );
                                        }
                                        $i++;
                                    }
                                }
                            }
                        }
                    }
                    
                    // check if media file has changed location; update ubcar_point_media and ubcar_layer_points metadata
                    if( $_POST['ubcar_media_old_location'] != $_POST['ubcar_media_location'] ) {
                        $location_media = get_post_meta( $_POST['ubcar_media_location'], 'ubcar_point_media', true );
                        if( $location_media == null ) {
                            $location_media = array();
                        }
                        array_push( $location_media, $_POST['ubcar_media_edit_id'] );
                        update_post_meta( $_POST['ubcar_media_location'], 'ubcar_point_media',  $location_media );
                        $old_location_media = get_post_meta( $_POST['ubcar_media_old_location'], 'ubcar_point_media', true );
                        if( $old_location_media != null ) {
                            $old_location_media_index = array_search( $_POST['ubcar_media_edit_id'], $old_location_media );
                            if( $old_location_media_index !== FALSE) {
                                array_splice( $old_location_media, $old_location_media_index, 1 );
                                $old_location_media_index = array_search( $_POST['ubcar_media_edit_id'], $old_location_media );
                                if( $old_location_media_index !== FALSE) {
                                    array_splice( $old_location_media, $old_location_media_index, 1 );
                                }
                                update_post_meta( $_POST['ubcar_media_old_location'], 'ubcar_point_media',  $old_location_media );
                            }
                        }
                        foreach( $_POST['ubcar_media_layers'] as $layer ) {
                            if( $layer != null ) {
                                $layer_points = get_post_meta( $layer, 'ubcar_layer_points', true );
                                $layer_points_index = array_search( array( $_POST['ubcar_media_edit_id'], $_POST['ubcar_media_old_location'] ), $layer_points );
                                $layer_points[$layer_points_index] = array( $_POST['ubcar_media_edit_id'], $_POST['ubcar_media_location'] );
                                update_post_meta( $layer, 'ubcar_layer_points', $layer_points );
                            }
                        }
                    }
                    echo wp_send_json( $this->ubcar_get_media( $_POST['ubcar_media_edit_id'] ) );
                }
            }
        }

    }
    
?>
