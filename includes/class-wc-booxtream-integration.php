<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * BooXtream Settings
 *
 * @package  WC_BooXtream
 * @category Integration
 * @author   Icontact B.V.
 */
if ( ! class_exists( 'WC_BooXtream_Integration' ) ) :

	class WC_BooXtream_Integration extends WC_Integration {
        public $contractname;
        public $contractpassword;
        public $accounts;
        public $selectaccounts;
        public $accountkey;
        public $referenceprefix;
        public $expirydays;
        public $downloadlimit;
        public $exlibrisfile;
        public $language;
        public $connected;
        public $languages;
        public $storedfiles;
        public $exlibrisfiles;
        public $exlibrisfont;
        public $onstatus;

		/**
		 * Init and hook in the integration.
		 */
		public function __construct() {
			$this->id                 = 'booxtream';
			$this->method_title       = __( 'BooXtream Settings', 'woocommerce_booxtream' );
			$this->method_description = __( 'Settings for BooXtream', 'woocommerce_booxtream' );

            // supported languages:
            // Setting English first because default value does not work for selects here
            // @see generate_select_html() in parent class
            // @todo fix that
            $this->languages = array(
                '1033'  => __( 'English', 'woocommerce_booxtream' ),
                '1025'  => __( 'Arabic', 'woocommerce_booxtream' ),
                '11265' => __( 'Arabic (Jordan)', 'woocommerce_booxtream' ),
                '1029'  => __( 'Czech', 'woocommerce_booxtream' ),
                '1043'  => __( 'Dutch', 'woocommerce_booxtream' ),
                '1036'  => __( 'French', 'woocommerce_booxtream' ),
                '1031'  => __( 'German', 'woocommerce_booxtream' ),
                '1037'  => __( 'Hebrew', 'woocommerce_booxtream' ),
                '1040'  => __( 'Italian', 'woocommerce_booxtream' ),
                '1044'  => __( 'Norwegian', 'woocommerce_booxtream' ),
                '2070'  => __( 'Portuguese', 'woocommerce_booxtream' ),
                '1049'  => __( 'Russian', 'woocommerce_booxtream' ),
                '1051'  => __( 'Slovak', 'woocommerce_booxtream' ),
                '1034'  => __( 'Spanish', 'woocommerce_booxtream' ),
                '1053'  => __( 'Swedish', 'woocommerce_booxtream' ),
            );

            // Load customer settings from database
			$this->get_settings();

            // Load input fields
            $this->init_form_fields();

            // Enable saving and updating of given values for options.
            add_action( 'woocommerce_update_options_integration_' . $this->id, array( $this, 'process_admin_options' ) );

        }

        /**
         * Define user set variables from database
         */
        public function get_settings() {
            // Define user set variables.
            $this->contractname          = $this->get_option( 'contractname' );
            $this->contractpassword      = $this->get_option( 'contractpassword' );

            $this->referenceprefix       = $this->get_option( 'referenceprefix' );

            $this->expirydays            = $this->get_option( 'expirydays' );
            $this->downloadlimit         = $this->get_option( 'downloadlimit' );
            $this->exlibrisfile          = $this->get_option( 'exlibrisfile' );

            $this->language              = $this->get_option( 'language' );

            $this->exlibrisfiles         = $this->get_option( 'exlibrisfiles' );
            if($this->exlibrisfiles == '') {$this->exlibrisfiles = null;}

            $this->exlibrisfont          = $this->get_option( 'exlibrisfont' );

            $this->storedfiles           = $this->get_option( 'storedfiles' );
            if($this->storedfiles == '') {$this->storedfiles = null;}

            $this->onstatus              = 'wc-processing'; //$this->get_option( 'onstatus' );

            $this->accountkey            = $this->get_accountkey();
        }

        /**
         * Initialize BooXtream settings form fields.
         * Sanitation and validation of normal text and password fields are handled by WC_Settings_API automatically
         *
         * Each field contains an array of properties:
         *
         * type – type of field (text, textarea, password, select)
         * label – label for the input field
         * placeholder – placeholder for the input
         * class – class for the input
         * required – true or false, whether or not the field is require
         * clear – true or false, applies a clear fix to the field/label
         * label_class – class for the label element
         * options – for select boxes, array of options (key => value pairs)
         *
         */
        public function init_form_fields() {
            $this->form_fields = array();

            // Add input fields for Contract settings
            if(!$this->connected) {
                    $this->form_fields['enter_credentials'] = array (
                        'title' => __( 'Please enter your BooXtream contract credentials to continue', 'woocommerce_booxtream' ),
                        'type' => 'message',
                        'id' => 'defaults_section'
                    );
            }

            $this->form_fields['contractname'] = array(
                'title'             => __( 'Contract name', 'woocommerce_booxtream' ),
                'type'              => 'text',
                'description'       => __( 'Enter your BooXtream contract name', 'woocommerce_booxtream' ),
                'desc_tip'          => true,
                'default'           => '',
                'custom_attributes' => array(
                    'auto-complete' => 'off'
                )
            );
            $this->form_fields['contractpassword'] = array(
                'title'             => __( 'Password', 'woocommerce_booxtream' ),
                'type'              => 'password',
                'description'       => __( 'Enter your BooXtream password', 'woocommerce_booxtream' ),
                'desc_tip'          => true,
                'default'           => '',
                'custom_attributes' => array(
                    'auto-complete' => 'off'
                )
            );


            // Add accounts when they are set
            if ( count( $this->accounts ) > 0 ) {
                // generate selectaccounts
                $this->generate_selectaccounts();

                if(null == $this->accountkey) {
                    $this->form_fields['choose_accountkey'] = array (
                        'title' => __( 'Please choose an account to continue', 'woocommerce_booxtream' ),
                        'type' => 'message',
                        'id' => 'defaults_section'
                    );
                }
                $this->form_fields['accountkey'] = array(
                    'title' => __('Account name', 'woocommerce_booxtream'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select',
                    'description' => __('Account', 'woocommerce_booxtream'),
                    'desc_tip' => true,
                    'value' => $this->accountkey,
                    'options' => $this->selectaccounts
                );

                if($this->accountkey !== null) {
                    $this->form_fields['defaults_section'] = array (
                        'title' => __( 'General settings', 'woocommerce_booxtream' ),
                        'description' => __( 'Settings that control how BooXtream is integrated.', 'woocommerce_booxtream' ),
                        'type' => 'title',
                        'id' => 'defaults_section'
                    );

                    // Add exlibris, language, expirydays, downloadlimit as global product settings
                    /*
                     * @todo: disabled for now
                    $this->form_fields['onstatus'] = array(
                        'title' => __('Moment of transaction', 'woocommerce_booxtream'),
                        'type' => 'select',
                        'description' => __('This controls when your shop contacts BooXtream. Usually this would be right after payment has been succesfully processed or on order completion.', 'woocommerce_booxtream'),
                        'desc_tip' => false,
                        'default' => 'wc-processing',
                        'options' => array(
                            'wc-processing' => __( 'Processing', 'woocommerce_booxtream' ),
                            'wc-completed'  => __( 'Completed', 'woocommerce_booxtream' ),
                        )
                    );
                    */

                    $this->form_fields['referenceprefix'] = array(
                        'title'             => __( 'Reference ID prefix', 'woocommerce_booxtream' ),
                        'type'              => 'text',
                        'description'       => __( 'Specify a prefix to the Reference ID', 'woocommerce_booxtream' ),
                        'desc_tip'          => true,
                        'default'           => 'woocommerce_',
                        'custom_attributes' => array(
                            'auto-complete' => 'off'
                        )
                    );


                    $this->form_fields['defaults_section'] = array (
                        'title' => __( 'Default settings', 'woocommerce_booxtream' ),
                        'description' => __( 'These values can be overwritten when creating a BooXtreamable product.', 'woocommerce_booxtream' ),
                        'type' => 'title',
                        'id' => 'defaults_section'
                    );

                    // Add exlibris, language, expirydays, downloadlimit as global product settings
                    $this->form_fields['exlibrisfile'] = array(
                        'title' => __('Ex libris', 'woocommerce_booxtream'),
                        'type' => 'select',
                        'class' => 'wc-enhanced-select',
                        'description' => __('This value can be overwritten when creating a BooXtreamable product; otherwise, this value will be used', 'woocommerce_booxtream'),
                        'desc_tip' => true,
                        'options' => $this->get_exlibrisfiles()
                    );

                    // Add exlibris, language, expirydays, downloadlimit as global product settings
                    $this->form_fields['exlibrisfont'] = array(
                        'title' => __('Ex libris font', 'woocommerce_booxtream'),
                        'type' => 'select',
                        'description' => __('This value can be overwritten when creating a BooXtreamable product; otherwise, this value will be used', 'woocommerce_booxtream'),
                        'desc_tip' => true,
                        'options' => array(
                            'sans' => 'Sans serif',
                            'script' => 'Script',
                            'serif' => 'Serif'
                        )
                    );

                    $this->form_fields['language'] = array(
                        'title' => __('Language', 'woocommerce_booxtream'),
                        'type' => 'select',
                        'class' => 'wc-enhanced-select',
                        'description' => __('This value can be overwritten when creating a BooXtreamable product; otherwise, this value will be used', 'woocommerce_booxtream'),
                        'desc_tip' => true,
                        'options' => $this->languages
                    );

                    $this->form_fields['downloadlimit'] = array(
                        'title' => __('Download limit', 'woocommerce_booxtream'),
                        'type' => 'number',
                        'description' => __('This value can be overwritten when creating a BooXtreamable product; otherwise, this value will be used', 'woocommerce_booxtream'),
                        'desc_tip' => true,
                        'custom_attributes' => array(
                            'step' => 'any',
                            'min' => '1',
                            'max' => '99',
                        ),
                    );

                    $this->form_fields['expirydays'] = array(
                        'title' => __('Days until download expires', 'woocommerce_booxtream'),
                        'type' => 'number',
                        'description' => __('This value can be overwritten when creating a BooXtreamable product; otherwise, this value will be used', 'woocommerce_booxtream'),
                        'desc_tip' => true,
                        'custom_attributes' => array(
                            'step' => 'any',
                            'min' => '1',
                            'max' => '99',
                        ),
                    );
                }
            }
        }


        /**
         * Store Settings page input, call for checking BooXtream connection and BooXtream accounts
         * @return bool on success or failure
         */
        public function process_admin_options() {

	        if ( parent::process_admin_options() ) {

		        // make sure we have most recent settings
		        $this->get_settings();

		        if ( 0 === strlen( $this->contractname ) && 0 === strlen( $this->contractpassword ) ) {
			        // @todo: handle error, admin notice?

			        $this->show_notice( 'woocommerce_settings_saved', 'error', 'No contractname or password given.' );

			        // Ter info:
			        // "Save changes" komt uit wp-content/plugins/woocommerce/includes/admin/views/html-admin-settings.php

			        update_option( $this->plugin_id . $this->id . '_accounts', array() );

			        return false;
		        }

		        // succesfully saved, check connection to booxtream
		        $response = $this->connect_booxtream();

		        // check for response with valid credentials
		        if ( ! is_array( $response ) ) {
			        switch ( $response ) {

				        case 401:
					        $this->show_notice( 'woocommerce_settings_saved', 'error', 'No valid contractname or password given.' );
					        break;
				        default:
					        $this->show_notice( 'woocommerce_settings_saved', 'error', 'Unable to connect to BooXtream.' );
					        break;
			        }

			        // set connected status to false
			        update_option( $this->plugin_id . $this->id . '_connected', '0' );

			        $this->connected = false;
			        $this->accounts  = array();

			        // @todo: besides removing accounts array we also may need to remove saved account?
			        update_option( $this->plugin_id . $this->id . '_accounts', array() );

			        $this->init_form_fields();

			        return false;
		        }

		        // set connected status to true
		        update_option( $this->plugin_id . $this->id . '_connected', '1' );

		        $this->connected = true;

		        // try to retrieve and save accounts
		        $this->get_accounts();

		        $this->init_form_fields();

		        return true;
            }

	        return false;
        }

        /**
         * Display messages in Settings page
         */
        public function show_notice( $tag, $type, $message ) {
            add_action( $tag, function ()
                use ( $type, $message ) {
                    echo '<div class="' . $type . '"><p>' . __( $message, 'woocommerce_booxtream' ) . '</p></div>';
                }
            );
        }

        /**
         * check if database-retrieved accountkey is valid
         */
        public function get_accountkey() {
            // check if connected
            $this->connected = '1' === get_option( $this->plugin_id . $this->id . '_connected' ) ? true : false;
            if ( $this->connected ) {
                // get accounts
                $this->accounts = get_option( $this->plugin_id . $this->id . '_accounts' );
                $this->accountkey = $this->get_option( 'accountkey' );
                if(!isset($this->accounts[$this->accountkey])) {$this->accountkey = null;}
            } else {
                $this->accounts = array();
                $this->accountkey = null;
            }

            return $this->accountkey;
        }

        /**
         * return a list of available languages with default-setting indicated
         */
        public function get_languages() {
            $languages = $this->languages;
            if($this->language !== '') {
                $languages[$this->language] = $languages[$this->language].' '.__( '(default)', 'woocommerce_booxtream' );
            }

            return $languages;
        }

        /**
         * return a list of available exlibrisfiles
         */
        public function get_exlibrisfiles() {
            if(is_null($this->exlibrisfiles)) {
                // check of er een connectie is
                if ( !$this->connected ) {
                    // @todo: handle error, admin notice?
                    return false;
                }

                // Set authentication
                $args = array(
                    'reject_unsafe_urls' => false,
                    'headers' => array(
                        'Authorization' => 'Basic ' . base64_encode( $this->contractname . ':' . $this->contractpassword),
                    )
                );
                $url = str_replace('ACCOUNTKEY', $this->accountkey, WC_BooXtream::listexlibrisfilesurl). '?limit=0';

                $response = wp_safe_remote_get($url, $args);

                if ( is_array($response) && 200 === $response['response'] ['code'] ) {
                    $result = json_decode($response['body']);
                    if( is_object($result) && isset($result->message->response)) {
                        $this->exlibrisfiles = array(
                            '' => __( 'No ex libris', 'woocommerce_booxtream' )
                        );
                        foreach($result->message->response as $file) {
                            $this->exlibrisfiles[$file->FileName] = $file->FileName;
                        }

                        /*
                         * Save exlibrisfiles
                         */
                        update_option( $this->plugin_id . $this->id . '_exlibrisfiles', $this->exlibrisfiles );

                    } else {
                        WC_Admin_Meta_Boxes::add_error( __( 'Could not retrieve list of ex libris files', 'woocommerce_booxtream' ) );
                        return false;
                    }
                } else {
                    WC_Admin_Meta_Boxes::add_error( __( 'Could not retrieve list of ex libris files', 'woocommerce_booxtream' ) );
                    return false;
                }
            }

            return $this->exlibrisfiles;
        }

        /**
         * return a list of available storedfiles
         */
        public function get_storedfiles() {
            if(is_null($this->storedfiles)) {
                // check of er een connectie is
                if ( !$this->connected ) {
                    return false;
                }

                 // Set authentication
                $args = array(
                    'reject_unsafe_urls' => false,
                    'headers' => array(
                        'Authorization' => 'Basic ' . base64_encode( $this->contractname . ':' . $this->contractpassword),
                    )
                );
                $url = str_replace('ACCOUNTKEY', $this->accountkey, WC_BooXtream::listepubfilesurl). '?limit=0';

                $response = wp_safe_remote_get($url, $args);

                if ( is_array($response) && 200 === $response['response'] ['code'] ) {
                    $result = json_decode($response['body']);
                    if( is_object($result) && isset($result->message->response)) {
                        $this->storedfiles = array(
                            '' => __( 'Select an e-book', 'woocommerce_booxtream' )
                        );
                        foreach($result->message->response as $file) {
                            if(isset($file->StoredFileKey)) {
                                $this->storedfiles[$file->StoredFileKey] = $file->FileName;
                            }
                        }

                        /*
                         * Save storedfiles
                         */
                        update_option( $this->plugin_id . $this->id . '_storedfiles', $this->storedfiles );

                    } else {
                        WC_Admin_Meta_Boxes::add_error( __( 'Could not retrieve list of stored files', 'woocommerce_booxtream' ) );
                        return false;
                    }
                } else {
                    WC_Admin_Meta_Boxes::add_error( __( 'Could not retrieve list of stored files', 'woocommerce_booxtream' ) );
                    return false;
                }
            }

            return $this->storedfiles;
        }

        /**
         * Generate Message HTML.
         *
         * @param  mixed $key
         * @param  mixed $data
         *
*@since  1.0.0
         * @return string
         */
        public function generate_message_html( $key, $data ) {

            $field    = $this->get_field_key( $key );
            $defaults = array(
                'title'             => '',
                'class'             => ''
            );

            $data = wp_parse_args( $data, $defaults );

            ob_start();
            ?>
                </table>
                <div class="notice"><p><?php echo wp_kses_post( $data['title'] ); ?></p></div>
                <table class="form-table">
            <?php

            return ob_get_clean();
        }

        public function admin_options() {
            // check if curl is available
            if(!function_exists('curl_version')) {
                ?>
                <div class="update-nag">
                    <p><?php _e( 'Warning, your version of PHP does not support Curl. We highly recommend this when using our BooXtream plugin.', 'woocommerce_booxtream' ); ?></p>
                </div>
                <?php
            }
            parent::admin_options();
        }

		/**
		 * Checks if we can establish a connection to BooXtream with a given contractname and password
         * @return bool on success or failure
		 */
		private function connect_booxtream() {
			/*
			 * attempt to connect to BooXtream
			 */
			$args[ 'reject_unsafe_urls' ] = false;
			$args [ 'headers' ] = array(
				'Authorization' => 'Basic ' . base64_encode( $this->contractname . ':' . $this->contractpassword),
			);
			$response =  wp_remote_post( WC_BooXtream::contractinfourl, $args );

			if ( is_array($response) && 200 === $response['response'] ['code'] ) {

				return $response;

			} else {

				return is_array($response) ? $response[ 'response' ] [ 'code' ] : 500;

			}

		}

        /**
         * Load accounts from BooXtream
         * @return bool on success or failure
         */
        private function get_accounts() {

            $this->accounts = array();

            // check of er een connectie is
            if ( !$this->connected ) {
                // @todo: handle error, admin notice?
                return false;
            }

            // check BooXtream contractname and password
            $args[ 'reject_unsafe_urls' ] = false;
            $args [ 'headers' ] = array(
                'Authorization' => 'Basic ' . base64_encode( $this->contractname . ':' . $this->contractpassword ),
            );

            // The "response" consists of an json-array with keys referring to an array "headers", a string "body", an array "response", an array "cookies" and an array "filename"
            $response =  wp_remote_post( WC_BooXtream::accountsurl, $args );

            // if ok, retrieve and store accounts
            if ( 200 === $response[ 'response' ] [ 'code' ] ) {

                // We need the encoded json-array "body", which contains an message with a response, containing the requested data
                $body = wp_remote_retrieve_body( $response );
                $body = json_decode( $body, true );

                if ( is_array( $body ) && isset( $body[ 'message' ] ) && count( $body[ 'message' ] ) > 0 ) {
                    foreach ( $body[ 'message' ] [ 'response' ] as $response ) {
                        $account = array (
                            'accountname' => $response[ 'AccountName' ],
                            'loginname' => $response[ 'LoginName' ],
                        );
                        $this->accounts[ $response[ 'AuthenticationKey' ] ] = $account;
                    }

                    // save accounts
                    update_option( $this->plugin_id . $this->id . '_accounts', $this->accounts );
                }

            } else {

                // @todo: handle error, admin notice?
                // return false;
            }

            // return true;

        }

        private function generate_selectaccounts() {
            $this->selectaccounts = array('' => __('Please choose an account', 'woocommerce_booxtream'));
            if(count($this->accounts) > 0) {
                foreach($this->accounts as $key=>$value) {
                    $this->selectaccounts[$key] = $value['accountname'];
                }
            }
        }



	}

endif;