( function ($) {

	$( document ).ready( function() {

		$( '.admin-search-input' ).autocomplete({
			source: 'admin-search-autocomplete.php',
			minLength: 4
		});

		$( '#admin-search-test-button' ).click( function(event) {
			event.preventDefault();
			indexAdminScreens();
		});

	});

	function indexAdminScreens() {
		$( '#adminmenu li > a' ).each( function() {
			$.get( $( this ).attr( 'href' ), function( data ) {
				var path = this.url;
				var markup = $( '#wpbody-content' ).html();
				sendScreenMarkup( path, markup );
			});
		});
	}

	function sendScreenMarkup( path, markup ) {

		$.ajax({
			type : "post",
			dataType : "json",
			url : screenIndexer.ajaxurl,
			data : { action: "update_search_index", path : path, markup : markup },
			success: function( response ) {
				console.log( response );
				console.log( 'Success!' );
			},
			error: function( jqXHR, textStatus, errorThrown ) {
				console.log( jqXHR.responseText );
			}

		});


	}

}( jQuery ) );