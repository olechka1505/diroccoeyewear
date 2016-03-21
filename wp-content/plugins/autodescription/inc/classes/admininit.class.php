<?php
/**
 * The SEO Framework plugin
 * Copyright (C) 2015 - 2016 Sybre Waaijer, CyberWire (https://cyberwire.nl/)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 3 as published
 * by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Class AutoDescription_Admin_Init
 *
 * Initializes the plugin for the wp-admin screens.
 * Enqueues css and javascript.
 *
 * @since 2.1.6
 */
class AutoDescription_Admin_Init extends AutoDescription_Init {

	/**
	 * Constructor, load parent constructor
	 *
	 * Initalizes wp-admin functions
	 */
	public function __construct() {
		parent::__construct();

		add_action( 'admin_init', array( $this, 'post_state' ) );
		add_action( 'init', array( $this, 'post_type_support' ) );

		/**
		 * @since 2.2.4
		 */
		add_filter( 'genesis_detect_seo_plugins', array( $this, 'no_more_genesis_seo' ), 10 );

		/**
		 * @since 2.5.0
		 * Doesn't work. ePanel filters are buggy and inconsistent.
		 */
		// add_filter( 'epanel_page_maintabs', array( $this, 'no_more_elegant_seo' ), 10, 1 );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ), 10, 1 );

		/**
		 * @since 2.5.0
		 *
		 * PHP 5.2 compat
		 * @since 2.5.2
		 */
		add_action( 'admin_footer', array( $this, 'debug_screens' ) );
	}

	/**
	 * Add post state on edit.php to the page or post that has been altered
	 *
	 * Called outside autodescription_run
	 *
	 * Applies `hmpl_ad_states` filters.
	 *
	 * @uses $this->add_post_state
	 *
	 * @since 2.1.0
	 */
	public function post_state() {

		/**
		 * New filter.
		 * @since 2.3.0
		 *
		 * Removed previous filter.
		 * @since 2.3.5
		 */
		$allow_states = (bool) apply_filters( 'the_seo_framework_allow_states', true );

		//* Prevent this function from running if this plugin is set to disabled.
		if ( ! $allow_states )
			return;

		add_filter( 'display_post_states', array( $this, 'add_post_state' ) );

	}

	/**
	 * Adds post states in post/page edit.php query
	 *
	 * @param array states 		the current post state
	 * @param string redirected	$this->get_custom_field( 'redirect' );
	 * @param string noindex	$this->get_custom_field( '_genesis_noindex' );
	 *
	 * @since 2.1.0
	 */
	public function add_post_state( $states = array() ) {

		$post_id = $this->get_the_real_ID( false );

		$searchexclude = (bool) $this->get_custom_field( 'exclude_local_search', $post_id );

		if ( $searchexclude === true )
			$states[] = __( 'No Search', 'autodescription' );

		return $states;
	}

	/**
	 * Removes the Genesis SEO meta boxes on the SEO Settings page
	 *
	 * @since 2.2.4
	 */
	public function no_more_genesis_seo() {

		$plugins = array(
				// Classes to detect.
				'classes' => array(
					'The_SEO_Framework_Load',
				),

				// Functions to detect.
				'functions' => array(),

				// Constants to detect.
				'constants' => array(),
			);

		return (array) $plugins;
	}

