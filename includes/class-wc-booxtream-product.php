<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_BooXtream_Product' ) ) :

	/**
	 * Class WC_BooXtream_Product
	 */
	class WC_BooXtream_Product {
		/**
		 * @var WC_BooXtream_Integration
		 */
		private $settings;

		/**
		 * @param WC_BooXtream_Integration $settings
		 */
		public function __construct( WC_BooXtream_Integration $settings ) {
			$this->settings = $settings;

			// check for connection
			if ( ! $this->settings->connected ) {
				// @todo: handle error, admin notice?
				return false;
			}

			// add checkbox "booxtreamable" to products panel
			add_filter( 'product_type_options', array( $this, 'add_checkbox' ) );

			// add input fields for booxtreamable products to products panel
			add_action( 'woocommerce_product_options_general_product_data', array( $this, 'add_input_fields' ) );

			// load Javascript for displaying or hiding BooXtream input fields
			add_action( 'admin_enqueue_scripts', array( $this, 'load_scripts' ) );

			// check and save all data
			add_action( 'woocommerce_process_product_meta', array( $this, 'save_product' ) );

			// Add action to refresh storedfiles
			add_action( 'wp_ajax_refresh_storedfiles', array( $this, 'refresh_storedfiles' ) );
			add_action( 'wp_ajax_refresh_exlibrisfiles', array( $this, 'refresh_exlibrisfiles' ) );

			return true;
		}

		/**
		 * @param $product_type_options
		 *
		 * @return mixed
		 */
		public function add_checkbox( $product_type_options ) {
			$product_type_options['booxtreamable'] = array(
				'id'            => '_bx_booxtreamable',
				'wrapper_class' => 'show_if_simple',
				'label'         => __( 'Booxtreamable', 'woocommerce_booxtream' ),
				'description'   => __( '', 'woocommerce_booxtream' ),
				'default'       => $this->is_booxtreamable(),
			);

			return $product_type_options;
		}

		/**
		 *
		 */
		public function refresh_storedfiles() {
			global $thepostid;
			$this->settings->storedfiles = null;

			$this->settings->get_storedfiles( true );

			// we have to set this global to ensure woocommerce_wp_select() works
			$thepostid = intval( $_POST['booxtream-post-id'] );

			$select = $this->generate_storedfiles_select( $thepostid );
			echo $select;

			// exit so we won't get that weird extra 0
			exit;
		}

		/**
		 * @param $thepostid
		 *
		 * @return string
		 */
		public function generate_storedfiles_select( $thepostid ) {

			$bx_filename = get_post_meta( $thepostid, '_bx_filename', true );

			// Input field for storedfiles (select)
			ob_start();
			woocommerce_wp_select(
				array(
					'id'          => '_bx_filename',
					'class'       => 'wc-enhanced-select short',
					'label'       => __( 'E-book', 'woocommerce_booxtream' ),
					'desc_tip'    => 'true',
					'description' => __( 'Select e-book', 'woocommerce_booxtream' ),
					'type'        => 'select',
					'value'       => $bx_filename,
					'options'     => $this->settings->get_storedfiles()
				)
			);
			$select = rtrim( ob_get_contents() );
			ob_end_clean();

			// remove last </p>
			$select = substr( $select, 0, - 4 );

			return '<div id="booxtream-select-storedfiles">' . $select . ' <a href="#" class="booxtream-refresh-storedfiles" data-post-id="' . $thepostid . '">' . __( 'Refresh list',
					'woocommerce_booxtream' ) . '</a></p></div>';
		}

		/**
		 *
		 */
		public function refresh_exlibrisfiles() {
			global $thepostid;
			$this->settings->exlibrisfiles = null;

			$this->settings->get_exlibrisfiles( true );

			// we have to set this global to ensure woocommerce_wp_select() works
			$thepostid = intval( $_POST['booxtream-post-id'] );

			$select = $this->generate_exlibrisfiles_select( $thepostid );
			echo $select;

			// exit so we won't get that weird extra 0
			exit;
		}

		/**
		 * @param $thepostid
		 *
		 * @return string
		 */
		public function generate_exlibrisfiles_select( $thepostid ) {

			$bx_exlibrisfile = get_post_meta( $thepostid, '_bx_exlibrisfile', true );
			if ( '' == $bx_exlibrisfile ) {
				$bx_exlibrisfile = $this->settings->exlibrisfile;
			}

			// Input field for exlibrisfiles (select)
			ob_start();
			woocommerce_wp_select(
				array(
					'id'          => '_bx_exlibrisfile',
					'class'       => 'wc-enhanced-select short',
					'label'       => __( 'Ex libris', 'woocommerce_booxtream' ),
					'desc_tip'    => 'true',
					'description' => __( 'Select a file to use as an ex libris', 'woocommerce_booxtream' ),
					'type'        => 'select',
					'value'       => $bx_exlibrisfile,
					'options'     => $this->settings->get_exlibrisfiles()
				)
			);
			$select = rtrim( ob_get_contents() );
			ob_end_clean();

			// remove last </p>
			$select = substr( $select, 0, - 4 );

			return '<div id="booxtream-select-exlibrisfiles">' . $select . ' <a href="#" class="booxtream-refresh-exlibrisfiles" data-post-id="' . $thepostid . '">' . __( 'Refresh list',
					'woocommerce_booxtream' ) . '</a></p></div>';
		}

		/**
		 *
		 */
		public function add_input_fields() {
			global $thepostid;

			$bx_language      = get_post_meta( $thepostid, '_bx_language', true );
			$bx_downloadlimit = get_post_meta( $thepostid, '_bx_downloadlimit', true );
			$bx_expirydays    = get_post_meta( $thepostid, '_bx_expirydays', true );
			$bx_outputepub    = get_post_meta( $thepostid, '_bx_outputepub', true );
			$bx_outputmobi    = get_post_meta( $thepostid, '_bx_outputmobi', true );
			$bx_chapterfooter = get_post_meta( $thepostid, '_bx_chapterfooter', true );
			$bx_disclaimer    = get_post_meta( $thepostid, '_bx_disclaimer', true );
			$bx_showdate      = get_post_meta( $thepostid, '_bx_showdate', true );
			$bx_exlibrisfont  = get_post_meta( $thepostid, '_bx_exlibrisfont', true );
			if ( '' == $bx_exlibrisfont ) {
				$bx_exlibrisfont = $this->settings->exlibrisfont;
			}

			// some defaults for checkboxes
			if ( $bx_outputepub === '' ) {
				$bx_outputepub = 'yes';
			}
			if ( $bx_outputmobi === '' ) {
				$bx_outputmobi = 'no';
			}
			if ( $bx_chapterfooter === '' ) {
				$bx_chapterfooter = 'no';
			}
			if ( $bx_disclaimer === '' ) {
				$bx_disclaimer = 'no';
			}
			if ( $bx_showdate === '' ) {
				$bx_showdate = 'no';
			}

			// Remove number 0 for visibility of placeholder value
			if ( 0 == $bx_downloadlimit ) {
				$bx_downloadlimit = '';
			}

			if ( 0 == $bx_expirydays ) {
				$bx_expirydays = '';
			}
			if ( '' == $bx_language ) {
				$bx_language = $this->settings->language;
			}

			// Get placeholder value for expiry days and download limit
			$global_downloadlimit = ( int ) $this->settings->downloadlimit;
			$global_expirydays    = ( int ) $this->settings->expirydays;


			echo '<div class="options_group show_if_booxtreamable">';

			/*
			woocommerce_wp_text_input(
				array(
					'id'          => '_bx_filename',
					'label'       => __( 'File', 'woocommerce_booxtream' ),
					'placeholder' => $bx_filename,
					'desc_tip'    => 'true',
					'description' => __( 'Enter Filename.', 'woocommerce_booxtream' ),
					'type'        => 'text',
					'value'       => $bx_filename,
				)
			);
			*/

			echo $this->generate_storedfiles_select( $thepostid );

			// Input field for language (select)
			woocommerce_wp_select(
				array(
					'id'          => '_bx_language',
					'class'       => 'wc-enhanced-select short',
					'label'       => __( 'Language', 'woocommerce_booxtream' ),
					'desc_tip'    => 'true',
					'description' => __( 'Select language', 'woocommerce_booxtream' ),
					'type'        => 'select',
					'value'       => $bx_language,
					'options'     => $this->settings->get_languages()
				)
			);

			// Input field for downloads (number)
			woocommerce_wp_text_input(
				array(
					'id'                => '_bx_downloadlimit',
					'label'             => __( 'Download limit', 'woocommerce_booxtream' ),
					'placeholder'       => $global_downloadlimit . ' ' . __( '(default)', 'woocommerce_booxtream' ),
					'desc_tip'          => true,
					'description'       => __( 'Enter download limit', 'woocommerce_booxtream' ),
					'type'              => 'number',
					'value'             => $bx_downloadlimit,
					'custom_attributes' => array(
						'step' => 'any',
						'min'  => '1',
						'max'  => '99',
					)
				)
			);

			// Input field for expiry days (number)
			woocommerce_wp_text_input(
				array(
					'id'                => '_bx_expirydays',
					'label'             => __( 'Days until download expires', 'woocommerce_booxtream' ),
					'placeholder'       => $global_expirydays . ' ' . __( '(default)', 'woocommerce_booxtream' ),
					'desc_tip'          => true,
					'description'       => __( 'The number of days until the download expires',
						'woocommerce_booxtream' ),
					'type'              => 'number',
					'value'             => $bx_expirydays,
					'custom_attributes' => array(
						'step' => 'any',
						'min'  => '1',
						'max'  => '99',
					)
				)
			);

			// Input field for epub output (boolean)
			woocommerce_wp_checkbox(
				array(
					'id'          => '_bx_outputepub',
					'label'       => __( 'Generate downloadlink for epub', 'woocommerce_booxtream' ),
					'desc_tip'    => 'true',
					'description' => __( 'Check if you want to generate a downloadlink to a watermarked epub file',
						'woocommerce_booxtream' ),
					'type'        => 'checkbox',
					'value'       => $bx_outputepub,
					'default'     => '1'
				)
			);

			// Input field for mobi output (boolean)
			woocommerce_wp_checkbox(
				array(
					'id'          => '_bx_outputmobi',
					'label'       => __( 'Generate downloadlink for mobi', 'woocommerce_booxtream' ),
					'desc_tip'    => 'true',
					'description' => __( 'Check if you want to generate a downloadlink to a watermarked mobi file',
						'woocommerce_booxtream' ),
					'type'        => 'text',
					'value'       => $bx_outputmobi,
				)
			);

			echo $this->generate_exlibrisfiles_select( $thepostid );

			// Select field for selection of ex libris font
			// Input field for language (select)
			woocommerce_wp_select(
				array(
					'id'          => '_bx_exlibrisfont',
					'class'       => 'short',
					'label'       => __( 'Ex libris font', 'woocommerce_booxtream' ),
					'desc_tip'    => 'true',
					'description' => __( 'Select font', 'woocommerce_booxtream' ),
					'type'        => 'select',
					'value'       => $bx_exlibrisfont,
					'options'     => array(
						'sans'   => 'Sans serif',
						'script' => 'Script',
						'serif'  => 'Serif'
					)
				)
			);

			// Input field for chapterfooter (boolean)
			woocommerce_wp_checkbox(
				array(
					'id'          => '_bx_chapterfooter',
					'label'       => __( 'Chapter footer', 'woocommerce_booxtream' ),
					'desc_tip'    => 'true',
					'description' => __( 'Check if you would like to include a chapter footer',
						'woocommerce_booxtream' ),
					'type'        => 'text',
					'value'       => $bx_chapterfooter,
				)
			);

			// Input field for disclaimer (boolean)
			woocommerce_wp_checkbox(
				array(
					'id'          => '_bx_disclaimer',
					'label'       => __( 'Disclaimer', 'woocommerce_booxtream' ),
					'desc_tip'    => 'true',
					'description' => __( 'Check if you would like to include a disclaimer', 'woocommerce_booxtream' ),
					'type'        => 'text',
					'value'       => $bx_disclaimer,
				)
			);

			// Input field for showdate (boolean)
			woocommerce_wp_checkbox(
				array(
					'id'          => '_bx_showdate',
					'label'       => __( 'Include date in visible watermarks', 'woocommerce_booxtream' ),
					'desc_tip'    => 'true',
					'description' => __( 'Check if you would like to include the date in the visible watermarks',
						'woocommerce_booxtream' ),
					'type'        => 'text',
					'value'       => $bx_showdate,
				)
			);

			echo '</div>';

		}

		/**
		 *
		 */
		public function load_scripts() {
			wp_enqueue_script(
				'wc-admin-booxtream-meta-boxes',
				plugins_url( 'js/booxtream.js', dirname( __FILE__ ) ),
				array( 'jquery' ),
				'0.9.4',
				true
			);

		}

		/**
		 * @param $product_id
		 */
		public function save_product( $product_id ) {
			$post = get_post( $product_id );

			if ( 'product' === $post->post_type ) {

				if ( isset( $_POST['_bx_booxtreamable'] ) && 'on' === $_POST['_bx_booxtreamable'] ) {
					update_post_meta( $product_id, '_booxtreamable', 'yes' );
				} else {
					update_post_meta( $product_id, '_booxtreamable', 'no' );
				}

				if ( isset ( $_POST['_bx_filename'] ) ) {
					if ( '' != $_POST['_bx_filename'] ) {
						$result = $this->validate_filename( $_POST['_bx_filename'] . '.epub' );
						if ( $result ) {
							update_post_meta( $product_id, '_bx_filename_exists', 'yes' );
						} else {
							update_post_meta( $product_id, '_bx_filename_exists', 'no' );
						}
						update_post_meta( $product_id, '_bx_filename', sanitize_file_name( $_POST['_bx_filename'] ) );
					}
				}

				if ( isset ( $_POST['_bx_exlibrisfile'] ) ) {
					if ( '' != $_POST['_bx_exlibrisfile'] ) {
						$result = $this->validate_filename( $_POST['_bx_exlibrisfile'] );
						if ( $result ) {
							update_post_meta( $product_id, '_bx_exlibrisfile_exists', 'yes' );
						} else {
							update_post_meta( $product_id, '_bx_exlibrisfile_exists', 'no' );
						}
						update_post_meta( $product_id, '_bx_exlibrisfile',
							sanitize_file_name( $_POST['_bx_exlibrisfile'] ) );
					}
				}

				if ( isset ( $_POST['_bx_language'] ) ) {

					$language = $this->validate_language( $_POST['_bx_language'] );
					update_post_meta( $product_id, '_bx_language', $language );

				}

				if ( isset ( $_POST['_bx_downloadlimit'] ) ) {

					$downloads = $this->validate_downloadlimit( $_POST['_bx_downloadlimit'] );
					update_post_meta( $product_id, '_bx_downloadlimit', $downloads );

				}

				if ( isset ( $_POST['_bx_expirydays'] ) ) {

					$expirydays = $this->validate_expirydays( $_POST['_bx_expirydays'] );
					update_post_meta( $product_id, '_bx_expirydays', $expirydays );

				}

				if ( isset ( $_POST['_bx_exlibrisfont'] ) ) {

					$bx_exlibrisfont = $this->validate_exlibrisfont( $_POST['_bx_exlibrisfont'] );
					update_post_meta( $product_id, '_bx_exlibrisfont', $bx_exlibrisfont );

				}

				if ( isset( $_POST['_bx_outputepub'] ) && 'yes' === $_POST['_bx_outputepub'] ) {

					update_post_meta( $product_id, '_bx_outputepub', 'yes' );

				} else {

					update_post_meta( $product_id, '_bx_outputepub', 'no' );

				}

				if ( isset( $_POST['_bx_outputmobi'] ) && 'yes' === $_POST['_bx_outputmobi'] ) {

					update_post_meta( $product_id, '_bx_outputmobi', 'yes' );

				} else {

					update_post_meta( $product_id, '_bx_outputmobi', 'no' );

				}

				if ( isset( $_POST['_bx_chapterfooter'] ) && 'yes' === $_POST['_bx_chapterfooter'] ) {

					update_post_meta( $product_id, '_bx_chapterfooter', 'yes' );

				} else {

					update_post_meta( $product_id, '_bx_chapterfooter', 'no' );

				}

				if ( isset( $_POST['_bx_disclaimer'] ) && 'yes' === $_POST['_bx_disclaimer'] ) {

					update_post_meta( $product_id, '_bx_disclaimer', 'yes' );

				} else {

					update_post_meta( $product_id, '_bx_disclaimer', 'no' );

				}

				if ( isset( $_POST['_bx_showdate'] ) && 'yes' === $_POST['_bx_showdate'] ) {

					update_post_meta( $product_id, '_bx_showdate', 'yes' );

				} else {

					update_post_meta( $product_id, '_bx_showdate', 'no' );

				}

				// check if booxtream will work
				$this->check_parameters( $product_id );

			}
		}

		/**
		 * @param $product_id
		 *
		 * @return bool
		 */
		private function check_parameters( $product_id ) {
			$error = false;

			if ( 'yes' == $this->is_booxtreamable() ) {

				$bx_downloadlimit       = get_post_meta( $product_id, '_bx_downloadlimit', true );
				$bx_expirydays          = get_post_meta( $product_id, '_bx_expirydays', true );
				$bx_outputepub          = get_post_meta( $product_id, '_bx_outputepub', true );
				$bx_outputmobi          = get_post_meta( $product_id, '_bx_outputmobi', true );
				$bx_filename            = get_post_meta( $product_id, '_bx_filename', true );
				$bx_exlibrisfile        = get_post_meta( $product_id, '_bx_exlibrisfile', true );
				$bx_filename_exists     = get_post_meta( $product_id, '_bx_filename_exists', true );
				$bx_exlibrisfile_exists = get_post_meta( $product_id, '_bx_exlibrisfile_exists', true );

				// Set this to default if not available
				if ( 0 == (int) $bx_downloadlimit ) {
					$bx_downloadlimit = ( int ) $this->settings->downloadlimit;
				}

				if ( 0 == (int) $bx_expirydays ) {
					$bx_expirydays = ( int ) $this->settings->expirydays;;
				}


				// Some required parameters should be always set or are set elsewhere (referenceid, languagecode, etc). We don't care about optional parameters at this point
				if ( '' == $bx_filename ) {
					$error = true;
					WC_Admin_Meta_Boxes::add_error( __( 'E-book has not been set', 'woocommerce_booxtream' ) );
				} elseif ( 'no' == $bx_filename_exists ) {
					$error = true;
					WC_Admin_Meta_Boxes::add_error( sprintf( __( 'The e-book %s does not exist',
						'woocommerce_booxtream' ),
						'<code>' . basename( sanitize_file_name( $bx_filename ) ) . '</code>' ) );
				}

				if ( 'no' == $bx_outputepub && 'no' == $bx_outputmobi ) {
					$error = true;
					WC_Admin_Meta_Boxes::add_error( __( 'Output of epub and/or mobi is not set',
						'woocommerce_booxtream' ) );
				}

				if ( 0 == (int) $bx_downloadlimit ) {
					$error = true;
					WC_Admin_Meta_Boxes::add_error( __( 'Download limit should be greater than 0',
						'woocommerce_booxtream' ) );
				}

				if ( 0 == (int) $bx_expirydays ) {
					$error = true;
					WC_Admin_Meta_Boxes::add_error( __( 'Number of days until download expires should be greater than 0',
						'woocommerce_booxtream' ) );
				}

				if ( '' != $bx_exlibrisfile && 'no' == $bx_exlibrisfile_exists ) {
					WC_Admin_Meta_Boxes::add_error( sprintf( __( 'Ex libris %s does not exist',
						'woocommerce_booxtream' ),
						'<code>' . basename( sanitize_file_name( $bx_exlibrisfile ) ) . '</code>' ) );
				}
			}

			return $error;
		}

		/**
		 * @return string
		 */
		private function is_booxtreamable() {
			global $thepostid;

			$booxtreamable = get_post_meta( $thepostid, '_booxtreamable', true );

			return 'yes' === $booxtreamable ? 'yes' : 'no';
		}

		/**
		 * @param $filename
		 *
		 * @return bool
		 */
		private function validate_filename( $filename ) {
			// check if file exists
			$url = WC_BooXtream::storedfilesurl . sanitize_file_name( $filename ) . '?exists';

			// Set authentication
			$accountkey = $this->settings->accountkey;
			$loginname  = $this->settings->accounts[ $accountkey ] ['loginname'];
			$args       = array(
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( $loginname . ':' . $accountkey )
				)
			);

			$response = wp_safe_remote_head( $url, $args );
			if ( $response['response'] ['code'] !== 200 ) {
				return false;
			}

			return true;

		}

		/**
		 * @param $language
		 *
		 * @return bool
		 */
		private function validate_language( $language ) {
			if ( array_key_exists( $language, $this->settings->languages ) ) {
				return $language;
			} else {
				WC_Admin_Meta_Boxes::add_error( sprintf( __( 'Unsupported language %s', 'woocommerce_booxtream' ),
					'<code>' . $language . '</code>' ) );

				return false;
			}

		}

		/**
		 * @param $expirydays
		 *
		 * @return int
		 */
		private function validate_expirydays( $expirydays ) {
			return (int) $expirydays;
		}

		/**
		 * @param $downloadlimit
		 *
		 * @return int
		 */
		private function validate_downloadlimit( $downloadlimit ) {
			return (int) $downloadlimit;
		}

		private function validate_exlibrisfont( $exlibrisfont ) {
			switch ( $exlibrisfont ) {
				case 'sans':
				case 'serif':
				case 'script':
					return $exlibrisfont;
					break;
				default:
					return '';
					break;
			}
		}

	}

endif;
