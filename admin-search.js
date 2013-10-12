( function ($) {

	$( document ).ready( function() {

		$( '.admin-search-input' ).autocomplete({
			source: function( request, response ) {
				$.ajax({
					type: 'post',
					dataType: 'json',
					url: screenIndexer.ajaxurl,
					data: { action : 'admin_screen_search_autocomplete', term : request.term },
					success: function( response ) {
						console.log( 'Autocomplete Success : ' + response );
					},
					error: function( jqXHR, textStatus, errorThrown ) {
						console.log( 'Autocomplete Error: ' + jqXHR.responseText );
					}
				});
			},
			minLength: 3
		});

		$( '#admin-search-test-button' ).click( function(event) {
			event.preventDefault();
			indexAdminScreens();
		});

	});

	function indexAdminScreens() {
		$( '#adminmenu li > a' ).each( function() {
			$.get( $( this ).attr( 'href' ), function( data ) {
				sendScreenMarkup( this.url, data );
			});
		});
	}

	function sendScreenMarkup( path, markup ) {

		$.ajax({
			type : 'post',
			dataType : 'json',
			url : screenIndexer.ajaxurl,
			data : { action: 'update_search_index', path : path, markup : markup },
			success: function( response ) {
				console.log( 'Send Markup Success' );
			},
			error: function( jqXHR, textStatus, errorThrown ) {
				console.log( jqXHR.responseText );
			}

		});




	}

}( jQuery ) );