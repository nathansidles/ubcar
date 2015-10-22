jQuery( document ).ready(function( $ ) {

	var data = {
		'action' : 'layer_initial',
		'ubcar_layer_offset' : escapeHTML( jQuery( '#ubcar-layer-display-count' ).val() )
	};
	jQuery.post( ajax_object.ajax_url, data, function( response ) {
		displayLayers( response );
		if( response.length < 25 ) {
			jQuery( '#ubcar-layer-forward' ).hide();
		}
		jQuery( '#ubcar-layer-back' ).hide();
	});

	// ubcar layer updater
	jQuery( '#ubcar-layer-submit' ).click(function() {
		updateLayers();
	});

	// ubcar layer forward
	jQuery( '#ubcar-layer-forward' ).click(function() {
		forwardLayers();
	});

	// ubcar layer backward
	jQuery( '#ubcar-layer-back' ).click(function() {
		backwardLayers();
	});

	jQuery( '[ id^=ubcar-layer-delete- ]' ).click(function() {
		deleteLayers();
	});

	jQuery( '#ubcar-layer-goto' ).click(function() {
		goToLayers();
	});

});

/**
 * AJAX call to class-ubcar-admin-layer.php's ubcar_layer_updater_callback() and
 * ubcar_layer_initial(), inserting an ubcar_layer post and updating the window
 */
function updateLayers() {
	var data = {
		'action': 'layer_updater',
		'ubcar_nonce_field': escapeHTML( jQuery( '#ubcar-nonce-field' ).val() ),
		'ubcar_layer_title': escapeHTML( jQuery( '#ubcar-layer-title' ).val() ),
		'ubcar_layer_description': escapeHTML( jQuery( '#ubcar-layer-description' ).val() ),
		'ubcar_layer_password': escapeHTML( jQuery( '#ubcar-layer-password' ).prop( 'checked' ) )
	};
	jQuery.post( ajax_object.ajax_url, data, function( response ) {
		alert( response );
		data = {
			'action' : 'layer_initial',
			'ubcar_layer_offset' : escapeHTML( jQuery( '#ubcar-layer-display-count' ).val() )
		};
		jQuery.post( ajax_object.ajax_url, data, function( response ) {
			displayLayers( response );
			jQuery( '#ubcar-layer-display-count' ).html( 1 );
			jQuery( '#ubcar-layer-back' ).hide();
			if( response.length < 25 ) {
				jQuery( '#ubcar-layer-forward' ).hide();
			} else {
				jQuery( '#ubcar-layer-forward' ).show();
			}
		});
	});
}

/**
 * AJAX call to class-ubcar-admin-layer.php's ubcar_layer_forward(),
 * incrementing the displayed ubcar_layer posts
 */
function forwardLayers() {
	var data, currentPage;
	data = {
		'action' : 'layer_forward',
		'ubcar_layer_offset' : jQuery( '#ubcar-layer-display-count' ).html()
	};
	jQuery.post( ajax_object.ajax_url, data, function( response ) {
		displayLayers( response );
		currentPage = parseInt( jQuery( '#ubcar-layer-display-count' ).html() );
		jQuery( '#ubcar-layer-display-count' ).html( currentPage + 1 );
		if( response.length < 25 ) {
			jQuery( '#ubcar-layer-forward' ).hide();
		} else {
			jQuery( '#ubcar-layer-forward' ).show();
		}
		jQuery( '#ubcar-layer-back' ).show();
	});
}

/**
 * AJAX call to class-ubcar-admin-layer.php's ubcar_layer_backward(),
 * decrementing the displayed ubcar_layer posts
 */
function backwardLayers() {
	var data, currentPage;
	data = {
		'action' : 'layer_backward',
		'ubcar_layer_offset' : escapeHTML( jQuery( '#ubcar-layer-display-count' ).html() )
	};
	jQuery.post( ajax_object.ajax_url, data, function( response ) {
		displayLayers( response );
		currentPage = parseInt( jQuery( '#ubcar-layer-display-count' ).html() ) - 1;
		jQuery( '#ubcar-layer-display-count' ).html( currentPage );
		if( currentPage === 1 ) {
			jQuery( '#ubcar-layer-back' ).hide();
		}
	});
	jQuery( '#ubcar-layer-forward' ).show();
}

