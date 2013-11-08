<?php
/**
 * @todo Rank search results
 * @todo Limit number of results
 */

if ( ! class_exists( 'WP_List_Table' ) )
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

class WP_Admin_Search_Extend_Omnisearch extends WP_List_Table {
	var $post_type = 'post',
		$post_type_object;

	function __construct( $post_type = 'post' ) {
		$this->post_type = $post_type;
		add_filter( 'omnisearch_results', array( $this, 'search' ), 10, 2 );
	}

	function search( $results, $search_term ) {
		if ( ! post_type_exists( $this->post_type ) )
			return $results;

		parent::__construct();

		$this->post_type_obj = get_post_type_object( $this->post_type );

		$html = '<h2>' . esc_html( $this->post_type_obj->labels->name ) . '</h2>';

		$this->posts = get_posts(
			array(
				'post_type'      => $this->post_type,
				'posts_per_page' => -1,
				'post_status'    => 'any',
			)
		);

		$this->posts = Admin_Screen_Search::scan_posts( $search_term, $this->posts );

		$this->posts = Admin_Screen_Search::gather_matches( $search_term, $this->posts );

		$this->prepare_items();

		ob_start();
		$this->display();
		$html .= ob_get_clean();

		$results[ $this->post_type_obj->labels->name ] = $html;
		return $results;
	}

	function get_columns() {
		$columns = array(
		#	'id' => __('ID', 'admin-search'),
			'post_title' => __( 'Title', 'admin-search' ),
			'snippet' => __( 'Matches', 'admin-search' ),
			'date' => __( '' ),
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
				$d = get_option( 'date_format' );
				$t = get_option( 'time_format' );
				return get_post_modified_time( $d, 0, $post, 1 ) . ' @ ' . get_post_modified_time( $t, 0, $post, 1 );
			default:
				return print_r( $post, true );
		}
	}
}

