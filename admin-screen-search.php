<?php

/**
 * Plugin Name: Admin Screen Search
 * Description: Quick find admin pages with a UI like you find in Chrome's preferences or OSX's System Preferences
 * Version:     0.1
 * Author:      X-Team
 * Author URI:  http://x-team.com/wordpress/
 * License:     GPLv2+
 * Text Domain: admin-search
 * Domain Path: /languages
 */

/**
 * Copyright (c) 2013 X-Team (http://x-team.com/)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @todo need a way to remove Admin Screens when they're deleted (like if a plugin is deactivated)
 * @todo add cleanup on uninstall
 */


class Admin_Screen_Search {

	private static $tags = array(
		'h1',
		'h2',
		'h3',
		'h4',
		'h5',
		'h6',
		'th',
		'label',
		'td',
		'a',
		'strong',
		'em',
		'p',
		'span'
	);


	static function setup() {
		self::load_textdomain();
		add_action( 'admin_init', array( __CLASS__ , 'enqueue_scripts' ) );
		add_action( 'init', array( __CLASS__ , 'create_search_index_post_type' ) );
		add_action( 'wp_ajax_update_search_index', array( __CLASS__ , 'update_search_index' ) );
		add_action( 'wp_ajax_admin_screen_search_autocomplete', array( __CLASS__ , 'admin_screen_search_autocomplete' ) );
		add_action( 'admin_bar_menu', array( __CLASS__ , 'admin_bar_search' ) );

		//Remove the following line before Production
		add_action( 'adminmenu', array( __CLASS__ , 'test_button' ) );
	}


	static function load_textdomain() {
		$text_domain = self::get_plugin_meta( 'TextDomain' );
		$locale      = apply_filters( 'plugin_locale', get_locale(), $text_domain );
		$mo_file     = sprintf( '%s/%s/%s-%s.mo', WP_LANG_DIR, $text_domain, $text_domain, $locale );
		load_textdomain( $text_domain, $mo_file );
		$plugin_rel_path = dirname( plugin_basename( __FILE__ ) ) . trailingslashit( self::get_plugin_meta( 'DomainPath' ) );
		load_plugin_textdomain( $text_domain, false, $plugin_rel_path );
	}

	/**
	 * Add link to fire Indexing Script for Development
	 * located at bottom of Admin Menu (in Sidebar)
	 *
	 * @todo Remove this before Production
	 */
	static function test_button() {
		echo "<a href='#' id='admin-search-test-button' style='font-weight:bold;' >Test Admin Search</a>";
	}

	/**
	 * @param null|string meta key, if omitted all meta are returned
	 * @return array|mixed meta value(s)
	 */
	static function get_plugin_meta( $key = null ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		$data = get_plugin_data( __FILE__ );
		if ( ! is_null( $key ) ) {
			return $data[$key];
		} else {
			return $data;
		}
	}


	/**
	 * @return string the plugin version
	 */
	static function get_version() {
		return self::get_plugin_meta( 'Version' );
	}


	/**
	 * Gets Plugin URL from a path
	 * Not using plugin_dir_url because it is not symlink-friendly
	 */
	static function get_plugin_path_url( $path = null ) {
		$plugin_dirname = basename( dirname( __FILE__ ) );
		$base_dir = trailingslashit( plugin_dir_url( '' ) ) . $plugin_dirname;
		if ( $path ) {
			return trailingslashit( $base_dir ) . ltrim( $path, '/' );
		} else {
			return $base_dir;
		}
	}


	static function enqueue_scripts(){
		wp_enqueue_style(
			'admin-search-style',
			self::get_plugin_path_url( 'admin-search.css' ),
			array(),
			self::get_version()
		);
		wp_enqueue_script( 'jquery-ui-autocomplete' );
		wp_enqueue_script(
			'admin-search-script',
			self::get_plugin_path_url( 'admin-search.js' ),
			array( 'jquery' ),
			self::get_version(),
			true
		);
		wp_localize_script(
			'admin-search-script',
			'screenIndexer', array(
				'ajaxurl' => admin_url( 'admin-ajax.php'),
			)
		);
	}


	/**
	 * Create Search Index Post Type
	 */
	static function create_search_index_post_type() {
		$args = array(
			'label'              => 'Search Index',
			// leave 'public' to true for development
			'public'             => true,
			'publicly_queryable' => false,
			// leave 'show_ui' to true for development
			'show_ui'            => true,
			'hierarchical'       => false,
			'supports'           => array(
				'title',
				'author',
			),
		);
		register_post_type( 'search_index', $args );
	}


	/**
	 * Save Indexed Admin Screen as Post
	 *
	 * @action wp_ajax_update_search_index
	 */
	static function update_search_index() {

		$path   = isset( $_POST['path'] )   ? $_POST['path']   : null;
		$markup = isset( $_POST['markup'] ) ? $_POST['markup'] : null;

		if ( is_null( $path ) ) {
			$error = json_encode( "Path Error" );
			echo $error;
			exit();
		}

		if ( is_null( $markup ) ) {
			$error = json_encode( "Markup Error" );
			echo $error;
			exit();
		}

		$user_ID = get_current_user_id();
		$post_ID = '';
		$post_title = wp_unslash( sanitize_post_field( 'post_title', $path, 0, 'db' ) );
		//check if post exists with user and meta key/value.
		$args = array(
			'author'     => $user_ID,
			'post_type'  => 'search_index',
			's'          => $post_title,
		);
		$posts = get_posts( $args );

		foreach ( $posts as $post ) {
			$post_ID = $post->ID;
		}

		if ( ! empty( $post_ID ) ) {
			$new_post = array(
				'ID'          => $post_ID,
				'post_title'  => $path,
				'post_status' => 'publish',
				'post_author' => $user_ID,
				'post_type'   => 'search_index',
			);
			$post_ID = wp_update_post( $new_post );
		} else {
			$new_post = array(
				'post_title'  => $path,
				'post_status' => 'publish',
				'post_author' => $user_ID,
				'post_type'   => 'search_index',
			);
			$post_ID = wp_insert_post( $new_post );
		}

		update_post_meta( $post_ID, 'search_admin_page', $path );

		self::sort_save_markup( $post_ID, $markup );

		exit();
	}


