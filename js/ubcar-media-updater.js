jQuery( document ).ready(function( $ ) {

	var data = {
		'action' : 'media_initial',
		'ubcar_media_offset' : escapeHTML( jQuery( '#ubcar_media_display_count' ).val() ),
		'ubcar_author_name': escapeHTML( jQuery( '#ubcar-author-name' ).val() )
	};
	jQuery.post( ajax_object.ajax_url, data, function( response ) {
		displayMedias( response );
		if( response.length < 25 ) {
			jQuery( '#ubcar-media-forward' ).hide();
		}
		jQuery( '#ubcar-media-back' ).hide();
	});

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

	// ubcar media forward
	jQuery( '#ubcar-media-forward' ).click(function() {
		forwardMedias();
	});

	// ubcar media backward
	jQuery( '#ubcar-media-back' ).click(function() {
		backwardMedias();
	});

	jQuery( '[ id^=ubcar-media-delete- ]' ).click(function() {
		deleteMedias();
	});

	jQuery( '#ubcar-media-goto' ).click(function() {
		goToMedias();
	});

});

/**
 * AJAX call to class-ubcar-admin-medium.php's ubcar_media_forward(),
 * incrementing the displayed ubcar_medium posts
 */
function forwardMedias() {

	var data, currentPage;

	data = {
		'action' : 'media_forward',
		'ubcar_media_offset' : escapeHTML( jQuery( '#ubcar-media-display-count' ).html() ),
		'ubcar_author_name': escapeHTML( jQuery( '#ubcar-author-name' ).val() )
	};
	jQuery.post( ajax_object.ajax_url, data, function( response ) {
		displayMedias( response );
		currentPage = parseInt( jQuery( '#ubcar-media-display-count' ).html() );
		jQuery( '#ubcar-media-display-count' ).html( currentPage + 1 );
		if( response.length < 25 ) {
			jQuery( '#ubcar-media-forward' ).hide();
		} else {
			jQuery( '#ubcar-media-forward' ).show();
		}
		jQuery( '#ubcar-media-back' ).show();
	});
}

/**
 * AJAX call to class-ubcar-admin-medium.php's ubcar_media_backward(),
 * decrementing the displayed ubcar_medium posts
 */
function backwardMedias() {

	var data, currentPage;

	data = {
		'action' : 'media_backward',
		'ubcar_media_offset' : escapeHTML( jQuery( '#ubcar-media-display-count' ).html() ),
		'ubcar_author_name': escapeHTML( jQuery( '#ubcar-author-name' ).val() )
	};
	jQuery.post( ajax_object.ajax_url, data, function( response ) {
		displayMedias( response );
		currentPage = parseInt( jQuery( '#ubcar-media-display-count' ).html() ) - 1;
		jQuery( '#ubcar-media-display-count' ).html( currentPage );
		if( currentPage === 1 ) {
			jQuery( '#ubcar-media-back' ).hide();
		}
	});
	jQuery( '#ubcar-media-forward' ).show();
}

/**
 * AJAX call to class-ubcar-admin-media.php's ubcar_media_goto(),
 * going to the designated media page
 */
function goToMedias() {
	var data, currentPage, desiredPage, maxPage;
	desiredPage = parseInt( jQuery( '#ubcar-media-choose-count' ).val() );
	maxPage = parseInt( jQuery( '#ubcar-media-max-count' ).html() )
	if( desiredPage > maxPage ) {
		desiredPage = maxPage;
	} else if( desiredPage < 1 ) {
		desiredPage = 1;
	}
	data = {
		'action' : 'media_goto',
		'ubcar_media_offset' : desiredPage
	};
	jQuery.post( ajax_object.ajax_url, data, function( response ) {
		displayMedias( response );
		currentPage = desiredPage;
		jQuery( '#ubcar-media-choose-count' ).val( currentPage );
		jQuery( '#ubcar-media-display-count' ).html( currentPage );
		if( currentPage === 1 ) {
			jQuery( '#ubcar-media-back' ).hide();
		}
		if( response.length < 25 ) {
			jQuery( '#ubcar-media-forward' ).hide();
		} else {
			jQuery( '#ubcar-media-forward' ).show();
		}
	});
	jQuery( '#ubcar-media-forward' ).show();
}

