jQuery( document ).ready(function( $ ) {

	var requestedLatlng, mapOptions, map, marker, data;

	requestedLatlng = new google.maps.LatLng( 49.2683366, -123.2550359 );
	mapOptions = {
		zoom: 10,
		center: requestedLatlng,
		mapTypeId: google.maps.MapTypeId.ROADMAP
	};
	map = new google.maps.Map( document.getElementById( 'ubcar-map-canvas' ), mapOptions );
	marker = new google.maps.Marker( {
	});

   data = {
		'action' : 'point_initial'
	};
	jQuery.post( ajax_object.ajax_url, data, function( response ) {
		displayPoints( response );
		if( response.length < 25 ) {
			jQuery( '#ubcar-point-forward' ).hide();
		}
		jQuery( '#ubcar-point-back' ).hide();
	});

	// ubcar point updater
	jQuery( '#ubcar-point-submit' ).click(function() {
		updatePoints();
	});

	// ubcar point forward
	jQuery( '#ubcar-point-forward' ).click(function() {
		forwardPoints();
	});

	// ubcar point backward
	jQuery( '#ubcar-point-back' ).click(function() {
		backwardPoints();
	});

	jQuery( '[ id^=ubcar-point-delete- ]' ).click(function() {
		deletePoints();
	});

	jQuery( '#ubcar-point-goto' ).click(function() {
		goToPoints();
	});

	google.maps.event.addListener( map, 'click', function( event ) {
		jQuery( '#ubcar-point-longitude' ).val( event.latLng.lng() );
		jQuery( '#ubcar-point-latitude' ).val( event.latLng.lat() );
		marker.setPosition( event.latLng );
		marker.setMap( map );
	});

	jQuery( '#ubcar-point-latlng-check' ).click(function() {
		requestedLatlng = new google.maps.LatLng( escapeHTML( jQuery( '#ubcar-point-latitude' ).val() ), escapeHTML( jQuery( '#ubcar-point-longitude' ).val() ) );
		map.setCenter( requestedLatlng );
		marker.setPosition( requestedLatlng );
		marker.setMap( map );
	});

});

/**
 * AJAX call to class-ubcar-admin-point.php's ubcar_point_updater_callback() and
 * ubcar_point_initial(), inserting an ubcar_point post and updating the window
 */
function updatePoints() {
	var data = {
		'action': 'point_updater',
		'ubcar_nonce_field': escapeHTML( jQuery( '#ubcar-nonce-field' ).val() ),
		'ubcar_point_title': escapeHTML( jQuery( '#ubcar-point-title' ).val() ),
		'ubcar_point_description': escapeHTML( jQuery( '#ubcar-point-description' ).val() ),
		'ubcar_point_latitude': escapeHTML( jQuery( '#ubcar-point-latitude' ).val() ),
		'ubcar_point_longitude': escapeHTML( jQuery( '#ubcar-point-longitude' ).val() ),
		'ubcar_point_tags': escapeHTML( jQuery( '#ubcar-point-tags' ).val() )
	};
	jQuery.post( ajax_object.ajax_url, data, function( response ) {
		alert( response );
		data = {
			'action' : 'point_initial'
		};
		jQuery.post( ajax_object.ajax_url, data, function( response ) {
			displayPoints( response );
			jQuery( '#ubcar-point-display-count' ).html( 1 );
			jQuery( '#ubcar-point-back' ).hide();
			if( response.length < 25 ) {
				jQuery( '#ubcar-point-forward' ).hide();
			} else {
				jQuery( '#ubcar-point-forward' ).show();
			}
		});
	});
}

/**
 * AJAX call to class-ubcar-admin-point.php's ubcar_point_forward(),
 * incrementing the displayed ubcar_point posts
 */
function forwardPoints() {

	var data, currentPage;

	data = {
		'action' : 'point_forward',
		'ubcar_point_offset' : escapeHTML( jQuery( '#ubcar-point-display-count' ).html() )
	};
	jQuery.post( ajax_object.ajax_url, data, function( response ) {
		displayPoints( response );
		currentPage = parseInt( jQuery( '#ubcar-point-display-count' ).html() );
		jQuery( '#ubcar-point-display-count' ).html( currentPage + 1 );
		if( response.length < 25 ) {
			jQuery( '#ubcar-point-forward' ).hide();
		} else {
			jQuery( '#ubcar-point-forward' ).show();
		}
		jQuery( '#ubcar-point-back' ).show();
	});
}

/**
 * AJAX call to class-ubcar-admin-point.php's ubcar_point_backward(),
 * decrementing the displayed ubcar_point posts
 */
