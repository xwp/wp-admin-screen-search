( function ($) {

	$( document ).ready( function() {

		var origColor = $( '#adminmenu a' ).css( 'color' );

		$( '.admin-search-input' ).autocomplete({
			source: function( request, response ) {
				$.ajax({
					type: 'post',
					dataType: 'json',
					url: screenIndexer.ajaxurl,
					data: { action : 'admin_screen_search_autocomplete', term : request.term },
					success: function( data ) {
							console.log( data );
							$( '#adminmenu a' ).css( 'color', origColor );
							$.each( data, function( slug, string ) {
								console.log( slug );
							$( '#adminmenu a[href$="' + slug + '"]' ).css( { 'color' : '#f00' });
							});
					},
					error: function( jqXHR, textStatus, errorThrown ) {
						console.log( 'Autocomplete Error: ' + jqXHR.responseText );
					}
				});
			},
			minLength: 1
		});

		$( '.admin-search-input' ).attr( 'autocomplete', 'on' );

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