<?php
/*
Plugin Name: WS-Fed client by miniOrange
Plugin URI: http://miniorange.com/
Description: miniOrange WS federation SSO enables user to perform Single Sign On with any WS federation enabled Identity Provider.
Version: 2.1.3
Author: miniOrange
Author URI: http://miniorange.com/
*/


include_once dirname( __FILE__ ) . '/mo_login_saml_sso_widget.php';
require('mo-wsfed-client-class-customer.php');
require('mo_wsfed_client_settings_page.php');
require('Utilities.php');
class mo_wsfed_client_login {

	function __construct() {
		add_action( 'admin_menu', array( $this, 'miniorange_sso_menu' ) );
		add_action( 'admin_init', array( $this, 'miniorange_login_widget_saml_save_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'plugin_settings_style' ) );
		register_deactivation_hook(__FILE__, array( $this, 'mo_sso_saml_deactivate'));
		add_action( 'admin_enqueue_scripts', array( $this, 'plugin_settings_script' ) );
		remove_action( 'admin_notices', array( $this, 'mo_wsfed_client_success_message') );
		remove_action( 'admin_notices', array( $this, 'mo_wsfed_client_error_message') );
	}

	function  mo_login_widget_saml_options () {
		global $wpdb;
		update_option( 'mo_wsfed_client_host_name', 'https://auth.xecurify.com' );
		$host_name = get_option('mo_wsfed_client_host_name');
		mo_wsfed_client_config_page();
	}


	function mo_wsfed_client_success_message() {
		$class = "error";
		$message = get_option('mo_wsfed_client_message');
		echo "<div class='" . $class . "'> <p>" . $message . "</p></div>";
	}

	function mo_wsfed_client_error_message() {
		$class = "updated";
		$message = get_option('mo_wsfed_client_message');
		echo "<div class='" . $class . "'> <p>" . $message . "</p></div>";
	}

	public function mo_sso_saml_deactivate() {
		if(!is_multisite()) {
			//delete all customer related key-value pairs
			delete_option('mo_wsfed_client_host_name');
			delete_option('mo_wsfed_client_new_registration');
			delete_option('mo_wsfed_client_admin_phone');
			delete_option('mo_wsfed_client_admin_password');
			delete_option('mo_wsfed_client_verify_customer');
			delete_option('mo_wsfed_client_admin_customer_key');
			delete_option('mo_wsfed_client_admin_api_key');
			delete_option('mo_wsfed_client_customer_token');

			delete_option('mo_wsfed_client_message');
			delete_option('mo_wsfed_client_registration_status');
			delete_option('mo_wsfed_client_idp_config_complete');
			delete_option('mo_wsfed_client_transactionId');
			delete_option('mo_proxy_host');
			delete_option('mo_proxy_username');
			delete_option('mo_proxy_port');
			delete_option('mo_proxy_password');

		} else {
			global $wpdb;
			$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
			$original_blog_id = get_current_blog_id();

			foreach ( $blog_ids as $blog_id )
			{
				switch_to_blog( $blog_id );
				//delete all your options
				//E.g: delete_option( {option name} );
				delete_option('mo_wsfed_client_host_name');
				delete_option('mo_wsfed_client_new_registration');
				delete_option('mo_wsfed_client_admin_phone');
				delete_option('mo_wsfed_client_admin_password');
				delete_option('mo_wsfed_client_verify_customer');
				delete_option('mo_wsfed_client_admin_customer_key');
				delete_option('mo_wsfed_client_admin_api_key');
				delete_option('mo_wsfed_client_customer_token');
				delete_option('mo_wsfed_client_message');
				delete_option('mo_wsfed_client_registration_status');
				delete_option('mo_wsfed_client_idp_config_complete');
				delete_option('mo_wsfed_client_transactionId');
			}
			switch_to_blog( $original_blog_id );
		}
	}

	private function mo_wsfed_client_show_success_message() {
		remove_action( 'admin_notices', array( $this, 'mo_wsfed_client_success_message') );
		add_action( 'admin_notices', array( $this, 'mo_wsfed_client_error_message') );
	}
	function mo_wsfed_client_show_error_message() {
		remove_action( 'admin_notices', array( $this, 'mo_wsfed_client_error_message') );
		add_action( 'admin_notices', array( $this, 'mo_wsfed_client_success_message') );
	}
	function plugin_settings_style() {
		wp_enqueue_style( 'mo_wsfed_client_admin_settings_style', plugins_url( 'includes/css/style_settings.css?ver=3.7', __FILE__ ) );
		wp_enqueue_style( 'mo_wsfed_client_admin_settings_phone_style', plugins_url( 'includes/css/phone.css', __FILE__ ) );
	}
	function plugin_settings_script() {
		wp_enqueue_script( 'mo_wsfed_client_admin_settings_script', plugins_url( 'includes/js/settings.js', __FILE__ ) );
		wp_enqueue_script( 'mo_wsfed_client_admin_settings_phone_script', plugins_url('includes/js/phone.js', __FILE__ ) );
	}
	function miniorange_login_widget_saml_save_settings(){
		if ( current_user_can( 'manage_options' )){

		if(isset($_POST['option']) and $_POST['option'] == "login_widget_saml_save_settings"){
			if(!mo_wsfed_client_is_curl_installed()) {
				update_option( 'mo_wsfed_client_message', 'ERROR: <a href="http://php.net/manual/en/curl.installation.php" target="_blank">PHP cURL extension</a> is not installed or disabled. Save Identity Provider Configuration failed.');
				$this->mo_wsfed_client_show_error_message();
				return;
			}


			//validation and sanitization
			$mo_wsfed_client_identity_name = '';
			$mo_wsfed_client_idp_issuer = '';
			$mo_wsfed_client_login_url = '';
			
			$mo_wsfed_client_x509_certificate = '';
			if( $this->mo_wsfed_client_check_empty_or_null( $_POST['mo_wsfed_client_identity_name'] ) || $this->mo_wsfed_client_check_empty_or_null( $_POST['mo_wsfed_client_login_url'] ) ) {
				update_option( 'mo_wsfed_client_message', 'All the fields are required. Please enter valid entries.');
				$this->mo_wsfed_client_show_error_message();
				return;
			} else if(!preg_match("/^\w*$/", $_POST['mo_wsfed_client_identity_name'])) {
				update_option( 'mo_wsfed_client_message', 'Please match the requested format for Identity Provider Name. Only alphabets, numbers and underscore is allowed.');
				$this->mo_wsfed_client_show_error_message();
				return;
			} else{
				$mo_wsfed_client_identity_name = trim( $_POST['mo_wsfed_client_identity_name'] );
				$mo_wsfed_client_idp_issuer = trim( $_POST['mo_wsfed_client_idp_issuer'] );
				$mo_wsfed_client_login_url = trim( $_POST['mo_wsfed_client_login_url'] );
				$mo_wsfed_client_x509_certificate = ( $_POST['mo_wsfed_client_x509_certificate'] );
			}

			update_option('mo_wsfed_client_identity_name', $mo_wsfed_client_identity_name);
			update_option('mo_wsfed_client_idp_issuer', $mo_wsfed_client_idp_issuer);
			update_option('mo_wsfed_client_login_url', $mo_wsfed_client_login_url);
			//update_option('mo_wsfed_client_x509_certificate', $mo_wsfed_client_x509_certificate);

			if(isset($_POST['mo_wsfed_client_response_signed']))
				{
				update_option('mo_wsfed_client_response_signed' , 'checked');
				}
			else
				{
				update_option('mo_wsfed_client_response_signed' , 'Yes');
				}
			

			foreach ($mo_wsfed_client_x509_certificate as $key => $value) {
				if(empty($value))
					unset($mo_wsfed_client_x509_certificate[$key]);
				else
					{
						$mo_wsfed_client_x509_certificate[$key] = Utilities::sanitize_certificate( $value );

					if(!@openssl_x509_read($mo_wsfed_client_x509_certificate[$key])){
						update_option('mo_wsfed_client_message', 'Invalid certificate: Please provide a valid certificate.');
						$this->mo_wsfed_client_show_error_message();
						delete_option('mo_wsfed_client_x509_certificate');
						return;
					}
									}
			}
			if(empty($mo_wsfed_client_x509_certificate)){
				update_option("mo_wsfed_client_message",'Invalid Certificate: Please provide a certificate');
				$this->mo_wsfed_client_show_error_message();
				
				return;
			}
			update_option('mo_wsfed_client_x509_certificate', maybe_serialize( $mo_wsfed_client_x509_certificate ) );
			if(isset($_POST['mo_wsfed_client_assertion_signed']))
				{
				update_option('mo_wsfed_client_assertion_signed' , 'checked');
				}
			else
				{
				update_option('mo_wsfed_client_assertion_signed' , 'Yes');
				}

			 
				update_option('mo_wsfed_client_message', 'Identity Provider details saved successfully.');
				$this->mo_wsfed_client_show_success_message();
			
		}
		//Save Attribute Mapping
		if(isset($_POST['option']) and $_POST['option'] == "login_widget_saml_attribute_mapping"){

			if(!mo_wsfed_client_is_curl_installed()) {
				update_option( 'mo_wsfed_client_message', 'ERROR: <a href="http://php.net/manual/en/curl.installation.php" target="_blank">PHP cURL extension</a> is not installed or disabled. Save Attribute Mapping failed.');
				$this->mo_wsfed_client_show_error_message();
				return;
			}

			if (!get_option('mo_wsfed_client_free_version')) {
				update_option('mo_wsfed_client_am_username', stripslashes($_POST['mo_wsfed_client_am_username']));
				update_option('mo_wsfed_client_am_email', stripslashes($_POST['mo_wsfed_client_am_email']));
				update_option('mo_wsfed_client_am_group_name', stripslashes($_POST['mo_wsfed_client_am_group_name']));
			}
			update_option('mo_wsfed_client_am_first_name', stripslashes($_POST['mo_wsfed_client_am_first_name']));
			update_option('mo_wsfed_client_am_last_name', stripslashes($_POST['mo_wsfed_client_am_last_name']));
			update_option('mo_wsfed_client_am_account_matcher', stripslashes($_POST['mo_wsfed_client_am_account_matcher']));
			update_option('mo_wsfed_client_message', 'Attribute Mapping details saved successfully');
			$this->mo_wsfed_client_show_success_message();

		}
		//Save Role Mapping
		if(isset($_POST['option']) and $_POST['option'] == "login_widget_saml_role_mapping"){

			if(!mo_wsfed_client_is_curl_installed()) {
				update_option( 'mo_wsfed_client_message', 'ERROR: <a href="http://php.net/manual/en/curl.installation.php" target="_blank">PHP cURL extension</a> is not installed or disabled. Save Role Mapping failed.');
				$this->mo_wsfed_client_show_error_message();
				return;
			}

			if (!get_option('mo_wsfed_client_free_version')) {
				if(isset($_POST['mo_wsfed_client_am_dont_allow_unlisted_user_role'])) {
					update_option('mo_wsfed_client_am_default_user_role', false);
					update_option('mo_wsfed_client_am_dont_allow_unlisted_user_role', 'checked');
				} else {
					update_option('mo_wsfed_client_am_default_user_role', $_POST['mo_wsfed_client_am_default_user_role']);
					update_option('mo_wsfed_client_am_dont_allow_unlisted_user_role', 'unchecked');
				}
				$wp_roles = new WP_Roles();
				$roles = $wp_roles->get_names();
				$role_mapping;
				foreach ($roles as $role_value => $role_name) {
					$attr = 'saml_am_group_attr_values_' . $role_value;
					$role_mapping[$role_value] = stripslashes($_POST[$attr]);
				}
				update_option('mo_wsfed_client_am_role_mapping', $role_mapping);
			} else {
				update_option('mo_wsfed_client_am_default_user_role', $_POST['mo_wsfed_client_am_default_user_role']);
			}
			update_option('mo_wsfed_client_message', 'Role Mapping details saved successfully.');
			$this->mo_wsfed_client_show_success_message();
		}


		if( isset( $_POST['option'] ) and $_POST['option'] == "mo_wsfed_client_register_customer" ) {	//register the admin to miniOrange

			if(!mo_wsfed_client_is_curl_installed()) {
				update_option( 'mo_wsfed_client_message', 'ERROR: <a href="http://php.net/manual/en/curl.installation.php" target="_blank">PHP cURL extension</a> is not installed or disabled. Registration failed.');
				$this->mo_wsfed_client_show_error_message();
				return;
			}

			//validation and sanitization
			$email = '';
			$company = '';
			$first_name = '';
			$last_name = '';
			$phone = '';
			$password = '';
			$confirmPassword = '';

			if( $this->mo_wsfed_client_check_empty_or_null( $_POST['email'] ) || $this->mo_wsfed_client_check_empty_or_null( $_POST['password'] ) || $this->mo_wsfed_client_check_empty_or_null( $_POST['confirmPassword'] ) || $this->mo_wsfed_client_check_empty_or_null( $_POST['company'] )) {

				update_option( 'mo_wsfed_client_message', 'Please enter the required fields.');
				$this->mo_wsfed_client_show_error_message();
				return;
			} else if( strlen( $_POST['password'] ) < 6 || strlen( $_POST['confirmPassword'] ) < 6){
				update_option( 'mo_wsfed_client_message', 'Choose a password with minimum length 6.');
				$this->mo_wsfed_client_show_error_message();
				return;
			} else{
				$email = sanitize_email( $_POST['email'] );
				$company = sanitize_text_field( $_POST['company'] );
				$first_name = sanitize_text_field( $_POST['first_name'] );
				$last_name = sanitize_text_field( $_POST['last_name'] );
				$phone = sanitize_text_field( $_POST['phone'] );
				$password = sanitize_text_field( $_POST['password'] );
				$confirmPassword = sanitize_text_field( $_POST['confirmPassword'] );
			}
			update_option( 'mo_wsfed_client_admin_email', $email );
			update_option( 'mo_wsfed_client_admin_phone', $phone );
			update_option( 'mo_wsfed_client_admin_company', $company );
			update_option( 'mo_wsfed_client_admin_first_name', $first_name );
			update_option( 'mo_wsfed_client_admin_last_name', $last_name );
			if( strcmp( $password, $confirmPassword) == 0 ) {
				update_option( 'mo_wsfed_client_admin_password', $password );
				$email = get_option('mo_wsfed_client_admin_email');
				$customer = new mo_wsfed_client_Customer();
				$content = json_decode($customer->check_customer(), true);
				if( strcasecmp( $content['status'], 'CUSTOMER_NOT_FOUND') == 0 ){
					$content = json_decode($customer->send_otp_token($email, ''), true);
					if(strcasecmp($content['status'], 'SUCCESS') == 0) {
						update_option( 'mo_wsfed_client_message', ' A one time passcode is sent to ' . get_option('mo_wsfed_client_admin_email') . '. Please enter the otp here to verify your email.');
						update_option('mo_wsfed_client_transactionId',$content['txId']);
						update_option('mo_wsfed_client_registration_status','MO_OTP_DELIVERED_SUCCESS_EMAIL');
						$this->mo_wsfed_client_show_success_message();
					}else{
						update_option('mo_wsfed_client_message','There was an error in sending email. Please verify your email and try again.');
						update_option('mo_wsfed_client_registration_status','MO_OTP_DELIVERED_FAILURE_EMAIL');
						$this->mo_wsfed_client_show_error_message();
					}
				}else{
					$this->get_current_customer();
				}

			} else {
				update_option( 'mo_wsfed_client_message', 'Passwords do not match.');
				delete_option('mo_wsfed_client_verify_customer');
				$this->mo_wsfed_client_show_error_message();
			}

			//new starts here

		}


		if(isset($_POST['option']) and $_POST['option'] == "mo_wsfed_client_validate_otp"){

			if(!mo_wsfed_client_is_curl_installed()) {
				update_option( 'mo_wsfed_client_message', 'ERROR: <a href="http://php.net/manual/en/curl.installation.php" target="_blank">PHP cURL extension</a> is not installed or disabled. Validate OTP failed.');
				$this->mo_wsfed_client_show_error_message();
				return;
			}

			//validation and sanitization
			$otp_token = '';
			if( $this->mo_wsfed_client_check_empty_or_null( $_POST['otp_token'] ) ) {
				update_option( 'mo_wsfed_client_message', 'Please enter a value in otp field.');
				//update_option('mo_wsfed_client_registration_status','MO_OTP_VALIDATION_FAILURE');
				$this->mo_wsfed_client_show_error_message();
				return;
			} else{
				$otp_token = sanitize_text_field( $_POST['otp_token'] );
			}

			$customer = new mo_wsfed_client_Customer();
			$content = json_decode($customer->validate_otp_token(get_option('mo_wsfed_client_transactionId'), $otp_token ),true);
			if(strcasecmp($content['status'], 'SUCCESS') == 0) {

					$this->create_customer();
			}else{
				update_option( 'mo_wsfed_client_message','Invalid one time passcode. Please enter a valid otp.');
				//update_option('mo_wsfed_client_registration_status','MO_OTP_VALIDATION_FAILURE');
				$this->mo_wsfed_client_show_error_message();
			}
		}
		if( isset( $_POST['option'] ) and $_POST['option'] == "mo_wsfed_client_verify_customer" ) {	//register the admin to miniOrange

			if(!mo_wsfed_client_is_curl_installed()) {
				update_option( 'mo_wsfed_client_message', 'ERROR: <a href="http://php.net/manual/en/curl.installation.php" target="_blank">PHP cURL extension</a> is not installed or disabled. Login failed.');
				$this->mo_wsfed_client_show_error_message();
				return;
			}

			//validation and sanitization
			$email = '';
			$password = '';
			if( $this->mo_wsfed_client_check_empty_or_null( $_POST['email'] ) || $this->mo_wsfed_client_check_empty_or_null( $_POST['password'] ) ) {
				update_option( 'mo_wsfed_client_message', 'All the fields are required. Please enter valid entries.');
				$this->mo_wsfed_client_show_error_message();
				return;
			} else{
				$email = sanitize_email( $_POST['email'] );
				$password = sanitize_text_field( $_POST['password'] );
			}

			update_option( 'mo_wsfed_client_admin_email', $email );
			update_option( 'mo_wsfed_client_admin_password', $password );
			$customer = new mo_wsfed_client_Customer();
			$content = $customer->get_customer_key();
			$customerKey = json_decode( $content, true );
			if( json_last_error() == JSON_ERROR_NONE ) {
				update_option( 'mo_wsfed_client_admin_customer_key', $customerKey['id'] );
				update_option( 'mo_wsfed_client_admin_api_key', $customerKey['apiKey'] );
				update_option( 'mo_wsfed_client_customer_token', $customerKey['token'] );
				update_option( 'mo_wsfed_client_admin_phone', $customerKey['phone'] );
				$certificate = get_option('mo_wsfed_client_x509_certificate');
				if(empty($certificate)) {
					update_option( 'mo_wsfed_client_free_version', 1 );
				}
				update_option('mo_wsfed_client_admin_password', '');
				update_option( 'mo_wsfed_client_message', 'Customer retrieved successfully');
				update_option('mo_wsfed_client_registration_status' , 'Existing User');
				delete_option('mo_wsfed_client_verify_customer');
				$this->mo_wsfed_client_show_success_message();
			} else {
				update_option( 'mo_wsfed_client_message', 'Invalid username or password. Please try again.');
				$this->mo_wsfed_client_show_error_message();
			}
			update_option('mo_wsfed_client_admin_password', '');
		}else if( isset( $_POST['option'] ) and $_POST['option'] == "mo_wsfed_client_contact_us_query_option" ) {

			if(!mo_wsfed_client_is_curl_installed()) {
				update_option( 'mo_wsfed_client_message', 'ERROR: <a href="http://php.net/manual/en/curl.installation.php" target="_blank">PHP cURL extension</a> is not installed or disabled. Query submit failed.');
				$this->mo_wsfed_client_show_error_message();
				return;
			}

			// Contact Us query
			$email = $_POST['mo_wsfed_client_contact_us_email'];
			$phone = $_POST['mo_wsfed_client_contact_us_phone'];
			$query = $_POST['mo_wsfed_client_contact_us_query'];
			$customer = new mo_wsfed_client_Customer();
			if ( $this->mo_wsfed_client_check_empty_or_null( $email ) || $this->mo_wsfed_client_check_empty_or_null( $query ) ) {
				update_option('mo_wsfed_client_message', 'Please fill up Email and Query fields to submit your query.');
				$this->mo_wsfed_client_show_error_message();
			} else {
				$submited = $customer->submit_contact_us( $email, $phone, $query );
				if ( $submited == false ) {
					update_option('mo_wsfed_client_message', 'Your query could not be submitted. Please try again.');
					$this->mo_wsfed_client_show_error_message();
				} else {
					update_option('mo_wsfed_client_message', 'Thanks for getting in touch! We shall get back to you shortly.');
					$this->mo_wsfed_client_show_success_message();
				}
			}
		}
		else if( isset( $_POST['option'] ) and $_POST['option'] == "mo_wsfed_client_resend_otp_email" ) {

			if(!mo_wsfed_client_is_curl_installed()) {
				update_option( 'mo_wsfed_client_message', 'ERROR: <a href="http://php.net/manual/en/curl.installation.php" target="_blank">PHP cURL extension</a> is not installed or disabled. Resend OTP failed.');
				$this->mo_wsfed_client_show_error_message();
				return;
			}
			$email = get_option ( 'mo_wsfed_client_admin_email' );
		    $customer = new mo_wsfed_client_Customer();
			$content = json_decode($customer->send_otp_token($email, ''), true);
			if(strcasecmp($content['status'], 'SUCCESS') == 0) {
					update_option( 'mo_wsfed_client_message', ' A one time passcode is sent to ' . get_option('mo_wsfed_client_admin_email') . ' again. Please check if you got the otp and enter it here.');
					update_option('mo_wsfed_client_transactionId',$content['txId']);
					update_option('mo_wsfed_client_registration_status','MO_OTP_DELIVERED_SUCCESS_EMAIL');
					$this->mo_wsfed_client_show_success_message();
			}else{
					update_option('mo_wsfed_client_message','There was an error in sending email. Please click on Resend OTP to try again.');
					update_option('mo_wsfed_client_registration_status','MO_OTP_DELIVERED_FAILURE_EMAIL');
					$this->mo_wsfed_client_show_error_message();
			}
		} else if( isset( $_POST['option'] ) and $_POST['option'] == "mo_wsfed_client_resend_otp_phone" ) {

			if(!mo_wsfed_client_is_curl_installed()) {
				update_option( 'mo_wsfed_client_message', 'ERROR: <a href="http://php.net/manual/en/curl.installation.php" target="_blank">PHP cURL extension</a> is not installed or disabled. Resend OTP failed.');
				$this->mo_wsfed_client_show_error_message();
				return;
			}
			$phone = get_option('mo_wsfed_client_admin_phone');
		    $customer = new mo_wsfed_client_Customer();
			$content = json_decode($customer->send_otp_token('', $phone, FALSE, TRUE), true);
			if(strcasecmp($content['status'], 'SUCCESS') == 0) {
					update_option( 'mo_wsfed_client_message', ' A one time passcode is sent to ' . $phone . ' again. Please check if you got the otp and enter it here.');
					update_option('mo_wsfed_client_transactionId',$content['txId']);
					update_option('mo_wsfed_client_registration_status','MO_OTP_DELIVERED_SUCCESS_PHONE');
					$this->mo_wsfed_client_show_success_message();
			}else{
					update_option('mo_wsfed_client_message','There was an error in sending email. Please click on Resend OTP to try again.');
					update_option('mo_wsfed_client_registration_status','MO_OTP_DELIVERED_FAILURE_PHONE');
					$this->mo_wsfed_client_show_error_message();
			}
		}
		else if( isset( $_POST['option'] ) and $_POST['option'] == "mo_wsfed_client_go_back" ){
				update_option('mo_wsfed_client_registration_status','');
				update_option('mo_wsfed_client_verify_customer', '');
				delete_option('mo_wsfed_client_new_registration');
				delete_option('mo_wsfed_client_admin_email');
				delete_option('mo_wsfed_client_admin_phone');
		} else if(isset( $_POST['option'] ) and $_POST['option'] == "mo_wsfed_client_goto_login"){
				delete_option('mo_wsfed_client_new_registration');
				update_option('mo_wsfed_client_verify_customer','true');
		}else if( isset( $_POST['option'] ) and $_POST['option'] == "mo_wsfed_client_register_with_phone_option" ) {
			if(!mo_wsfed_client_is_curl_installed()) {
				update_option( 'mo_wsfed_client_message', 'ERROR: <a href="http://php.net/manual/en/curl.installation.php" target="_blank">PHP cURL extension</a> is not installed or disabled. Resend OTP failed.');
				$this->mo_wsfed_client_show_error_message();
				return;
			}
			$phone = sanitize_text_field($_POST['phone']);
			$phone = str_replace(' ', '', $phone);
			$phone = str_replace('-', '', $phone);
			update_option('mo_wsfed_client_admin_phone', $phone);
			$customer = new mo_wsfed_client_Customer();
			$content = json_decode($customer->send_otp_token('', $phone, FALSE, TRUE), true);
			if(strcasecmp($content['status'], 'SUCCESS') == 0) {
				update_option( 'mo_wsfed_client_message', ' A one time passcode is sent to ' . get_option('mo_wsfed_client_admin_phone') . '. Please enter the otp here to verify your email.');
				update_option('mo_wsfed_client_transactionId',$content['txId']);
				update_option('mo_wsfed_client_registration_status','MO_OTP_DELIVERED_SUCCESS_PHONE');
				$this->mo_wsfed_client_show_success_message();
			}else{
				update_option('mo_wsfed_client_message','There was an error in sending SMS. Please click on Resend OTP to try again.');
				update_option('mo_wsfed_client_registration_status','MO_OTP_DELIVERED_FAILURE_PHONE');
				$this->mo_wsfed_client_show_error_message();
			}
		}
		else if(isset($_POST['option']) && $_POST['option'] == 'mo_wsfed_client_forgot_password_form_option'){
			if(!mo_wsfed_client_is_curl_installed()) {
				update_option( 'mo_wsfed_client_message', 'ERROR: <a href="http://php.net/manual/en/curl.installation.php" target="_blank">PHP cURL extension</a> is not installed or disabled. Resend OTP failed.');
				$this->mo_wsfed_client_show_error_message();
				return;
			}

			$email = get_option('mo_wsfed_client_admin_email');

			$customer = new mo_wsfed_client_Customer();
			$content = json_decode($customer->mo_wsfed_client_forgot_password($email),true);
			if(strcasecmp($content['status'], 'SUCCESS') == 0){
				update_option( 'mo_wsfed_client_message','Your password has been reset successfully. Please enter the new password sent to ' . $email . '.');
				$this->mo_wsfed_client_show_success_message();
			}else{
				update_option( 'mo_wsfed_client_message','An error occured while processing your request. Please Try again.');
				$this->mo_wsfed_client_show_error_message();
			}
		}
		
		}
	}

	function create_customer(){
		$customer = new mo_wsfed_client_Customer();
		$customerKey = json_decode( $customer->create_customer(), true );
		if( strcasecmp( $customerKey['status'], 'CUSTOMER_USERNAME_ALREADY_EXISTS') == 0 ) {
					$this->get_current_customer();
		} else if( strcasecmp( $customerKey['status'], 'SUCCESS' ) == 0 ) {
			update_option( 'mo_wsfed_client_admin_customer_key', $customerKey['id'] );
			update_option( 'mo_wsfed_client_admin_api_key', $customerKey['apiKey'] );
			update_option( 'mo_wsfed_client_customer_token', $customerKey['token'] );
			update_option( 'mo_wsfed_client_free_version', 1 );
			update_option('mo_wsfed_client_admin_password', '');
			update_option( 'mo_wsfed_client_message', 'Thank you for registering with miniorange.');
			update_option('mo_wsfed_client_registration_status','');
			delete_option('mo_wsfed_client_verify_customer');
			delete_option('mo_wsfed_client_new_registration');
			$this->mo_wsfed_client_show_success_message();
			wp_redirect(admin_url().'admin.php?page=mo_wsfed_client_settings&tab=save');
		}
		update_option('mo_wsfed_client_admin_password', '');
	}

	function get_current_customer(){
		$customer = new mo_wsfed_client_Customer();
		$content = $customer->get_customer_key();
		$customerKey = json_decode( $content, true );
		if( json_last_error() == JSON_ERROR_NONE ) {
			update_option( 'mo_wsfed_client_admin_customer_key', $customerKey['id'] );
			update_option( 'mo_wsfed_client_admin_api_key', $customerKey['apiKey'] );
			update_option( 'mo_wsfed_client_customer_token', $customerKey['token'] );
			update_option('mo_wsfed_client_admin_password', '' );
			$certificate = get_option('mo_wsfed_client_x509_certificate');
			if(empty($certificate)) {
				update_option( 'mo_wsfed_client_free_version', 1 );
			}
			update_option( 'mo_wsfed_client_message', 'Your account has been retrieved successfully.' );
			delete_option('mo_wsfed_client_verify_customer');
			delete_option('mo_wsfed_client_new_registration');
			$this->mo_wsfed_client_show_success_message();
			wp_redirect(admin_url().'admin.php?page=mo_wsfed_client_settings&tab=save');
		} else {
			update_option( 'mo_wsfed_client_message', 'You already have an account with miniOrange. Please enter a valid password.');
			update_option('mo_wsfed_client_verify_customer', 'true');
			delete_option('mo_wsfed_client_new_registration');
			$this->mo_wsfed_client_show_error_message();
		}
	}

	public function mo_wsfed_client_check_empty_or_null( $value ) {
		if( ! isset( $value ) || empty( $value ) ) {
			return true;
		}
		return false;
	}

	function miniorange_sso_menu() {
		$page = add_menu_page( 'MO SAML Settings ' . __( 'Configure WSFED Identity Provider for SSO', 'mo_wsfed_client_settings' ), 'miniOrange WS Federation SSO', 'administrator', 'mo_wsfed_client_settings', array( $this, 'mo_login_widget_saml_options' ), plugin_dir_url(__FILE__) . 'images/miniorange.png' );
	}


	
	
}
new mo_wsfed_client_login;
