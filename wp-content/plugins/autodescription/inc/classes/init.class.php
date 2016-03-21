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
 * Class AutoDescription_Init
 *
 * Initializes the plugin
 * Outputs all data in front-end header
 *
 * @since 2.1.6
 */
class AutoDescription_Init {

	/**
	 * Allow object caching through a filter.
	 *
	 * @since 2.4.3
	 *
	 * @var bool Enable object caching.
	 */
	protected $use_object_cache = true;

	/**
	 * Constructor. Init actions.
	 *
	 * @since 2.1.6
	 */
	public function __construct() {

		add_action( 'init', array( $this, 'autodescription_run' ), 1 );
		add_action( 'template_redirect', array( $this, 'custom_field_redirect') );

		/**
		 * Applies filters : the_seo_framework_use_object_cache
		 *
		 * @since 2.4.3
		 */
		$this->use_object_cache = (bool) apply_filters( 'the_seo_framework_use_object_cache', true );

	}

	/**
	 * Run the plugin
	 *
	 * @since 1.0.0
	 */
	public function autodescription_run() {

		/**
		 * Don't run in admin.
		 * Don't do anything on preview either.
		 * @since 2.2.4
		 */
		if ( is_admin() || is_preview() )
			return;

		$this->init_actions();
		$this->init_filters();
	}

	/**
	 * Initialize front-end actions.
	 *
	 * @since 2.5.2
	 */
	protected function init_actions() {

		//* Remove canonical header tag from WP
		remove_action( 'wp_head', 'rel_canonical' );
		//* Remove shortlink.
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
		//* Remove adjecent rel tags.
		remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' );
		//* Earlier removal of the generator tag. Doesn't require filter.
		remove_action( 'wp_head', 'wp_generator' );

		if ( $this->is_theme( 'genesis', false ) ) {
			add_action( 'genesis_meta', array( $this, 'html_output' ), 5 );
		} else {
			add_action( 'wp_head', array( $this, 'html_output' ), 1 );
		}
	}

	/**
	 * Initialize front-end filters.
	 *
	 * @since 2.5.2
	 */
	protected function init_filters() {

		//* Removes all pre_get_document_title filters.
		remove_all_filters( 'pre_get_document_title', false );

		//* New WordPress 4.4.0 filter. Hurray! It's also much faster :)
		add_filter( 'pre_get_document_title', array( $this, 'title_from_cache' ), 10 );
		//* Override AnsPress Theme Title
		add_filter( 'ap_title', array( $this, 'title_from_cache' ), 99, 1 );
		//* Override bbPress title
		add_filter( 'bbp_title', array( $this, 'title_from_cache' ), 99, 3 );
		//* Override Woo Themes Title
		add_filter( 'woo_title', array( $this, 'title_from_cache'), 99 );

		/**
		 * Disables the title tag manipulation on old themes.
		 * Applies filters the_seo_framework_manipulate_title
		 *
		 * @since 2.4.1
		 */
		if ( (bool) apply_filters( 'the_seo_framework_manipulate_title', true ) ) {
			//* Override WordPress Title
			add_filter( 'wp_title', array( $this, 'title_from_cache' ), 9, 3 );
		}
	}

	/**
	 * Header actions.
	 *
	 * @uses The_SEO_Framework_Load::call_function()
	 *
	 * @param string|array $args the arguments that will be passed
	 * @param bool $before if the header actions should be before or after the SEO Frameworks output
	 *
	 * @since 2.2.6
	 *
	 * @return string|null
	 */
	public function header_actions( $args = '', $before = true ) {

		$output = '';

		//* Placeholder callback and args.
		$functions = array();

		/**
		 * New filter.
		 * @since 2.3.0
		 *
		 * Removed previous filter.
		 * @since 2.3.5
		 */
		$filter_tag = $before ? 'the_seo_framework_before_output' : 'the_seo_framework_after_output';
		$filter = (array) apply_filters( $filter_tag, $functions );

		$functions = wp_parse_args( $args, $filter );

		if ( ! empty( $functions ) && is_array( $functions ) ) {
			foreach ( $functions as $function ) {
				$arguments = isset( $function['args'] ) ? $function['args'] : '';

				if ( isset( $function['callback'] ) )
					$output .= $this->call_function( $function['callback'], '2.2.6', $arguments );

			}
		}

		return $output;
	}

