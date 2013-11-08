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
 */


class Admin_Screen_Search {


	public static $tags = array(
		'h1',
		'h2',
		'h3',
		'h4',
		'h5',
		'h6',
		// 'th', // Exclude table headers
		'label',
		'td',
		'a',
		'strong',
		'em',
		'div',
		'p',
		'span',
	);


	static function setup() {
		self::load_textdomain();
		add_action( 'admin_init', array( __CLASS__, 'enqueue_scripts' ) );
		add_action( 'init', array( __CLASS__, 'create_search_index_post_type' ) );
		add_action( 'wp_ajax_update_search_index', array( __CLASS__, 'update_search_index' ) );
		add_action( 'wp_ajax_admin_screen_search_autocomplete', array( __CLASS__, 'admin_screen_search_autocomplete' ) );
		add_action( 'wp_ajax_check_screens', array( __CLASS__, 'check_screens' ) );
		add_action( 'omnisearch_add_providers', array( __CLASS__, 'integrate_with_omnisearch' ) );
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
	 *
	 * @param  string  URL Path
	 * @return string  New URL
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


	/**
	 * Enqueue Scripts
	 *
	 * @uses get_plugin_path_url
	 */
	static function enqueue_scripts(){
		wp_enqueue_style(
			'admin-search-style',
			self::get_plugin_path_url( 'admin-search.css' ),
			array(),
			self::get_version()
		);
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
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
			)
		);
	}


	/**
	 * Create Search Index Post Type
	 *
	 * @todo Disable "show_ui" before production
	 */
	static function create_search_index_post_type() {
		$args = array(
			'label'              => 'Admin Screens',
			'labels'             => array(
				'singluar_name'  => 'Admin Screen',
				'view_item'      => 'View Screen',
			),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'hierarchical'       => false,
			'supports'           => array(
				'title',
				'author',
			),
		);
		register_post_type( 'admin_search_index', $args );
	}


	/**
	 * Check slugs of links in Admin Menu to see if they've changed.
	 *
	 * @action wp_ajax_check_screens
	 */
	static function check_screens() {
		$new_slug_array = isset( $_POST['slugs'] ) ? $_POST['slugs'] : null;
		if ( is_null( $new_slug_array ) ) {
			$error = 'Slugs Error';
			wp_send_json_error( $error );
		}

		$old_slug_array = get_option( 'admin_search_slugs' );
		update_option( 'admin_search_slugs', $new_slug_array );
		if ( ! ( $old_slug_array === $new_slug_array ) ) {

			$posts = get_posts( array( 'post_type' => 'admin_search_index', 'posts_per_page' => -1 ) );
			foreach ( $posts as $post ) {
				if ( ! in_array( $post->post_title, $new_slug_array ) ) {
					$meta_values = get_post_meta( $post->ID );
					foreach ( $meta_values as $value ) {
							delete_post_meta( $post->ID, $value );
						}
						wp_delete_post( $post->ID, true );
					}
				}
			wp_send_json_success( 'updated' );
		}
		wp_send_json_success( 'finished' );
	}


	/**
	 * Save Indexed Admin Screen as Post
	 *
	 * @action wp_ajax_update_search_index
	 * @uses sort_save_markup
	 */
	static function update_search_index() {

		$label  = isset( $_POST['label'] )  ? $_POST['label']  : null;
		$path   = isset( $_POST['path'] )   ? $_POST['path']   : null;
		$markup = isset( $_POST['markup'] ) ? $_POST['markup'] : null;

		if ( is_null( $label ) ) {
			$error = 'Label Error';
			wp_send_json_error( $error );
		}

		if ( is_null( $path ) ) {
			$error = 'Path Error';
			wp_send_json_error( $error );
		}

		if ( is_null( $markup ) ) {
			$error = 'Markup Error';
			wp_send_json_error( $error );
		}

		$user_ID    = get_current_user_id();
		$post_ID    = '';
		$post_title = wp_unslash( sanitize_text_field( $label ) );
		$path       = wp_unslash( sanitize_text_field( $path ) );

		// Check if post exists by searching for matching post title
		$args = array(
			'author'     => $user_ID,
			'post_type'  => 'admin_search_index',
			's'          => $post_title,
		);
		$posts = get_posts( $args );

		foreach ( $posts as $post ) {
			$post_ID = $post->ID;
		}

		if ( ! empty( $post_ID ) ) {
			$new_post = array(
				'ID'          => $post_ID,
				'post_title'  => $label,
				'post_status' => 'publish',
				'post_author' => $user_ID,
				'post_type'   => 'admin_search_index',
			);
			$post_ID = wp_update_post( $new_post );
		} else {
			$new_post = array(
				'post_title'  => $label,
				'post_status' => 'publish',
				'post_author' => $user_ID,
				'post_type'   => 'admin_search_index',
			);
			$post_ID = wp_insert_post( $new_post );
		}

		update_post_meta( $post_ID, 'admin_screen_search_path', $path );

		self::sort_save_markup( $post_ID, $markup );

	}


	/**
	 * Sort the Markup by HTML tag, then save into postmeta
	 *
	 * @todo   Need to account for 'alt' and 'title' attributes
	 * @todo   Combine preg_replaces
	 * @todo   Remove irrelevant elements from DOM (like Contextual Help, below)
	 *
	 * @param  int     $post_ID  Post ID of Admin Screen
	 * @param  string  $markup   HTML of current Admin Screen
	 */
	static function sort_save_markup( $post_ID = null, $markup = null ) {

		if ( is_null( $post_ID ) || is_null( $markup ) ){
			$error = 'Error Saving Markup';
			wp_send_json_error( $error );
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

		// Only search #wpbody
		$body = $dom->getElementById( 'wpbody' );

		// exclude contextual help
		$help = $dom->getElementById( 'screen-meta' );
		if ( isset( $help ) ) {
			$help->parentNode->removeChild( $help );
		}

		if ( isset( $body ) ) {
			foreach ( self::$tags as $tag ) {
				$content_array = array();
				$elements = $body->getElementsByTagName( $tag );
				foreach ( $elements as $element ) {
					$content_array[] = $element->nodeValue;
				}
				update_post_meta( $post_ID, $tag, $content_array );
			}
		}
		unset( $dom );
	}


	/**
	 * Connect Admin Screens with Omnisearch
	 *
	 * @action omnisearch_add_providers
	 */
	static function integrate_with_omnisearch() {
		if ( ! is_plugin_active( 'jetpack/jetpack.php' ) )
			return;

		require_once( plugin_dir_path( __FILE__ ) . '/extend-omnisearch.php' );
		new WP_Admin_Search_Extend_Omnisearch( 'admin_search_index' );
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
	public static function scan_posts( $search_term, $posts ){
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
	public static function gather_matches( $search_term, $posts ) {
		$search_term = strtolower( $search_term );
		$admin_pages = array();
		foreach ( $posts as $post ) {
			$strings = array();
			foreach ( self::$tags as $tag ) {
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
	public static function format_array( $array ) {
		$i = 0;
		ob_start();
			echo '<ul>';
				foreach ( $array as $value ) {
					if ( $i++ < 5 ) {
						echo '<li>' . $value . '</li>';
				}
			echo '</ul>';
		return ob_get_clean();
	}


	/**
	 * Remove all plugin data
	 */
	static function uninstall() {
		$tags  = self::$tags;
		$posts = get_posts( array( 'post_type' => 'admin_search_index', 'posts_per_page' => -1 ) );
		foreach ( $posts as $post ) {
			foreach ( $tags as $tag ) {
				delete_post_meta( $post->ID, $tag );
			}
			delete_post_meta( $post->ID, 'admin_screen_search_path' );
			wp_delete_post( $post->ID );
		}
		delete_option( 'admin_search_slugs' );
	}

}

add_action( 'plugins_loaded', array( 'Admin_Screen_Search', 'setup' ) );