	/**
	 * Sort the Markup by HTML tag, then save into postmeta
	 *
	 * @todo   Need to account for 'alt' and 'title' attributes
	 * @todo   Eliminate errors thrown by loadHTML()
	 * @todo   Combine preg_replaces
	 *
	 * @param  int     $post_ID  Post ID of Admin Screen
	 * @param  string  $markup   HTML of current Admin Screen
	 * @param  array   $tags     List of HTML tags
	 */
	static function sort_save_markup( $post_ID = null, $markup = null ) {

		if ( is_null( $post_ID ) || is_null( $markup ) ){
			$error = json_encode( "Error Saving Markup" );
			echo $error;
			exit();
		}

		// I'm sure we can combine these preg_replaces
		// Remove line breaks
		$markup = preg_replace( '/\r|\n/', ' ', $markup );
		// Remove More than 2 spaces
		$markup = preg_replace( '/\s{3,}/',' ', $markup );
		// Remove Tabs
		$markup = preg_replace( '/\t+/', ' ', $markup );

		$markup = stripslashes( $markup );

		// Suppress loadHTML errors
		libxml_use_internal_errors( true );

		$dom = new DOMDocument();
		$dom->loadHTML( $markup );

		foreach ( self::$tags as $tag ) {
			$content_array = array();
			$elements = $dom->getElementsByTagName( $tag );
			foreach ( $elements as $element ) {
				$content_array[] = $element->nodeValue;
			}
			update_post_meta( $post_ID, $tag, $content_array );
		}
		unset( $dom );
	}


	/**
	 * Add Search Form to Admin Bar
	 *
	 * (Adapted from Jetpack's Omnisearch)
	 */
	static function admin_bar_search( $wp_admin_bar ) {
		if( ! is_admin() )
			return;

		$form = self::get_admin_search_form();

		$wp_admin_bar->add_menu( array(
			'parent' => 'top-secondary',
			'id'     => 'admin-search',
			'title'  => $form,
			'meta'   => array(
				'class'    => 'admin-bar-search',
				'tabindex' => -1,
			)
		) );
	}


	/**
	 * Creates Admin Search form
	 *
	 * (Adapted from Jetpack's Omnisearch)
	 */
	static function get_admin_search_form( $args = array() ) {
		$defaults = array(
			'search_value'       => isset( $_REQUEST['s'] ) ? $_REQUEST['s'] : null,
			'search_placeholder' => __( 'Search Admin', 'admin-search' ),
			'submit_value'       => __( 'Search', 'admin-search' ),
			'alternate_submit'   => false,
		);
		extract( array_map( 'esc_attr', wp_parse_args( $args, $defaults ) ) );

		ob_start();
		?>

		<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="get" class="admin-search-form" id="admin-search-form">
			<input type="hidden" name="page" value="admin-search" />
			<input name="s" type="search" class="admin-search-input" id="admin-search-input" value="<?php echo $search_value; ?>" placeholder="<?php echo $search_placeholder; ?>" />
			<?php if ( $alternate_submit ) : ?>
				<button type="submit" class="admin-search-submit"><span><?php echo $submit_value; ?></span></button>
			<?php else : ?>
				<input type="submit" class="admin-search-submit" value="<?php echo $submit_value; ?>" />
			<?php endif; ?>
		</form>

		<?php
		return apply_filters( 'get_admin_search_form', ob_get_clean(), $args, $defaults );
	}

	/**
	 *
	 *
	 *
	 * @action admin_screen_search_autocomplete
	 */

	static function admin_screen_search_autocomplete() {

		$term = isset( $_POST['term'] ) ? $_POST['term'] : '' ;

		$user_ID = get_current_user_id();

		$args = array(
			'author'         => $user_ID,
			'post_type'      => 'search_index',
			'posts_per_page' => -1,
		);
		$posts = get_posts( $args );

		$strings = array();
		// For each post, get all tags values saved in post meta and save to an array
		$i = 0;
		foreach ( $posts as $post ) {
			$post_ID = $post->ID;
			foreach ( self::$tags as $tag ) {
				$post_meta = get_post_meta( $post_ID, $tag, true );
				if ( is_array( $post_meta ) ) {
					foreach ( $post_meta as $string ) {
						$strings[$i]['slug'] = $post->post_title;
						$strings[$i]['tag'] = $tag;
						$strings[$i]['string'] = $string;
					}
				} else {
					$strings[$i]['slug'] = $post->post_title;
					$strings[$i]['tag'] = $tag;
					$strings[$i]['string'] = $post_meta;
				}
				$i++;
			}
		}

		// Assemble the Response
		$response = array();
		foreach ( $strings as $string ) {
			if ( strpos( $string['string'], $term ) !== false ) {
				$slug = $string['slug'];
				$response[$slug]['tag'] = $string['tag'];
				$response[$slug]['string'] = $string['string'];
			}
		}

		echo json_encode( $response );

		exit();

	}

}

add_action( 'plugins_loaded', array( 'Admin_Screen_Search', 'setup' ) );