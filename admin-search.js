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
		$( '#admin-search-progress-bar' ).css( { 'height': '2px' } );
	}

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

	function sendScreenMarkup( label, path, markup, loadedScreens, totalScreens ) {
		$.ajax({
			type : 'post',
			dataType : 'json',
			url : screenIndexer.ajaxurl,
			data : { action: 'update_search_index', label : label, path : path, markup : markup },
			success : function() {
				increaseProgressBar( loadedScreens, totalScreens );
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


}( jQuery ) );