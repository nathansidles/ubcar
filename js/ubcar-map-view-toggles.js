jQuery( document ).ready(function() {

	var ubcarLayersBodyStatus, ubcarToursBodyStatus;
	ubcarLayersBodyStatus = 'closed';
	var ubcarToursBodyStatus = 'closed';

	jQuery( '#ubcar-header-information' ).css( 'background', '#DEDEDE' );
	jQuery( '#ubcar-header-media' ).css( 'background', '#DEDEDE' );
	jQuery( '#ubcar-header-comments' ).css( 'background', '#DEDEDE' );
	jQuery( '#ubcar-header-media-submit' ).css( 'background', '#DEDEDE' );
	jQuery( '#ubcar-header-comments-submit' ).css( 'background', '#DEDEDE' );
	jQuery( '#ubcar-header-media-submit' ).css( 'background', '#DEDEDE' );
	jQuery( '#ubcar-header-aggregate' ).css( 'background', '#DEDEDE' );
	jQuery( '#ubcar-display-choice-street' ).css( 'background', '#DEDEDE' );

	jQuery( '#ubcar-accordion-header-layers' ).click(function() {
		jQuery( '#ubcar-accordion-body-layers' ).slideToggle( 'slow' );
		if( ubcarLayersBodyStatus === 'closed' ) {
			ubcarLayersBodyStatus = 'open';
			if( ubcarToursBodyStatus === 'open' ) {
				jQuery( '#ubcar-accordion-body-tours' ).slideToggle( 'slow' );
				ubcarToursBodyStatus = 'closed';
			}
		} else {
			ubcarLayersBodyStatus = 'closed';
		}
	});

	jQuery( '#ubcar-accordion-header-tours' ).click(function() {
		jQuery( '#ubcar-accordion-body-tours' ).slideToggle( 'slow' );
		if( ubcarToursBodyStatus === 'closed' ) {
			ubcarToursBodyStatus = 'open';
			if( ubcarLayersBodyStatus === 'open' ) {
				jQuery( '#ubcar-accordion-body-layers' ).slideToggle( 'slow' );
				ubcarLayersBodyStatus = 'closed';
			}
		} else {
			ubcarToursBodyStatus = 'closed';
		}
	});

	jQuery( '#ubcar-show-all' ).click(function() {
		jQuery( '#ubcar-accordion-body-layers' ).hide();
		jQuery( '#ubcar-accordion-body-tours' ).hide();
		ubcarLayersBodyStatus = 'closed';
		ubcarToursBodyStatus = 'closed';
	});

	jQuery( '#ubcar-header-aggregate' ).click(function() {
		if( jQuery( '#ubcar-body-aggregate' ).html() != '' ) {
			jQuery( '#ubcar-body-comments-submit' ).hide();
			jQuery( '#ubcar-body-media' ).hide();
			jQuery( '#ubcar-body-information' ).hide();
			jQuery( '#ubcar-body-comments' ).hide();
			jQuery( '#ubcar-body-media-submit' ).hide();
			jQuery( '#ubcar-body-aggregate' ).toggle();
		}
	});

	jQuery( '#ubcar-header-information' ).click(function() {
		if( jQuery( '#ubcar-body-information' ).html() != '' ) {
			jQuery( '#ubcar-body-comments-submit' ).hide();
			jQuery( '#ubcar-body-media' ).hide();
			jQuery( '#ubcar-body-comments' ).hide();
			jQuery( '#ubcar-body-aggregate' ).hide();
			jQuery( '#ubcar-body-media-submit' ).hide();
			jQuery( '#ubcar-body-information' ).toggle();
		}
	});

	jQuery( '#ubcar-header-media' ).click(function() {
		if( jQuery( '#ubcar-body-media' ).html() != '' ) {
			jQuery( '#ubcar-body-comments-submit' ).hide();
			jQuery( '#ubcar-body-comments' ).hide();
			jQuery( '#ubcar-body-information' ).hide();
			jQuery( '#ubcar-body-aggregate' ).hide();
			jQuery( '#ubcar-body-media-submit' ).hide();
			jQuery( '#ubcar-body-media' ).toggle();
		}
	});

	jQuery( '#ubcar-header-comments' ).click(function() {
		if( jQuery( '#ubcar-body-comments' ).html() != '' ) {
			jQuery( '#ubcar-body-comments-submit' ).hide();
			jQuery( '#ubcar-body-media' ).hide();
			jQuery( '#ubcar-body-information' ).hide();
			jQuery( '#ubcar-body-aggregate' ).hide();
			jQuery( '#ubcar-body-media-submit' ).hide();
			jQuery( '#ubcar-body-comments' ).toggle();
		}
	});

	jQuery( '#ubcar-header-comments-submit' ).click(function() {
		if( jQuery( '#ubcar-body-comments-submit' ).html() != '' ) {
			jQuery( '#ubcar-body-comments' ).hide();
			jQuery( '#ubcar-body-media' ).hide();
			jQuery( '#ubcar-body-information' ).hide();
			jQuery( '#ubcar-body-aggregate' ).hide();
			jQuery( '#ubcar-body-media-submit' ).hide();
			jQuery( '#ubcar-body-comments-submit' ).toggle();
		}
	});

	jQuery( '#ubcar-header-media-submit' ).click(function() {
		if( jQuery( '#ubcar-body-media-submit' ).html() != '' ) {
			jQuery( '#ubcar-body-comments' ).hide();
			jQuery( '#ubcar-body-media' ).hide();
			jQuery( '#ubcar-body-information' ).hide();
			jQuery( '#ubcar-body-aggregate' ).hide();
			jQuery( '#ubcar-body-comments-submit' ).hide();
			jQuery( '#ubcar-body-media-submit' ).toggle();
		}
	});

});