	/**
	 * Removes ePanel (Elegant Themes) SEO options.
	 *
	 * @since 2.5.0
	 */
	public function no_more_elegant_seo( $modules = array() ) {

		//* Something went wrong here.
		if ( ! is_array( $modules ) )
			return $modules;

		$modules = array_flip( $modules );
		unset( $modules['seo'] );
		//* Fill the keys back in order.
		$modules = array_values( array_flip( $modules ) );

		/**
		 * Unset globals $options['randomkeyforseo']
		 *
		 * @NOTE to Elegant Themes:
		 * Why Elegant Themes? This is why I never trusted your themes. :(
		 * Uploading most of them in binary will crash also the layout.
		 * And having unsanitized globals $options (great name for a global!), shouldn't be used.
		 *
		 * Try statically cached functions, take a look at the `the_seo_framework_init` function for a great example of countering globals.
		 *
		 * Please also provide more documentation for developers.
		 *
		 * Please rewrite your ePanel. Try to start by adding keys to options and removing globals.
		 * More filters are also for everyone's pleasure :).
		 *
		 * I also recommend using Atom.io or Notepad++, because whatever you're using:
		 * It's not working well with UTF-8.
		 *
		 * @global $options
		 */
		global $options;

		if ( is_array( $options ) ) {
			$keys = array();

			foreach ( $options as $key => $array ) {
				$seo_key = array_search( 'seo', $array );
				if ( false !== $seo_key && 'name' === $seo_key )
					$keys[] = $seo_key;
			}

			foreach ( $keys as $key )
				unset( $options[$key] );
		}

		return (array) $modules;
	}

	/**
	 * Adds post type support
	 *
	 * Applies filters the_seo_framework_supported_post_types : The supported post types.
	 * @since 2.3.1
	 *
	 * @since 2.1.6
	 */
	public function post_type_support() {

		$args = array();

		/**
		 * Added product post type.
		 *
		 * @since 2.3.1
		 */
		$defaults = array(
			'post', 'page',
			'product',
			'forum', 'topic',
			'jetpack-testimonial', 'jetpack-portfolio'
		);
		$post_types = (array) apply_filters( 'the_seo_framework_supported_post_types', $defaults, $args );

		$post_types = wp_parse_args( $args, $post_types );

		foreach ( $post_types as $type )
			add_post_type_support( $type, array( 'autodescription-meta' ) );

	}

	/**
	 * Enqueues scripts in the admin area on the supported screens.
	 *
	 * @since 2.3.3
	 *
	 * @param $hook the current page
	 */
	public function enqueue_admin_scripts( $hook ) {

		/**
		 * Check hook first.
		 * @since 2.3.9
		 */
		if ( isset( $hook ) && ! empty( $hook ) && ( $hook == 'edit.php' || $hook == 'post.php' || $hook = 'edit-tags.php' ) ) {
			/**
			 * @uses $this->post_type_supports_custom_seo()
			 * @since 2.3.9
			 */
			if ( $this->post_type_supports_custom_seo() ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_css' ), 11 );
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_javascript' ), 11 );
			}
		}

	}