	/**
	 * Output the header meta and script
	 *
	 * @since 1.0.0
	 *
	 * @param blog_id : the blog id
	 *
	 * Applies filters the_seo_framework_pre 	: Adds content before
	 * 											: @param before
	 *											: cached
	 * Applies filters the_seo_framework_pro 	: Adds content after
	 *											: @param after
	 *											: cached
	 * Applies filters the_seo_framework_indicator : True to show indicator in html
	 *
	 * @uses hmpl_ad_description()
	 * @uses $this->og_image()
	 * @uses $this->og_locale()
	 * @uses $this->og_type()
	 * @uses $this->og_title()
	 * @uses $this->og_description()
	 * @uses $this->og_url()
	 * @uses $this->og_sitename()
	 * @uses $this->ld_json()
	 * @uses $this->canonical()
	 *
	 * Echos output.
	 */
	public function html_output() {
		global $blog_id, $paged, $page;

		/**
		 * Start the timer here. I know it doesn't calculate the initiation of
		 * the plugin, but it will make the code smelly if I were to do so.
		 * A static array cache counter function would make it possible, but meh.
		 * This function takes the most time anyway.
		 */
		$plugin_start = microtime( true );

		/**
		 * Cache key buster
		 * Hexadecimal revision, e.g. 0, 1, 2, e, f,
		 *
		 * @busted to '2' @version 2.5.2.1
		 */
		$revision = '2';
		$the_id = $this->get_the_real_ID();
		$key = $this->generate_cache_key( $the_id ) . $revision;

		/**
		 * Give each paged pages/archives a different cache key.
		 * @since 2.2.6
		 */
		$page = isset( $page ) ? (string) $page : '0';
		$paged = isset( $paged ) ? (string) $paged : '0';

		$cache_key = 'seo_framework_output_' . $key . '_' . $paged . '_' . $page;

		$output = $this->object_cache_get( $cache_key );
		if ( false === $output ) {

			$robots = $this->robots();

			/**
			 * New filter.
			 * @since 2.3.0
			 *
			 * Removed previous filter.
			 * @since 2.3.5
			 */
			$before = (string) apply_filters( 'the_seo_framework_pre', '' );

			$before_actions = $this->header_actions( '', true );

			//* Limit processing on 404 or search
			if ( ! is_404() && ! is_search() ) {
				$output	= $this->the_description()
						. $this->og_image()
						. $this->og_locale()
						. $this->og_type()
						. $this->og_title()
						. $this->og_description()
						. $this->og_url()
						. $this->og_sitename()
						. $this->facebook_publisher()
						. $this->facebook_author()
						. $this->facebook_app_id()
						. $this->article_published_time()
						. $this->article_modified_time()
						. $this->twitter_card()
						. $this->twitter_site()
						. $this->twitter_creator()
						. $this->twitter_title()
						. $this->twitter_description()
						. $this->twitter_image()
						. $this->shortlink()
						. $this->canonical()
						. $this->paged_urls()
						. $this->ld_json()
						. $this->google_site_output()
						. $this->bing_site_output()
						. $this->pint_site_output()
						;
			} else {
				$output	= $this->og_locale()
						. $this->og_type()
						. $this->og_title()
						. $this->og_url()
						. $this->og_sitename()
						. $this->canonical()
						. $this->google_site_output()
						. $this->bing_site_output()
						. $this->pint_site_output()
						;
			}

			$after_actions = $this->header_actions( '', false );

			/**
			 * New filter.
			 * @since 2.3.0
			 *
			 * Removed previous filter.
			 * @since 2.3.5
			 */
			$after = (string) apply_filters( 'the_seo_framework_pro', '' );

			/**
			 * @see https://wordpress.org/plugins/generator-the-seo-framework/
			 *
			 * New filter.
			 * @since 2.3.0
			 *
			 * Removed previous filter.
			 * @since 2.3.5
			 */
			$generator = (string) apply_filters( 'the_seo_framework_generator_tag', '' );

			if ( '' !== $generator )
				$generator = '<meta name="generator" content="' . esc_attr( $generator ) . '" />' . "\r\n";

			$output = $robots . $before . $before_actions . $output . $after_actions . $after . $generator;

			$this->object_cache_set( $cache_key, $output, 86400 );
		}

		/**
		 * New filter.
		 * @since 2.3.0
		 *
		 * Removed previous filter.
		 * @since 2.3.5
		 */
		$indicator = (bool) apply_filters( 'the_seo_framework_indicator', true );

		$indicatorbefore = '';
		$indicatorafter = '';

		if ( true === $indicator ) {
			$timer = (bool) apply_filters( 'the_seo_framework_indicator_timing', true );
			$sybre = (bool) apply_filters( 'sybre_waaijer_<3', true );

			$start = __( 'Start The Seo Framework', 'autodescription' );
			$end = __( 'End The Seo Framework', 'autodescription' );
			$me =  $sybre ? ' ' . __( 'by Sybre Waaijer', 'autodescription' ) : '';

			$indicatorbefore = '<!-- ' . $start . $me . ' -->' . "\r\n";

			/**
			 * Calculate the plugin load time.
			 * @since 2.4.0
			 */
			if ( true === $timer ) {
				$indicatorafter = '<!-- ' . $end . $me . ' | ' . number_format( microtime( true ) - $plugin_start, 5 ) . 's -->' . "\r\n";
			} else {
				$indicatorafter = '<!-- ' . $end . $me . ' -->' . "\r\n";
			}
		}

		$output = "\r\n" . $indicatorbefore . $output . $indicatorafter . "\r\n";

		echo $output;
	}