/**
 * AJAX call to class-ubcar-admin-layer.php's ubcar_layer_goto(),
 * going to the designated layer page
 */
function goToLayers() {
	var data, currentPage, desiredPage, maxPage;
	desiredPage = parseInt( jQuery( '#ubcar-layer-choose-count' ).val() );
	maxPage = parseInt( jQuery( '#ubcar-layer-max-count' ).html() )
	if( desiredPage > maxPage ) {
		desiredPage = maxPage;
	} else if( desiredPage < 1 ) {
		desiredPage = 1;
	}
	data = {
		'action' : 'layer_goto',
		'ubcar_layer_offset' : desiredPage
	};
	jQuery.post( ajax_object.ajax_url, data, function( response ) {
		displayLayers( response );
		currentPage = desiredPage;
		jQuery( '#ubcar-layer-choose-count' ).val( currentPage );
		jQuery( '#ubcar-layer-display-count' ).html( currentPage );
		if( currentPage === 1 ) {
			jQuery( '#ubcar-layer-back' ).hide();
		}
		if( response.length < 25 ) {
			jQuery( '#ubcar-layer-forward' ).hide();
		} else {
			jQuery( '#ubcar-layer-forward' ).show();
		}
	});
	jQuery( '#ubcar-layer-forward' ).show();
}

/**
 * AJAX call to class-ubcar-admin-layer.php's ubcar_layer_delete(), deleting the
 * selected ubcar_layer post.
 *
 * @param {Number} deleteID
 */
function deleteLayers( deleteID ) {
	var data;
	if( confirm( 'Are you sure you want to delete this layer?' ) ){
		data = {
			'action' : 'layer_delete',
			'ubcar_nonce_field': escapeHTML( jQuery( '#ubcar-nonce-field' ).val() ),
			'ubcar_layer_delete_id' : deleteID
		};
		jQuery.post( ajax_object.ajax_url, data, function( response ) {
			if( response === false ) {
				alert( 'Sorry, you do not have permission to delete that layer.' );
			} else {
				displayLayers( response );
				jQuery( '#ubcar-layer-display-count' ).html( 1 );
				jQuery( '#ubcar-layer-back' ).hide();
				if( response.length < 25 ) {
					jQuery( '#ubcar-layer-forward' ).hide();
				} else {
					jQuery( '#ubcar-layer-forward' ).show();
				}
			}
		});
	}
}

/**
 * Helper function to display retrieved ubcar_layer posts data.
 *
 * @param {Object[]} response JSON object of ubcar_layer posts' data
 */
function displayLayers( response ) {

	if( Object.prototype.toString.call( response ) === '[object Array]' ) {

		var htmlString, deleteID, editID;
		htmlString = '<tr><td>ID</td><td>Title</td><td>Uploader</td><td>Date Uploaded</td><td>Description</td><td>Blocked?</td><td>Action</td></tr>';
		for( i in response ) {
			htmlString += '<tr id="ubcar-layer-line-';
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
			htmlString += '</td><td style="text-align: center">';
			htmlString += '<input type="checkbox" disabled';
			if( response[ i ].password === 'true' ) {
				htmlString += ' checked';
			}
			htmlString += '></td><td><a id="ubcar-layer-edit-';
			htmlString += response[ i ].ID;
			htmlString += '" class="ubcar-edit-delete-control">Edit</a>/<a class="ubcar-edit-delete-control" id="ubcar-layer-delete-';
			htmlString += response[ i ].ID;
			htmlString += '">Delete</a></td></tr>';
		}
		if( response.length === 0 ) {
			htmlString = 'No layers found.';
		}
		jQuery( '#ubcar-layer-table' ).html( htmlString );

		jQuery( '[ id^=ubcar-layer-delete- ]' ).click(function() {
			deleteID = jQuery( this ).attr( 'id' ).replace( 'ubcar-layer-delete-', '' );
			deleteLayers( deleteID );
		});

		jQuery( '[ id^=ubcar-layer-edit- ]' ).click(function() {
			editID = jQuery( this ).attr( 'id' ).replace( 'ubcar-layer-edit-', '' );
			editLayers( editID );
		});

	} else {
		alert( 'An UBCAR error has occurred. Please log out and try again.' );
	}
}