	/**
	 * AutoDescription Javascript helper file
	 *
	 * @since 2.0.2
	 *
	 * @usedby add_inpost_seo_box
	 * @usedby enqueue_javascript
	 *
	 * @param string|array|object $hook the current page
	 */
	public function enqueue_admin_javascript( $hook ) {

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script( 'autodescription-js', THE_SEO_FRAMEWORK_DIR_URL . "lib/js/autodescription{$suffix}.js", array( 'jquery' ), THE_SEO_FRAMEWORK_VERSION, true );

		/**
		 * i18n.
		 */
		$blog_name = $this->get_blogname();
		$description = $this->get_blogdescription();

		$tagline = (bool) $this->get_option( 'homepage_tagline' );
		$home_tagline = $this->get_option( 'homepage_title_tagline' );
		$title_location = $this->get_option( 'title_location' );
		$title_rem_additions = (bool) $this->get_option( 'title_rem_additions' );

		$separator = $this->get_separator( 'title', true );

		$rtl = (bool) is_rtl();
		$ishome = false;

		/**
		 * We're gaining UX in exchange for resource usage.
		 *
		 * Any way to cache this?
		 *
		 * @since 2.2.4
		 */
		if ( '' !== $hook ) {
			// We're somewhere within default WordPress pages.
			$post_id = $this->get_the_real_ID();

			if ( $this->is_static_frontpage( $post_id ) ) {
				$title = $blog_name;
				$title_location = $this->get_option( 'home_title_location' );
				$ishome = true;

				if ( $tagline ) {
					$additions = $home_tagline ? $home_tagline : $description;
				} else {
					$additions = '';
				}
			} else if ( $post_id ) {
				//* We're on post.php
				$title = $this->title( '', '', '', array( 'placeholder' => true, 'notagline' => true ) );

				if ( ! $title_rem_additions || ! $this->theme_title_doing_it_right() ) {
					$additions = $blog_name;
				} else {
					$additions = '';
				}
			} else {
				//* We're in a special place.
				// Can't fetch title.
				$title = '';
				$additions = $blog_name;
			}

		} else {
			// We're on our SEO settings pages.
			if ( 'page' === get_option( 'show_on_front' ) ) {
				// Home is a page.
				$inpost_title = $this->get_custom_field( '_genesis_title', get_option( 'page_on_front' ) );
			} else {
				// Home is a blog.
				$inpost_title = '';
			}
			$title = ! empty( $inpost_title ) ? $inpost_title : $blog_name;
			$additions = $home_tagline ? $home_tagline : $description;
		}

		$strings = array(
			'saveAlert'		=> __( 'The changes you made will be lost if you navigate away from this page.', 'autodescription' ),
			'confirmReset'	=> __( 'Are you sure you want to reset all SEO settings to their defaults?', 'autodescription' ),
			'siteTitle' 	=> $title,
			'titleAdditions' => $additions,
			'blogDescription' => $description,
			'titleTagline' 	=> $tagline,
			'titleSeparator' => $separator,
			'titleLocation' => $title_location,
			'isRTL' => $rtl,
			'isHome' => $ishome,
		);

		wp_localize_script( 'autodescription-js', 'autodescriptionL10n', $strings );

	}

	/**
	 * CSS for the AutoDescription Bar
	 *
	 * @since 2.1.9
	 *
	 * @param $hook the current page
	 *
	 * @todo get_network_option
	 */
	public function enqueue_admin_css( $hook ) {

		$rtl = '';

		if ( is_rtl() )
			$rtl = '-rtl';

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style( 'autodescription-css', THE_SEO_FRAMEWORK_DIR_URL . "lib/css/autodescription{$rtl}{$suffix}.css", array(), THE_SEO_FRAMEWORK_VERSION, 'all' );

	}

	/**
	 * Mark up content with code tags.
	 *
	 * Escapes all HTML, so `<` gets changed to `&lt;` and displays correctly.
	 *
	 * @since 2.0.0
	 *
	 * @param  string $content Content to be wrapped in code tags.
	 *
	 * @return string Content wrapped in code tags.
	 */
	public function code_wrap( $content ) {
		return '<code>' . esc_html( $content ) . '</code>';
	}

	/**
	 * Mark up content with code tags.
	 *
	 * Escapes no HTML.
	 *
	 * @since 2.2.2
	 *
	 * @param  string $content Content to be wrapped in code tags.
	 *
	 * @return string Content wrapped in code tags.
	 */
	public function code_wrap_noesc( $content ) {
		return '<code>' . $content . '</code>';
	}

	/**
	 * Return custom field post meta data.
	 *
	 * Return only the first value of custom field. Return false if field is
	 * blank or not set.
	 *
	 * @since 2.0.0
	 *
	 * @param string $field	Custom field key.
	 * @param int $post_id	The post ID
	 *
	 * @return string|boolean Return value or false on failure.
	 *
	 * @thanks StudioPress (http://www.studiopress.com/) for some code.
	 *
	 * @staticvar array $field_cache
	 * @since 2.2.5
	 */
	public function get_custom_field( $field, $post_id = null ) {

		//* No field has been provided.
		if ( empty( $field ) )
			return false;

		//* Setup cache.
		static $field_cache = array();

		//* Check field cache.
		if ( isset( $field_cache[$field][$post_id] ) )
			//* Field has been cached.
			return $field_cache[$field][$post_id];

		if ( null === $post_id || empty( $post_id ) )
			$post_id = $this->get_the_real_ID();

		if ( null === $post_id || empty( $post_id ) )
			return '';

		$custom_field = get_post_meta( $post_id, $field, true );

		// If custom field is empty, return null.
		if ( ! $custom_field )
			$field_cache[$field][$post_id] = '';

		//* Render custom field, slashes stripped, sanitized if string
		$field_cache[$field][$post_id] = is_array( $custom_field ) ? stripslashes_deep( $custom_field ) : stripslashes( wp_kses_decode_entities( $custom_field ) );

		return $field_cache[$field][$post_id];
	}

