jQuery( document ).ready(function() {

	var requestedLatLng, mapOptions, map, ubcarMap;

	requestedLatLng = new google.maps.LatLng( 49.2683366, -123.2550359 );
	mapOptions = {
		zoom: 10,
		center: requestedLatLng,
		streetViewControl: false,
		mapTypeId: google.maps.MapTypeId.SATELLITE
	};

	map = new google.maps.Map( document.getElementById( 'ubcar-map-canvas' ), mapOptions );

	var ubcarMap = new UBCARMap( map );

	jQuery( '#ubcar-layers-form input' ).click(function() {
		jQuery( '#ubcar-tours-form input' ).prop( 'checked', false );
		ubcarMap.retrievePoints( jQuery( this ).attr( 'id' ), 'ubcar_layer' );
	});

	jQuery( '#ubcar-tours-form input' ).click(function() {
		jQuery( '#ubcar-layers-form input' ).prop( 'checked', false );
		ubcarMap.retrievePoints( jQuery( this ).attr( 'id' ), 'ubcar_tour' );
	});

	jQuery( '#ubcar-search-button' ).click(function() {
		jQuery( '#ubcar-tours-form input' ).prop( 'checked', false );
		jQuery( '#ubcar-layers-form input' ).prop( 'checked', false );
		ubcarMap.retrievePoints( escapeHTML( jQuery( '#ubcar-search-input' ).val() ), 'ubcar_search' );
	});

	jQuery( '#ubcar-display-choice-map' ).click(function() {
		jQuery( '#ubcar-map-canvas' ).show();
		jQuery( '#ubcar-streetview-canvas' ).hide();
		jQuery( '#ubcar-display-fullscreen' ).removeClass( 'ubcar-display-fullscreen-move' );
		jQuery( '#ubcar-display-choice-map' ).removeClass( 'ubcar-display-choice-map-move' );
		jQuery( '#ubcar-display-choice-street' ).removeClass( 'ubcar-display-choice-street-move' );
	});

	jQuery( '#ubcar-show-all' ).click(function() {
		jQuery( '#ubcar-tours-form input' ).prop( 'checked', false );
		jQuery( '#ubcar-layers-form input' ).prop( 'checked', false );
		ubcarMap.retrievePoints( 0, 'all' );
	});

	jQuery( '#ubcar-display-fullscreen' ).toggle(function() {
		jQuery( 'body' ).prepend( jQuery( '.ubcar-content' ) );
		jQuery( '.ubcar-content' ).css( 'position', 'fixed' );
		jQuery( '.ubcar-content' ).css( 'left', '0px' );
		jQuery( '.ubcar-content' ).css( 'top', '0px' );
		jQuery( '.ubcar-content' ).css( 'width', '100%' );
		jQuery( '.ubcar-content' ).css( 'height', '100%' );
		jQuery( '.ubcar-content' ).css( 'z-index', '99999999999' ); // this may be higher than strictly necessary
		jQuery( window ).scrollTop( 0 );
		jQuery( '#ubcar-display-fullscreen' ).html( 'Regular' );
	},
	function() {
		jQuery( '.entry-content' ).prepend( jQuery( '.ubcar-content' ) );
		jQuery( '.ubcar-content' ).css( 'position', 'relative' );
		jQuery( '.ubcar-content' ).css( 'height', '800px' );
		jQuery( '#ubcar-display-fullscreen' ).html( 'Fullscreen' );
		jQuery( window ).scrollTop( jQuery( '.ubcar-content' ).offset().top - 40 );
	});

	// Test to see if the one-pane, unified data display option is selected in full-width display mode
	if( jQuery( '#ubcar-point-media' ).html() === undefined &&  jQuery( '#ubcar-header-aggregate' ).css( 'bottom' ) !== 'auto' ) {
		jQuery( '#ubcar-header-aggregate' ).css( 'bottom', '270px' );
		jQuery( '#ubcar-header-information' ).css( 'bottom', '225px' );
	}

	ubcarMap.requestDetector();

});

