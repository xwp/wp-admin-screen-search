( function ($) {

	$( document ).ready( function() {

		// Checks to see if Admin Menu has changed.
		checkScreens();

		$( '.admin-search-input' ).keyup( function( event ){
			var term = $( this ).val();
			if( term != lastentry) {
				$.ajax({
					type: 'post',
					dataType: 'json',
					url: screenIndexer.ajaxurl,
					data: { action : 'admin_screen_search_autocomplete', term : term },
					success: function( data ) {
						menuResults( data );
						$( '.admin-search-input' ).focusout( function() {
							$( '.admin-search-autocomplete ul' ).animate({ opacity: 0, width: 0 }, 300 );
						});
					}
				});
			}
			var lastentry = term;
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
	}

	function menuResults( data ) {
		$( '#adminmenu li.wp-has-submenu' ).removeClass( 'opensub' );
		$( '#adminmenu a' ).removeClass( 'admin-search-result h1 h2 h3 h4 h5 h6 th label td a strong em p span' );
		$( '.admin-search-autocomplete ul' ).html( '' );

		$.each( data, function( slug, array ) {
			var menuLink = $( '#adminmenu a[href$="' + slug + '"]' )
			var linkTitle = $( menuLink ).text();
			menuLink.addClass( 'admin-search-result ' + array.tag );
			if ( menuLink.parents( 'li.wp-has-submenu' ).length ) {
				var parentLi = menuLink.parents( 'li.wp-has-submenu' );
				var parentLink = $( ' > a .wp-menu-name', parentLi ).text();
				$( '.admin-search-autocomplete ul' ).append( '<li><a href="' + slug + '">' + parentLink + '  >  '+ linkTitle + '</a></li>' );
			} else {
				$( '.admin-search-autocomplete ul' ).append( '<li><a href="' + slug + '">' + linkTitle + '</a></li>' );
			}
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

}( jQuery ) );