<?php
	/**
	 * The UBCAR_Data_KML subclass
	 * 
	 * This file defines the UBCAR_Data_KML subclass. The UBCAR_Data_KML
	 * class produces KML-formatted ubcar_point data upon GET request. It
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
	 * The UBCAR_Data_KML subclass
	 */
	class UBCAR_Data_KML extends UBCAR_Data {
	
		/**
		 * The UBCAR_Data_KML constructor.
		 * 
		 * @access public
		 * @return void
		 */
		public function __construct() {
			$this->add_actions();
		}
		
		/**
		 * This function adds the UBCAR_Data_KML actions and filters.
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
			$query_vars[] = 'ubcar_download_kml';
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
			if( array_key_exists( 'ubcar_download_kml', $wp->query_vars ) ) {
				$this->ubcar_download_kml();
				exit;
			}
		}

		/**
		 * This function assembles and echoes the KML file.
		 * 
		 * @access public
		 * @return void
		 */
		function ubcar_download_kml() {
			$this->ubcar_download_kml_headers();
			$this->ubcar_download_kml_body();
			exit;
		}
		
		/**
		 * This is the helper function for assembling a KML file's headers,
		 * assigning a random suffix to the filename to preclude Google caching.
		 * 
		 * @access public
		 * @return void
		 */
		function ubcar_download_kml_headers() {
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
		 * This is the helper function for assembling a KML file's body.
		 * 
		 * @access public
		 * @return void
		 */
		function ubcar_download_kml_body() {
			echo '<?xml version="1.0" encoding="utf-8"?>';
			echo "\n";
			echo '<kml xmlns="http://www.opengis.net/kml/2.2"';
			echo "\n";
			echo 'xmlns:ar="http://www.openarml.org/arml/1.0"';
			echo "\n";
			echo 'xmlns:wikitude="http://www.openarml.org/wikitude/1.0">';
			echo "\n";
			echo '	  <Document>';
			echo "\n";
			echo '	  	  <ar:provider id="UBCAR">';
			echo "\n";
			echo '	  	  	  <ar:name>UBCAR Test Points</ar:name>';
			echo "\n";
			echo '	  	  	  <ar:description>UBCAR sample points</ar:description>';
			echo "\n";
			echo '	  	  	  <wikitude:providerUrl>http://www.sidl.es/</wikitude:providerUrl>';
			echo "\n";
			echo '	  	  	  <wikitude:logo>http://localhost/layar/wp-content/uploads/2015/04/Eagle.jpg</wikitude:logo>';
			echo "\n";
			echo '	   	  </ar:provider>';
			echo "\n";
			$this->ubcar_download_kml_body_points();
			echo '	  </Document>';
			echo "\n";
			echo '</kml>';
		}

		/**
		 * This is the helper function for assembling the KML file's individual
		 * points.
		 * 
		 * @access public
		 * @return void
		 */
		function ubcar_download_kml_body_points() {
			$ubcar_points = $this->ubcar_retrieve_points();
			$temp_counter = 0;
			foreach( $ubcar_points as $ubcar_point ) {
				if( !( $ubcar_point['longitude'] == 0 && $ubcar_point['longitude'] == 0 ) ) {
					echo '		  <Placemark id="' . $temp_counter . '">';
					echo "\n";
					echo "			  <ar:provider>UBCAR</ar:provider>";
					echo "\n";
					echo "			  <name>" . htmlspecialchars( $ubcar_point['title'] ) . " (Point " . htmlspecialchars( $ubcar_point['ID'] ) . ")</name>";
					echo "\n";
//					echo "			  <point_id>" . number_format( ( int )$ubcar_point['ID'] ) . "</point_id>";
//					echo "\n";
					echo "			  <description>" . $ubcar_point['description'] . "</description>";
					echo "\n";
					echo "			  <wikitude:info>";
					echo "\n";
					if( array_key_exists( 'thumbnail', $ubcar_point ) ) {
						echo "			  <wikitude:thumbnail>" . $ubcar_point['thumbnail'] . "</wikitude:thumbnail>";
						echo "\n";
					}
					echo "			  </wikitude:info>";
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
				$temp_counter++;
			}
		}

		/**
		 * This is the helper function for retrieving ubcar_point data for the
		 * KML file, including a custom SQL query for tag searching.
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
					$temp_inner_array['description'] = $temp_point->post_content;
					$temp_thumbnails = get_post_meta( $temp_point->ID, 'ubcar_point_media', true );
					if( !empty( $temp_thumbnails ) ) {
						$temp_media_meta = get_post_meta( $temp_thumbnails[0], 'ubcar_media_meta', true );
						if( $temp_media_meta != "" ) {
							$temp_media_type = $temp_media_meta['type'];
							if( $temp_media_type == 'image' ) {
								$temp_media_url = wp_get_attachment_url( $temp_media_meta['url'] );
								$temp_inner_array['thumbnail'] = $temp_media_url;
							}
						}
					}
					array_push( $ubcar_points, $temp_inner_array );
				}
			}
			return $ubcar_points;
		}

	}
	
?>
