( function ($) {

	$( document ).ready( function() {

		// Checks to see if Admin Menu has changed.
		checkScreens();

		$( '.admin-search-input' ).autocomplete({
			source: function( request, response ) {
				$.ajax({
					type: 'post',
					dataType: 'json',
					url: screenIndexer.ajaxurl,
					data: { action : 'admin_screen_search_autocomplete', term : request.term },
					success: function( data ) {
						menuResults( data );
					}
				});
			},
			minLength: 1
		});

	});

	function checkScreens() {
		//send list of slugs to function
		var slugArray = [];
		$( '#adminmenu li > a' ).each( function() {
			slugArray.push( $( this ).attr( 'href' ) );
		});
		$.ajax({
			type : 'post',
			dataType : 'json',
			url : screenIndexer.ajaxurl,
			data : { action: 'check_screens', slugs : slugArray },
			success: function( response ) {
				if ( response ) {
					indexAdminScreens();
				}
			}
		});
	}

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
			data : { action: 'update_search_index', path : path, markup : markup }
		});

		function menuResults( data ) {
			$( '#adminmenu li.wp-has-submenu' ).removeClass( 'opensub' );
			$( '#adminmenu a' ).removeClass( 'admin-search-result h1 h2 h3 h4 h5 h6 th label td a strong em p span' );

			$.each( data, function( slug, array ) {
					console.log( array.tag );
				$( '#adminmenu a[href$="' + slug + '"]' ).addClass( 'admin-search-result ' + array.tag );
			});

			$.each( $( '#adminmenu > li.wp-has-submenu' ), function() {
				self = $( this );
				if ( $( 'ul li a', self ).hasClass( 'admin-search-result' ) ) {
					self.addClass( 'opensub' );
				};
			});

			if ( $( '.opensub' ).length >= 2 ) {
				$( '.opensub' ).css('opacity', '.9');
			} else {
				$( '.opensub' ).css('opacity', '1');
			}

			$( '.opensub' ).hover( function() {
				$( this ).css({'opacity': '1', 'z-index': 1000 });
				//Need to override hoverIntent.out.
			});
		}
	}

}( jQuery ) );