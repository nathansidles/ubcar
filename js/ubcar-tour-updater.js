jQuery( document ).ready(function( $ ) {

	var data = {
		'action' : 'tour_initial',
		'ubcar_tour_offset' : escapeHTML( jQuery( '#ubcar-tour-display-count' ).val() )
	};
	jQuery.post( ajax_object.ajax_url, data, function( response ) {
		displayTours( response );
		if( response.length < 25 ) {
			jQuery( '#ubcar-tour-forward' ).hide();
		}
		jQuery( '#ubcar-tour-back' ).hide();
	});

	// ubcar tour updater
	jQuery( '#ubcar-tour-submit' ).click(function() {
		updateTours();
	});

	// ubcar tour forward
	jQuery( '#ubcar-tour-forward' ).click(function() {
		forwardTours();
	});

	// ubcar tour backward
	jQuery( '#ubcar-tour-back' ).click(function() {
		backwardTours();
	});

	jQuery( '[ id^=ubcar-tour-delete- ]' ).click(function() {
		deleteTours();
	});

	jQuery( '#ubcar-tour-goto' ).click(function() {
		goToTours();
	});

	jQuery(function() {
		jQuery( '#ubcar-tour-locations-selected-list, #ubcar-tour-locations-complete-list' ).sortable( {
			connectWith: '.ubcar-tour-order-locations'
		});
	});

});

/**
 * AJAX call to class-ubcar-admin-tour.php's ubcar_tour_updater_callback() and
 * ubcar_tour_initial(), inserting an ubcar_tour post and updating the window
 */
function updateTours() {
	var tourLocations = [];
	jQuery( '#ubcar-tour-locations-selected-list li input' ).each(function() {
		tourLocations.push( escapeHTML( jQuery( this ).val() ) );
	});
	var data = {
		'action': 'tour_updater',
		'ubcar_nonce_field': escapeHTML( jQuery( '#ubcar-nonce-field' ).val() ),
		'ubcar_tour_title': escapeHTML( jQuery( '#ubcar-tour-title' ).val() ),
		'ubcar_tour_description': escapeHTML( jQuery( '#ubcar-tour-description' ).val() ),
		'ubcar_tour_locations': tourLocations
	};
	jQuery.post( ajax_object.ajax_url, data, function( response ) {
		alert( response );
		data = {
			'action' : 'tour_initial',
			'ubcar_tour_offset' : escapeHTML( jQuery( '#ubcar-tour-display-count' ).val() )
		};
		jQuery.post( ajax_object.ajax_url, data, function( response ) {
			displayTours( response );
			jQuery( '#ubcar-tour-display-count' ).html( 1 );
			jQuery( '#ubcar-tour-back' ).hide();
			if( response.length < 25 ) {
				jQuery( '#ubcar-tour-forward' ).hide();
			} else {
				jQuery( '#ubcar-tour-forward' ).show();
			}
		});
	});
}

/**
 * AJAX call to class-ubcar-admin-tour.php's ubcar_tour_forward(),
 * incrementing the displayed ubcar_tour posts
 */
function forwardTours() {

	var data, currentPage;

	data = {
		'action' : 'tour_forward',
		'ubcar_tour_offset' : escapeHTML( jQuery( '#ubcar-tour-display-count' ).html() )
	};
	jQuery.post( ajax_object.ajax_url, data, function( response ) {
		displayTours( response );
		currentPage = parseInt( jQuery( '#ubcar-tour-display-count' ).html() );
		jQuery( '#ubcar-tour-display-count' ).html( currentPage + 1 );
		if( response.length < 25 ) {
			jQuery( '#ubcar-tour-forward' ).hide();
		} else {
			jQuery( '#ubcar-tour-forward' ).show();
		}
		jQuery( '#ubcar-tour-back' ).show();
	});
}

/**
 * AJAX call to class-ubcar-admin-tour.php's ubcar_tour_backward(),
 * decrementing the displayed ubcar_tour posts
 */
