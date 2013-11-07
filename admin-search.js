( function ($) {

	$( document ).ready( function() {

		// Checks to see if Admin Menu has changed.
		checkScreens();

		buildProgressBar();

		$( '.admin-search-input' ).keyup( function( event ){
			var term = $( this ).val();
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
					}
				});
			}
			var lastentry = term;
		});

		highlight();

	});

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
			}
		});
	}

	function buildProgressBar () {
		$( '#wpadminbar' ).prepend( '<div id="admin-search-progress-bar"><div></div></div>' );
		$( '#admin-search-progress-bar' ).css( { 'height': '3px', 'backgroundColor' : '#777' } );
	}

	function indexAdminScreens( totalScreens ) {
		var loadedScreens = 0;
		$( '#adminmenu li > a' ).each( function() {
			var label = '';
			if ( $( this ).hasClass( 'wp-has-submenu' ) ) {
				label = $( '.wp-menu-name', this ).text();
			} else {
				label = $( this ).text();
			}
			$.get( $( this ).attr( 'href' ), function( data ) {
				sendScreenMarkup( label, this.url, data )
				loadedScreens++
				increaseProgressBar( loadedScreens, totalScreens);
			});
		});
	}

	function sendScreenMarkup( label, path, markup ) {
		$.ajax({
			type : 'post',
			dataType : 'json',
			url : screenIndexer.ajaxurl,
			data : { action: 'update_search_index', label : label, path : path, markup : markup }
		});
	}

	function increaseProgressBar( loadedScreens, totalScreens ) {
		var percentage = ( loadedScreens / totalScreens ) * 100;
		console.log( percentage );
		$( '#admin-search-progress-bar div' ).css( { 'height': '3px', 'backgroundColor' : '#0074a2', 'width' : percentage + '%' } );
	}

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

	function highlight() {
		if( getParameterByName( 'admin_search' ) != '' ) {
			var string = getParameterByName( 'admin_search' );
			console.log ( string );
			$("*:contains('" + string + "')" ).each(function(){
				 if ( $( this ).children().length < 1 )
					$( this ).wrapInner( '<span class="highlighted"></span>' );
			});
		}
	}

}( jQuery ) );