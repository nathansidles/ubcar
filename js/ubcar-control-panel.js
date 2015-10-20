var ubcarAddNewMediaStatus = 0;

jQuery( '#ubcar-add-new-toggle' ).click(function() {
	jQuery( '#ubcar-add-new-form' ).slideToggle( 'slow' );
	if( ubcarAddNewMediaStatus === 0 ) {
		jQuery( '#ubcar-add-toggle-arrow' ).html( "&#9658" );
		ubcarAddNewMediaStatus = 1;
	} else {
		jQuery( '#ubcar-add-toggle-arrow' ).html("&#9660");
		ubcarAddNewMediaStatus = 0;
	}
});

jQuery( '#ubcar-add-new-toggle' ).click();

var entityMap = {
	"&": "&amp;",
	"<": "&lt;",
	">": "&gt;",
	'"': '&quot;',
	"'": '&#39;',
	"/": '&#x2F;',
	"\n": '<br />'
};

function escapeHTML( string ) {
	return String( string ).replace( /[&<>"'\/]|[\n]/g, function ( characterToBeReplaced ) {
		return entityMap[ characterToBeReplaced ];
	});
}
