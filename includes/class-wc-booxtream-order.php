<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_BooXtream_Order' ) ) :

	/**
	 * Class WC_BooXtream_Order
	 */
	class WC_BooXtream_Order {

		/**
		 * @param WC_BooXtream_Integration $settings
		 */
		public function __construct( WC_BooXtream_Integration $settings ) {
			$this->settings = $settings;

			// runs when an order is committed
			add_action( 'woocommerce_order_add_product', array( $this, 'add_order_item_meta' ), 1, 5 );

			// runs when order status changes
			add_action( 'woocommerce_order_status_changed', array( $this, 'handle_status_change' ), 1, 3 );

		}

		/**
		 * @param $order_id
		 * @param $item_id
		 * @param $product
		 * @param $qty
		 * @param $args
		 *
		 * @todo $qty and $args?
		 */
		public function add_order_item_meta( $order_id, $item_id, $product, $qty, $args ) {

			// get BooXtream settings
			$request_data = $this->get_product_request_data( $product->id, $order_id );

			// add BooXtream settings to item
			foreach ( $request_data as $key => $value ) {
				wc_add_order_item_meta( $item_id, $key, $value );
			}

		}

		/**
		 * @param $order
		 * @param $item_id
		 *
		 * @return mixed
		 */
		public function get_order_item_meta( $order, $item_id ) {
			$data = $order->get_item_meta( $item_id );

			return $data;
		}

		/**
		 * @param $order_id
		 * @param $old_status
		 * @param $new_status
		 *
		 * @return bool|void
		 */
		public function handle_status_change( $order_id, $old_status, $new_status ) {
			$statuses = wc_get_order_statuses();
			// if we don't get a wc-prefixed status.
			$status = 'wc-' === substr( $new_status, 0, 3 ) ? substr( $new_status, 3 ) : $new_status;
			if ( isset( $statuses[ 'wc-' . $status ] ) && 'wc-' . $status === $this->settings->onstatus ) {
				return $this->process_items( $order_id );
			}

			return false;
		}

		/**
		 * @param $order_id
		 */
		public function process_items( $order_id ) {
			$order = new WC_Order( $order_id );
			$items = $order->get_items( array( 'line_item' ) );

			// check general BooXtream conditions
			$accountkey = $this->settings->get_accountkey();
			if ( ! is_null( $accountkey ) ) {
				foreach ( $items as $item_id => $item ) {
					$downloadlinks = wc_get_order_item_meta( $item_id, '_bx_downloadlinks', true );
					if ( ! is_array( $downloadlinks ) && 'yes' === get_post_meta( $item['product_id'], '_bx_booxtreamable', true ) ) {
						$this->request_downloadlinks( $item['product_id'], $order_id, $item_id ); // use this for actual data
					}
				}
			}

			return null;
		}

		/**
		 * @param $product_id
		 * @param $order_id
		 *
		 * @return array
		 */
		private function get_product_request_data( $product_id, $order_id ) {
			$data = array();

			$data['_bx_filename']      = get_post_meta( $product_id, '_bx_filename', true );
			$data['_bx_language']      = get_post_meta( $product_id, '_bx_language', true );
			$data['_bx_outputepub']    = get_post_meta( $product_id, '_bx_outputepub', true );
			$data['_bx_outputmobi']    = get_post_meta( $product_id, '_bx_outputmobi', true );
			$data['_bx_downloadlimit'] = get_post_meta( $product_id, '_bx_downloadlimit', true );
			$data['_bx_expirydays']    = get_post_meta( $product_id, '_bx_expirydays', true );

			// check if downloadlimit, expirydays, language are set; if not, take global settings
			if ( $data['_bx_downloadlimit'] == 0 ) {
				$data['_bx_downloadlimit'] = ( int ) $this->settings->downloadlimit;
			}

			if ( $data['_bx_expirydays'] == 0 ) {
				$data['_bx_expirydays'] = ( int ) $this->settings->expirydays;
			}
			if ( $data['_bx_language'] == '' ) {
				$data['_bx_language'] = $this->settings->language;
			}

			$data['_bx_referenceid']   = get_post_meta( $product_id, '_bx_referenceid', true );
			$data['_bx_exlibrisfile']  = get_post_meta( $product_id, '_bx_exlibrisfile', true );
			$data['_bx_exlibrisfont']  = get_post_meta( $product_id, '_bx_exlibrisfont', true );
			$data['_bx_chapterfooter'] = get_post_meta( $product_id, '_bx_chapterfooter', true );
			$data['_bx_disclaimer']    = get_post_meta( $product_id, '_bx_disclaimer', true );
			$data['_bx_showdate']      = get_post_meta( $product_id, '_bx_showdate', true );

			// add customer data
			$order                            = new WC_Order( $order_id );
			$data['_bx_customername']         = $order->billing_first_name . ' ' . $order->billing_last_name;
			$data['_bx_customeremailaddress'] = $order->billing_email;

			return $data;
		}

		private function create_internal_links( $item_id, $epub, $mobi ) {
			global $wp_rewrite;

			if ( get_option( 'permalink_structure' ) ) {
				$links = array();
				if ( $epub ) {
					$download_id = uniqid( md5( rand() ), true );
					$link        = 'bx-download/' . $item_id . '-' . $download_id;
					wc_update_order_item_meta( $item_id, '_bx_epub_link', $download_id );
					$links['epub'] = site_url( $link );
				}
				if ( $mobi ) {
					$download_id = uniqid( md5( rand() ), true );
					$link        = 'bx-download/' . $item_id . '-' . $download_id;
					wc_update_order_item_meta( $item_id, '_bx_mobi_link', $download_id );
					$links['mobi'] = site_url( $link );
				}
			} else {
				// no permalink_struct yet
				$links = array();
				if ( $epub ) {
					$download_id = uniqid( md5( rand() ), true );
					$link        = 'index.php?bx-download=1&bx-item-id' . $item_id . '&bx-download-id' . $download_id;
					wc_update_order_item_meta( $item_id, '_bx_epub_link', $download_id );
					$links['epub'] = site_url( $link );
				}
				if ( $mobi ) {
					$download_id = uniqid( md5( rand() ), true );
					$link        = 'index.php?bx-download=1&bx-item-id' . $item_id . '&bx-download-id' . $download_id;
					wc_update_order_item_meta( $item_id, '_bx_mobi_link', $download_id );
					$links['mobi'] = site_url( $link );
				}
			}

			// add readable link to item
			foreach ( $links as $type => $link ) {
				$downloadlink = '<a href="' . $wp_rewrite->root . $link . '">' . $type . '</a>';
				wc_add_order_item_meta( $item_id, __( 'download', 'woocommerce_booxtream' ), $downloadlink, false );
			}

		}

		/**
		 * @param $product_id
		 * @param $order_id
		 *
		 * @return array|bool
		 */
		private function request_downloadlinks( $product_id, $order_id, $item_id ) {
			global $wp_rewrite;

			// do request
			$requestdata = $this->get_product_request_data( $product_id, $order_id );

			$exlibris = false;
			if ( '' != $requestdata['_bx_exlibrisfile'] ) {
				$exlibris = true;
			}

			$parameters = array(
				'referenceid'          => $this->settings->referenceprefix . $order_id,
				'languagecode'         => $requestdata['_bx_language'],
				'expirydays'           => $requestdata['_bx_expirydays'],
				'downloadlimit'        => $requestdata['_bx_downloadlimit'],
				'customeremailaddress' => $requestdata['_bx_customeremailaddress'],
				'customername'         => $requestdata['_bx_customername'],
				'disclaimer'           => 'yes' === $requestdata['_bx_disclaimer'] ? 1 : 0,
				'exlibris'             => 'yes' === $exlibris ? 1 : 0,
				'chapterfooter'        => 'yes' === $requestdata['_bx_chapterfooter'] ? 1 : 0,
				'showdate'             => 'yes' === $requestdata['_bx_showdate'] ? 1 : 0,
				'epub'                 => 'yes' === $requestdata['_bx_outputepub'] ? 1 : 0,
				'kf8mobi'              => 'yes' === $requestdata['_bx_outputmobi'] ? 1 : 0,
				'exlibrisfont'         => $requestdata['_bx_exlibrisfont'],
			);
			if ( $exlibris ) {
				$parameters['exlibrisfile'] = $requestdata['_bx_exlibrisfile'];
			}

			$epub = 'yes' === $requestdata['_bx_outputepub'] ? true : false;
			$mobi = 'yes' === $requestdata['_bx_outputmobi'] ? true : false;

			$this->create_internal_links( $item_id, $epub, $mobi );

			$url = WC_BooXtream::storedfilesurl . $requestdata['_bx_filename'] . '.xml';

			$args = array();

			// Set authentication
			$accountkey                        = $this->settings->accountkey;
			$loginname                         = $this->settings->accounts[ $accountkey ] ['loginname'];
			$args['headers'] ['Authorization'] = 'Basic ' . base64_encode( $loginname . ':' . $accountkey );
			// add timeout
			$args['timeout'] = 600;

			// send non-blocking request to API
			$request  = array(
				'url'        => $url,
				'args'       => $args,
				'parameters' => $parameters,
				'order_id'   => $order_id,
				'item_id'    => $item_id
			);
			$args     = array(
				'method'      => 'POST',
				'redirection' => 3,
				'user-agent'  => 'booxtreamrequest',
				'httpversion' => '1.1',
				'blocking'    => false,
				'timeout'     => 1, // setting blocking to false and timeout to 1 should just fire the request
				'body'        => array( 'request' => json_encode( $request ) )
			);
			$response = wp_remote_post( site_url( $wp_rewrite->root . 'wc-api/booxtream_callback' ), $args );
			if ( is_wp_error( $response ) ) {
				/*
				 * @todo: handle this, show message to admin?
				 */
			}

			return;
		}

	}

endif;