function backwardTours() {

	var data, currentPage;

	data = {
		'action' : 'tour_backward',
		'ubcar_tour_offset' : escapeHTML( jQuery( '#ubcar-tour-display-count' ).html() )
	};
	jQuery.post( ajax_object.ajax_url, data, function( response ) {
		displayTours( response );
		currentPage = parseInt( jQuery( '#ubcar-tour-display-count' ).html() ) - 1;
		jQuery( '#ubcar-tour-display-count' ).html( currentPage );
		if( currentPage === 1 ) {
			jQuery( '#ubcar-tour-back' ).hide();
		}
	});
	jQuery( '#ubcar-tour-forward' ).show();
}

/**
 * AJAX call to class-ubcar-admin-tour.php's ubcar_tour_goto(),
 * going to the designated tour page
 */
function goToTours() {
	var data, currentPage, desiredPage, maxPage;
	desiredPage = parseInt( jQuery( '#ubcar-tour-choose-count' ).val() );
	maxPage = parseInt( jQuery( '#ubcar-tour-max-count' ).html() )
	if( desiredPage > maxPage ) {
		desiredPage = maxPage;
	} else if( desiredPage < 1 ) {
		desiredPage = 1;
	}
	data = {
		'action' : 'tour_goto',
		'ubcar_tour_offset' : desiredPage
	};
	jQuery.post( ajax_object.ajax_url, data, function( response ) {
		displayTours( response );
		currentPage = desiredPage;
		jQuery( '#ubcar-tour-choose-count' ).val( currentPage );
		jQuery( '#ubcar-tour-display-count' ).html( currentPage );
		if( currentPage === 1 ) {
			jQuery( '#ubcar-tour-back' ).hide();
		}
		if( response.length < 25 ) {
			jQuery( '#ubcar-tour-forward' ).hide();
		} else {
			jQuery( '#ubcar-tour-forward' ).show();
		}
	});
	jQuery( '#ubcar-tour-forward' ).show();
}

/**
 * AJAX call to class-ubcar-admin-tour.php's ubcar_tour_delete(), deleting the
 * selected ubcar_tour post.
 *
 * @param {Number} deleteID
 */
function deleteTours( deleteID ) {
	if( confirm( 'Are you sure you want to delete this tour?' ) ){
		var data = {
			'action' : 'tour_delete',
			'ubcar_nonce_field': escapeHTML( jQuery( '#ubcar-nonce-field' ).val() ),
			'ubcar_tour_delete_id' : deleteID
		};
		jQuery.post( ajax_object.ajax_url, data, function( response ) {
			if( response === false ) {
				alert( 'Sorry, you do not have permission to delete that tour.' );
			} else {
				displayTours( response );
				jQuery( '#ubcar-tour-display-count' ).html( 1 );
				jQuery( '#ubcar-tour-back' ).hide();
				if( response.length < 25 ) {
					jQuery( '#ubcar-tour-forward' ).hide();
				} else {
					jQuery( '#ubcar-tour-forward' ).show();
				}
			}
		});
	}
}

/**
 * Helper function to display retrieved ubcar_tour posts data.
 *
 * @param {Object[]} response JSON object of ubcar_tour posts' data
 */
