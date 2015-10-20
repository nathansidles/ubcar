<?php

	/**
	 * The UBCAR_Data_JSON class
	 *
	 * This file defines the UBCAR_Data_JSONsubclass. The
	 * UBCAR_Data_JSON subclass produces JSON-formatted data about
	 * ubcar_points and associated data.
	 *
	 * @package UBCAR
	 */

	/**
	 * The UBCAR_Data_JSON class.
	 */
	class UBCAR_Data_JSON extends UBCAR_Data {

	   // TODO: create JSON data for individual ubcar_point and associated data

		/**
		 * The UBCAR_Data_JSON constructor.
		 *
		 * @access public
		 * @return void
		 */
		public function __construct() {
			$this->add_actions();
		}

        /**
         * This function adds the UBCAR_Data_JSON actions and filters.
         *
         * @access public
         * @return void
         */
        function add_actions() {
            add_filter( 'query_vars', array( $this, 'query_vars' ) );
            add_action( 'parse_request', array( $this, 'parse_request' ) );
        }

        /**
         * This function adds the desired UBCAR query variable to the query_vars
         * array
         *
         * @param array $query_vars
         *
         * @access public
         * @return array
         */
        function query_vars( $query_vars ) {
            $query_vars[] = 'ubcar_download_json';
            return $query_vars;
        }

        /**
         * This function sets the behavior to be performed if the UBCAR query
         * variable is set in a request.
         *
         * @param object $wp
         *
         * @access public
         * @return void
         */
        function parse_request( $wp ) {
            if( array_key_exists( 'ubcar_download_json', $wp->query_vars ) ) {
                $test = $this->ubcar_determine_request();
                wp_send_json( $test );
                exit;
            }
        }

        function ubcar_determine_request() {
            if( isset( $_GET['ubcar_aggregates'] ) ) {
                return $this->ubcar_retrieve_aggregates();
            } else if( isset( $_GET['ubcar_points_min'] ) ) {
                return $this->ubcar_retrieve_points_min();
            } else {
                return $this->ubcar_retrieve_points();
            }

        }

        /**
         * This is the helper function for retrieving ubcar_point data for the
         * JSON object, including a custom SQL query for tag searching.
         *
         * @access public
         * @return array
         */
        function ubcar_retrieve_points() {
            $ubcar_points = array();
            if( isset( $_GET['ubcar_layers'] ) && is_array( $_GET['ubcar_layers'] ) ) {
                foreach( $_GET['ubcar_layers'] as $ubcar_layer_id ) {
                    $ubcar_point_metas = get_post_meta( $ubcar_layer_id, 'ubcar_layer_points', true );
                    if( $ubcar_point_metas != null ) {
                        foreach( $ubcar_point_metas as $ubcar_point_meta ) {
                            $temp_inner_array = array();
                            $temp_point = get_post( $ubcar_point_meta[1] );
                            if( $temp_point != null ) {
                                $temp_inner_array['id'] = $temp_point->ID;
                                $temp_inner_array['title'] = $temp_point->post_title;
                                $temp_inner_array['longitude'] = get_post_meta( $ubcar_point_meta[1], 'ubcar_point_longitude', true );
                                $temp_inner_array['latitude'] = get_post_meta( $ubcar_point_meta[1], 'ubcar_point_latitude', true );
																$temp_inner_array['description'] = $temp_point->post_content;
																$temp_inner_array['name'] = $temp_point->post_title;
																$temp_inner_array['distance'] = 0;
																$temp_inner_array['bearing'] = 0;
																array_push( $ubcar_points, $temp_inner_array );
                            }
                        }
                    }
                }
            } else if( isset( $_GET['ubcar_tours'] ) && is_array( $_GET['ubcar_tours'] ) ) {
                foreach( $_GET['ubcar_tours'] as $ubcar_tour_id ) {
                    $ubcar_point_metas = get_post_meta( $ubcar_tour_id, 'ubcar_tour_locations', true );
                    if( $ubcar_point_metas != null ) {
                        foreach( $ubcar_point_metas as $ubcar_point_meta ) {
                            $temp_inner_array = array();
                            $temp_point = get_post( $ubcar_point_meta );
                            $temp_inner_array['id'] = $temp_point->ID;
                            $temp_inner_array['title'] = $temp_point->post_title;
                            $temp_inner_array['longitude'] = get_post_meta( $ubcar_point_meta, 'ubcar_point_longitude', true );
                            $temp_inner_array['latitude'] = get_post_meta( $ubcar_point_meta, 'ubcar_point_latitude', true );
														$temp_inner_array['description'] = $temp_point->post_content;
														$temp_inner_array['name'] = $temp_point->post_title;
														$temp_inner_array['distance'] = 0;
														$temp_inner_array['bearing'] = 0;
														array_push( $ubcar_points, $temp_inner_array );
                        }
                    }
                }
            } else if( isset( $_GET['ubcar_search'] ) ) {
                global $wpdb;
                $ubcar_meta_field = 'ubcar_point_tags';
                $ubcar_search_results = $wpdb->get_results( $wpdb->prepare(
                                                                           "SELECT post_id FROM $wpdb->postmeta
                                                                           WHERE meta_key = '%s'
                                                                           AND meta_value LIKE '%%%s%%'",
                                                                           $ubcar_meta_field,
                                                                           urldecode( $_GET['ubcar_search'] )
                                                                           ) );
                if( $ubcar_search_results != null ) {
                    foreach( $ubcar_search_results as $ubcar_point_element ) {
                        $temp_inner_array = array();
                        $ubcar_point = $ubcar_point_element->post_id;
                        $temp_point = get_post( $ubcar_point );
                        $temp_inner_array['id'] = $temp_point->ID;
                        $temp_inner_array['name'] = $temp_point->post_title;
                        $temp_inner_array['longitude'] = get_post_meta( $ubcar_point, 'ubcar_point_longitude', true );
                        $temp_inner_array['latitude'] = get_post_meta( $ubcar_point, 'ubcar_point_latitude', true );
												$temp_inner_array['description'] = $temp_point->post_content;
												$temp_inner_array['name'] = $temp_point->post_title;
												$temp_inner_array['distance'] = 0;
												$temp_inner_array['bearing'] = 0;
												array_push( $ubcar_points, $temp_inner_array );
                    }
                }
            } else {
                $args = array( 'posts_per_page' => -1, 'post_type' => 'ubcar_point' );
                $all_ubcar_points = get_posts( $args );
                foreach( $all_ubcar_points as $temp_point ) {
                    $temp_inner_array = array();
                    $temp_inner_array['id'] = $temp_point->ID;
                    $temp_inner_array['longitude'] = get_post_meta( $temp_point->ID, 'ubcar_point_longitude', true );
                    $temp_inner_array['latitude'] = get_post_meta( $temp_point->ID, 'ubcar_point_latitude', true );
                    $temp_inner_array['description'] = $temp_point->post_content;
                    $temp_inner_array['name'] = $temp_point->post_title;
										$temp_inner_array['distance'] = 0;
										$temp_inner_array['bearing'] = 0;
                    $temp_thumbnails = get_post_meta( $temp_point->ID, 'ubcar_point_media', true );
                    if( !empty( $temp_thumbnails ) ) {
                        $temp_media = array();
                        foreach( $temp_thumbnails as $temp_thumbnail ) {
                            $temp_media_meta = get_post_meta( $temp_thumbnail, 'ubcar_media_meta', true );
                            if( $temp_media_meta != "" ) {
                                $temp_media_type = $temp_media_meta['type'];
                                if( $temp_media_type == 'image' ) {
                                    $temp_media_url['url'] = wp_get_attachment_url( $temp_media_meta['url'] );
                                    array_push( $temp_media, $temp_media_url );
                                }
                            }
                        }
                        $temp_inner_array['media'] = $temp_media;
                    }
                    array_push( $ubcar_points, $temp_inner_array );
                }
            }
            return $ubcar_points;
        }

        function ubcar_retrieve_aggregates() {
            $ubcar_aggregates = array();

            $ubcar_layers = array();
            $args = array( 'posts_per_page' => -1, 'post_type' => 'ubcar_layer' );
            $all_ubcar_points = get_posts( $args );
            foreach( $all_ubcar_points as $temp_point ) {
                $temp_inner_array = array();
                $temp_inner_array['id'] = $temp_point->ID;
                $temp_inner_array['name'] = $temp_point->post_title;
                $temp_inner_array['description'] = $temp_point->post_content;
                array_push( $ubcar_layers, $temp_inner_array );
            }
            $ubcar_layers_label = array();
            $ubcar_layers_label['type_id'] = 'layers';
            $ubcar_layers_label['aggregates'] = $ubcar_layers;
            array_push( $ubcar_aggregates, $ubcar_layers_label );

            $ubcar_tours = array();
            $args = array( 'posts_per_page' => -1, 'post_type' => 'ubcar_tour' );
            $all_ubcar_points = get_posts( $args );
            foreach( $all_ubcar_points as $temp_point ) {
                $temp_inner_array = array();
                $temp_inner_array['id'] = $temp_point->ID;
                $temp_inner_array['name'] = $temp_point->post_title;
                $temp_inner_array['description'] = $temp_point->post_content;
                array_push( $ubcar_tours, $temp_inner_array );
            }
            $ubcar_tours_label = array();
            $ubcar_tours_label['type_id'] = 'tours';
            $ubcar_tours_label['aggregates'] = $ubcar_tours ;
            array_push( $ubcar_aggregates, $ubcar_tours_label );

            return $ubcar_aggregates;
        }

	}

?>