	/**
	 * Redirect singular page to an alternate URL.
	 * Called outside html_output
	 *
	 * Applies filters the_seo_framework_allow_external_redirect
	 *
	 * @since 2.0.9
	 */
	public function custom_field_redirect() {

		//* Prevent redirect from options on uneditable pages.
		if ( ! $this->is_singular() )
			return;

		$url = $this->get_custom_field( 'redirect' );

		if ( $url ) {

			/**
			 * New filter.
			 * @since 2.3.0
			 *
			 * Removed previous filter.
			 * @since 2.3.5
			 */
			$allow_external = (bool) apply_filters( 'the_seo_framework_allow_external_redirect', true );

			/**
			 * If the URL is made relative, prevent scheme issues
			 *
			 * Removes http:// and https://
			 *
			 * esc_url_raw uses is_ssl() to make the url valid again :)
			 */
			if ( true !== $allow_external ) {
				$pattern 	= 	'/'
							.	'(((http)(s)?)\:)' 	// 1: http: https:
							. 	'(\/\/)'			// 2: slash slash
							.	'/s'
							;

				$url = preg_replace( $pattern, '', $url );
			}

			wp_redirect( esc_url_raw( $url ), 301 );
			exit;

		}

	}

	/**
	 * Object cache set wrapper.
	 * Applies filters 'the_seo_framework_use_object_cache' : Disable object
	 * caching for this plugin, when applicable.
	 *
	 * @param string $key The Object cache key.
	 * @param mixed $data The Object cache data.
	 * @param int $expire The Object cache expire time.
	 * @param string $group The Object cache group.
	 *
	 * @since 2.4.3
	 *
	 * @return bool true on set, false when disabled.
	 */
	public function object_cache_set( $key, $data, $expire = 0, $group = 'the_seo_framework' ) {

		if ( $this->use_object_cache )
			return wp_cache_set( $key, $data, $group, $expire );

		return false;
	}

	/**
	 * Object cache get wrapper.
	 * Applies filters 'the_seo_framework_use_object_cache' : Disable object
	 * caching for this plugin, when applicable.
	 *
	 * @param string $key The Object cache key.
	 * @param string $group The Object cache group.
	 * @param bool $force Wether to force an update of the local cache.
	 * @param bool $found Wether the key was found in the cache. Disambiguates a return of false, a storable value.
	 *
	 * @since 2.4.3
	 *
	 * @return mixed wp_cache_get if object caching is allowed. False otherwise.
	 */
	public function object_cache_get( $key, $group = 'the_seo_framework', $force = false, &$found = null ) {

		if ( $this->use_object_cache )
			return wp_cache_get( $key, $group, $force, $found );

		return false;
	}

	/**
	 * Well, this is annoying.
	 *
	 * @since 2.4.2
	 * @return something that will make your head explode.
	 */
	public function explode() {
		add_action( 'wp_head', array( $this, 'roll' ) );

		/* the code to run this :
		add_action( 'init', 'tsf_explode' );
		function tsf_explode() {
			if ( function_exists( 'the_seo_framework' ) ) {
				$the_seo_framework = the_seo_framework();
				if (isset( $the_seo_framework ) )
					$the_seo_framework->call_function( array( $the_seo_framework, 'explode' ) );
			}
		}
		*/
	}

	/**
	 * Header CSS
	 *
	 * @since 2.5.2
	 * @return annoying front-end CSS.
	 */
	public function roll() {
		?>
		<style>div:hover>div{-webkit-animation:troll 5s infinite cubic-bezier(0,1.5,.5,1)1s;animation:troll 5s infinite cubic-bezier(0,1.5,.5,1)1s}@-webkit-keyframes troll{100%{-webkit-transform:rotate(0)}75%{-webkit-transform:rotate(30deg)}25%{-webkit-transorm:rotate(0)}0%{-webkit-transorm:rotate(30deg)}}@keyframes troll{100%,25%{transform:rotate(0)}0%,75%{transform:rotate(30deg)}}#container:hover,.site-container:hover{-webkit-animation:none;animation:none}</style>
		<?php
		echo "\r\n";
	}

}