function backwardPoints() {

	var data, currentPage;

	data = {
		'action' : 'point_backward',
		'ubcar_point_offset' : escapeHTML( jQuery( '#ubcar-point-display-count' ).html() )
	};
	jQuery.post( ajax_object.ajax_url, data, function( response ) {
		displayPoints( response );
		currentPage = parseInt( jQuery( '#ubcar-point-display-count' ).html() ) - 1;
		jQuery( '#ubcar-point-display-count' ).html( currentPage );
		if( currentPage === 1 ) {
			jQuery( '#ubcar-point-back' ).hide();
		}
	});
	jQuery( '#ubcar-point-forward' ).show();
}

/**
 * AJAX call to class-ubcar-admin-point.php's ubcar_point_goto(),
 * going to the designated point page
 */
function goToPoints() {
	var data, currentPage, desiredPage, maxPage;
	desiredPage = parseInt( jQuery( '#ubcar-point-choose-count' ).val() );
	maxPage = parseInt( jQuery( '#ubcar-point-max-count' ).html() )
	if( desiredPage > maxPage ) {
		desiredPage = maxPage;
	} else if( desiredPage < 1 ) {
		desiredPage = 1;
	}
	data = {
		'action' : 'point_goto',
		'ubcar_point_offset' : desiredPage
	};
	jQuery.post( ajax_object.ajax_url, data, function( response ) {
		displayPoints( response );
		currentPage = desiredPage;
		jQuery( '#ubcar-point-choose-count' ).val( currentPage );
		jQuery( '#ubcar-point-display-count' ).html( currentPage );
		if( currentPage === 1 ) {
			jQuery( '#ubcar-point-back' ).hide();
		}
		if( response.length < 25 ) {
			jQuery( '#ubcar-point-forward' ).hide();
		} else {
			jQuery( '#ubcar-point-forward' ).show();
		}
	});
	jQuery( '#ubcar-point-forward' ).show();
}

/**
 * AJAX call to class-ubcar-admin-point.php's ubcar_point_delete(), deleting the
 * selected ubcar_point post.
 *
 * @param {Number} deleteID
 */
function deletePoints( deleteID ) {
	if( confirm( 'Are you sure you want to delete this point?' ) ){
		var data = {
			'action' : 'point_delete',
			'ubcar_nonce_field': escapeHTML( jQuery( '#ubcar-nonce-field' ).val() ),
			'ubcar_point_delete_id' : deleteID
		};
		jQuery.post( ajax_object.ajax_url, data, function( response ) {
			if( response === false ) {
				alert( 'Sorry, you do not have permission to delete that point.' );
			} else {
				displayPoints( response );
				jQuery( '#ubcar-point-display-count' ).html( 1 );
				jQuery( '#ubcar-point-back' ).hide();
				if( response.length < 25 ) {
					jQuery( '#ubcar-point-forward' ).hide();
				} else {
					jQuery( '#ubcar-point-forward' ).show();
				}
			}
		});
	}
}

/**
 * Helper function to display retrieved ubcar_point posts data.
 *
 * @param {Object[]} response JSON object of ubcar_point posts' data
 */
function displayPoints( response ) {

	if( Object.prototype.toString.call( response ) === '[object Array]' ) {

		var htmlString, deleteID, editID;

		htmlString = '<tr><td>ID</td><td>Title</td><td>Uploader</td><td>Date Uploaded</td><td>Description</td><td>Latitude</td><td>Longitude</td><td>Tags</td><td>Action</td></tr>';
		for( i in response ) {
			htmlString += '<tr id="ubcar-point-line-';
			htmlString += response[ i ].ID;
			htmlString += '"><td>';
			htmlString += response[ i ].ID;
			htmlString += '</td><td>';
			htmlString += response[ i ].title;
			htmlString += '</td><td>';
			htmlString += response[ i ].uploader;
			htmlString += '</td><td>';
			htmlString += response[ i ].date;
			htmlString += '</td><td>';
			htmlString += response[ i ].description;
			htmlString += '</td><td>';
			htmlString += response[ i ].latitude;
			htmlString += '</td><td>';
			htmlString += response[ i ].longitude;
			htmlString += '</td><td>';
			htmlString += response[ i ].tags;
			htmlString += '</td><td><a id="ubcar-point-edit-';
			htmlString += response[ i ].ID;
			htmlString += '" class="ubcar-edit-delete-control">Edit</a>/<a class="ubcar-edit-delete-control" id="ubcar-point-delete-';
			htmlString += response[ i ].ID;
			htmlString += '">Delete</a></td></tr>';
		}
		if( response.length === 0 ) {
			htmlString = 'No points found.';
		}
		jQuery( '#ubcar-point-table' ).html( htmlString );
	} else {
		alert( 'An UBCAR error has occurred! Please log out and log back in again.' );
	}

	jQuery( '[ id^=ubcar-point-delete- ]' ).click(function() {
		deleteID = jQuery( this ).attr( 'id' ).replace( 'ubcar-point-delete-', '' );
		deletePoints( deleteID );
	});

	jQuery( '[ id^=ubcar-point-edit- ]' ).click(function() {
		editID = jQuery( this ).attr( 'id' ).replace( 'ubcar-point-edit-', '' );
		editPoints( editID );
	});
}