/**
 * AJAX call to class-ubcar-admin-layer.php's ubcar_layer_edit(), retrieving an
 * ubcar_layer post's data and formatting it for editing.
 *
 * @param {Number} editID
 */
function editLayers( editID ) {
	var data, htmlString;
	data = {
		'action' : 'layer_edit',
		'ubcar_nonce_field': escapeHTML( jQuery( '#ubcar-nonce-field' ).val() ),
		'ubcar_layer_edit_id' : editID
	};
	jQuery.post( ajax_object.ajax_url, data, function( response ) {
		if( response === false ) {
			alert( 'Sorry, you do not have permission to edit that layer.' );
		} else {

			if( Object.prototype.toString.call( response ) === '[object Object]' ) {

				htmlString = '<td>';
				htmlString += response.ID;
				htmlString += '</td><td><input type="text" id="ubcar-layer-edit-title-';
				htmlString += response.ID;
				htmlString += '" value="';
				htmlString += response.title;
				htmlString += '" /></td><td>';
				htmlString += response.uploader;
				htmlString += '</td><td>';
				htmlString += response.date;
				htmlString += '</td><td><textarea id="ubcar-layer-edit-description-';
				htmlString += response.ID;
				htmlString += '">';
				htmlString += response.description;
				htmlString += '</textarea></td><td style="text-align: center"><input type="checkbox" id="ubcar-layer-edit-password-';
				htmlString += response.ID;
				htmlString += '"';
				if( response.password === 'true' ) {
					htmlString += ' checked';
				}
				htmlString += '></td><td><div class="button button-primary" id="ubcar-layer-edit-submit-';
				htmlString += response.ID;
				htmlString += '">Upload Edit</div></td>';
				jQuery( '#' + 'ubcar-layer-line-' + response.ID ).html( htmlString );

			}
		}
		jQuery( '#ubcar-layer-edit-submit-' + editID ).click(function() {
			editLayersSubmit( this );
		});
	});
}

/**
 * AJAX call to class-ubcar-admin-layer.php's ubcar_layer_edit_submit(),
 * submitting an ubcar_layer post's edited information and displaying it
 *
 * @param {Number} thisthis The unique div of the post to be submitted
 */
function editLayersSubmit( thisthis ) {
	var editID, submitData, htmlString;
	editID = jQuery( thisthis ).attr( 'id' ).replace( 'ubcar-layer-edit-submit-', '' );
	submitData = {
		'action' : 'layer_edit_submit',
		'ubcar_nonce_field': escapeHTML( jQuery( '#ubcar-nonce-field' ).val() ),
		'ubcar_layer_edit_id' : editID,
		'ubcar_layer_title': escapeHTML( jQuery( '#ubcar-layer-edit-title-' + editID ).val() ),
		'ubcar_layer_description': escapeHTML( jQuery( '#ubcar-layer-edit-description-' + editID ).val() ),
		'ubcar_layer_password': escapeHTML( jQuery( '#ubcar-layer-edit-password-' + editID ).prop( 'checked' ) )
	};
	jQuery.post( ajax_object.ajax_url, submitData, function( response ) {
		if( response === false ) {
			alert( 'Sorry, you do not have permission to delete that layer.' );
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
				htmlString += '</td><td style="text-align: center"><input type="checkbox"';
				if( response.password === 'true' ) {
					htmlString += ' checked';
				}
				htmlString += ' disabled></td><td>Updated!</td>';
				jQuery( '#' + 'ubcar-layer-line-' + response.ID ).html( htmlString );

			}
		}
	});
}
