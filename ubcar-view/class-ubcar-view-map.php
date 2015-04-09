<?php
	/**
	 * The UBCAR_View_map subclass
	 * 
	 * This file defines the UBCAR_View_Map subclass. The UBCAR_View_Map class
	 * displays UBCAR data on the frontend with the 'ubcar-map' shortcode. Any
	 * specific UBCAR point requires data from all four UBCAR post types:
	 * ubcar_point, ubcar_layer, ubcar_tour, and ubcar_media. Additionally,
	 * regular WordPress comments and comment submission forms are displayed
	 * for each point.
	 * 
	 * A specific point, tour, or layer can be viewed with a '?point=#',
	 * '?tour=#', or '?layer=#' GET request, where '#' is the ID of the object.
	 * 
	 * @package UBCAR
	 */

	/*
	 * The UBCAR_View_map subclass
	 */
	class UBCAR_View_Map extends UBCAR_View {

		/**
		 * The UBCAR_View_Map constructor.
		 * 
		 * @access public
		 * @return void
		 */
		public function __construct() {
			$this->add_shortcodes();
			$this->add_actions();
		}
		
		/**
		 * This function adds the UBCAR_View_Map actions, including its AJAX
		 * callback hooks. ( AJAX is about it in terms of actions, actually. )
		 * 
		 * @access public
		 * @return void
		 */
		function add_actions() {
		
			add_action( 'wp_ajax_nopriv_ubcar_aggregate_information_retriever', array( $this, 'ubcar_get_aggregate_information' ) );
			add_action( 'wp_ajax_ubcar_aggregate_information_retriever', array( $this, 'ubcar_get_aggregate_information' ) );
			add_action( 'wp_ajax_nopriv_ubcar_aggregate_points_retriever', array( $this, 'ubcar_get_aggregate_points' ) );
			add_action( 'wp_ajax_ubcar_aggregate_points_retriever', array( $this, 'ubcar_get_aggregate_points' ) );
			add_action( 'wp_ajax_nopriv_ubcar_point_information_retriever', array( $this, 'ubcar_get_point_information' ) );
			add_action( 'wp_ajax_ubcar_point_information_retriever', array( $this, 'ubcar_get_point_information' ) );
			add_action( 'wp_ajax_nopriv_ubcar_point_comments_retriever', array( $this, 'ubcar_get_point_comments' ) );
			add_action( 'wp_ajax_ubcar_point_comments_retriever', array( $this, 'ubcar_get_point_comments' ) );
			add_action( 'wp_ajax_nopriv_ubcar_wiki_page', array( $this, 'ubcar_get_wiki_page' ) );
			add_action( 'wp_ajax_ubcar_wiki_page', array( $this, 'ubcar_get_wiki_page' ) );
			add_action( 'wp_ajax_ubcar_submit_comment', array( $this, 'ubcar_submit_comment' ) );
			add_action( 'wp_ajax_ubcar_submit_reply', array( $this, 'ubcar_submit_reply' ) );
			
		}
		
		/**
		 * This function adds the UBCAR_View_Map shortcodes.
		 * 
		 * @access public
		 * @return void
		 */
		function add_shortcodes() {
			add_shortcode( 'ubcar-map', array( $this, 'ubcar_make_map' ) );
		}
		
		/**
		 * This function adds the UBCAR_View_Map scripts, including Google Maps.
		 * 
		 * @access public
		 * @return void
		 */
		function add_scripts() {
			wp_enqueue_script( 'ubcar_map_display_google_script', 'https://maps.googleapis.com/maps/api/js?v=3.exp&key=' . get_option( 'ubcar_google_maps_api_key' ) );
			wp_register_script( 'ubcar_map_display_toggles_script', plugins_url( 'js/ubcar-map-view-toggles.js', dirname( __FILE__ ) ) );
			wp_register_script( 'ubcar_map_display_script', plugins_url( 'js/ubcar-map-view.js', dirname( __FILE__ ) ) );
			wp_enqueue_script( 'ubcar_map_display_toggles_script', array( 'jquery' ) );
			wp_enqueue_script( 'ubcar_map_display_script', array( 'jquery', 'ubcar_map_display_toggles_script' ) );
			wp_enqueue_script( 'jquery-effects-drop', array( 'jquery' ) );
			wp_localize_script( 'ubcar_map_display_script', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		}
		
		/**
		 * This function adds the UBCAR_View_Map styles.
		 * 
		 * @access public
		 * @return void
		 */
		function add_styles() {
			wp_register_style( 'ubcar_view_style', plugins_url().'/ubcar/css/ubcar-view-style.css' );
			wp_enqueue_style( 'ubcar_view_style' );
			if( get_option( 'ubcar_css_choice' ) == 'full' ) {
				wp_register_style( 'ubcar_view_full_style', plugins_url().'/ubcar/css/ubcar-view-full-style.css' );
				wp_enqueue_style( 'ubcar_view_full_style' );
			}
		}
		
		/**
		 * This function initializes the main UBCAR_View_Map page, which depends
		 * on AJAX to populate it.
		 * 
		 * @access public
		 * @return void
		 */
		function ubcar_make_map() {
			$this->add_scripts();
			$this->add_styles();
			$ubcar_layers = get_posts( array( 'posts_per_page' => -1, 'order' => 'ASC', 'post_type' => 'ubcar_layer' ) );
			$ubcar_tours = get_posts( array( 'posts_per_page' => -1, 'order' => 'ASC', 'post_type' => 'ubcar_tour' ) );
			wp_nonce_field( 'ubcar_nonce_check','ubcar_nonce_field' );

			?>
			<noscript>
			This plugin requires JavaScript.
			</noscript>
			<div class="ubcar-content">
				<div class="ubcar-informational-left-column">
					<div class="ubcar-aggregate-container">
						<div class="ubcar-header" id="ubcar-show-all">Show All</div>
						<div class="ubcar-accordion-header" id="ubcar-accordion-header-layers">Layers</div>
						<div class="ubcar-accordion-body" id="ubcar-accordion-body-layers">
							<form method="POST" action="" style="width: 100%;" id="ubcar-layers-form">
							<table>
								<?php
									foreach( $ubcar_layers as $ubcar_layer ) {
										?>
											<tr>
											<td><input type="checkbox" id="<?php echo $ubcar_layer->ID ?>" /> <?php echo $ubcar_layer->post_title ?> ( #<?php echo $ubcar_layer->ID ?> )<br /></td>
											</tr>
										<?php
									}
								?>
							</table>
							</form>
						</div>
						<div class="ubcar-accordion-header" id="ubcar-accordion-header-tours">Tours</div>
						<div class="ubcar-buffer"></div>
						<div class="ubcar-accordion-body" id="ubcar-accordion-body-tours">
							<form method="POST" action="" style="width: 100%;" id="ubcar-tours-form">
							<table>
							<?php
								foreach( $ubcar_tours as $ubcar_tour ) {
									?>
										<tr>
											<td><input type="checkbox" id="<?php echo $ubcar_tour->ID ?>" /> <?php echo $ubcar_tour->post_title ?> ( #<?php echo $ubcar_tour->ID ?> )<br /></td>
										</tr>
									<?php
								}
							?>
							</table>
							</form>
						</div>
					</div>
					<div id="ubcar-map-canvas">
					</div>
					<div id="ubcar-streetview-canvas">
					</div>
					<div class="ubcar-display-choice">
						<div class="ubcar-accordion-header ubcar-half" id="ubcar-display-choice-map">Map View</div>
						<div class="ubcar-half-buffer"></div>
						<div class="ubcar-accordion-header ubcar-half" id="ubcar-display-choice-street">Street View</div>
						<div class="ubcar-half-buffer"></div>
						<div class="ubcar-accordion-header ubcar-half" id="ubcar-display-fullscreen">Fullscreen</div>
					</div>
					<div class="ubcar-header" id="ubcar-title-search">
						<input id="ubcar-search-input">
						<div class="ubcar-button" id="ubcar-search-button">Search</div>
					</div>
				</div>
				<div class="ubcar-informational-right-column">
					<div id="ubcar-tour-information">
						<div class="ubcar-header" id="ubcar-header-aggregate">Layer/Tour<br />Information</div>
						<div class="ubcar-body" id="ubcar-body-aggregate"></div>
					</div>
					<div id="ubcar-point-information">
						<div class="ubcar-header" id="ubcar-header-information">Point<br />Information</div>
						<div class="ubcar-body" id="ubcar-body-information"></div>
					</div>
					<div id="ubcar-point-media">
						<div class="ubcar-header" id="ubcar-header-media">Point<br />Media</div>
						<div class="ubcar-body" id="ubcar-body-media"></div>
					</div>
					<div class="ubcar-header" id="ubcar-header-comments">Point<br />Comments</div>
					<div class="ubcar-body" id="ubcar-body-comments"></div>
					<div class="ubcar-header" id="ubcar-header-comments-submit">Submit New Comment</div>
					<div class="ubcar-body" id="ubcar-body-comments-submit"></div>
				</div>
			</div>
		<?php
			// determine if a GET request is being made and populate extra fields for JavaScript detection
			if( ( isset( $_GET['point'] ) && is_numeric( $_GET['point'] ) ) || ( isset( $_GET['layer'] ) && is_numeric( $_GET['layer'] ) ) || ( isset( $_GET['tour'] ) && is_numeric( $_GET['tour'] ) ) ) {
				$request_type = "";
				$request_value = "";
				if( isset( $_GET['point'] ) ) {
					$request_type = 'ubcar_point';
					$request_value = $_GET['point'];
					$request_post_meta = get_post_meta( $request_value );
					if( isset( $request_post_meta ) && isset( $request_post_meta["ubcar_point_latitude"] ) ) {
						$request_latitude = number_format( ( float )$request_post_meta["ubcar_point_latitude"][0], 7, '.', '' );
					}

					if( isset( $request_post_meta ) && isset( $request_post_meta["ubcar_point_longitude"] ) ) {
						$request_longitude =  number_format( ( float )$request_post_meta["ubcar_point_longitude"][0], 7, '.', '' );
					}
				} else if( isset( $_GET['layer'] ) ) {
					$request_type = 'ubcar_layer';
					$request_value = $_GET['layer'];
				} else if( isset( $_GET['tour'] ) ) {
					$request_type = 'ubcar_tour';
					$request_value = $_GET['tour'];
				}
				$ubcar_post_type = get_post_type( $request_value );
				if( $ubcar_post_type == $request_type ) {
					echo '<input type="hidden" value="' . $request_type . '" id="ubcar-hidden-request-type">';
					echo '<input type="hidden" value="' . $request_value . '" id="ubcar-hidden-request-value">';
					if( isset( $_GET['point'] ) ) {
						echo '<input type="hidden" value="' . $request_latitude . '" id="ubcar-hidden-request-latitude">';
						echo '<input type="hidden" value="' . $request_longitude . '" id="ubcar-hidden-request-longitude">';
					}
				}
			}
		}
		
		/**
		 * This is the callback function for
		 * ubcar_map_click_point_updater_comments()'s wp_list_comments(),
		 * formatting the retrieved comments.
		 * 
		 * @param object comment
		 * @args array $args
		 * @param int $depth
		 * 
		 * @global object comment
		 * @access public
		 * @return void
		 */
		function ubcar_comments_callback( $comment, $args, $depth ) {
			$GLOBALS['comment'] = $comment;
			extract( $args, EXTR_SKIP );
			$user = get_user_by( 'email', $comment->comment_author_email );
			echo '<div ';
			comment_class( empty( $args['has_children'] ) ? '' : 'parent' );
			echo ' id="ubcar-comment-';
			comment_ID();
			echo '">';
			echo  '<div class="ubcar-comment-header">' . $user->first_name . " " . $user->last_name . " ( " . $comment->comment_author . " ) - " . get_comment_date() . " " .  get_comment_time() . '</div>';
			if( is_user_logged_in() ) {
				echo '<div class="ubcar-comment-header ubcar-comment-reply" id="ubcar-comment-reply-' . get_comment_ID() . '"> - <a>Reply</a></div>';
			}
			comment_text();
			echo '<div class="ubcar-reply-area" id="ubcar-reply-area-' . get_comment_ID() . '"></div>';
		}
		
		/**
		 * This is the callback function for ubcar-map-view.js's
		 * submit_new_comment() AJAX request, inserting a new comment for the
		 * associated ubcar_point post.
		 * 
		 * @access public
		 * @global $current_user
		 * @return void
		 */
		function ubcar_submit_comment() {
			if ( !isset( $_POST['ubcar_nonce_field'] ) || !wp_verify_nonce( $_POST['ubcar_nonce_field'],'ubcar_nonce_check' ) || !is_user_logged_in() ) {
				echo 0;
			} else {
				global $current_user;
				get_currentuserinfo();
				$comment_array = array();
				$comment_array['user_ID'] = $current_user->ID;
				$comment_array['comment_author'] = $current_user->user_login;
				$comment_array['comment_author_email'] = $current_user->user_email;
				$comment_array['comment_author_url'] = $current_user->user_url;
				$comment_array['comment_post_ID'] = $_POST['ubcar_point_id'];
				$comment_array['comment_content'] = $_POST['ubcar_comment_text'];
				$comment_array['comment_type'] = '';
				wp_new_comment( $comment_array );
			}
			die();
		}
		
		/**
		 * This is the callback function for ubcar-map-view.js's
		 * submit_new_reply() AJAX request, inserting a new reply for the
		 * associated comment of the ubcar_point post.
		 * 
		 * @access public
		 * @global $current_user
		 * @return void
		 */
		function ubcar_submit_reply() {
			if ( !isset( $_POST['ubcar_nonce_field'] ) || !wp_verify_nonce( $_POST['ubcar_nonce_field'],'ubcar_nonce_check' ) || !is_user_logged_in() ) {
				echo 0;
			} else {
				global $current_user;
				get_currentuserinfo();
				$comment_array = array();
				$comment_array['user_ID'] = $current_user->ID;
				$comment_array['comment_author'] = $current_user->user_login;
				$comment_array['comment_author_email'] = $current_user->user_email;
				$comment_array['comment_author_url'] = $current_user->user_url;
				$comment_array['comment_post_ID'] = $_POST['ubcar_point_id'];
				$comment_array['comment_content'] = $_POST['ubcar_reply_text'];
				$comment_array['comment_type'] = '';
				$comment_array['comment_parent'] = $_POST['ubcar_comment_parent'];
				wp_new_comment( $comment_array );
			}
			die();
		}
		
		/**
		 * This is the callback function for ubcar-map-view.js's display_point()
		 * AJAX request, retrieving a wiki page's html if UBC CTLT's Wiki Embed
		 * plugin is enabled or the wiki page's URL if it is not.
		 * 
		 * @access public
		 * @return void
		 */
		function ubcar_get_wiki_page() {
			$ubcar_media_id = $_POST[ 'ubcar_wiki_id' ];
			$ubcar_media_meta = get_post_meta( $ubcar_media_id, 'ubcar_media_meta', true );
			$ubcar_media_url = html_entity_decode( $ubcar_media_meta["url"] );
			if( class_exists( 'Wiki_Embed' ) ) {
				$wiki_html = new Wiki_Embed();
				echo $wiki_html->shortcode( array( 'url' => $ubcar_media_url ) );
			} else {
				$no_wiki_html = array( 'url' => $ubcar_media_url );
				wp_send_json( $no_wiki_html );
			}
			die();
		}
		
		/**
		 * This is the callback function for ubcar-map-view.js's
		 * retrieve_aggregate_information() AJAX request, retrieving information
		 * the ubcar_layer or ubcar_tour itself.
		 * 
		 * @access public
		 * @return void
		 */
		function ubcar_get_aggregate_information() {
			$ubcar_aggregate_id = $_POST[ 'ubcar_aggregate_id' ];
			if( $ubcar_aggregate_id != 'all' ) {
				$ubcar_aggregate = get_post( $ubcar_aggregate_id );
				$temp_array["id"] = $ubcar_aggregate_id;
				$temp_array["title"] = $ubcar_aggregate->post_title;
				$temp_array["description"] = $ubcar_aggregate->post_content;
				if( $ubcar_aggregate->post_type == 'ubcar_tour' ) {
					$temp_array["type"] = 'ubcar_tour';
					$ubcar_aggregate_points = get_post_meta( $ubcar_aggregate_id, 'ubcar_tour_locations', true );
					$temp_array_points = array();
					if( $ubcar_aggregate_points != null ) {
						foreach( $ubcar_aggregate_points as $ubcar_aggregate_point ) {
							$temp_inner_array = array();
							$temp_point = get_post( $ubcar_aggregate_point );
							if( $temp_point != null ) {
								$ubcar_point_meta = get_post_meta( $temp_point->ID );
								$temp_inner_array['id'] = $temp_point->ID;
								$temp_inner_array['title'] = $temp_point->post_title;
								$temp_inner_array['latitude'] = number_format( ( float )$ubcar_point_meta["ubcar_point_latitude"][0], 7, '.', '' );
								$temp_inner_array['longitude'] =  number_format( ( float )$ubcar_point_meta["ubcar_point_longitude"][0], 7, '.', '' );
								array_push( $temp_array_points, $temp_inner_array );
							}
						}
						$temp_array['points'] = $temp_array_points;
					}
				} else if( $ubcar_aggregate->post_type == 'ubcar_layer' ) {
					$temp_array["type"] = 'ubcar_layer';
				}
				echo wp_send_json( $temp_array );
			}
			die();
		}
		
		/**
		 * This is the callback function for ubcar-map-view.js's
		 * retrieve_points() AJAX request, retrieving the points associated
		 * with a particular ubcar_layer, ubcar_tour, or ubcar_search.
		 * 
		 * @access public
		 * @return void
		 */
		function ubcar_get_aggregate_points() {
			global $wpdb;
			$ubcar_aggregate_id = $_POST['ubcar_aggregate_id'];
			$ubcar_aggregate_type = $_POST['ubcar_aggregate_type'];
			if( $ubcar_aggregate_type == 'ubcar_layer' ) {
				$ubcar_points_request = 'ubcar_layer_points';
			} else if( $ubcar_aggregate_type == 'ubcar_tour' ) {
				$ubcar_points_request = 'ubcar_tour_locations';
			}
			$ubcar_points = array();
			
			if( $ubcar_aggregate_type == 'ubcar_layer' || $ubcar_aggregate_type == 'ubcar_tour' ) {
				$ubcar_point_metas = get_post_meta( $ubcar_aggregate_id, $ubcar_points_request, true );
			} else if( $ubcar_aggregate_type == 'ubcar_search' ) {
				$ubcar_point_metas = $wpdb->get_results( $wpdb->prepare( 
					"SELECT post_id AS ID FROM $wpdb->postmeta
					WHERE meta_key = '%s'
					AND meta_value LIKE '%%%s%%'",
					'ubcar_point_tags',
					$ubcar_aggregate_id
				) );
				$ubcar_point_metas = array_merge( $wpdb->get_results( $wpdb->prepare( 
					"SELECT ID FROM $wpdb->posts
					WHERE post_type = '%s'
					AND post_title LIKE '%%%s%%'",
					'ubcar_point',
					$ubcar_aggregate_id
				) ), $ubcar_point_metas );
			} else {
				$args = array( 'posts_per_page' => -1, 'post_type' => 'ubcar_point' );
				$ubcar_point_metas = get_posts( $args );
			}
			
			$ubcar_geojson_response = array( 
				'type' => $ubcar_aggregate_type
			);
			
			if( $ubcar_point_metas != null ) {
			
				$ubcar_geojson = array( 
					'type' => 'FeatureCollection',
					'features' => array()
				);
				
				$ubcar_geojson_bounds = array( 
					'ne_lat' => -90,
					'ne_lng' => -180,
					'sw_lat' => 90,
					'sw_lng' => 180
				);
			
				// This loop strips away the duplicate points inherent in the ubcar_layer_points
				// metadata. ( Each ubcar_media associated with a point adds an element to this
				// array. )
				foreach( $ubcar_point_metas as $ubcar_point_meta ) {
					$temp_inner_array = array();
					if( $ubcar_aggregate_type == 'ubcar_layer' ) {
						$temp_point_id = $ubcar_point_meta[1];
						$temp_point = get_post( $temp_point_id );
					} else if( $ubcar_aggregate_type == 'ubcar_tour' ) {
						$temp_point_id = $ubcar_point_meta;
						$temp_point = get_post( $temp_point_id );
					} else if( $ubcar_aggregate_type == 'ubcar_search' ){
						$temp_point_id = $ubcar_point_meta->ID;
						$temp_point = get_post( $temp_point_id );
					} else {
						$temp_point_id = $ubcar_point_meta->ID;
						$temp_point = $ubcar_point_meta;
					}
					if( $temp_point != null ) {
						$temp_inner_array['id'] = 1;
						if( !array_key_exists( $temp_point_id, $ubcar_points ) ) {
							$ubcar_points[$temp_point_id] = true;
							$temp_inner_array = array();
							$temp_inner_array['type'] = 'Feature';
							$temp_longitude = ( float )number_format( ( float )get_post_meta( $temp_point_id, 'ubcar_point_longitude', true ), 7, '.', '' );
							$temp_latitude = ( float )number_format( ( float )get_post_meta( $temp_point_id, 'ubcar_point_latitude', true ), 7, '.', '' );
							$temp_inner_array['geometry'] = array( 
								'type' => 'Point',
								'coordinates' =>  array( 
									$temp_longitude,
									$temp_latitude
								),
							);
							if( $temp_latitude > $ubcar_geojson_bounds['ne_lat'] ) {
								$ubcar_geojson_bounds['ne_lat'] = $temp_latitude;
							}
							if( $temp_latitude < $ubcar_geojson_bounds['sw_lat'] ) {
								$ubcar_geojson_bounds['sw_lat'] = $temp_latitude;
							}
							if( $temp_longitude > $ubcar_geojson_bounds['ne_lng'] ) {
								$ubcar_geojson_bounds['ne_lng'] = $temp_longitude;
							}
							if( $temp_longitude < $ubcar_geojson_bounds['sw_lng'] ) {
								$ubcar_geojson_bounds['sw_lng'] = $temp_longitude;
							}
							$temp_inner_array['properties'] = array( 
								'id' => $temp_point->ID,
								'title' => $temp_point->post_title
							);
							array_push( $ubcar_geojson['features'], $temp_inner_array );
						}
					}
				}
				
				if( $ubcar_geojson_bounds['ne_lat'] > -90 ) {
					$ubcar_geojson_response['geojson_bounds'] = $ubcar_geojson_bounds;
				}
				
				$ubcar_geojson_response['geojson'] = $ubcar_geojson;
			}
			
			wp_send_json( $ubcar_geojson_response );
			die();
		}
		
		/**
		 * This is the callback function for the first of ubcar-map-view.js's two
		 * retrieve_point() AJAX requests, retrieving general information
		 * associated with a single ubcar_point.
		 * 
		 * @access public
		 * @return void
		 */
		function ubcar_get_point_information() {
			$ubcar_point_id = $_POST[ 'ubcar_point_id' ];
			$ubcar_point = get_post( $ubcar_point_id );
			$ubcar_point_meta = get_post_meta( $ubcar_point_id );
			$temp_array = array();
			$temp_array["point_ID"] = $ubcar_point->ID;
			$temp_array["point_title"] = $ubcar_point->post_title;
			$temp_array["point_description"] = $ubcar_point->post_content;
			$temp_array["point_latitude"] = number_format( ( float )$ubcar_point_meta["ubcar_point_latitude"][0], 7, '.', '' );
			$temp_array["point_longitude"] =  number_format( ( float )$ubcar_point_meta["ubcar_point_longitude"][0], 7, '.', '' );
			$temp_array["point_tags"] = $ubcar_point_meta["ubcar_point_tags"][0];
			$ubcar_point_media_meta = get_post_meta( $ubcar_point_id, 'ubcar_point_media', true );
			$temp_media_array = array();
			if( $ubcar_point_media_meta != null ) {
				foreach( $ubcar_point_media_meta as $ubcar_medium_id ) {
					$temp_medium_array = $this->ubcar_get_media( $ubcar_medium_id );
					if( $temp_medium_array != null ) {
						array_push( $temp_media_array, $temp_medium_array );
					}
				}
			}
			$temp_array["ubcar_media"] = $temp_media_array;
			$temp_array["logged_in"] = is_user_logged_in();
			echo wp_send_json( $temp_array );
			die();
		}
		
		/**
		 * This is the callback function for the second of ubcar-map-view.js's two
		 * retrieve_point() AJAX requests, retrieving comment information
		 * associated with a single ubcar_point.
		 * 
		 * @access public
		 * @return void
		 */
		function ubcar_get_point_comments() {
			$ubcar_point_id = $_POST[ 'ubcar_point_id' ];
			echo '<ol class="commentlist">';
			//Gather comments for a specific page/post
			$comments = get_comments( array( 
				'post_id' => $ubcar_point_id,
				'order' => 'ASC'
			) );

			//Display the list of comments
			wp_list_comments( array( 'echo' => true, 'style' => 'div', 'callback' => array( $this, 'ubcar_comments_callback' ) ), $comments );
			echo '</ol>';
			die();
		}
		
		 /**
		 * This is the helper function for retrieving an ubcar_media datum from
		 * the database.
		 * 
		 * @param int $ubcar_media_id
		 * 
		 * @access public
		 * @return array
		 */
		function ubcar_get_media( $ubcar_media_id ) {
			$ubcar_media = get_post( $ubcar_media_id );
			if( $ubcar_media == null ) {
				return;
			}
			$ubcar_media_meta = get_post_meta( $ubcar_media->ID, 'ubcar_media_meta', true );
			if( $ubcar_media_meta['hidden'] == 'on' || $ubcar_media_meta == null ) {
				return;
			}
			$ubcar_media_author = get_user_by( 'id', $ubcar_media->post_author );
			$tempArray = array();
			$tempArray["ID"] = $ubcar_media->ID;
			$tempArray["type"] = $ubcar_media_meta['type'];
			if( $ubcar_media_meta['type'] == 'image' || $ubcar_media_meta['type'] == 'imagewp' ) {
				$tempArray["image"] = wp_get_attachment_image( $ubcar_media_meta['url'], 'medium' );
				$tempArray["full_size_url"] = wp_get_attachment_url( $ubcar_media_meta['url'] );
			} else {
				$tempArray["url"] = $ubcar_media_meta['url'];
			}
			$tempArray["uploader"] = $ubcar_media_author->first_name . ' ' . $ubcar_media_author->last_name . ' ( ' . $ubcar_media_author->user_login . ' )';
			$tempArray["title"] = $ubcar_media->post_title;
			$tempArray["date"] = date( 'F j, Y', strtotime( get_the_date( 'Y-m-d', $ubcar_media->ID ) ) );
			$tempArray["description"] = $ubcar_media->post_content;
			$ubcar_media_layer_names = array();
			if( $ubcar_media_meta['layers'] != null ) {
				foreach( $ubcar_media_meta['layers'] as $ubcar_media_layer ) {
					$layer = get_post( $ubcar_media_layer );
					if( $layer != null ) {
						$inner_temp_array = array();
						$inner_temp_array["ID"] = $layer->ID;
						$inner_temp_array["title"] = $layer->post_title;
						array_push( $ubcar_media_layer_names, $inner_temp_array );
					}
				}
			}
			$tempArray["layers"] = $ubcar_media_layer_names;
			return $tempArray;
		}
		
	}
?>