/**
 * AJAX call to class-ubcar-admin-point.php's ubcar_point_edit(), retrieving an
 * ubcar_point post's data and formatting it for editing.
 *
 * @param {Number} editID
 */
function editPoints( editID ) {

	var data, htmlString;

	data = {
		'action' : 'point_edit',
		'ubcar_nonce_field': escapeHTML( jQuery( '#ubcar-nonce-field' ).val() ),
		'ubcar_point_edit_id' : editID
	};
	jQuery.post( ajax_object.ajax_url, data, function( response ) {
		if( response === false ) {
			alert( 'Sorry, you do not have pssermission to edit that point.' );
		} else {
			if( Object.prototype.toString.call( response ) === '[object Object]' ) {

				htmlString = '<td>';
				htmlString += response.ID;
				htmlString += '</td><td><input type="text" id="ubcar-point-edit-title-';
				htmlString += response.ID;
				htmlString += '" value="';
				htmlString += response.title;
				htmlString += '" /></td><td>';
				htmlString += response.uploader;
				htmlString += '</td><td>';
				htmlString += response.date;
				htmlString += '</td><td><textarea id="ubcar-point-edit-description-';
				htmlString += response.ID;
				htmlString += '">';
				htmlString += response.description;
				htmlString += '</textarea></td><td><input type="text" id="ubcar-point-edit-latitude-';
				htmlString += response.ID;
				htmlString += '" value="';
				htmlString += response.latitude;
				htmlString += '" /></td><td><input type="text" id="ubcar-point-edit-longitude-';
				htmlString += response.ID;
				htmlString += '" value="';
				htmlString += response.longitude;
				htmlString += '" /></td><td><input type="text" id="ubcar-point-edit-tags-';
				htmlString += response.ID;
				htmlString += '" value="';
				htmlString += response.tags;
				htmlString += '" /></td><td><div class="button button-primary" id="ubcar-point-edit-submit-';
				htmlString += response.ID;
				htmlString += '">Upload Edit</div></td>';
				jQuery( '#' + 'ubcar-point-line-' + response.ID ).html( htmlString );
			}
		}
		jQuery( '#ubcar-point-edit-submit-' + editID ).click(function() {
			editPointsSubmit( this );
		});
	});
}

/**
 * AJAX call to class-ubcar-admin-point.php's ubcar_point_edit_submit(),
 * submitting an ubcar_point post's edited information and displaying it
 *
 * @param {Number} thisthis The unique div of the post to be submitted
 */
function editPointsSubmit( thisthis ) {

	var editID, submitData, htmlString;

	editID = jQuery( thisthis ).attr( 'id' ).replace( 'ubcar-point-edit-submit-', '' );
	submitData = {
		'action' : 'point_edit_submit',
		'ubcar_nonce_field': escapeHTML( jQuery( '#ubcar-nonce-field' ).val() ),
		'ubcar_point_edit_id' : editID,
		'ubcar_point_title': escapeHTML( jQuery( '#ubcar-point-edit-title-' + editID ).val() ),
		'ubcar_point_description': escapeHTML( jQuery( '#ubcar-point-edit-description-' + editID ).val() ),
		'ubcar_point_latitude': escapeHTML( jQuery( '#ubcar-point-edit-latitude-' + editID ).val() ),
		'ubcar_point_longitude': escapeHTML( jQuery( '#ubcar-point-edit-longitude-' + editID ).val() ),
		'ubcar_point_tags': escapeHTML( jQuery( '#ubcar-point-edit-tags-' + editID ).val() )
	};
	jQuery.post( ajax_object.ajax_url, submitData, function( response ) {
		if( response === false ) {
			alert( 'Sorry, you do not have permission to delete that point.' );
		} else {
			if( Object.prototype.toString.call( response ) === '[object Object]' ) {

			htmlString = '<td>';
			htmlString += response.ID;
			htmlString += '</td><td>';
			htmlString += response.title;
			htmlString += '</td><td>';
			htmlString += response.uploader;
			htmlString += '</td><td>';
			htmlString += response.date;
			htmlString += '</td><td>';
			htmlString += response.description;
			htmlString += '</td><td>';
			htmlString += response.latitude;
			htmlString += '</td><td>';
			htmlString += response.longitude;
			htmlString += '</td><td>';
			htmlString += response.tags;
			htmlString += '</td><td>Updated!</td>';
			jQuery( '#' + 'ubcar-point-line-' + response.ID ).html( htmlString );
			}
		}
	});
}