function UBCARMap( map ) {

	var mapInstance, mapInfowindow, objectInstance, ubcarPoints, data, tempData;

	mapInstance = map
	mapInfowindow = new google.maps.InfoWindow( { pixelOffset: new google.maps.Size( 0, -35 ) });
	objectInstance = this;
	ubcarPoints = []; // set of previously retrieved points
	this.activeType = 'ubcar_layer'; // activeType shoudl be ubcar_layer, ubcar_tour, ubcar_search, or all


	/**
	 * AJAX call to class-ubcar-view-map.php's ubcar_get_aggregate_points(),
	 * then displaying the points of the new layer, tour, search, or all
	 * points, removing points if they are not of the currently selected kind.
	 *
	 * @param {String} aggregate_id - The object id or search string.
	 * @param {String} selected_type - The type of collection being displayed.
	 */
	this.retrievePoints = function( aggregate_id, selected_type ) {
		if( objectInstance.activeType != selected_type || selected_type === 'ubcar_search' ) {
			for( i in ubcarPoints ) {
				if( ubcarPoints[ i ].active === true ) {
					objectInstance.hidePoints( i );
					objectInstance.hideAggregateInformation( i );
				}
			}
		}
		objectInstance.activeType = selected_type;
		if( ubcarPoints[ aggregate_id ] == null || ( ubcarPoints[ aggregate_id ].type === 'ubcar_search' && selected_type != 'ubcar_search' ) || ( ubcarPoints[ aggregate_id ].type != 'ubcar_search' && selected_type === 'ubcar_search' ) ) {
			data = {
				'action': 'ubcar_aggregate_points_retriever',
				'ubcar_aggregate_id': aggregate_id,
				'ubcar_aggregate_type': selected_type
			};
			jQuery.post( ajax_object.ajax_url, data, function( response ) {
				tempData = new google.maps.Data();
				tempData.addGeoJson( response.geojson );
				ubcarPoints[ aggregate_id ] = {
					'type': selected_type,
					'active': true,
					'raw_data': response,
					'map_data': tempData
				};
				objectInstance.displayPoints( aggregate_id );
			});
		} else {
			if( ubcarPoints[ aggregate_id ].active === false ) {
				objectInstance.displayPoints( aggregate_id );
			} else if( selected_type != 'all' ) {
				objectInstance.hidePoints( aggregate_id );
			}
		}
	}

	/**
	 * Helper function to retrievePoints(), formatting the points and
	 * putting them onto the displayed map.
	 *
	 * @param {String} aggregate_id - The object id ( id may be a search string ).
	 */
	this.displayPoints = function( aggregate_id ) {

		var anchor;

		ubcarPoints[ aggregate_id ].map_data.setMap( mapInstance );
		ubcarPoints[ aggregate_id ].active = true;
		if( ubcarPoints[ aggregate_id ].type != 'ubcar_search' ) {
			objectInstance.retrieveAggregateInformation( aggregate_id );
		}
		if( ubcarPoints[ aggregate_id ].type === 'ubcar_tour' ) {
			objectInstance.displayRoute( aggregate_id );
		}
		objectInstance.resizeMap();
		ubcarPoints[ aggregate_id ].map_data.addListener( 'mouseover', function( event ) {
			mapInfowindow.setContent( '<div style="text-align: center;">' + event.feature.getProperty( 'title' ) + ' (#' + event.feature.getProperty( 'id' ) + ')</div>' );
			anchor = new google.maps.MVCObject();
			anchor.set( 'position', event.latLng );
			mapInfowindow.open( mapInstance, anchor );
		});
//		ubcarPoints[ aggregate_id ].map_data.addListener( 'mouseout', function( event ) {
//			mapInfowindow.close();
//		});
		ubcarPoints[ aggregate_id ].map_data.addListener( 'click', function( event ) {
			objectInstance.retrievePoint( event.feature.getProperty( 'id' ) );
		});
	}

	/**
	 * Helper function to displayPoints(), displaying Google Maps
	 * directions if an ubcar_tour is being displayed.
	 *
	 * @param {String} aggregate_id - The object id.
	 */
	this.displayRoute = function( aggregate_id ) {

		var coordinates, lastIndex;

		if( ubcarPoints[ aggregate_id ].raw_data.geojson != null ) {
			if( ubcarPoints[ aggregate_id ].route_data == null ) {

				coordinates = ubcarPoints[ aggregate_id ].raw_data.geojson.features;
				lastIndex = coordinates.length-1;
				ubcarPoints[ aggregate_id ].route_data = [];

				objectInstance.retrieveRouteRecursive( aggregate_id, 0, lastIndex, coordinates );

			} else {
				for( i in ubcarPoints[ aggregate_id ].route_data ) {
					ubcarPoints[ aggregate_id ].route_data[ i ].setMap( mapInstance );
				}
			}
		}
	}

	/**
	 * Helper function to displayRoute(), retrieving Google Maps
	 * directions. This function is recursively called in the Google
	 * route requester's callback function, allowing more than ten
	 * points in a single route be displayed.
	 *
	 * @param {String} aggregate_id - The object id.
	 * @param {String} start_index - The starting index of the object's points to be routed.
	 * @param {String} lastIndex - The last index of the object's points to be routed.
	 * @param {Object} coordinates - The GeoJSON object associated with the object id.
	 */
	this.retrieveRouteRecursive = function( aggregate_id, start_index, lastIndex, coordinates ) {
		var tempLastIndex, firstCoordinate, lastCoordinate, waypoints, tempLatlng, directionsDisplay, directionsService, request;

		var tempLastIndex = start_index + 9;
		if( lastIndex <= tempLastIndex ) {
			tempLastIndex = lastIndex;
		}
		firstCoordinate = new google.maps.LatLng( coordinates[ start_index ].geometry.coordinates[ 1 ], coordinates[ start_index ].geometry.coordinates[ 0 ] );
		lastCoordinate = new google.maps.LatLng( coordinates[ tempLastIndex ].geometry.coordinates[ 1 ], coordinates[ tempLastIndex ].geometry.coordinates[ 0 ] );
		waypoints = [];
		for( var j = start_index + 1; j < tempLastIndex; j++ ) {
			var tempLatlng = new google.maps.LatLng( coordinates[ j ].geometry.coordinates[ 1 ], coordinates[ j ].geometry.coordinates[ 0 ] );
			waypoints[ j-1-start_index ] = { location:tempLatlng, stopover:true };
		}
		directionsDisplay = new google.maps.DirectionsRenderer( { suppressMarkers : true, preserveViewport: true });
		directionsService = new google.maps.DirectionsService();
		request = {
			origin: firstCoordinate,
			destination: lastCoordinate,
			waypoints: waypoints,
			optimizeWaypoints: false,
			travelMode: google.maps.TravelMode.WALKING
		}
		directionsService.route( request, function( response, status ) {
			if ( status === google.maps.DirectionsStatus.OK ) {
				directionsDisplay.setDirections( response );
			}
			directionsDisplay.setMap( mapInstance );
			ubcarPoints[ aggregate_id ].route_data.push( directionsDisplay );
			if( lastIndex === tempLastIndex ) {
				return;
			} else {
				objectInstance.retrieveRouteRecursive( aggregate_id, start_index + 9, lastIndex, coordinates );
			}
		});
	}

	/**
	 * Helper function to retrievePoints(), removing an object
	 * from the map.
	 *
	 * @param {String} aggregate_id - The object id.
	 */
	this.hidePoints = function( aggregate_id ) {
		ubcarPoints[ aggregate_id ].map_data.setMap( null );
		ubcarPoints[ aggregate_id ].active = false;
		objectInstance.hideAggregateInformation( aggregate_id );
		objectInstance.resizeMap();
		if( ubcarPoints[ aggregate_id ].route_data != null ) {
			for( i in ubcarPoints[ aggregate_id ].route_data ) {
				ubcarPoints[ aggregate_id ].route_data[ i ].setMap( null );
			}
		}
		mapInfowindow.close();
	}

	/**
	 * AJAX call to class-ubcar-view-map.php's ubcar_get_aggregate_information(),
	 * then displaying the information of the new layer or tour.
	 *
	 * @param {String} aggregate_id - The object id.
	 */
	this.retrieveAggregateInformation = function( aggregate_id ) {

		var data;

		if( aggregate_id != 'all_tours' && aggregate_id != 'all_layers' ) {
			data = {
				'action': 'ubcar_aggregate_information_retriever',
				'ubcar_aggregate_id': aggregate_id
			};
			jQuery.post( ajax_object.ajax_url, data, function( response ) {
				objectInstance.displayAggregateInformation( response );
			});
		}
	}

	/**
	 * Helper function to retrieveAggregateInformation(), formatting and
	 * displaying the AJAX response.
	 *
	 * @param {Object} response - The AJAX response to display.
	 */
	this.displayAggregateInformation = function( response ) {

		var htmlString, pointID, latitude, longitude, myLatlng, maxZoomService;

		htmlString = '<div id="ubcar-aggregate-description-' + response.id + '">';
		if( response.title != null ) {
			htmlString += '<p><strong>' + response.title + ' (#' + response.id + ')</strong></p>';
		}
		if( response.description != null ) {
			htmlString += '<p>' + response.description + '</p>';
		}
		if( response.type === 'ubcar_tour' && response.points != null ) {
			htmlString += '<p>Points:</p>';
			htmlString += '<ol>';
			for( i in response.points ) {
				htmlString += '<li id="ubcar-aggregate-point-' + response.points[ i ].id + '">'
				htmlString += response.points[ i ].title + ' (Point #' + response.points[ i ].id + ')';
				htmlString += '<input hidden name="latitude" value="' + response.points[ i ].latitude + '" />';
				htmlString += '<input hidden name="longitude" value="' + response.points[ i ].longitude + '" />';
				htmlString += '</li>';
			}
			htmlString += '</ol>';
		}
		if( response.id != null ) {
			htmlString += '<hr />';
			htmlString += '</div>';
			jQuery( '#ubcar-body-aggregate' ).append( htmlString );
			jQuery( '#ubcar-header-aggregate' ).css( 'background', '#002145' );
		}

		jQuery( document ).on( 'click', '#ubcar-aggregate-description-' + response.id + ' ol li', function() {

			jQuery( '#ubcar-body-aggregate > div ol li' ).css( 'background', 'white' );
			jQuery( '#ubcar-aggregate-description-' + response.id + ' ol li' ).css( 'background', 'white' );
			jQuery( this ).css( 'background', '#DEDEDE' );
			jQuery( '#ubcar-body-aggregate' ).animate( {scrollTop: jQuery( this ).position().top + jQuery( '#ubcar-body-aggregate' ).scrollTop() - 20 }, 500, 'swing' );

			pointID = jQuery( this ).attr( 'id' ).replace( 'ubcar-aggregate-point-', '' );
			objectInstance.retrievePoint( pointID );

			latitude = parseFloat( jQuery( this ).children( 'input[ name=latitude ]' ).val() );
			longitude = parseFloat( jQuery( this ).children( 'input[ name=longitude ]' ).val() );
			myLatlng = new google.maps.LatLng( latitude, longitude );
			mapInstance.panTo( myLatlng );
			maxZoomService = new google.maps.MaxZoomService();
			maxZoomService.getMaxZoomAtLatLng( myLatlng, function( max_zoom ){
				mapInstance.setZoom( max_zoom.zoom - 2 );
			});

		});
	}

	/**
	 * Helper function to retrievePoints() and hidePoints(), removing
	 * an object from the map.
	 *
	 * @param {String} aggregate_id - The object id.
	 */
	this.hideAggregateInformation = function( aggregate_id ) {
		jQuery( '#ubcar-aggregate-description-' + aggregate_id ).remove();
		jQuery( document ).off( 'click', '#ubcar-layers-form input' );
		jQuery( document ).off( 'click', '#ubcar-aggregate-description-' + aggregate_id + ' ol li' );
		if( jQuery( '#ubcar-body-aggregate' ).html() === '' ) {
			jQuery( '#ubcar-body-aggregate' ).hide();
			jQuery( '#ubcar-header-aggregate' ).html( 'Layer/Tour Information' );
			jQuery( '#ubcar-header-aggregate' ).css( 'background', '#DEDEDE' );
		}
	}

	/**
	 * AJAX call to class-ubcar-view-map.php's ubcar_get_point_information()
	 * and ubcar_get_point_comments(), then displaying the information of
	 * the new point.
	 *
	 * @param {String} pointID - The point id.
	 */
	this.retrievePoint = function( pointID ) {

		var data, tempLatlng;

		jQuery( '#ubcar-body-information' ).html( '<div class="ubcar-delay"></div>' );
		jQuery( '#ubcar-body-media' ).html( '<div class="ubcar-delay"></div>' );
		jQuery( '#ubcar-body-comments' ).html( '<div class="ubcar-delay"></div>' );
		jQuery( '#ubcar-header-information' ).css( 'background', '#002145' );
		jQuery( '#ubcar-header-media' ).css( 'background', '#DEDEDE' );
		jQuery( '#ubcar-header-comments' ).css( 'background', '#DEDEDE' );
		jQuery( '#ubcar-display-choice-street' ).css( 'background', '#DEDEDE' );
		jQuery( '#ubcar-display-choice-street' ).unbind( 'click' );
		jQuery( '#ubcar-map-canvas' ).show();
		jQuery( '#ubcar-streetview-canvas' ).hide();
		data = {
			'action': 'ubcar_point_information_retriever',
			'ubcar_point_id': pointID
		};
		jQuery.post( ajax_object.ajax_url, data, function( response ) {
			objectInstance.displayPointInformation( response );
			if( response.logged_in === true ) {
				jQuery( '#ubcar-header-comments-submit' ).css( 'background', '#002145' );
				jQuery( '#ubcar-header-media-submit' ).css( 'background', '#002145' );
			} else {
				jQuery( '#ubcar-header-comments-submit' ).unbind( 'click' );
				jQuery( '#ubcar-header-media-submit' ).unbind( 'click' );
			}
			tempLatlng = new google.maps.LatLng( response.point_latitude, response.point_longitude );
			objectInstance.displayStreetview( pointID, tempLatlng, map );
		});
		data = {
			'action': 'ubcar_point_comments_retriever',
			'ubcar_point_id': pointID
		};
		jQuery.post( ajax_object.ajax_url, data, function( response ) {
			objectInstance.displayPointComments( response, pointID );
		});
		data = {
			'action': 'ubcar_point_media_submit_retriever',
			'ubcar_point_id': pointID
		};
		jQuery.post( ajax_object.ajax_url, data, function( response ) {
			objectInstance.displayMediaSubmit( response );
		});
	}

	/**
	 * Helper function to retrievePoint(), formatting and
	 * displaying the AJAX response ( point information ).
	 *
	 * @param {Object} response - The AJAX response to display.
	 */
	this.displayPointInformation = function( response ) {

		var htmlStringMedia, htmlStringDescription, wikiID, data;

		htmlStringMedia = '';
		htmlStringDescription = '<h4>' + response.point_title + ' (#' + response.point_ID +')</h4>';
		htmlStringDescription += '<p>' + response.point_description + '</p>';
		htmlStringDescription += '<p class="ubcar-meta">Latitude: ' + response.point_latitude + ', Longitude: ' + response.point_longitude + '</p>';
		htmlStringDescription += '<p>Tags: ' + response.point_tags + '</p>';

		if( response.ubcar_media == null || response.ubcar_media.length != 0 ) {
			for( i in response.ubcar_media ) {
				if( response.ubcar_media[ i ].type === 'external' ) {
					htmlStringDescription += '<p><strong>External Link: </strong><a href="' + response.ubcar_media[ i ].url + '" TARGET="_blank" >';
					if( response.ubcar_media[ i ].title != '' ) {
						htmlStringDescription += response.ubcar_media[ i ].title;
					} else {
						htmlStringDescription += response.ubcar_media[ i ].url;
					}
					htmlStringDescription += '</a>';
					if( response.ubcar_media[ i ].description != '' ) {
						htmlStringDescription += ' - ' + response.ubcar_media[ i ].description;
					}
					if( response.ubcar_media[ i ].layers.length > 0 ) {
						htmlStringDescription += ' (Layers: ';
						for( j in response.ubcar_media[ i ].layers ) {
							htmlStringDescription += response.ubcar_media[ i ].layers[ j ].title;
							if( j < response.ubcar_media[ i ].layers.length - 1 ) {
								htmlStringDescription += ', ';
							}
						}
						htmlStringDescription += ')';
					}
					htmlStringDescription += '</p>';
				} else if( response.ubcar_media[ i ].type === 'wiki' ) {
					htmlStringDescription += '<div class="ubcar-wiki-header" id="ubcar-wiki-header-' + response.ubcar_media[ i ].ID + '">' + response.ubcar_media[ i ].title;
					if( response.ubcar_media[ i ].layers.length > 0 ) {
						htmlStringDescription += ' (Layers: ';
						for( j in response.ubcar_media[ i ].layers ) {
							htmlStringDescription += response.ubcar_media[ i ].layers[ j ].title;
							if( j < response.ubcar_media[ i ].layers.length - 1 ) {
								htmlStringDescription += ', ';
							}
						}
						htmlStringDescription += ')';
					}
					htmlStringDescription += '</div><div class="ubcar-wiki-body" id="ubcar-wiki-body-' + response.ubcar_media[ i ].ID + '"></div>';
				} else if( response.ubcar_media[ i ].type === 'image' ) {
					htmlStringMedia += '<a href="' + response.ubcar_media[ i ].full_size_url + '" TARGET="_blank">';
					htmlStringMedia += response.ubcar_media[ i ].image;
					htmlStringMedia += '</a>';
				} else if( response.ubcar_media[ i ].type === 'video' ) {
					if( response.ubcar_media[ i ].video_type === 'youtube' ) {
						htmlStringMedia += '<iframe width="100%" height="300" src="//www.youtube.com/embed/' + response.ubcar_media[ i ].url + '" frameborder="0" allowfullscreen></iframe>';
					} else if( response.ubcar_media[ i ].video_type === 'vimeo' ) {
						htmlStringMedia += '<iframe src="https://player.vimeo.com/video/' + response.ubcar_media[ i ].url  + '" width="100%" height="300" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
					}
				} else if( response.ubcar_media[ i ].type === 'audio' ) {
					htmlStringMedia += '<iframe width="100%" height="150" scrolling="no" frameborder="no" src="https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/tracks/' + response.ubcar_media[ i ].url + '&amp;auto_play=false&amp;hide_related=true&amp;show_comments=false&amp;show_user=true&amp;show_reposts=false&amp;visual=false"></iframe>';
				}
				if( response.ubcar_media[ i ].type != 'external' && response.ubcar_media[ i ].type != 'wiki' ) {
					htmlStringMedia += '<div class="ubcar-caption-title"><p>';
					htmlStringMedia += '<strong>' + response.ubcar_media[ i ].title + '</strong>' + ' (#' + response.ubcar_media[ i ].ID + ')';
					htmlStringMedia += '</p></div>';
					htmlStringMedia += '<div class="ubcar-caption-main">';
					htmlStringMedia += '<p>' + response.ubcar_media[ i ].description + '</p>';
					htmlStringMedia += '<div class="ubcar-uploader"><p>';
					htmlStringMedia += 'Uploader: ' + response.ubcar_media[ i ].uploader + ' | ' + response.ubcar_media[ i ].date;
					if( response.ubcar_media[ i ].layers.length > 0 ) {
						htmlStringMedia += '<br />Layers: ';
						for( j in response.ubcar_media[ i ].layers ) {
							htmlStringMedia += response.ubcar_media[ i ].layers[ j ].title;
							if( j < response.ubcar_media[ i ].layers.length - 1 ) {
								htmlStringMedia += ', ';
							}
						}
					}
					htmlStringMedia += '</div>';
					htmlStringMedia += '</div>';
					htmlStringMedia += '<hr />';
				}
			}
			if( htmlStringMedia != '' ) {
				jQuery( '#ubcar-header-media' ).css( 'background', '#002145' );
			} else {
				jQuery( '#ubcar-body-media' ).hide();
			}
		}

		jQuery( '#ubcar-body-information' ).html( htmlStringDescription );
		jQuery( '#ubcar-body-media' ).html( htmlStringMedia );

		// Test to see if the one-pane, unified data display option is selected in full-width display mode
		if( jQuery( '#ubcar-point-media' ).html() === undefined &&  jQuery( '#ubcar-header-aggregate' ).css( 'bottom' ) !== 'auto' ) {
			jQuery( '#ubcar-body-information' ).append( "<br /><hr /><br />" );
			jQuery( '#ubcar-body-information' ).append( htmlStringMedia );
		} else {
			jQuery( '#ubcar-body-media' ).html( htmlStringMedia );
		}

		jQuery( '.ubcar-wiki-header' ).click(function() {
			wikiID = jQuery( this ).attr( 'id' ).replace( 'ubcar-wiki-header-', '' );
			if( jQuery( '#ubcar-wiki-body-' + wikiID ).css( 'display' ) === 'none' ) {
				if( jQuery( '#ubcar-wiki-body-' + wikiID ).html() === '' ) {
					jQuery( '#ubcar-wiki-body-' + wikiID ).html( '<div class="ubcar-delay"></div>' );
					jQuery( '#ubcar-wiki-body-' + wikiID ).show();
					data = {
						'action': 'ubcar_wiki_page',
						'ubcar_wiki_id': wikiID
					};
					jQuery.post( ajax_object.ajax_url, data, function( response ) {
						if( response.url == null ) {
							jQuery( '#ubcar-wiki-body-' + wikiID ).html( response );
						} else {
							jQuery( '#ubcar-wiki-body-' + wikiID ).html( '<br /><p>The Wiki Embed plugin is not activated. You may visit the linked wiki page <a TARGET="_blank" href="' + response.url + '">here</a>.' );
						}
					});
				}
				jQuery( '#ubcar-wiki-body-' + wikiID ).show();
			} else {
				jQuery( '#ubcar-wiki-body-' + wikiID ).hide();
			}
		});
	}

	/**
	 * Helper function to retrievePoint(), submitNewComment(), and
	 * submitNewReply(), formatting and displaying the AJAX
	 * response ( point comments ).
	 *
	 * @param {Object} response - The AJAX response to display.
	 * @param {String} pointID - The point id.
	 */
	this.displayPointComments = function( response, pointID ) {

		var editID, htmlString, parentID;

		if( response != '<ol class="commentlist"></ol>' && response != '0' ) {
			jQuery( '#ubcar-header-comments' ).css( 'background', '#002145' );
			jQuery( '#ubcar-body-comments' ).html( response );
		} else {
			jQuery( '#ubcar-body-comments' ).html( '' );
			jQuery( '#ubcar-body-comments' ).hide();
		}

		jQuery( '#ubcar-body-comments-submit' ).html( '<textarea rows="4" id="ubcar-new-comment-text"></textarea><br /><div class="ubcar-button" id="ubcar-new-comment-submit">Submit New Comment</div>' );

		jQuery( '[ id^=ubcar-comment-reply- ]' ).click(function() {
			jQuery( this ).hide();
			editID = jQuery( this ).attr( 'id' ).replace( 'ubcar-comment-reply-', '' );
			htmlString = '<textarea rows="4" id="ubcar-comment-reply-text-' + editID + '"></textarea>';
			htmlString += '<div class="ubcar-button" id="ubcar-comment-submit-reply-' + editID + '">Submit Reply</div>';
			jQuery( '#ubcar-reply-area-' + editID ).html( htmlString );

			jQuery( '[ id^=ubcar-comment-submit-reply- ]' ).unbind( 'click' ).click(function() {
				parentID = jQuery( this ).attr( 'id' ).replace( 'ubcar-comment-submit-reply-', '' );
				objectInstance.submitNewReply( pointID, parentID );
			});
		});

		jQuery( '#ubcar-new-comment-submit' ).click(function() {
			objectInstance.submitNewComment( pointID );
		});
	}

	/**
	 * AJAX call to class-ubcar-view-map.php's ubcar_submit_comment(),
	 * then displaying the retrieved comments.
	 *
	 * @param {String} pointID - The point id.
	 */
	this.submitNewComment = function( pointID ) {
		var data = {
			'action': 'ubcar_submit_comment',
			'ubcar_point_id': pointID,
			'ubcar_comment_text': jQuery( '#ubcar-new-comment-text' ).val(),
			'ubcar_nonce_field': jQuery( '#ubcar_nonce_field' ).val()
		};
		jQuery( '#ubcar-body-comments-submit' ).html( '<div class="ubcar-delay"></div>' );
		jQuery.post( ajax_object.ajax_url, data, function( response ) {
			data = {
				'action': 'ubcar_point_comments_retriever',
				'ubcar_point_id': pointID
			};
			jQuery.post( ajax_object.ajax_url, data, function( response ) {
				objectInstance.displayPointComments( response, pointID );
			});
		});
		jQuery( '#ubcar-body-comments-submit' ).html( '<div class="ubcar-delay"></div>' );
	}

	/**
	 * AJAX call to class-ubcar-view-map.php's ubcar_submit_reply(),
	 * then displaying the retrieved comments.
	 *
	 * @param {String} pointID - The point id.
	 * @param {String} parentID - The parent comment id.
	 */
	this.submitNewReply = function( pointID, parentID ) {
		var data = {
			'action': 'ubcar_submit_reply',
			'ubcar_point_id': pointID,
			'ubcar_reply_text': jQuery( '#ubcar-comment-reply-text-' + parentID ).val(),
			'ubcar_comment_parent': parentID,
			'ubcar_nonce_field': jQuery( '#ubcar_nonce_field' ).val(),
		};
		jQuery( '#ubcar-body-comments' ).html( '<div class="ubcar-delay"></div>' );
		jQuery.post( ajax_object.ajax_url, data, function( response ) {
			data = {
				'action': 'ubcar_point_comments_retriever',
				'ubcar_point_id': pointID
			};
			jQuery.post( ajax_object.ajax_url, data, function( response ) {
				objectInstance.displayPointComments( response, pointID );
			});;
		});
	}

	/**
	 * AJAX call to class-ubcar-view-map.php's ubcar_get_point_media_submit(),
	 * displaying the frontend media submit button.
	 *
	 * @param {Object} response - The AJAX response to display.
	 * @param {String} pointID - The point id.
	 */
	this.displayMediaSubmit = function( response ) {

		jQuery( '#ubcar-body-media-submit' ).html( response );

		jQuery( '#ubcar-media-type' ).change(function() {
			switch( this.value ) {
				case 'image':
					jQuery( '.ubcar-add-media-image' ).show();
					jQuery( '.ubcar-add-media-video' ).hide();
					jQuery( '.ubcar-add-media-audio' ).hide();
					jQuery( '.ubcar-add-media-imagewp' ).hide();
					jQuery( '.ubcar-add-media-external' ).hide();
					jQuery( '.ubcar-add-media-wiki' ).hide();
					jQuery( '#ubcar-media-wiki-warning' ).hide();
					break;
				case 'audio':
					jQuery( '.ubcar-add-media-image' ).hide();
					jQuery( '.ubcar-add-media-video' ).hide();
					jQuery( '.ubcar-add-media-audio' ).show();
					jQuery( '.ubcar-add-media-imagewp' ).hide();
					jQuery( '.ubcar-add-media-external' ).hide();
					jQuery( '.ubcar-add-media-wiki' ).hide();
					jQuery( '#ubcar-media-wiki-warning' ).hide();
					break;
				case 'video':
					jQuery( '.ubcar-add-media-image' ).hide();
					jQuery( '.ubcar-add-media-video' ).show();
					jQuery( '.ubcar-add-media-audio' ).hide();
					jQuery( '.ubcar-add-media-imagewp' ).hide();
					jQuery( '.ubcar-add-media-external' ).hide();
					jQuery( '.ubcar-add-media-wiki' ).hide();
					jQuery( '#ubcar-media-wiki-warning' ).hide();
					break;
				case 'imagewp':
					jQuery( '.ubcar-add-media-image' ).hide();
					jQuery( '.ubcar-add-media-video' ).hide();
					jQuery( '.ubcar-add-media-audio' ).hide();
					jQuery( '.ubcar-add-media-imagewp' ).show();
					jQuery( '.ubcar-add-media-external' ).hide();
					jQuery( '.ubcar-add-media-wiki' ).hide();
					jQuery( '#ubcar-media-wiki-warning' ).hide();
					break;
				case 'external':
					jQuery( '.ubcar-add-media-image' ).hide();
					jQuery( '.ubcar-add-media-video' ).hide();
					jQuery( '.ubcar-add-media-audio' ).hide();
					jQuery( '.ubcar-add-media-imagewp' ).hide();
					jQuery( '.ubcar-add-media-external' ).show();
					jQuery( '.ubcar-add-media-wiki' ).hide();
					jQuery( '#ubcar-media-wiki-warning' ).hide();
					break;
				case 'wiki':
					jQuery( '.ubcar-add-media-image' ).hide();
					jQuery( '.ubcar-add-media-video' ).hide();
					jQuery( '.ubcar-add-media-audio' ).hide();
					jQuery( '.ubcar-add-media-imagewp' ).hide();
					jQuery( '.ubcar-add-media-external' ).hide();
					jQuery( '.ubcar-add-media-wiki' ).show();
					jQuery( '#ubcar-media-wiki-warning' ).show();
					break;
			}
		});

	}

	/**
	 * Helper function to retrievePoint(), testing if a streetview
	 * is available for a certain point.
	 *
	 * @param {String} pointID - The point id.
	 * @param {Object} latlng_object - The latlng object with the coordinates of the point.
	 */
	this.displayStreetview = function( pointID, latlng_object ) {
		var streetviewTester = new google.maps.StreetViewService();
		streetviewTester.getPanoramaByLocation( latlng_object, 50, function( data, status ) {
			objectInstance.displayStreetviewTest( pointID, data, status, latlng_object );
		});
	}

	/**
	 * Helper function to displayStreetview(), testing if a streetview
	 * is available for a certain point and allowing the option to
	 * display it if it is.
	 *
	 * @param {String} pointID - The point id.
	 * @param {Object} data - Google supplied parameter.
	 * @param {Object} status - Google supplied parameter.
	 * @param {Object} latlng_object - The latlng object with the coordinates of the point.
	 */
	this.displayStreetviewTest = function( pointID, data, status, latlng_object ) {

		var streetviewOptions, streetview;

		if ( status === google.maps.StreetViewStatus.OK ) {
			jQuery( '#ubcar-display-choice-street' ).css( 'background', '#002145' );
			jQuery( '#ubcar-display-choice-street' ).click(function() {
				jQuery( '#ubcar-map-canvas' ).hide();
				jQuery( '#ubcar-streetview-canvas' ).show();
				jQuery( '#ubcar-display-fullscreen' ).addClass( 'ubcar-display-fullscreen-move' );
				jQuery( '#ubcar-display-choice-map' ).addClass( 'ubcar-display-choice-map-move' );
				jQuery( '#ubcar-display-choice-street' ).addClass( 'ubcar-display-choice-street-move' );
				streetviewOptions = {
					position: latlng_object
				}
				streetview = new google.maps.StreetViewPanorama( document.getElementById( 'ubcar-streetview-canvas' ), streetviewOptions );
			});
		}
	}

	/**
	 * Helper function to displayPoints() and hidePoints(),
	 * resizing the map if necessary.
	 */
	this.resizeMap = function() {

		var mapBounds, tempSW, tempNE, tempBounds;

		mapBounds = new google.maps.LatLngBounds();
		for( i in ubcarPoints ) {
			if( ubcarPoints[ i ].active === true && ubcarPoints[ i ].raw_data.geojson_bounds != null ) {
				tempSW = new google.maps.LatLng( ubcarPoints[ i ].raw_data.geojson_bounds.sw_lat, ubcarPoints[ i ].raw_data.geojson_bounds.sw_lng );
				tempNE = new google.maps.LatLng( ubcarPoints[ i ].raw_data.geojson_bounds.ne_lat, ubcarPoints[ i ].raw_data.geojson_bounds.ne_lng );
				tempBounds = new google.maps.LatLngBounds( tempSW, tempNE );
				mapBounds.union( tempBounds );
				mapInstance.fitBounds( mapBounds );
				if( tempSW.lat() === tempNE.lat() && tempSW.lng() === tempNE.lng() ) {
					mapInstance.setZoom( 16 );
				}
			}
		}
	}

	/**
	 * Function to detect and respond to hidden input fields used
	 * to communicate a user's GET request for a specific point,
	 * tour, or layer.
	 */
	this.requestDetector = function() {

		var requestedType, requestedValue, requestedLatitude, requestedLongitude, requestedLatLng, maxZoomService;

		requestedType = jQuery( '#ubcar-hidden-request-type' ).val();
		requestedValue = jQuery( '#ubcar-hidden-request-value' ).val();
		if( requestedType != null && requestedValue != null ) {
			if( requestedType === 'ubcar_point' ) {
				objectInstance.retrievePoint( requestedValue );
				requestedLatitude = jQuery( '#ubcar-hidden-request-latitude' ).val();
				requestedLongitude = jQuery( '#ubcar-hidden-request-longitude' ).val();
				requestedLatLng = new google.maps.LatLng( requestedLatitude, requestedLongitude );
				maxZoomService = new google.maps.MaxZoomService();
				maxZoomService.getMaxZoomAtLatLng( requestedLatLng, function( max_zoom ){
					mapInstance.setCenter( requestedLatLng );
					if( max_zoom.zoom != null ) {
						mapInstance.setZoom( max_zoom.zoom );
					} else {
						mapInstance.setZoom( 16 );
					}
				});
			} else if( requestedType === 'ubcar_layer' ) {
				jQuery( '#ubcar-accordion-header-layers' ).click();
				jQuery( '#' + requestedValue ).click();
			} else if( requestedType === 'ubcar_tour' ) {
				jQuery( '#ubcar-accordion-header-tours' ).click();
				jQuery( '#' + requestedValue ).click();
			} else {
				objectInstance.retrievePoints( 0, 'all' );
			}
		} else {
			objectInstance.retrievePoints( 0, 'all' );
		}
	}

}

// bad variables used for XSS
var entityMap = {
	"&": "&amp;",
	"<": "&lt;",
	">": "&gt;",
	'"': '&quot;',
	"'": '&#39;',
	"/": '&#x2F;',
	"\n": '<br />'
};

/**
 * Function to sanitize user-supplied input fields not handled
 * by WordPress's comments system.
 *
 * @param {String} string - Input string.
 * @return {String} string - Sanitized output string.
 */
function escapeHTML( string ) {
	return String( string ).replace( /[ &<>"'\/ ]|[ \n ]/g, function ( characterToBeReplaced ) {
		return entityMap[ characterToBeReplaced ];
	});
}