/**
 * AJAX call to class-ubcar-admin-medium.php's ubcar_media_delete(), deleting
 * the selected ubcar_medium post.
 *
 * @param {Number} deleteID
 */
function deleteMedias( deleteID ) {

	var data;

	if( confirm( 'Are you sure you want to delete this media?' ) ){
		data = {
			'action' : 'media_delete',
			'ubcar_nonce_field': escapeHTML( jQuery( '#ubcar-nonce-field' ).val() ),
			'ubcar_media_delete_id' : deleteID,
			'ubcar_author_name': escapeHTML( jQuery( '#ubcar-author-name' ).val() )
		};
		jQuery.post( ajax_object.ajax_url, data, function( response ) {
			if( response === '0' ) {
				alert( 'Sorry, you do not have permission to delete that media.' );
			} else {
				displayMedias( response );
				jQuery( '#ubcar-media-display_count' ).html( 1 );
				jQuery( '#ubcar-media-back' ).hide();
				if( response.length < 25 ) {
					jQuery( '#ubcar-media-forward' ).hide();
				} else {
					jQuery( '#ubcar-media-forward' ).show();
				}
			}
		});
	}
}

/**
 * Helper function to display retrieved ubcar_medium posts data.
 *
 * @param {Object[]} response JSON object of ubcar_medium posts' data
 */
function displayMedias( response ) {

	if( Object.prototype.toString.call( response ) === '[object Array]' ) {

		var htmlString, deleteID, editID;

		htmlString = '<tr><td>ID</td><td>Preview</td><td>Title</td><td>Uploader</td><td>Date Uploaded</td><td>Description</td><td>Location</td><td>Layers</td><td>Hide?</td><td>Action</td></tr>';
		for( i in response ) {
			htmlString += '<tr id="ubcar-media-line-';
			htmlString += response[ i ].ID;
			htmlString += '"><td>';
			htmlString += response[ i ].ID;
			htmlString += '</td><td style="text-align: center">';
			if( response[ i ].type === 'image' ) {
				htmlString += '<a href="';
				htmlString += response[ i ].full_size_url;
				htmlString += '" TARGET="_blank"><img src="';
				htmlString += response[ i ].url;
				htmlString += '" alt="';
				htmlString += response[ i ].title;
				htmlString += '" /></a>';
			} else if( response[ i ].type === 'video' ) {
				if( response[ i ].video_type === 'youtube' ) {
					htmlString += '<iframe width="150" height="150" src="//www.youtube.com/embed/' + response[ i ].url + '" frameborder="0" allowfullscreen></iframe>';
				} else if ( response[ i ].video_type === 'vimeo' ) {
					htmlString += '<iframe src="https://player.vimeo.com/video/' + response[ i ].url  + '" width="150" height="150" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
				}
			} else if( response[ i ].type === 'audio' ) {
				htmlString += '<iframe width="150" height="150" scrolling="no" frameborder="no" src="https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/tracks/' + response[ i ].url + '&amp;auto_play=false&amp;hide_related=true&amp;show_comments=false&amp;show_user=true&amp;show_reposts=false&amp;visual=false"></iframe>';
			} else if( response[ i ].type === 'external' || response[ i ].type === 'wiki' ) {
				htmlString += '<input disabled type="text" value="' + response[ i ].url + '">';
			}
			htmlString += '</td><td>';
			htmlString += response[ i ].title;
			htmlString += '</td><td>';
			htmlString += response[ i ].uploader;
			htmlString += '</td><td>';
			htmlString += response[ i ].date;
			htmlString += '</td><td>';
			htmlString += response[ i ].description;
			htmlString += '</td><td>';
			if( response[ i ].location != null ) {
				htmlString += response[ i ].location.title + ' (#' + response[ i ].location.ID + ')';
			}
			htmlString += '</td><td>';
			htmlString += '<select multiple disabled size="5">';
			for( j in response[ i ].layers ) {
				htmlString += '<option value="' + response[ i ].layers[ j ].ID + '">' + response[ i ].layers[ j ].title + ' (#' + response[ i ].layers[ j ].ID + ' )</option>';
			}
			htmlString += '</select>';
			htmlString += '</td><td style="text-align: center"><input type="checkbox" disabled ';
			if( response[ i ].hidden === 'on' ) {
				htmlString += 'checked ';
			}
			htmlString += '/></td><td><a id="ubcar-media-edit-';
			htmlString += response[ i ].ID;
			htmlString += '" class="ubcar-edit-delete-control">Edit</a>/<a class="ubcar-edit-delete-control" id="ubcar-media-delete-';
			htmlString += response[ i ].ID;
			htmlString += '">Delete</a></td></tr>';
		}
		if( response.length === 0 ) {
			htmlString = 'No media found.';
		}
	jQuery( '#ubcar-media-table' ).html( htmlString );
	} else {
		// alert( 'An UBCAR error has occurred! Please log out and back in.' );
	}


	jQuery( '[ id^=ubcar-media-delete- ]' ).click(function() {
		deleteID = jQuery( this ).attr( 'id' ).replace( 'ubcar-media-delete-', '' );
		deleteMedias( deleteID );
	});

	jQuery( '[ id^=ubcar-media-edit- ]' ).click(function() {
		editID = jQuery( this ).attr( 'id' ).replace( 'ubcar-media-edit-', '' );
		editMedias( editID );
	});
}