	/**
	 * Checks the screen hook.
	 *
	 * @since 2.2.2
	 *
	 * @return bool true if screen match.
	 */
	public function is_menu_page( $pagehook = '' ) {
		global $page_hook;

		if ( isset( $page_hook ) && $page_hook === $pagehook )
			return true;

			//* May be too early for $page_hook
		if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] === $pagehook )
			return true;

		return false;
	}

	/**
	 * Redirect the user to an admin page, and add query args to the URL string
	 * for alerts, etc.
	 *
	 * @since 2.2.2
	 *
	 * @param string $page			Menu slug.
	 * @param array  $query_args 	Optional. Associative array of query string arguments
	 * 								(key => value). Default is an empty array.
	 *
	 * @return null Return early if first argument is false.
	 */
	public function admin_redirect( $page, array $query_args = array() ) {

		if ( ! $page )
			return;

		$url = html_entity_decode( menu_page_url( $page, 0 ) );

		foreach ( (array) $query_args as $key => $value ) {
			if ( empty( $key ) && empty( $value ) ) {
				unset( $query_args[$key] );
			}
		}

		$url = add_query_arg( $query_args, $url );

		wp_redirect( esc_url_raw( $url ) );
		exit;

	}

	/**
	 * Google docs language determinator.
	 *
	 * @since 2.2.2
	 *
	 * @staticvar string $language
	 *
	 * @return string language code
	 */
	protected function google_language() {

		/**
		 * Cache value
		 * @since 2.2.4
		 */
		static $language = null;

		if ( isset( $language ) )
			return $language;

		//* Language shorttag to be used in Google help pages,
		$language = _x( 'en', 'e.g. en for English, nl for Dutch, fi for Finish, de for German', 'autodescription' );

		return $language;
	}

	/**
	 * Fetch Tax labels
	 *
	 * @param string $tax_type the Taxonomy type.
	 *
	 * @since 2.3.1
	 *
	 * @staticvar object $labels
	 *
	 * @return object|null with all the labels as member variables
	 */
	public function get_tax_labels( $tax_type ) {

		static $labels = null;

		if ( isset( $labels ) )
			return $labels;

		$tax_object = get_taxonomy( $tax_type );

		if ( is_object( $tax_object ) )
			return $labels = (object) $tax_object->labels;

		//* Nothing found.
		return null;
	}

	/**
	 * Echo debug values.
	 *
	 * @param mixed $values What to be output.
	 *
	 * @since 2.3.4
	 */
	public function echo_debug_information( $values ) {

		if ( $this->the_seo_framework_debug ) {
			echo "\r\n";

			if ( ! $this->the_seo_framework_debug_hidden ) {
				echo "<br>\r\n";
				echo '<span class="code highlight">';
			}

			if ( ! isset( $values ) ) {
				echo $this->debug_value_wrapper( "Debug message: Value isn't set." ) . "\r\n";
				return;
			}

			if ( is_object( $values ) ) {
				// Ugh.
				$values = (array) $values;

				if ( is_array( $values ) ) {
					foreach ( $values as $key => $value ) {
						if ( is_object( $value ) ) {
							foreach ( $values as $key => $value ) {
								$values = $value;
								break;
							}
						}
						break;
					}
				}
			}

			if ( is_array( $values ) ) {
				foreach ( $values as $key => $value ) {
					if ( '' === $value ) {
						echo $this->debug_key_wrapper( $key ) . ' => ';
						echo $this->debug_value_wrapper( "''" );
						echo "\r\n";
					} else if ( is_string( $value ) || is_int( $value ) ) {
						echo $this->debug_key_wrapper( $key ) . ' => ' . $this->debug_value_wrapper( $value );
						echo "\r\n";
					} else if ( is_bool( $value ) ) {
						echo $this->debug_key_wrapper( $key ) . ' => ';
						echo $this->debug_value_wrapper( $value ? 'true' : 'false' );
						echo "\r\n";
					} else if ( is_array( $value ) ) {
						echo $this->debug_key_wrapper( $key ) . ' => ';
						echo "Array[\r\n";
						foreach ( $value as $k => $v ) {
							if ( '' === $v ) {
								echo $this->debug_key_wrapper( $k ) . ' => ';
								echo $this->debug_value_wrapper( "''" );
								echo ',';
								echo "\r\n";
							} else if ( is_string( $v ) || is_int( $v ) ) {
								echo $this->debug_key_wrapper( $k ) . ' => ' . $this->debug_value_wrapper( $v );
								echo ',';
								echo "\r\n";
							} else if ( is_bool( $v ) ) {
								echo $this->debug_key_wrapper( $k ) . ' => ';
								echo $this->debug_value_wrapper( $v ? 'true' : 'false' );
								echo ',';
								echo "\r\n";
							} else if ( is_array( $v ) ) {
								echo $this->debug_key_wrapper( $k ) . ' => ';
								echo $this->debug_value_wrapper( 'Debug message: Three+ dimensional array.' );
								echo ',';
							} else {
								echo $this->debug_key_wrapper( $k ) . ' => ';
								echo $this->debug_value_wrapper( $v );
								echo ',';
								echo "\r\n";
							}
						}
						echo "]";
					} else {
						echo $this->debug_key_wrapper( $key ) . ' => ';
						echo $this->debug_value_wrapper( $value );
						echo "\r\n";
					}
				}
			} else if ( '' === $values ) {
				echo $this->debug_value_wrapper( "''" );
			} else if ( is_string( $values ) || is_int( $values ) ) {
				echo $this->debug_value_wrapper( $values );
			} else if ( is_bool( $values ) ) {
				echo $this->debug_value_wrapper( $values ? 'true' : 'false' );
			} else {
				echo $this->debug_value_wrapper( $values );
			}

			if ( ! $this->the_seo_framework_debug_hidden ) {
				echo '</span>';
			}
			echo "\r\n";
		}

	}

	/**
	 * Wrap debug key in a colored span.
	 *
	 * @param string $key The debug key.
	 *
	 * @since 2.3.9
	 *
	 * @return string
	 */
	public function debug_key_wrapper( $key ) {
		if ( ! $this->the_seo_framework_debug_hidden )
			return '<font color="chucknorris">' . esc_attr( (string) $key ) . '</font>';

		return esc_attr( (string) $key );
	}

	/**
	 * Wrap debug value in a colored span.
	 *
	 * @param string $value The debug value.
	 *
	 * @since 2.3.9
	 *
	 * @return string
	 */
	public function debug_value_wrapper( $value ) {

		if ( ! is_scalar( $value ) )
			return 'Debug message: not scalar';

		if ( ! $this->the_seo_framework_debug_hidden )
			return '<span class="wp-ui-notification">' . esc_attr( (string) trim( $value ) ) . '</span>';

		return esc_attr( (string) $value );
	}

	/**
	 * Echo found screens in the admin footer when debugging is enabled.
	 *
	 * @uses bool $this->the_seo_framework_debug
	 * @global array $current_screen
	 *
	 * @since 2.5.2
	 */
	public function debug_screens() {
		if ( $this->the_seo_framework_debug ) {
			global $current_screen;

			?><div style="float:right;margin:3em;padding:1em;border:1px solid;background:#fff;color:#000;"><?php

				foreach( $current_screen as $screen )
					echo "<p>$screen</p>";

			?></div><?php
		}
	}

}
