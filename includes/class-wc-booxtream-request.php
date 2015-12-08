<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_BooXtream_Request' ) ) :

	class WC_BooXtream_Request {
		/**
		 * @param $links
		 * @param $item_id
		 */
		function add_downloadlinks( $links, $item_id ) {

			if ( is_array( $links ) ) {
				// add generated downloadlinks to item
				wc_update_order_item_meta( $item_id, '_bx_downloadlinks', $links );
			}
		}

		/**
		 * @param $url
		 * @param $parameters
		 *
		 * @return array|bool
		 * @throws Exception
		 */
		function do_request( $url, $args ) {
			// request (http://websupporter.net/blog/wp_remote_get-vs-wp_safe_remote_get-2/)
			$response = wp_safe_remote_post( $url, $args );
			$body     = wp_remote_retrieve_body( $response );

			// evaluate body
			if ( is_array( $response ) && $response['response'] ['code'] === 200 ) {
				return $this->parse_downloadlinks( $body );
			} elseif ( $links instanceof WP_Error ) {
				return $links;
			} else {
				return new WP_Error( 'booxtreamfail', 'Failed to BooXtream this product', $response );
			}

		}

		public function handle_request( $url, $args, $parameters, $order_id, $item_id ) {
			// multipart request
			$args  = $this->create_multipart_request( $parameters, $args );
			$links = $this->do_request( $url, $args );

			if ( $links instanceof WP_Error ) {
				// force status to something else
				$order = new WC_Order( $order_id );
				//@todo: handle this error better
				$order->update_status( 'wc-booxtream-error', __( 'An error occurred while watermarking. Please check your BooXtream Dashboard: ', 'woocommerce_booxtream' ) );
			}

			$this->add_downloadlinks( $links, $item_id );
		}

		/**
		 * @param $body
		 *
		 * @return array
		 * @throws Exception
		 * @todo: less primitive exceptions
		 */
		function parse_downloadlinks( $body ) {

			try {
				if ( ! $body ) {
					throw new Exception( __( 'An error occurred while watermarking. Please check your BooXtream Dashboard: ', 'woocommerce_booxtream' ) );
				}

				$links = array();
				$xml   = new SimpleXMLElement( $body );

				// get download link and file type from BooXtream response
				if ( isset( $xml->Response ) && isset( $xml->Response->DownloadLink ) ) {
					foreach ( $xml->Response->DownloadLink as $downloadlink ) {
						if ( strlen( ( string ) $downloadlink ) > 0 && isset( $downloadlink['type'] ) ) {
							$link           = ( string ) $downloadlink;
							$type           = ( string ) $downloadlink['type'];
							$links[ $type ] = $link;
						}
					}
				}

				return $links;

			} catch ( Exception $e ) {
				throw $e;
			}
		}

		/**
		 * @param $parameters
		 * @param $args
		 *
		 * @return mixed
		 */
		private function create_multipart_request( $parameters, $args ) {
			// http://codechutney.com/posting-file-using-wp_remote_post/
			$boundary = wp_generate_password( 24 );
			$payload  = '';
			foreach ( $parameters as $key => $value ) {
				$payload .= '--' . $boundary;
				$payload .= "\r\n";
				$payload .= 'Content-Disposition: form-data; name="' . $key . '"' . "\r\n\r\n";
				$payload .= $value;
				$payload .= "\r\n";
			}

			$args ['headers'] ['content-type'] = 'multipart/form-data; boundary=' . $boundary;
			$args ['body']                     = $payload;
			$args ['method']                   = 'POST';

			return $args;
		}
	}

endif;