/**
 * AJAX call to class-ubcar-admin-medium.php's ubcar_media_edit(), retrieving an
 * ubcar_medium post's data and formatting it for editing.
 *
 * @param {Number} editID
 */
function editMedias( editID ) {

	var data, htmlString, selectedLayers;

	data = {
		'action' : 'media_edit',
		'ubcar_nonce_field': escapeHTML( jQuery( '#ubcar-nonce-field' ).val() ),
		'ubcar_media_edit_id' : editID
	};
	jQuery.post( ajax_object.ajax_url, data, function( response ) {
		if( response === false ) {
			alert( 'Sorry, you do not have permission to edit that media.' );
		} else {

			if( Object.prototype.toString.call( response ) === '[object Object]' ) {

				htmlString = '<td>';
				htmlString += response.ID;
				htmlString += '</td><td style="text-align: center">';
				if( response.type === 'image' ) {
					htmlString += '<a href="';
					htmlString += response.full_size_url;
					htmlString += '" TARGET="_blank"><img src="';
					htmlString += response.url;
					htmlString += '" alt="';
					htmlString += response.title;
					htmlString += '" /></a>';
				} else if( response.type === 'video' ) {
					if( response.video_type === 'youtube' ) {
						htmlString += '<iframe width="150" height="150" src="//www.youtube.com/embed/' + response.url + '" frameborder="0" allowfullscreen></iframe>';
					} else if ( response.video_type === 'vimeo' ) {
						htmlString += '<iframe src="https://player.vimeo.com/video/' + response.url  + '" width="150" height="150" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
					}
				} else if( response.type === 'audio' ) {
					htmlString += '<iframe width="150" height="150" scrolling="no" frameborder="no" src="https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/tracks/' + response.url + '&amp;auto_play=false&amp;hide_related=true&amp;show_comments=false&amp;show_user=true&amp;show_reposts=false&amp;visual=false"></iframe>';
				} else if( response.type === 'external' || response.type === 'wiki' ) {
					htmlString += '<input disabled type="text" value="' + response.url + '">';
				}
				htmlString += '</td><td><input type="text" id="ubcar-media-edit-title-';
				htmlString += response.ID;
				htmlString += '" value="';
				htmlString += response.title;
				htmlString += '" /></td><td>';
				htmlString += response.uploader;
				htmlString += '</td><td>';
				htmlString += response.date;
				htmlString += '</td><td><textarea ';
				if( response.type === 'wiki' ) {
					htmlString += 'disabled ';
				}
				htmlString += 'id="ubcar-media-edit-description-';
				htmlString += response.ID;
				htmlString += '">';
				htmlString += response.description;
				htmlString += '</textarea></td><td><select id="ubcar-media-edit-location-';
				htmlString += response.ID;
				htmlString += '">';
				htmlString += '<option value="">---</option>';
				for( j in response.all_locations ) {
					htmlString += '<option ';
					if( response.all_locations[ j ].ID === response.location.ID ) {
						htmlString += 'selected ';
					}
					htmlString += 'value="' + response.all_locations[ j ].ID + '">' + response.all_locations[ j ].title + ' (#' + response.all_locations[ j ].ID + ' )</option>';
				}
				htmlString += '"</select></td><td><select multiple id="ubcar-media-edit-layers-';
				htmlString += response.ID;
				htmlString += '">';
				selectedLayers = [];
				for( j in response.layers ) {
					selectedLayers[ response.layers[ j ].ID ] = true;
				}
				for( j in response.all_layers ) {
					htmlString += '<option ';
					if( selectedLayers[ response.all_layers[ j ].ID ] === true ) {
						htmlString += 'selected ';
					}
					htmlString += 'value="' + response.all_layers[ j ].ID + '">' + response.all_layers[ j ].title + ' (#' + response.all_layers[ j ].ID + ' )</option>';
				}
				htmlString += '"</select></td><td style="text-align: center"><input type="checkbox" id="ubcar-media-edit-hidden-';
				htmlString += response.ID;
				htmlString += '"';
				if( response.hidden === 'on' ) {
					htmlString += ' checked ';
				}
				htmlString += '/></td><td><div class="button button-primary" id="ubcar-media-edit-submit-';
				htmlString += response.ID;
				htmlString += '">Upload Edit</div></td>';
				jQuery( '#ubcar-media-line-' + response.ID ).html( htmlString );
			}
		}
		jQuery( '#ubcar-media-edit-submit-' + editID ).click(function() {
			editMediasSubmit( this, selectedLayers, response.location.ID );
		});
	});
}

