<?php
	/**
	 * The UBCAR_DATA_GeoJSON subclass
	 * 
	 * This file defines the UBCAR_DATA_GeoJSON subclass. The UBCAR_DATA_GeoJSON
	 * class produces GeoJSON-formatted ubcar_point data upon GET request. It
	 * accepts the following fields:
	 * 
	 * - ubcar_layers[]: ubcar_layer IDs
	 * - ubcar_tours[]:  ubcar_tour IDs
	 * - ubcar_search[]: text strings to be searched
	 * 
	 * If no valid field is selected, all points are returned. The ubcar_search
	 * field retrieves data with a custom SQL query.
	 * 
	 * @package UBCAR
	 */

	/*
	 * The UBCAR_DATA_GeoJSON subclass
	 */
	class UBCAR_Data_GeoJSON extends UBCAR_Data {
	
		/**
		 * The UBCAR_DATA_GeoJSON constructor.
		 * 
		 * @access public
		 * @return void
		 */
		public function __construct() {
			$this->add_actions();
		}
		
		/**
		 * This function adds the UBCAR_DATA_GeoJSON actions and filters.
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
			$query_vars[] = 'ubcar_download_geojson';
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
			if( array_key_exists( 'ubcar_download_geojson', $wp->query_vars ) ) {
				$this->ubcar_download_geojson();
				exit;
			}
		}

		/**
		 * This function assembles and echoes the GeoJSON file.
		 * 
		 * @access public
		 * @return void
		 */
		function ubcar_download_geojson() {
			$this->ubcar_download_geojson_headers();
			$this->ubcar_download_geojson_body();
			exit;
		}
		
		/**
		 * This is the helper function for assembling a GeoJSON file's headers,
		 * assigning a random suffix to the filename to preclude Google caching.
		 * 
		 * @access public
		 * @return void
		 */
		function ubcar_download_geojson_headers() {
			header( "Pragma: public" );
			header( "Expires: 0" );
			header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
			header( "Cache-Control: private", false );
			header( "Content-Type: application/octet-stream" );
			$randomized_name = "Content-Disposition: attachment; filename=\"ubcar_download_kml-" . rand() . ".kml\";";
			header( $randomized_name );
			header( "Content-Transfer-Encoding: binary" );
		}
		
		/**
		 * This is the helper function for assembling a GeoJSON file's body.
		 * 
		 * @access public
		 * @return void
		 */
		function ubcar_download_geojson_body() {
			echo '<?xml version="1.0" encoding="utf-8"?>';
			echo "\n";
			echo '<kml xmlns="http://www.opengis.net/kml/2.2">';
			echo "\n";
			echo '	  <Document>';
			echo "\n";
			$this->ubcar_download_kml_body_points();
			echo '	  </Document>';
			echo "\n";
			echo '</kml>';
		}

		/**
		 * This is the helper function for assembling the GeoJSON file's individual
		 * points.
		 * 
		 * @access public
		 * @return void
		 */
		function ubcar_download_kml_body_points() {
			$ubcar_points = $this->ubcar_retrieve_points();
			foreach( $ubcar_points as $ubcar_point ) {
				if( !( $ubcar_point['longitude'] == 0 && $ubcar_point['longitude'] == 0 ) ) {
					echo "		  <Placemark>";
					echo "\n";
					echo "			  <name>" . htmlspecialchars( $ubcar_point['title'] ) . " ( Point " . htmlspecialchars( $ubcar_point['ID'] ) . " ) </name>";
					echo "\n";
					echo "			  <description>" . number_format( ( int )$ubcar_point['ID'] ) . "</description>";
					echo "\n";
					echo "			  <Point>";
					echo "\n";
					echo "				  <coordinates>" . number_format( ( float )$ubcar_point['longitude'], 7, '.', '' ) . "," . number_format( ( float )$ubcar_point['latitude'], 7, '.', '' ) . "</coordinates>";
					echo "\n";
					echo "			  </Point>";
					echo "\n";
					echo "		  </Placemark>";
					echo "\n";
				}
			}
		}

		/**
		 * This is the helper function for retrieving ubcar_point data for the
		 * GeoJSON file, including a custom SQL query for tag searching.
		 * 
		 * @access public
		 * @return array
		 */
		function ubcar_retrieve_points() {
			$ubcar_points = array();
			if( isset( $_GET['ubcar_layers'] ) ) {
				foreach( $_GET['ubcar_layers'] as $ubcar_layer_id ) {
					$ubcar_point_metas = get_post_meta( $ubcar_layer_id, 'ubcar_layer_points', true );
					if( $ubcar_point_metas != null ) {
						foreach( $ubcar_point_metas as $ubcar_point_meta ) {
							$temp_inner_array = array();
							$temp_point = get_post( $ubcar_point_meta[1] );
							if( $temp_point != null ) {
								$temp_inner_array['ID'] = $temp_point->ID;
								$temp_inner_array['title'] = $temp_point->post_title;
								$temp_longitude = get_post_meta( $ubcar_point_meta[1], 'ubcar_point_longitude', true );
								$temp_inner_array['longitude'] = get_post_meta( $ubcar_point_meta[1], 'ubcar_point_longitude', true );
								$temp_inner_array['latitude'] = get_post_meta( $ubcar_point_meta[1], 'ubcar_point_latitude', true );
								array_push( $ubcar_points, $temp_inner_array );
							}
						}
					}
				}
			} else if( isset( $_GET['ubcar_tours'] ) ) {
				foreach( $_GET['ubcar_tours'] as $ubcar_tour_id ) {
					$ubcar_point_metas = get_post_meta( $ubcar_tour_id, 'ubcar_tour_locations', true );
					if( $ubcar_point_metas != null ) {
						foreach( $ubcar_point_metas as $ubcar_point_meta ) {
							$temp_inner_array = array();
							$temp_point = get_post( $ubcar_point_meta );
							$temp_inner_array['ID'] = $temp_point->ID;
							$temp_inner_array['title'] = $temp_point->post_title;
							$temp_inner_array['longitude'] = get_post_meta( $ubcar_point_meta, 'ubcar_point_longitude', true );
							$temp_inner_array['latitude'] = get_post_meta( $ubcar_point_meta, 'ubcar_point_latitude', true );
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
						$temp_inner_array['ID'] = $temp_point->ID;
						$temp_inner_array['title'] = $temp_point->post_title;
						$temp_inner_array['longitude'] = get_post_meta( $ubcar_point, 'ubcar_point_longitude', true );
						$temp_inner_array['latitude'] = get_post_meta( $ubcar_point, 'ubcar_point_latitude', true );
						array_push( $ubcar_points, $temp_inner_array );
					}
				}
			} else {
				$args = array( 'posts_per_page' => -1, 'post_type' => 'ubcar_point' );
				$all_ubcar_points = get_posts( $args );
				foreach( $all_ubcar_points as $temp_point ) {
					$temp_inner_array = array();
					$temp_inner_array['ID'] = $temp_point->ID;
					$temp_inner_array['title'] = $temp_point->post_title;
					$temp_inner_array['longitude'] = get_post_meta( $temp_point->ID, 'ubcar_point_longitude', true );
					$temp_inner_array['latitude'] = get_post_meta( $temp_point->ID, 'ubcar_point_latitude', true );
					array_push( $ubcar_points, $temp_inner_array );
				}
			}
			return $ubcar_points;
		}

	}
	
?>
