// todo  Needs to comply with WP JS coding standards

( function ($) {

	// Insert Progress Bar
	$( '#wpadminbar' ).prepend( '<div id="admin-search-progress-bar"><div></div></div>' );
	$( '#adminbarsearch' ).append( '<div class="admin-search-autocomplete"><ul></ul></div>' );
	$( '#adminbar-search' ).attr('autocomplete', 'off');

	$( document ).ready( function() {

		// Highlights search string on resulting page using URL parameter
		highlight();

		// Checks to see if Admin Menu has changed.
		checkScreens();

		$( '#adminbar-search' ).keyup( function( event ){
			var term = $( '#adminbar-search' ).val();
			console.log( term );
			if( term != lastentry) {
				$.ajax({
					type: 'post',
					dataType: 'json',
					url: screenIndexer.ajaxurl,
					data: { action : 'admin_screen_search_autocomplete', term : term },
					success: function( data ) {
						$( '.admin-search-autocomplete ul' ).show().width( 'auto' ).addClass( 'open' );
						menuResults( data );
						$( '.admin-bar-autocomplete ul' ).focusout( function() {
							$( '.admin-search-autocomplete ul' ).animate({ opacity: 0, width: 0 }, 300 );
						});
					},
					error: function( j, t, e ) {
						console.log( j.responseText );
					}
				});
			}
			var lastentry = term;
		});

	});

	// Checks to see if Admin Menu has changed.
	// todo  Need to ensure this is working.
	function checkScreens() {
		//send list of slugs to function
		var slugArray = [];
		var totalScreens = 0;
		$( '#adminmenu li > a' ).each( function() {
			slugArray.push( $( this ).attr( 'href' ) );
			totalScreens++
		});
		$.ajax({
			type : 'post',
			dataType : 'json',
			url : screenIndexer.ajaxurl,
			data : { action: 'check_screens', slugs : slugArray },
			success: function( response ) {
				indexAdminScreens( totalScreens );
			},
			error: function( j, t, e ) {
				console.log( j.responseText );
			}
		});
	}

	// "Crawls" pages to save content
	function indexAdminScreens( totalScreens ) {
		var loadedScreens = 1;
		$( '#adminmenu li > a' ).each( function() {
			var label = '';
			if ( $( this ).hasClass( 'wp-has-submenu' ) ) {
				label = $( '.wp-menu-name', this ).text();
			} else {
				label = $( this ).text();
			}
			$.get( $( this ).attr( 'href' ), function( data ) {
				sendScreenMarkup( label, this.url, data, loadedScreens, totalScreens );
				loadedScreens++
			});
		});
	}

	// Helps screen indexer by actually sending the screen's markup
	function sendScreenMarkup( label, path, markup, loadedScreens, totalScreens ) {
		$.ajax({
			type : 'post',
			dataType : 'json',
			url : screenIndexer.ajaxurl,
			data : { action: 'update_search_index', label : label, path : path, markup : markup },
			success : function() {
				increaseProgressBar( loadedScreens, totalScreens );
			},
			error: function( j, t, e ) {
				console.log( j.responseText );
			}
		});
	}

	function increaseProgressBar( loadedScreens, totalScreens ) {
		var percentage = ( loadedScreens / totalScreens ) * 100;
		console.log( loadedScreens + ' : ' + percentage );
		$( '#admin-search-progress-bar div' ).css( {'height' : '2px', 'backgroundColor' : '#0074a2', 'width' : percentage + '%' } );
		if ( percentage == 100 ) {
			$( '#admin-search-progress-bar div' ).delay( 1000 ).fadeOut( 'slow' );
		}
	}

	// Highlights menu items that contain search results
	function menuResults( data ) {
		$( '#adminmenu li.wp-has-submenu' ).removeClass( 'opensub' );
		$( '#adminmenu a' ).removeClass( 'admin-search-result h1 h2 h3 h4 h5 h6 th label td a strong em p span' );
		$( '.admin-search-autocomplete ul' ).html( '' );

		$.each( data, function( slug, array ) {
			var menuLink = $( '#adminmenu a[href$="' + slug + '"]' ).last();
			var linkTitle = $( menuLink ).text();
			menuLink.addClass( 'admin-search-result ' + array.tag );
			if ( menuLink.parents( 'li.wp-has-submenu' ).length ) {
				var parentLi = menuLink.parents( 'li.wp-has-submenu' );
				var parentLink = $( ' > a .wp-menu-name', parentLi ).text();
				$( '.admin-search-autocomplete ul' ).append( '<li><a href="' + array.url + '">' + parentLink + '  >  '+ linkTitle + '</a></li>' );
			} else {
				$( '.admin-search-autocomplete ul' ).append( '<li><a href="' + array.url + '">' + linkTitle + '</a></li>' );
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

	// Highlights search string on resulting page using URL parameter
	// todo  Needs to account for strings with inline html elements inside,
	//       like "This is <strong>a string</strong>."
	function highlight() {
		if( getParameterByName( 'admin_search' ) != '' ) {
			var string = getParameterByName( 'admin_search' );
			$("*:contains('" + string + "')" ).each(function(){
				if ( $( this ).children().length < 1 ) {
					$( this ).wrapInner( '<span class="highlighted"></span>' );
				}
			});
		}
	}

	function getParameterByName( name ) {
		var href = window.location.href;
		name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
		var regexS = "[\\?&]"+name+"=([^&#]*)";
		var regex = new RegExp( regexS );
		var results = regex.exec( href );
		if( results == null ) {
			return "";
		} else {
			return decodeURIComponent(results[1].replace(/\+/g, " "));
		}
	}

}( jQuery ) );