/**
 * AJAX call to class-ubcar-admin-medium.php's ubcar_media_edit_submit(),
 * submitting an ubcar_medium post's edited information and displaying it
 *
 * @param {Number} thisthis The unique div of the post to be submitted
 */
function editMediasSubmit( thisthis, old_selectedLayers, old_location ) {

	var editID, newSelectedLayers, removedSelectedLayers, addedSelectedLayers, addedSelectedLayerstoSend, removedSelectedLayerstoSend, submitData, htmlString;

	editID = jQuery( thisthis ).attr( 'id' ).replace( 'ubcar-media-edit-submit-', '' );
	newSelectedLayers = escapeHTML( jQuery( '#ubcar-media-edit-layers-' + editID ).val() );
	removedSelectedLayers = old_selectedLayers;
	addedSelectedLayers = [];
	addedSelectedLayerstoSend = [];
	removedSelectedLayerstoSend = [];
	for( j in newSelectedLayers ) {
		addedSelectedLayers[ newSelectedLayers[ j ] ] = true;
	}
	for( i in removedSelectedLayers ) {
		if( addedSelectedLayers[ i ] === true ) {
			removedSelectedLayers[ i ] = null;
		}
	}
	for( i in old_selectedLayers ) {
		if( addedSelectedLayers[ i ] === true ) {
			addedSelectedLayers[ i ] = null;
		}
	}
	for( i in removedSelectedLayers ) {
		if( removedSelectedLayers[ i ] != null ) {
			removedSelectedLayerstoSend.push( i );
		}
	}
	for( i in addedSelectedLayers ) {
		if( addedSelectedLayers[ i ] != null ) {
			addedSelectedLayerstoSend.push( i );
		}
	}
	submitData = {
		'action' : 'media_edit_submit',
		'ubcar_nonce_field': escapeHTML( jQuery( '#ubcar-nonce-field' ).val() ),
		'ubcar_media_edit_id' : editID,
		'ubcar_media_title': escapeHTML( jQuery( '#ubcar-media-edit-title-' + editID ).val() ),
		'ubcar_media_description': escapeHTML( jQuery( '#ubcar-media-edit-description-' + editID ).val() ),
		'ubcar_media_old_location': old_location,
		'ubcar_media_location': escapeHTML( jQuery( '#ubcar-media-edit-location-' + editID ).val() ),
		'ubcar_media_layers': newSelectedLayers,
		'ubcar_media_added_layers': addedSelectedLayerstoSend,
		'ubcar_media_removed_layers': removedSelectedLayerstoSend,
		'ubcar_media_hidden': jQuery( '#ubcar-media-edit-hidden-' + editID ).prop( 'checked' )
	};
	jQuery.post( ajax_object.ajax_url, submitData, function( response ) {
		if( response === false ) {
			alert( 'Sorry, you do not have permission to delete that media.' );
		} else {

			if( Object.prototype.toString.call( response ) === '[object Object]' ) {

				htmlString = '<td>';
				htmlString += response.ID;
				htmlString += '</td><td style="text-align: center">';
				if( response.type === 'image' ) {
					htmlString += '<a href="';
					htmlString += response.full_size_url;
					htmlString += '" TARGET="_blank"><img src="';
					htmlString += response.url;
					htmlString += '" alt="';
					htmlString += response.title;
					htmlString += '" /></a>';
				} else if( response.type === 'video' ) {
					if( response.video_type === 'youtube' ) {
						htmlString += '<iframe width="150" height="150" src="//www.youtube.com/embed/' + response.url + '" frameborder="0" allowfullscreen></iframe>';
					} else if ( response.video_type === 'vimeo' ) {
						htmlString += '<iframe src="https://player.vimeo.com/video/' + response.url  + '" width="150" height="150" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
					}
				} else if( response.type === 'audio' ) {
					htmlString += '<iframe width="150" height="150" scrolling="no" frameborder="no" src="https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/tracks/' + response.url + '&amp;auto_play=false&amp;hide_related=true&amp;show_comments=false&amp;show_user=true&amp;show_reposts=false&amp;visual=false"></iframe>';
				} else if( response.type === 'external' || response.type === 'wiki' ) {
					htmlString += '<input disabled type="text" value="' + response.url + '">';
				}
				htmlString += '</td><td>';
				htmlString += response.title;
				htmlString += '</td><td>';
				htmlString += response.uploader;
				htmlString += '</td><td>';
				htmlString += response.date;
				htmlString += '</td><td>';
				htmlString += response.description;
				htmlString += '</td><td>';
				if( response.location != null ) {
					htmlString += response.location.title + ' (#' + response.location.ID + ')';
				} else {
					htmlString += 'Deleted location ( #? )';
				}
				htmlString += '</td><td>';
				htmlString += '<select multiple disabled size="5">';
				for( j in response.layers ) {
					htmlString += '<option value="' + response.layers[ j ].ID + '">' + response.layers[ j ].title + ' (#' + response.layers[ j ].ID + ' )</option>';
				}
				htmlString += '</select>';
				htmlString += '</td><td style="text-align: center"><input type="checkbox" disabled ';
				if( response.hidden === 'on' ) {
					htmlString += 'checked ';
				}
				htmlString += '/></td><td>Updated!</td>';
				jQuery( '#' + 'ubcar-media-line-' + response.ID ).html( htmlString );
			}
		}
	});
}