function displayTours( response ) {

	if( Object.prototype.toString.call( response ) === '[object Array]' ) {

	var htmlString, deleteID, editID;

	var htmlString = '<tr><td>ID</td><td>Title</td><td>Uploader</td><td>Date Uploaded</td><td>Description</td><td>Locations</td><td>Action</td></tr>';
	for( i in response ) {

		htmlString += '<tr id="ubcar-tour-line-';
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
		htmlString += '</td><td><div style="max-height: 200px; overflow: auto">';
		htmlString += '<ol id="ubcar-tour-locations-display">';
		for( j in response[ i ].locations ) {
			htmlString += '<li>' + response[ i ].locations[ j ].title + ' ( #' + response[ i ].locations[ j ].ID + ' )</li>';
		}
		htmlString += '</ol>';
		htmlString += '</div></td><td><a id="ubcar-tour-edit-';
		htmlString += response[ i ].ID;
		htmlString += '" class="ubcar-edit-delete-control">Edit</a>/<a class="ubcar-edit-delete-control" id="ubcar-tour-delete-';
		htmlString += response[ i ].ID;
		htmlString += '">Delete</a></td></tr>';
	}
	if( response.length === 0 ) {
		htmlString = 'No tours found.';
	}
	jQuery( '#ubcar-tour-table' ).html( htmlString );

	} else {
		alert( 'An UBCAR error has occurred. Please log out and log in again.' );
	}

	jQuery( '[ id^=ubcar-tour-delete- ]' ).click(function() {
		deleteID = jQuery( this ).attr( 'id' ).replace( 'ubcar-tour-delete-', '' );
		deleteTours( deleteID );
	});

	jQuery( '[ id^=ubcar-tour-edit- ]' ).click(function() {
		editID = jQuery( this ).attr( 'id' ).replace( 'ubcar-tour-edit-', '' );
		editTours( editID );
	});
}

/**
 * AJAX call to class-ubcar-admin-tour.php's ubcar_tour_edit(), retrieving an
 * ubcar_tour post's data and formatting it for editing.
 *
 * @param {Number} editID
 */
function editTours( editID ) {

	var data, htmlString, htmlStringSelected, selectedLocations, htmlStringComplete;

	var data = {
		'action' : 'tour_edit',
		'ubcar_nonce_field': escapeHTML( jQuery( '#ubcar-nonce-field' ).val() ),
		'ubcar_tour_edit_id' : editID
	};
	jQuery.post( ajax_object.ajax_url, data, function( response ) {
		if( response === false ) {
			alert( 'Sorry, you do not have permission to edit that tour.' );
		} else {

			if( Object.prototype.toString.call( response ) === '[object Object]' ) {
				htmlString = '<td>';
				htmlString += response.ID;
				htmlString += '</td><td><input type="text" id="ubcar-tour-edit-title-';
				htmlString += response.ID;
				htmlString += '" value="';
				htmlString += response.title;
				htmlString += '" /></td><td>';
				htmlString += response.uploader;
				htmlString += '</td><td>';
				htmlString += response.date;
				htmlString += '</td><td><textarea id="ubcar-tour-edit-description-';
				htmlString += response.ID;
				htmlString += '">';
				htmlString += response.description;
				htmlString += '</textarea></td><td style="text-align: center">';
				htmlString += '<div class="button button-primary" id="ubcar-tour-edit-locations-';
				htmlString += response.ID;
				htmlString += '">Edit Points</div><div class="ubcar-full-screen-popup" id="ubcar-reorder-locations-';
				htmlString += response.ID;
				htmlString += '">';
				htmlString += '<h3>Edit Points for Tour ' + response.ID + '</h3>';
				htmlString += '<div class="button button-primary" id="ubcar-tour-close-edit-locations-';
				htmlString += response.ID;
				htmlString += '">Close</div>';

				htmlStringSelected = '<div class="ubcar-tour-locations">';
				htmlStringSelected += '<h4>Selected Points</h4>';
				htmlStringSelected += '<ul id="ubcar-tour-locations-selected-list-reorder-'
				htmlStringSelected += response.ID;
				htmlStringSelected += '" class="ubcar-tour-reorder-locations-'
				htmlStringSelected += response.ID;
				htmlStringSelected += '">';

				selectedLocations = [];
				for( j in response.locations ) {
					selectedLocations[ response.locations[ j ].ID ] = true;
					htmlStringSelected += '<li>' + response.locations[ j ].title + ' ( #' + response.locations[ j ].ID + ' )';
					htmlStringSelected += '<input type="hidden" value="' + response.locations[ j ].ID + '">';
					htmlStringSelected += '</li>';
				}
				htmlStringSelected += '</ul>';
				htmlStringComplete += '</div>';

				htmlStringComplete = '<div class="ubcar-tour-locations">';
				htmlStringComplete += '<h4>Available Points</h4>';
				htmlStringComplete += '<ul id="ubcar-tour-locations-complete-list-reorder-'
				htmlStringComplete += response.ID;
				htmlStringComplete += '" class="ubcar-tour-reorder-locations-'
				htmlStringComplete += response.ID;
				htmlStringComplete += '">';
				for( j in response.all_locations ) {
					if( selectedLocations[ response.all_locations[ j ].ID ] != true ) {
						htmlStringComplete += '<li>' + response.all_locations[ j ].title + ' ( #' + response.all_locations[ j ].ID + ' )';
						htmlStringComplete += '<input type="hidden" value="' + response.all_locations[ j ].ID + '">';
						htmlStringComplete += '</li>';
					}
				}
				htmlStringComplete += '</ul>';
				htmlStringComplete += '</div>';

				htmlString += htmlStringComplete;
				htmlString += htmlStringSelected;

				htmlString += '</div></td>';
				htmlString += '<td><div class="button button-primary" id="ubcar-tour-edit-submit-';
				htmlString += response.ID;
				htmlString += '">Upload Edit</div></td>';
				jQuery( '#' + 'ubcar-tour-line-' + response.ID ).html( htmlString );
			}
		}
		jQuery(function() {
			jQuery( '#ubcar-tour-locations-selected-list-reorder-' + response.ID + ', #ubcar-tour-locations-complete-list-reorder-' + response.ID ).sortable( {
				connectWith: '.ubcar-tour-reorder-locations-' + response.ID
			});
		});
		jQuery( '#ubcar-tour-edit-locations-' + editID ).click(function() {
			jQuery( '#ubcar-reorder-locations-' + editID ).show();
		});
		jQuery( '#ubcar-tour-close-edit-locations-' + editID ).click(function() {
			jQuery( '#ubcar-reorder-locations-' + editID ).hide();
		});
		jQuery( '#ubcar-tour-edit-submit-' + editID ).click(function() {
			editToursSubmit( this );
		});
	});
}

