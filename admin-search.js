( function ($) {

	$( document ).ready( function() {

		$( '.admin-search-input' ).autocomplete({
			source: function( request, response ) {
				$.ajax({
					type: 'post',
					dataType: 'json',
					url: screenIndexer.ajaxurl,
					data: { action : 'admin_screen_search_autocomplete', term : request.term },
					success: function( data ) {
							console.log( data );
							$( '#adminmenu li.wp-has-submenu' ).removeClass( 'opensub' );
							$( '#adminmenu a' ).removeClass( 'admin-search-result h1 h2 h3 h4 h5 h6 th label td a strong em p span' )
							$.each( data, function( slug, array ) {
									console.log( array.tag );
								$( '#adminmenu a[href$="' + slug + '"]' ).addClass( 'admin-search-result ' + array.tag );
							});
							$.each( $( '#adminmenu > li.wp-has-submenu' ), function() {
								self = $( this );
								if ( $( 'ul li a', this ).hasClass( 'admin-search-result' ) ) {
									self.addClass( 'opensub' );
								};
							});
					},
					error: function( jqXHR, textStatus, errorThrown ) {
						console.log( 'Autocomplete Error: ' + jqXHR.responseText );
					}
				});
			},
			minLength: 1
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