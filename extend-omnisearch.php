<?php

if( ! class_exists( 'WP_List_Table' ) )
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

class WP_Admin_Search_Extend_Omnisearch extends WP_List_Table {
	var $post_type = 'post',
	    $post_type_object;

	function __construct( $post_type = 'post' ) {
		$this->post_type = $post_type;
		add_filter( 'omnisearch_results', array( $this, 'search'), 10, 2 );
	}

	function search( $results, $search_term ) {
		if( ! post_type_exists( $this->post_type ) )
			return $results;

		parent::__construct();

		$this->post_type_obj = get_post_type_object( $this->post_type );

		$html = '<h2>' . esc_html( $this->post_type_obj->labels->name ) . '</h2>';

		$this->posts = get_posts( array(
				'post_type'      => $this->post_type,
				'posts_per_page' => -1,
				'post_status'    => 'any',
			)
		);

		$this->posts = $this->scan_posts( $search_term, $this->posts );

		$this->posts = $this->gather_matches( $search_term, $this->posts );

		$this->prepare_items();

		ob_start();
		$this->display();
		$html .= ob_get_clean();

		$results[ $this->post_type_obj->labels->name ] = $html;
		return $results;
	}

	/**
	 * Load pages whose content matches the search terms
	 *
	 * Scan through each search_index post's postmeta, if it finds a match with the
	 * search terms, return the post object into the $admin_pages array.
	 *
	 * @param  string  Search term.
	 * @param  array   Array of Posts created via get_posts.
	 * @return array   Array of Posts matching the search terms.
	 */
	function scan_posts( $search_term, $posts ) {
		$search_term = strtolower( $search_term );
		$admin_pages = array();
		foreach ( $posts as $post ) {
			foreach ( Admin_Screen_Search::$tags as $tag ) {
				$post_meta = get_post_meta( $post->ID, $tag, true );
				if ( is_array( $post_meta ) ) {
					foreach ( $post_meta as $string ) {
						$string = strtolower( $string );
						if ( is_numeric( strpos( $string, $search_term ) ) ) {
							$admin_pages[] = $post;
							break 2;
						}
					}
				} else {
					$post_meta = strtolower( $post_meta );
					if ( is_numeric( strpos( $post_meta, $search_term ) ) ) {
						$admin_pages[] = $post;
						break;
					}
				}
			}
		}
		return $admin_pages;
	}

	/**
	 * Loads Post objects with an array of strings that match search terms.
	 *
	 * Scan through each search_index post's postmeta, if it finds a match with the
	 * search terms, add the found string to an array that will be added to the post object.
	 *
	 * @uses   format_array
	 * @param  string  Search term.
	 * @param  array   Array of Posts.
	 * @return array   Array of Posts with new object property "admin_search_strings".
	 */
	function gather_matches( $search_term, $posts ) {
		$search_term = strtolower( $search_term );
		$admin_pages = array();
		foreach ( $posts as $post ) {
			$strings = array();
			foreach ( Admin_Screen_Search::$tags as $tag ) {
				$post_meta = get_post_meta( $post->ID, $tag, true );
				if ( is_array( $post_meta ) ) {
					foreach ( $post_meta as $string ) {
						$lower_string = strtolower( $string );
						if ( is_numeric( strpos( $lower_string, $search_term ) ) ) {
							$strings[] = wp_trim_words( $string, 20 );
						}
					}
				} else {
					$lower_meta = strtolower( $post_meta );
					if ( is_numeric( strpos( $lower_meta, $search_term ) ) ) {
						$strings[] = wp_trim_words( $string, 20 );
					}
				}
			}
			$post->admin_search_strings = $this->format_array( $strings );
		}
		return $posts;
	}

	/**
	 * Convert array to unordered list.
	 *
	 * Limit list items to first five matches.
	 *
	 * @param  array   Array of Posts.
	 * @return string  Formatted Unordered List.
	 */
	function format_array( $array ) {
		$i = 0;
		ob_start();
			echo '<ul>';
				foreach( $array as $value ) if ( $i++ < 5) {
					echo '<li>' . $value . '</li>';
				}
			echo '</ul>';
		return ob_get_clean();
	}

	function get_columns() {
		$columns = array(
		#	'id' => __('ID', 'admin-search'),
			'post_title' => __('Title', 'admin-search'),
			'snippet' => __('Matches', 'admin-search'),
			'date' => __(''),
		);
		return $columns;
	}

	function prepare_items() {
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = array();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items = $this->posts;
	}

	function column_post_title( $post ) {
		$page_path = get_post_meta( $post->ID, 'admin_screen_search_path', true );
		$actions = array();
		$actions['view'] = sprintf( '<a href="%s">%s</a>', admin_url( $page_path ), esc_html( $this->post_type_obj->labels->view_item ) );
		return wptexturize( $post->post_title ) . $this->row_actions( $actions );
	}

	function column_date( $post ) {}

	function column_default( $post, $column_name ) {
		switch ( $column_name ) {
			case 'id':
				return $post->ID;
			case 'post_title': // Will never happen, class method overrides.
				return $post->post_title;
			case 'snippet':
				return $post->admin_search_strings;
			case 'date': // Will never happen, class method overrides.
				$d = get_option('date_format');
				$t = get_option('time_format');
				return get_post_modified_time( $d, 0, $post, 1 ) . ' @ ' . get_post_modified_time( $t, 0, $post, 1 );
			default:
				return print_r( $post, true );
		}
	}
}

