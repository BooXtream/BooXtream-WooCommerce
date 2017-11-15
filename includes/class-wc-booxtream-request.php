<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_BooXtream_Request' ) ) :

	class WC_BooXtream_Request {
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
			if ( is_array( $response ) && $response['response'] ['code'] === 202 ) {
				return $this->parse_downloadlinks( $body );
			} else {
				throw new Exception( __( 'An error occurred while watermarking. Please check your BooXtream Dashboard: ',
					'woocommerce_booxtream' ) );
			}

		}

		public function handle_request( $url, $args, $parameters, $order_id ) {
			// multipart request
			$args  = $this->create_multipart_request( $parameters, $args );
			// request (http://websupporter.net/blog/wp_remote_get-vs-wp_safe_remote_get-2/)
			$response = wp_safe_remote_post( $url, $args );
			$body     = wp_remote_retrieve_body( $response );

			// evaluate body
			if ( !is_array( $response ) || $response['response'] ['code'] !== 202 ) {
				$order = new WC_Order( $order_id );
				$order->update_status( 'wc-booxtream-error', $e->getMessage() );
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