/**
 * AJAX call to class-ubcar-admin-tour.php's ubcar_tour_edit_submit(),
 * submitting an ubcar_tour post's edited information and displaying it
 *
 * @param {Number} thisthis The unique div of the post to be submitted
 */
function editToursSubmit( thisthis ) {

	var editID, tourLocations, submitData, htmlString;

	editID = jQuery( thisthis ).attr( 'id' ).replace( 'ubcar-tour-edit-submit-', '' );
	tourLocations = [];
	jQuery( '#ubcar-tour-locations-selected-list-reorder-' + editID + ' li input' ).each(function() {
		tourLocations.push( escapeHTML( jQuery( this ).val() ) );
	});
	submitData = {
		'action' : 'tour_edit_submit',
		'ubcar_nonce_field': escapeHTML( jQuery( '#ubcar-nonce-field' ).val() ),
		'ubcar_tour_edit_id' : editID,
		'ubcar_tour_title': escapeHTML( jQuery( '#ubcar-tour-edit-title-' + editID ).val() ),
		'ubcar_tour_description': escapeHTML( jQuery( '#ubcar-tour-edit-description-' + editID ).val() ),
		'ubcar_tour_locations': tourLocations
	};
	jQuery.post( ajax_object.ajax_url, submitData, function( response ) {
		if( response === false ) {
			alert( 'Sorry, you do not have permission to delete that tour.' );
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
				htmlString += '</td><td><div style="max-height: 200px; overflow: auto">';
				htmlString += '<ol id="ubcar-tour-locations-display">';
				for( j in response.locations ) {
					htmlString += '<li>' + response.locations[ j ].title + ' ( #' + response.locations[ j ].ID + ' )</li>';
				}
				htmlString += '</ol>';
				htmlString += '</div></td><td>Updated!</td>';
				jQuery( '#' + 'ubcar-tour-line-' + response.ID ).html( htmlString );
			}
		}
	});
}
