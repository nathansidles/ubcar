jQuery( document ).ready(function( $ ) {

	jQuery( '#ubcar-options-submit' ).click(function() {
		updateOptions();
	});

});

/**
 * AJAX call to class-ubcar-admin.php's ubcar_options_updater_callback(),
 * updating UBCAR's options.
 */
function updateOptions() {

	var cssChoice, displayChoice, pointChoice, tourChoice, controlDisplayChoice, data;

	if( jQuery( '#ubcar-css-choice-1' ).attr( 'checked' ) === 'checked' ) {
		cssChoice = 'responsive';
	} else {
		cssChoice = 'full';
	}
	if( jQuery( '#ubcar-display-choice-1' ).attr( 'checked' ) === 'checked' ) {
		displayChoice = 'separate';
	} else {
		displayChoice = 'unified';
	}
	if( jQuery( '#ubcar-point-choice-1' ).attr( 'checked' ) === 'checked' ) {
		pointChoice = 0;
	} else {
		pointChoice = 1;
	}
	if( jQuery( '#ubcar-tour-choice-1' ).attr( 'checked' ) === 'checked' ) {
		tourChoice = 0;
	} else {
		tourChoice = 1;
	}
	if( jQuery( '#ubcar-control-display-choice-1' ).attr( 'checked' ) === 'checked' ) {
		controlDisplayChoice = 0;
	} else {
		controlDisplayChoice = 1;
	}
	data = {
		'action': 'options_updater',
		'ubcar_css_choice': cssChoice,
		'ubcar_display_choice': displayChoice,
		'ubcar_point_choice': pointChoice,
		'ubcar_tour_choice': tourChoice,
		'ubcar_google_maps_api_key': escapeHTML( jQuery( '#ubcar-google-maps-api-key' ).val() ),
		'ubcar_control_display_choice': controlDisplayChoice
	};
	jQuery.post( ajax_object.ajax_url, data, function( response ) {
		alert( response );
	});
}
