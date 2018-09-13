<?php

/*
Plugin Name: BooXtream Social DRM for WooCommerce
Plugin URI: http://www.booxtream.com/woocommerce
Description: Enables the use of BooXtream Social DRM with WooCommerce
Version: 1.1.0
Author: Icontact B.V.
Author URI: http://www.icontact.nl/
License: GPLv2 or later
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_BooXtream' ) ) :

	if (!defined('BOOXTREAM_PLUGIN_VERSION'))
		define('BOOXTREAM_PLUGIN_VERSION', '1.1.0');

	class WC_BooXtream {
		/**
		 * Instance of this class.
		 *
		 * @var object
		 */
		protected static $instance = null;

		const contractinfourl = 'https://service.booxtream.com/admin/contract/info.json';
		const accountsurl = 'https://service.booxtream.com/admin/contract/accounts.json';
		const storedfilesurl = 'https://service.booxtream.com/storedfiles/';
		const listepubfilesurl = 'https://service.booxtream.com/admin/contract/account/ACCOUNTKEY/storedfiles/listepub.json';
		const listexlibrisfilesurl = 'https://service.booxtream.com/admin/contract/account/ACCOUNTKEY/storedfiles/listexlibris.json';

		/**
		 * Instance of integration
		 *
		 * @var object
		 */
		private $settings = null;

		/**
		 * Construct the plugin when WooCommerce is loaded
		 */
		public function __construct() {
			// Call initialization
			if ( in_array( 'woocommerce/woocommerce.php',
				apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
				add_action( 'plugins_loaded', array( $this, 'init' ) );
			}

		}

		public function add_custom_order_status() {
			register_post_status( 'wc-booxtream-error', array(
				'label'                     => __( 'BooXtream error', 'woocommerce_booxtream' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'BooXtream error <span class="count">(%s)</span>',
					'BooXtream error <span class="count">(%s)</span>' )
			) );
		}

		public function add_custom_order_status_to_list( $order_statuses ) {

			$new_order_statuses = array();

			// add new order status after processing
			foreach ( $order_statuses as $key => $status ) {

				$new_order_statuses[ $key ] = $status;

				if ( 'wc-processing' === $key ) {
					$new_order_statuses['wc-booxtream-error'] = __( 'BooXtream error', 'woocommerce_booxtream' );
				}
			}

			return $new_order_statuses;
		}


		/**
		 * Return an instance of this class.
		 *
		 * @return object A single instance of this class.
		 */
		public static function get_instance() {
			// If the single instance hasn't been set, set it now.
			if ( null == self::$instance ) {
				self::$instance = new self;
			}

			return self::$instance;
		}

		public static function check_requirements() {
			$errors = array();

			/*
			 * SimpleXML
			 */
			if ( ! class_exists( 'SimpleXMLElement' ) ) {
				$errors[] = __( 'Your version of PHP does not support SimpleXML', 'woocommerce_booxtream' );
			}

			/*
			 * Test connection to BooXtream
			 */
			$result = self::test_connection();
			if ( ! $result || $result['code'] !== 200 ) {
				$errors[] = __( 'Unable to connect to the BooXtream servers. Please check your configuration and your firewall.',
					'woocommerce_booxtream' );
			}

			/*
			* Test connection to Woocommerce API
			*/
			$result = self::test_permalinks();
			if ( ! $result ) {
				$errors[] = __( 'Permalinks are not enabled.', 'woocommerce_booxtream' );
			}

			/*
			 * Check Woocommerce API
			 */
			$result = self::test_api();
			if ( ! $result ) {
				$errors[] = __( 'Please enable Woocommerce API.', 'woocommerce_booxtream' );
			}


			/*
			 * @todo: test if we can generate downloadlinks
			 */


			return $errors;
		}

		public static function activate_plugin() {
			global $wp_rewrite;

			$errors = self::check_requirements();
			if ( empty ( $errors ) ) {
				WC_BooXtream::add_rewrite_rules();
				$wp_rewrite->flush_rules();

				/*
				 * Create page for processing asynchronous request to BooXtream
				 */
				$slug    = __( 'download_processing', 'woocommerce_booxtream' );
				$title   = __( 'Your download is not ready yet', 'woocommerce_booxtream' );
				$content = '<p>' . __( 'We are currently processing your download, please try again in a few seconds',
						'woocommerce_booxtream' ) . '</p>';
				wc_create_page( esc_sql( $slug ), 'woocommerce_download_processing_page_id', $title, $content, '' );

				return;
			}

			// Suppress "Plugin activated" notice.
			unset( $_GET['activate'] );

			// this plugin's name
			$name = get_file_data( __FILE__, array( 'Plugin Name' ), 'plugin' );

			if ( count( $errors ) > 0 ) {
				printf(
					'<div class="error"><p>%1$s</p>' .
					'<p><i>%2$s</i> ' .
					__( 'has been deactivated', 'woocommerce_booxtream' ) .
					'</p></div>',
					join( '</p><p>', $errors ),
					$name[0]
				);
				deactivate_plugins( plugin_basename( __FILE__ ) );
			}
			// this will trigger the much coveted fatal error instead of 'unexpected output'
			if(is_admin()) exit;
		}

		public static function test_connection() {
			$url  = 'https://service.booxtream.com/online';
			$args = array(
				'method'      => 'POST',
				'timeout'     => 60,
				'redirection' => 3,
				'user-agent'  => 'pluginconnectioncheck',
				'httpversion' => '1.1',
			);
			$post = wp_remote_post( $url, $args );
			if ( is_wp_error( $post ) ) {
				return false;
			}
			$headers = $post['headers'];
			if ( ! is_array( $headers ) ) {
				$headers = $post['headers']->getAll();
			}

			$response = $post['response'];
			$response = array_merge( $response, $headers );

			return $response;
		}

		public static function test_permalinks() {
			if ( 0 === strlen( get_option( 'permalink_structure' ) ) ) {
				return false;
			}

			return true;
		}

		public static function test_api() {
			if ( 'yes' === get_option( 'woocommerce_api_enabled' ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Initialize the plugin.
		 */
		public function init() {
			// Load integration
			if ( class_exists( 'WC_Integration' ) ) {
				// Include our integration class.
				include_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-booxtream-integration.php';
				// Register the integration.
				add_filter( 'woocommerce_integrations', array( $this, 'add_integration' ) );

			} else {
				// @todo: handle this?
			}

			// Suppress warnings from php for simplexml (we handle exceptions)
			libxml_use_internal_errors( true );

			// Initialize the rest of the plugin
			add_action( 'woocommerce_init', array( $this, 'load_classes' ) );

			// handle the callback to BooXtream
			add_action( 'woocommerce_api_booxtream_callback', array( $this, 'callback_handler' ) );

			// download handling
			add_action( 'pre_get_posts', array( $this, 'handle_download' ) );

			// rewrites
			add_action( 'init', array( $this, 'add_rewrite_rules' ), 10, 0 );
			add_filter( 'query_vars', array( $this, 'add_query_vars' ) );

			// add custom order status
			add_action( 'init', array( $this, 'add_custom_order_status' ) );
			add_filter( 'wc_order_statuses', array( $this, 'add_custom_order_status_to_list' ) );
			add_filter('woocommerce_hidden_order_itemmeta', array( $this, 'woocommerce_hidden_order_itemmeta'), 10, 1);

			// load textdomain
			load_plugin_textdomain( 'woocommerce_booxtream', false, basename( dirname( __FILE__ ) ) . '/languages/' );

		}

		/**
		 * Load customer settings and classes
		 */
		public function load_classes() {
			// load settings
			global $woocommerce;

			// get integration
			$integrations   = $woocommerce->integrations->get_integrations();
			$this->settings = $integrations['booxtream'];

			// Load product class
			include_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-booxtream-product.php';
			new WC_BooXtream_Product( $this->settings );

			// Load order class
			include_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-booxtream-order.php';
			new WC_BooXtream_Order( $this->settings );

			// Load request class
			include_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-booxtream-request.php';

		}


		/**
		 * @param $integrations
		 *
		 * @return array
		 */
		public function add_integration( $integrations ) {
			$integrations[] = 'WC_BooXtream_Integration';

			return $integrations;
		}

		public function callback_handler() {
			$postdata = file_get_contents( "php://input" );
			$postdata = json_decode( $postdata, true );

			if(isset($_GET['item_id']) && isset($_GET['_bx_nonce'])) {
				$nonce = $_GET['_bx_nonce'];
				$item_id = (int) $_GET['item_id'];

				$bxnonce = wc_get_order_item_meta( $item_id, '_bx_nonce' );

				// invalidate nonce
				$newnonce = bin2hex(random_bytes(64));
				wc_update_order_item_meta( $item_id, '_bx_nonce', $newnonce );

				if($bxnonce == $nonce) {
					// invalidate nonce
					$nonce = bin2hex(random_bytes(64));
					wc_update_order_item_meta( $item_id, '_bx_nonce', $nonce );

					if ( is_array( $postdata ) ) {
						wc_update_order_item_meta( $item_id, '_bx_downloadlinks', $postdata );

						// say hi back as soon as possible!
						ignore_user_abort( true );
						set_time_limit( 0 );
						header( "HTTP/1.1 200 OK" );
						header( "Connection: close", true );
						header( "Content-Encoding: none\r\n" );
						header( "Content-Length: 2", true );
						echo 'ok';
						ob_end_flush();
						flush();

						session_write_close();
						return;
					}
				}
			}
			// say hi back as soon as possible!
			ignore_user_abort( true );
			set_time_limit( 0 );
			header( "HTTP/1.1 400 Bad Request" );
			header( "Connection: close", true );
			header( "Content-Encoding: none\r\n" );
			header( "Content-Length: 2", true );
			echo 'no';
			ob_end_flush();
			flush();

			session_write_close();
			return;
		}

		public function handle_download( $query ) {
			//gets the global query var object
			global $wp_query;


			if ( $wp_query->get( 'bx-download' ) ) {
				$item_id     = $wp_query->get( 'bx-item-id' );
				$download_id = $wp_query->get( 'bx-download-id' );

				$bx_links = wc_get_order_item_meta( $item_id, '_bx_downloadlinks' );
				if ( is_array( $bx_links ) ) {
					if ( $download_id === wc_get_order_item_meta( $item_id, '_bx_epub_link' ) ) {
						$link = $bx_links['epub'];
					} elseif ( $download_id === wc_get_order_item_meta( $item_id, '_bx_mobi_link' ) ) {
						$link = $bx_links['mobi'];
					}

					header( 'Location: ' . $link );
					exit;
				} else {
					//gets the front page id set in options
					$page_id = get_option( 'woocommerce_download_processing_page_id' );
					if ( false === $page_id ) {
						echo '<h1>' . __( 'Your download is not ready yet', 'woocommerce_booxtream' ) . '</h1>';
						echo '<p>' . __( 'We are currently processing your download, please try again in a few seconds',
								'woocommerce_booxtream' ) . '</p>';
						exit;
					}

					if ( ! $query->is_main_query() ) {
						return;
					}

					$query->set( 'post_type', 'page' );
					$query->set( 'p', $page_id );

					//we remove the actions hooked on the '__after_loop' (post navigation)
					remove_all_actions( '__after_loop' );

					return $query;
				}
			}
		}

		public function add_query_vars( $vars ) {
			$vars[] = "bx-download";
			$vars[] = "bx-item-id";
			$vars[] = "bx-download-id";

			return $vars;
		}

		/*
		 * Needs to be static because of activation
		 */
		public static function add_rewrite_rules() {
			add_rewrite_rule(
				'^bx-download/([0-9]+)\-([A-Za-z0-9.]+)/?$',
				'index.php?bx-download=1&bx-item-id=$matches[1]&bx-download-id=$matches[2]',
				'top'
			);
			add_rewrite_rule(
				'^bx/([0-9]+)b([A-Za-z0-9.]+)/?$',
				'index.php?bx-download=1&bx-item-id=$matches[1]&bx-download-id=b$matches[2]',
				'top'
			);
		}

		public static function check_version() {
			if (is_admin() && BOOXTREAM_PLUGIN_VERSION !== get_option('booxtream_plugin_version')) {
				self::activate_plugin();
				update_option('booxtream_plugin_version', BOOXTREAM_PLUGIN_VERSION);
			}
		}

		public function woocommerce_hidden_order_itemmeta($arr) {
			$arr[] = '_bx_filename';
			$arr[] = '_bx_language';
			$arr[] = '_bx_outputepub';
			$arr[] = '_bx_outputmobi';
			$arr[] = '_bx_downloadlimit';
			$arr[] = '_bx_expirydays';
			$arr[] = '_bx_referenceid';
			$arr[] = '_bx_exlibrisfile';
			$arr[] = '_bx_exlibrisfont';
			$arr[] = '_bx_chapterfooter';
			$arr[] = '_bx_disclaimer';
			$arr[] = '_bx_showdate';
			$arr[] = '_bx_customername';
			$arr[] = '_bx_customeremailaddress';
			$arr[] = '_bx_epub_link';
			$arr[] = '_bx_mobi_link';
			$arr[] = '_bx_epub_full_link';
			$arr[] = '_bx_mobi_full_link';
			$arr[] = '_bx_nonce';
			return $arr;
		}

	}

	// create page(s) on activation
	register_activation_hook( __FILE__, array( 'WC_BooXtream', 'activate_plugin' ) );

	// flush on deactivation
	register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

	// load class when plugins are loaded
	add_action( 'plugins_loaded', array( 'WC_BooXtream', 'get_instance' ), 0 );

	// load check version at the very end
	add_action( 'wp_loaded', array( 'WC_BooXtream', 'check_version' ), 0 );

	function act_trigger_error( $message, $errno ) {

		if ( isset( $_GET['action'] ) && $_GET['action'] == 'error_scrape' ) {

			echo '<strong>' . $message . '</strong>';

			exit;

		} else {

			trigger_error( $message, $errno );

		}

	}

endif;