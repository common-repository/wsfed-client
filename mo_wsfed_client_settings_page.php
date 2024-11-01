<?php

function mo_wsfed_client_config_page() {
	if( isset( $_GET[ 'tab' ] ) ) {
		$active_tab = $_GET[ 'tab' ];
	} else if(mo_wsfed_client_is_customer_registered_saml() && mo_wsfed_client_is_sp_configured()) {
		$active_tab = 'general';
	} else if(mo_wsfed_client_is_customer_registered_saml()) {
		$active_tab = 'config';
	} else {
		$active_tab = 'login';
	}
	?>
	<?php
		if(!mo_wsfed_client_is_curl_installed()) {
			?>
			<p><font color="#FF0000">(Warning: <a href="http://php.net/manual/en/curl.installation.php" target="_blank">PHP cURL extension</a> is not installed or disabled)</font></p>
			<?php
		}

		if(!mo_wsfed_client_is_openssl_installed()) {
			?>
			<p><font color="#FF0000">(Warning: <a href="http://php.net/manual/en/openssl.installation.php" target="_blank">PHP openssl extension</a> is not installed or disabled)</font></p>
			<?php
		}

	?>
<div id="mo_wsfed_client_settings">
	<div class="miniorange_container">
	<table style="width:100%;">
		<tr>
			<h2 class="nav-tab-wrapper">
				<?php if(!mo_wsfed_client_is_customer_registered_saml()) {?>
				<a class="nav-tab <?php echo $active_tab == 'login' ? 'nav-tab-active' : ''; ?>" href="<?php echo add_query_arg( array('tab' => 'login'), $_SERVER['REQUEST_URI'] ); ?>">Account Setup</a>
				<?php }?>
				<a class="nav-tab <?php echo $active_tab == 'config' ? 'nav-tab-active' : ''; ?>" href="<?php echo add_query_arg( array('tab' => 'config'), $_SERVER['REQUEST_URI'] ); ?>">Identity Provider</a>
				<a class="nav-tab <?php echo $active_tab == 'save' ? 'nav-tab-active' : ''; ?>" href="<?php echo add_query_arg( array('tab' => 'save'), $_SERVER['REQUEST_URI'] ); ?>">Service Provider</a>
				<?php if(mo_wsfed_client_is_customer_registered_saml()) {?>
				<a class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>" href="<?php echo add_query_arg( array('tab' => 'general'), $_SERVER['REQUEST_URI'] ); ?>">Sign in Settings</a>
				<?php }?>
				<a class="nav-tab <?php echo $active_tab == 'opt' ? 'nav-tab-active' : ''; ?>" href="<?php echo add_query_arg( array('tab' => 'opt'), $_SERVER['REQUEST_URI'] ); ?>">Attribute/Role Mapping</a>
			</h2>
			<td style="vertical-align:top;width:65%;">
			<?php
				if($active_tab == 'save') {
					mo_wsfed_client_apps_config_saml();
				} else if($active_tab == 'opt') {
					mo_wsfed_client_save_optional_config();
				} else if($active_tab == 'config'){
					mo_wsfed_client_configuration_steps();
				} else if($active_tab == 'general'){
					mo_wsfed_client_general_login_page();
				}  else {
					if (get_option ( 'mo_wsfed_client_verify_customer' ) == 'true') {
						mo_wsfed_client_show_verify_password_page_saml();
					} else if (trim ( get_option ( 'mo_wsfed_client_admin_email' ) ) != '' && trim ( get_option ( 'mo_wsfed_client_admin_api_key' ) ) == '' && get_option ( 'mo_wsfed_client_new_registration' ) != 'true') {
						mo_wsfed_client_show_verify_password_page_saml();
					}else if(get_option('mo_wsfed_client_registration_status') == 'MO_OTP_DELIVERED_SUCCESS_EMAIL' || get_option('mo_wsfed_client_registration_status') == 'MO_OTP_DELIVERED_SUCCESS_PHONE' || get_option('mo_wsfed_client_registration_status') == 'MO_OTP_VALIDATION_FAILURE_EMAIL' || get_option('mo_wsfed_client_registration_status') == 'MO_OTP_VALIDATION_FAILURE_PHONE' || get_option('mo_wsfed_client_registration_status') == 'MO_OTP_DELIVERED_FAILURE' ){
						mo_wsfed_client_show_otp_verification();
					}	else if (! mo_wsfed_client_is_customer_registered_saml()) {
						delete_option ( 'password_mismatch' );
						mo_wsfed_client_show_new_registration_page_saml();

					}
					else {
						mo_wsfed_client_general_login_page();
					}
				}
			?>
			</td>
			<td style="vertical-align:top;padding-left:1%;">
				<?php echo mo_wsfed_client_support_saml(); ?>
			</td>
		</tr>
	</table>
	</div>

<?php
}

function mo_wsfed_client_is_curl_installed() {
    if  (in_array  ('curl', get_loaded_extensions())) {
        return 1;
    } else
        return 0;
}
function mo_wsfed_client_is_openssl_installed() {
	if  (in_array  ('openssl', get_loaded_extensions())) {
		return 1;
	} else
		return 0;
}
function mo_wsfed_client_is_mcrypt_installed() {
	if  (in_array  ('mcrypt', get_loaded_extensions())) {
		return 1;
	} else
		return 0;
}

function mo_wsfed_client_show_new_registration_page_saml() {
	update_option ( 'mo_wsfed_client_new_registration', 'true' );
	$user = wp_get_current_user();
	?>
			<!--Register with miniOrange-->
		<form name="f" method="post" action="">
			<input type="hidden" name="option" value="mo_wsfed_client_register_customer" />
			<div class="mo_wsfed_client_table_layout">
				<div id="toggle1" class="panel_toggle">
					<h3>Register with miniOrange</h3>
				</div>
				<div id="panel1">
					<p><a href="#" id="help_register_link">[ Why should I register? ]</a></p>
					<div hidden id="help_register_desc" class="mo_wsfed_client_help_desc">
						You should register so that in case you need help, we can help you with step by step instructions. We support all known IdPs - <b>ADFS, Okta, Salesforce, Shibboleth, SimpleSAMLphp, OpenAM, Centrify, Ping, RSA, IBM, Oracle, OneLogin, Bitium, WSO2 etc</b>.
					</div>
					</p>
					<table class="mo_wsfed_client_settings_table">
						<tr>
							<td><b><font color="#FF0000">*</font>Email:</b></td>
							<td><input class="mo_wsfed_client_table_textbox" type="email" name="email"
								required placeholder="person@example.com"
								value="<?php echo (get_option('mo_wsfed_client_admin_email') == '') ? get_option('admin_email') : get_option('mo_wsfed_client_admin_email');?>" /></td>
						</tr>
						<tr>
							<td><b><font color="#FF0000">*</font>Company/Organisation:</b></td>
							<td><input class="mo_wsfed_client_table_textbox" type="text" name="company"
								required placeholder="Your company name"
								value="<?php echo (get_option('mo_wsfed_client_admin_company') == '') ? home_url() : get_option('mo_wsfed_client_admin_company');?>" /></td>
						</tr>
						<tr>
							<td><b>First Name:</b></td>
							<td><input class="mo_wsfed_client_table_textbox" type="text" name="first_name"
								placeholder="First Name"
								value="<?php echo (get_option('mo_wsfed_client_admin_first_name') == '') ? $user->first_name : get_option('mo_wsfed_client_admin_first_name');?>" /></td>
						</tr>
						<tr>
							<td><b>Last Name:</b></td>
							<td><input class="mo_wsfed_client_table_textbox" type="text" name="last_name"
								placeholder="Last Name"
								value="<?php echo (get_option('mo_wsfed_client_admin_last_name') == '') ? $user->last_name : get_option('mo_wsfed_client_admin_last_name');?>" /></td>
						</tr>
						<tr>
							<td><b>Phone number:</b></td>
							<td><input class="mo_wsfed_client_table_textbox" type="tel" id="phone_contact" style="width:80%;"
								pattern="[\+]\d{11,14}|[\+]\d{1,4}([\s]{0,1})(\d{0}|\d{9,10})" class="mo_wsfed_client_table_textbox" name="phone"
								title="Phone with country code eg. +1xxxxxxxxxx"
								placeholder="Phone with country code eg. +1xxxxxxxxxx"
								value="<?php echo get_option('mo_wsfed_client_admin_phone');?>" /></td>
						</tr>
							<tr>
								<td></td>
								<td>We will call only if you need support.</td>
							</tr>
						<tr>
							<td><b><font color="#FF0000">*</font>Password:</b></td>
							<td><input class="mo_wsfed_client_table_textbox" required type="password"
								name="password" placeholder="Choose your password (Min. length 6)" /></td>
						</tr>
						<tr>
							<td><b><font color="#FF0000">*</font>Confirm Password:</b></td>
							<td><input class="mo_wsfed_client_table_textbox" required type="password"
								name="confirmPassword" placeholder="Confirm your password" /></td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td><br><input type="submit" name="submit" value="Register"
								class="button button-primary button-large" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							<input type="button" name="mo_wsfed_client_goto_login" id="mo_wsfed_client_goto_login" value="Already have an account?" class="button button-primary button-large" /></td>
						</tr>
					</table>
				</div>
			</div>
		</form>
		<form name="f1" method="post" action="" id="mo_wsfed_client_goto_login_form">
				<input type="hidden" name="option" value="mo_wsfed_client_goto_login"/>
		</form>
		<script>
			jQuery('#mo_wsfed_client_goto_login').click(function(){
				jQuery('#mo_wsfed_client_goto_login_form').submit();
			});
		</script>
		<?php
}


function mo_wsfed_client_show_verify_password_page_saml() {
	?>
			<!--Verify password with miniOrange-->
		<form name="f" method="post" action="">
			<input type="hidden" name="option" value="mo_wsfed_client_verify_customer" />
			<div class="mo_wsfed_client_table_layout">
				<div id="toggle1" class="panel_toggle">
					<h3>Login with miniOrange</h3>
				</div>
				<div id="panel1">
					<p><b>It seems you already have an account with miniOrange. Please enter your miniOrange email and password.<br/> <a target="_blank" href="https://auth.xecurify.com/moas/idp/resetpassword">Click here if you forgot your password?</a></b></p>
					<br/>
					<table class="mo_wsfed_client_settings_table">
						<tr>
							<td><b><font color="#FF0000">*</font>Email:</b></td>
							<td><input class="mo_wsfed_client_table_textbox" type="email" name="email"
								required placeholder="person@example.com"
								value="<?php echo get_option('mo_wsfed_client_admin_email');?>" /></td>
						</tr>
						<tr>
						<td><b><font color="#FF0000">*</font>Password:</b></td>
						<td><input class="mo_wsfed_client_table_textbox" required type="password"
							name="password" placeholder="Enter your password" /></td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td>
							<input type="submit" name="submit" value="Login"
								class="button button-primary button-large" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							<input type="button" name="mo_wsfed_client_goback" id="mo_wsfed_client_goback" value="Back" class="button button-primary button-large" />
						</tr>
					</table>
				</div>
			</div>
		</form>
		<form name="f" method="post" action="" id="mo_wsfed_client_goback_form">
				<input type="hidden" name="option" value="mo_wsfed_client_go_back"/>
		</form>
		<form name="f" method="post" action="" id="mo_wsfed_client_forgotpassword_form">
				<input type="hidden" name="option" value="mo_wsfed_client_forgot_password_form_option"/>
		</form>
		<script>
			jQuery('#mo_wsfed_client_goback').click(function(){
				jQuery('#mo_wsfed_client_goback_form').submit();
			});
			jQuery("a[href=\"#mo_wsfed_client_forgot_password_link\"]").click(function(){
				jQuery('#mo_wsfed_client_forgotpassword_form').submit();
			});
		</script>
		<?php
}

function mo_wsfed_client_show_otp_verification(){
	?>
		<!-- Enter otp -->
		<form name="f" method="post" id="otp_form" action="">
			<input type="hidden" name="option" value="mo_wsfed_client_validate_otp" />
			<div class="mo_wsfed_client_table_layout">
				<table class="mo_wsfed_client_settings_table">
					<h3>Verify Your Email</h3>
					<tr>
						<td><b><font color="#FF0000">*</font>Enter OTP:</b></td>
						<td colspan="2"><input class="mo_wsfed_client_table_textbox" autofocus="true" type="text" name="otp_token" required placeholder="Enter OTP" style="width:61%;" pattern="[0-9]{6,8}"/>
						 &nbsp;&nbsp;<a style="cursor:pointer;" onclick="document.getElementById('resend_otp_form').submit();">Resend OTP</a></td>
					</tr>
					<tr><td colspan="3"></td></tr>
					<tr>

						<td>&nbsp;</td>
						<td style="width:17%">
						<input type="submit" name="submit" value="Validate OTP" class="button button-primary button-large" /></td>

		</form>
		<form name="f" method="post">
						<td style="width:18%">
							<input type="hidden" name="option" value="mo_wsfed_client_go_back"/>
							<input type="submit" name="submit"  value="Back" class="button button-primary button-large" />
						</td>
		</form>
		<form name="f" id="resend_otp_form" method="post" action="">
						<td>
			<?php if(get_option('mo_wsfed_client_registration_status') == 'MO_OTP_DELIVERED_SUCCESS_EMAIL' || get_option('mo_wsfed_client_registration_status') == 'MO_OTP_VALIDATION_FAILURE_EMAIL') { ?>
				<input type="hidden" name="option" value="mo_wsfed_client_resend_otp_email"/>
			<?php } else { ?>
				<input type="hidden" name="option" value="mo_wsfed_client_resend_otp_phone"/>
			<?php } ?>
						</td>

		</form>
		</tr>
			</table>
		<?php if(get_option('mo_wsfed_client_registration_status') == 'MO_OTP_DELIVERED_SUCCESS_EMAIL' || get_option('mo_wsfed_client_registration_status') == 'MO_OTP_VALIDATION_FAILURE_EMAIL') { ?>
			<hr>

				<h3>I did not recieve any email with OTP . What should I do ?</h3>
				<form id="mo_wsfed_client_register_with_phone_form" method="post" action="">
					<input type="hidden" name="option" value="mo_wsfed_client_register_with_phone_option" />
					 If you can't see the email from miniOrange in your mails, please check your <b>SPAM</b> folder. If you don't see an email even in the SPAM folder, verify your identity with our alternate method.
					 <br><br>
						<b>Enter your valid phone number here and verify your identity using one time passcode sent to your phone.</b><br><br>
						<input class="mo_wsfed_client_table_textbox" type="tel" id="phone_contact" style="width:40%;"
								pattern="[\+]\d{11,14}|[\+]\d{1,4}([\s]{0,1})(\d{0}|\d{9,10})" class="mo_wsfed_client_table_textbox" name="phone"
								title="Phone with country code eg. +1xxxxxxxxxx" required
								placeholder="Phone with country code eg. +1xxxxxxxxxx"
								value="<?php echo get_option('mo_wsfed_client_admin_phone');?>" />
						<br /><br /><input type="submit" value="Send OTP" class="button button-primary button-large" />

				</form>
		<?php } ?>
	</div>

<?php
}

function mo_wsfed_client_general_login_page() {
	
	?>
		<?php if(mo_wsfed_client_is_customer_registered_saml()){ ?>
			<div style="background-color:#FFFFFF; border:1px solid #CCCCCC; padding:0px 2% 0px 2%;">
					<h3>Sign in options</h3>
					
					<div id="mo_wsfed_client_add_widget_steps"  >
						<ol>
							<li>Go to Appearances > Widgets.</li>
							<li>Select "Login with <?php echo get_option('mo_wsfed_client_identity_name'); ?>". Drag and drop to your favourite location and save.</li>
						</ol>
						</div>	
			</div>
			
	<?php }
}

function mo_wsfed_client_configuration_steps() { ?>
	<div border="0" style="background-color:#FFFFFF; border:1px solid #CCCCCC; padding:0px 0px 0px 10px;">
		<br>
		<h4>You will need the following information to configure your IdP. Copy it and keep it handy:</h4>
		<table border="1" style="background-color:#FFFFFF; border:1px solid #CCCCCC; padding:0px 0px 0px 10px; margin:2px; border-collapse: collapse; width:98%">
			<?php
				$plugins_url = plugins_url('', __FILE__).'/';
				$parsed = parse_url($plugins_url);
				if (empty($parsed['scheme'])) {
					$plugins_url = home_url(). plugins_url('', __FILE__).'/';
				}
			?>
			<tr>
				<td style="width:40%; padding: 15px;"><b>WS Fed Realm</b></td>
				<td style="width:60%; padding: 15px;"><?php echo $plugins_url; ?></td>
			</tr>


			<tr>
				<td style="width:40%; padding: 15px;"><b>Reply URL</b></td>
				<td style="width:60%;  padding: 15px;"><?php echo home_url().'/'?></td>
			</tr>


	</table>
	<br>
	</div>
<?php }

function mo_wsfed_client_apps_config_saml() {
	$sync_interval = get_option('mo_wsfed_client_metadata_sync_interval');
	$sync_url = get_option('mo_wsfed_client_metadata_url_for_sync');
	$sync_selected = !empty($sync_url) ? 'checked' : '' ;
	$hidden = empty($sync_url) ? 'hidden' : '' ;
	$saml_identity_metadata_provider='';
	if (isset($_GET['action']) && $_GET['action'] == 'upload_metadata') {
		echo '<div border="0" style="background-color:#FFFFFF; border:1px solid #CCCCCC; padding:0px 0px 0px 10px;">
		<table style="width:100%;">
			<tr>
				<td colspan="3">
					<h3>Upload IDP Metadata
						<span style="float:right;margin-right:25px;">
							<a href="' . admin_url() . 'admin.php?page=mo_wsfed_client_settings&tab=save' . '"><input type="button" class="button" value="Cancel"/></a>
						</span>
					</h3>
				</td>
			</tr><tr><td colspan="4"><hr></td></tr>
			<tr>';
			
			echo '
			<form name="saml_form" method="post" action="' . admin_url() . 'admin.php?page=mo_wsfed_client_settings&tab=save' . '" enctype="multipart/form-data">
			
				<tr>
				<td width="30%"><strong>Identity Provider Name<span style="color:red;">*</span>:</strong></td>
				<td><input type="text" name="saml_identity_metadata_provider" placeholder="Identity Provider name like ADFS, SimpleSAML" style="width: 100%;" value="" required /></td>
				</tr>';
				
				
		echo '<tr>
				<td colspan="2"><p style="font-size:13pt;text-align:center;"><b>OR</b></p></td>
			</tr>';
		echo '
			<tr>
				<input type="hidden" name="option" value="saml_upload_metadata" />
				<input type="hidden" name="action" value="fetch_metadata" />
				<td width="20%">Enter metadata URL:</td>
				<td><input type="url" name="metadata_url" placeholder="Enter metadata URL of your IdP." style="width:100%" value="'.$sync_url.'"/></td>
				<td width="20%">&nbsp;&nbsp;<input type="submit" class="button button-primary button-large" value="Fetch Metadata"/></td>
			</tr>
			</form>';
		echo '</table><br /></div>';


	} else{
		global $wpdb;
		$entity_id = get_option('entity_id');
		if(!$entity_id) {
			$entity_id = 'https://auth.xecurify.com/moas';
		}
		$sso_url = get_option('sso_url');
		$cert_fp = get_option('cert_fp');

		//Broker Service
		$mo_wsfed_client_identity_name = get_option('mo_wsfed_client_identity_name');
		$mo_wsfed_client_idp_issuer = get_option('mo_wsfed_client_idp_issuer');
		$mo_wsfed_client_login_url = get_option('mo_wsfed_client_login_url');
		$mo_wsfed_client_issuer = get_option('mo_wsfed_client_issuer');
		$mo_wsfed_client_x509_certificate = maybe_unserialize(get_option ( 'mo_wsfed_client_x509_certificate' ));
		$mo_wsfed_client_x509_certificate = !is_array($mo_wsfed_client_x509_certificate) ? array(0=>$mo_wsfed_client_x509_certificate) : $mo_wsfed_client_x509_certificate;
		$mo_wsfed_client_response_signed = get_option('mo_wsfed_client_response_signed');
		if($mo_wsfed_client_response_signed == NULL) {$mo_wsfed_client_response_signed = 'checked'; }
		$mo_wsfed_client_assertion_signed = get_option('mo_wsfed_client_assertion_signed');
		if($mo_wsfed_client_assertion_signed == NULL) {$mo_wsfed_client_assertion_signed = 'Yes'; }

		$idp_config = get_option('mo_wsfed_client_idp_config_complete');
		?>
		<form width="98%" border="0" style="background-color:#FFFFFF; border:1px solid #CCCCCC; padding:0px 0px 0px 10px;" name="saml_form" method="post" action="">
		<input type="hidden" name="option" value="login_widget_saml_save_settings" />
		<table style="width:100%;">
			<tr>
				<td colspan="2">
					<h3>Configure Service Provider</h3>
			</tr><tr><td colspan="4"><hr></td></tr>
			<?php if(!mo_wsfed_client_is_customer_registered_saml()) { ?>
				<tr>
					<td colspan="2"><div style="display:block;color:red;background-color:rgba(251, 232, 0, 0.15);padding:5px;border:solid 1px rgba(255, 0, 9, 0.36);">Please <a href="<?php echo add_query_arg( array('tab' => 'login'), $_SERVER['REQUEST_URI'] ); ?>">Register or Login with miniOrange</a> to configure the Plugin.</div></td>
				</tr>
			<?php } ?>
			<?php if(!$idp_config && mo_wsfed_client_is_customer_registered_saml()) { ?>
				<!--<tr>
					<td colspan="2"><div style="display:block;color:red;background-color:rgba(251, 251, 0, 0.43);padding:5px;border:solid 1px yellow;">You skipped a step. Please complete your Identity Provider configuration before you can enter the fields given below. If you have already completed your IdP configuration, please confirm on <a href="<?php //echo add_query_arg( array('tab' => 'config'), $_SERVER['REQUEST_URI'] ); ?>">Configure Identity Provider</a> page to remove this warning.</div></td>
				</tr>-->
			<?php } ?>
			<tr>
				<td colspan="2">Enter the information gathered from your Identity Provider<br /><br /></td>
			</tr>
			
			<tr>
				<td style="width:200px;"><strong>Identity Provider Name <span style="color:red;">*</span>:</strong></td>
				<td><input type="text" name="mo_wsfed_client_identity_name" placeholder="Identity Provider name like ADFS, SimpleSAML, Salesforce" style="width: 95%;" value="<?php echo $mo_wsfed_client_identity_name;?>" required <?php if(!mo_wsfed_client_is_customer_registered_saml()) echo 'disabled'?> title="Only alphabets, numbers and underscore is allowed"/></td>
			</tr>

			
			<tr><td>&nbsp;</td></tr>
			
			<tr>
				<td style="width:200px;"><strong>Identity Entity Id / Issuer <span style="color:red;">*</span>:</strong></td>
				<td><input type="text" name="mo_wsfed_client_idp_issuer" placeholder="IdP issuer" style="width: 95%;" value="<?php echo $mo_wsfed_client_idp_issuer;?>" required <?php if(!mo_wsfed_client_is_customer_registered_saml()) echo 'disabled'?> /></td>
			</tr>
			
			<tr><td>&nbsp;</td></tr>
			<tr>
				<td><strong>WS Federation Login URL <span style="color:red;">*</span>:</strong></td>
				<td><input type="url" name="mo_wsfed_client_login_url" placeholder="Single Sign On Service URL (HTTP-Redirect binding) of your IdP" style="width: 95%;" value="<?php echo $mo_wsfed_client_login_url;?>" required <?php if(!mo_wsfed_client_is_customer_registered_saml()) echo 'disabled'?>/></td>
			</tr>

			

			<tr><td>&nbsp;</td></tr>
<?php
			foreach ($mo_wsfed_client_x509_certificate as $key => $value) {
				echo '<tr>
				<td><strong>X.509 Certificate <span style="color:red;">*</span>:</strong></td>
				<td><textarea rows="6" cols="5" name="mo_wsfed_client_x509_certificate['.$key.']" placeholder="Copy and Paste the content from the downloaded certificate or copy the content enclosed in X509Certificate tag (has parent tag KeyDescriptor use=signing) in IdP-Metadata XML file" style="width: 95%;"';
				 	echo ' >'.$value.'</textarea></td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td><b>NOTE:</b> Format of the certificate:<br/><b>-----BEGIN CERTIFICATE-----<br/>XXXXXXXXXXXXXXXXXXXXXXXXXXX<br/>-----END CERTIFICATE-----</b></i><br/>
				</tr>';
			}
?>
			<tr><td>&nbsp;</td></tr>
			<tr>
				<td>&nbsp;</td>
				<td><br /><input type="submit" name="submit" style="width:100px;" value="Save" class="button button-primary button-large" <?php if(!mo_wsfed_client_is_customer_registered_saml()) echo 'disabled'?>/> &nbsp;
				<input type="button" name="test" title="You can only test your Configuration after saving your Service Provider Settings." onclick="showTestWindow();" <?php if(!mo_wsfed_client_is_sp_configured() || !get_option('mo_wsfed_client_x509_certificate')) echo 'disabled'?> value="Test configuration" class="button button-primary button-large" style="margin-right: 3%;"/>
				</td>
			</tr>
		</table><br/>
		</form>
	<?php
	}
}


function mo_wsfed_client_save_optional_config(){
	global $wpdb;
	$entity_id = get_option('entity_id');
	if(!$entity_id) {
		$entity_id = 'https://auth.xecurify.com/moas';
	}
	$sso_url = get_option('sso_url');
	$cert_fp = get_option('cert_fp');

	$mo_wsfed_client_identity_name = get_option('mo_wsfed_client_identity_name');

	//Attribute mapping
	$mo_wsfed_client_am_username = get_option('mo_wsfed_client_am_username');
	if($mo_wsfed_client_am_username == NULL) {$mo_wsfed_client_am_username = 'NameID'; }
	$mo_wsfed_client_am_email = get_option('mo_wsfed_client_am_email');
	if($mo_wsfed_client_am_email == NULL) {$mo_wsfed_client_am_email = 'NameID'; }
	$mo_wsfed_client_am_first_name = get_option('mo_wsfed_client_am_first_name');
	$mo_wsfed_client_am_last_name = get_option('mo_wsfed_client_am_last_name');
	$mo_wsfed_client_am_group_name = get_option('mo_wsfed_client_am_group_name');
	?>
		<form name="saml_form_am" method="post" action="">
		<input type="hidden" name="option" value="login_widget_saml_attribute_mapping" />
		<table width="98%" border="0" style="background-color:#FFFFFF; border:1px solid #CCCCCC; padding:0px 0px 0px 10px;">
		  <tr>
			<td colspan="2">
				<h3>Attribute Mapping (Optional)</h3>
			</td>
		  </tr>
		  <?php if(!mo_wsfed_client_is_customer_registered_saml()) { ?>
		  <tr>
			<td colspan="2"><div style="display:block;color:red;background-color:rgba(251, 232, 0, 0.15);padding:5px;border:solid 1px rgba(255, 0, 9, 0.36);">Please <a href="<?php echo add_query_arg( array('tab' => 'login'), $_SERVER['REQUEST_URI'] ); ?>">Register or Login with miniOrange</a> to configure the Plugin.</div></td>
		  </tr>
		  <?php } ?>
		  <tr>
		  	<td colspan="2">[ <a href="#" id="attribute_mapping">Click Here</a> to know how this is useful. ]
		 		<div hidden id="attribute_mapping_desc" class="mo_wsfed_client_help_desc">
					<ol>
						<li>Attributes are user details that are stored in your Identity Provider.</li>
						<li>Attribute Mapping helps you to get user attributes from your IdP and map them to WordPress user attributes like firstname, lastname etc.</li>
						<li>While auto registering the users in your WordPress site these attributes will automatically get mapped to your WordPress user details.</li>
					</ol>
				</div>
				</td>
		  </tr>
		  <tr>
			 <td colspan="2"><br/><b>NOTE: </b>Use attribute name <code>NameID</code> if Identity is in the <i>NameIdentifier</i> element of the subject statement in SAML Response.<br /><br /></td>
		  </tr>

			  <tr>
			  <td style="width:200px;"><strong>Login/Create Wordpress account by: </strong></td>
			  <td><select name="mo_wsfed_client_am_account_matcher" id="mo_wsfed_client_am_account_matcher" <?php if(!mo_wsfed_client_is_customer_registered_saml()) echo 'disabled'?>>
				  <option value="email"<?php if(get_option('mo_wsfed_client_am_account_matcher') == 'email') echo 'selected="selected"' ; ?> >Email</option>
				  <option value="username"<?php if(get_option('mo_wsfed_client_am_account_matcher') == 'username') echo 'selected="selected"' ; ?> >Username</option>
				</select>
			  </td>
			  </tr>
			  <tr>
				<td>&nbsp;</td>
				<td><i>Users in Wordpress will be searched (existing wordpress users) or created (new users) based on this attribute. Use Email by default.</i></td>
			  </tr>
			  <?php if (!get_option('mo_wsfed_client_free_version')) { ?>
				  <tr>
					<td style="width:150px;"><strong>Username <span style="color:red;">*</span>:</strong></td>
					<td><input type="text" name="mo_wsfed_client_am_username" placeholder="Enter attribute name for Username" style="width: 350px;" value="<?php echo $mo_wsfed_client_am_username;?>" required <?php if(!mo_wsfed_client_is_customer_registered_saml()) echo 'disabled'?>/></td>
				  </tr>
				  <tr>
					<td><strong>Email <span style="color:red;">*</span>:</strong></td>
					<td><input type="text" name="mo_wsfed_client_am_email" placeholder="Enter attribute name for Email" style="width: 350px;" value="<?php echo $mo_wsfed_client_am_email;?>" required <?php if(!mo_wsfed_client_is_customer_registered_saml()) echo 'disabled'?>/></td>
				  </tr>
			  <?php } else { ?>
				  <tr>
					<td style="width:150px;"><span style="color:red;">*</span><strong>Username (required):</strong></td>
					<td><b>NameID</b></td>
				  </tr>
				  <tr>
					<td><span style="color:red;">*</span><strong>Email (required):</strong></td>
					<td><b>NameID</b></td>
				  </tr>
			  <?php } ?>
			  <tr>
				<td><strong>First Name:</strong></td>
				<td><input type="text" name="mo_wsfed_client_am_first_name" placeholder="Enter attribute name for First Name" style="width: 350px;" value="<?php echo $mo_wsfed_client_am_first_name;?>" <?php if(!mo_wsfed_client_is_customer_registered_saml()) echo 'disabled'?>/></td>
			  </tr>
			  <tr>
				<td><strong>Last Name:</strong></td>
				<td><input type="text" name="mo_wsfed_client_am_last_name" placeholder="Enter attribute name for Last Name" style="width: 350px;" value="<?php echo $mo_wsfed_client_am_last_name;?>" <?php if(!mo_wsfed_client_is_customer_registered_saml()) echo 'disabled'?>/></td>
			  </tr>
			  <?php if (!get_option('mo_wsfed_client_free_version')) { ?>
				  <tr>
					<td><strong>Group/Role:</strong></td>
					<td><input type="text" name="mo_wsfed_client_am_group_name" placeholder="Enter attribute name for Group/Role" style="width: 350px;" value="<?php echo $mo_wsfed_client_am_group_name;?>" <?php if(!mo_wsfed_client_is_customer_registered_saml()) echo 'disabled'?>/></td>
				  </tr>
			  <?php } else { ?>
			  	  <tr>
					<td><span style="color:red;">*</span><strong>Group/Role:</strong></td>
					<td><input type="text" disabled placeholder="Enter attribute name for Group/Role" style="width: 350px;background: #DCDAD1;"/></td>
				  </tr>
				  <tr>
			  		<td colspan="2"><br /><span style="color:red;">*</span>Customized Attribute Mapping is configurable in the <a href="<?php echo admin_url('admin.php?page=mo_wsfed_client_settings&tab=licensing');?>"><b>premium</b></a> version of the plugin.</td>
			 	  </tr>
			  <?php } ?>
			  <tr>
				<td>&nbsp;</td>
				<td><br /><input type="submit" style="width:100px;" name="submit" value="Save" class="button button-primary button-large" <?php if(!mo_wsfed_client_is_customer_registered_saml()) echo 'disabled'?>/> &nbsp;
				<br /><br />
				</td>
			  </tr>
			 </table>
			 </form>
			 <br />
			 <form name="saml_form_am_role_mapping" method="post" action="">
				<input type="hidden" name="option" value="login_widget_saml_role_mapping" />
				<table width="98%" border="0" style="background-color:#FFFFFF; border:1px solid #CCCCCC; padding:0px 0px 0px 10px;">
					<tr>
						<td colspan="2">
							<h3>Role Mapping (Optional)</h3>
						</td>
					</tr>
					 <tr>
					  	<td colspan="2">[ <a href="#" id="role_mapping">Click Here</a> to know how this is useful. ]
					 		<div hidden id="role_mapping_desc" class="mo_wsfed_client_help_desc">
								<ol>
									<li>WordPress uses a concept of Roles, designed to give the site owner the ability to control what users can and cannot do within the site.</li>
									<li>WordPress has six pre-defined roles: Super Admin, Administrator, Editor, Author, Contributor and Subscriber.</li>
									<li>Role mapping helps you to assign specific roles to users of a certain group in your IdP.</li>
									<li>While auto registering, the users are assigned roles based on the group they are mapped to.</li>
								</ol>
							</div>
							</td>
					  </tr>
					<tr><td colspan="2"><br/><b>NOTE: </b>Role will be assigned only to new users. Existing Wordpress users' role remains same.<br /><br/></td></tr>
					<tr><td colspan="2"><input type="checkbox" disabled style="background: #DCDAD1;" />&nbsp;&nbsp;<span style="color:red;">*</span>Do not auto create users if roles are not mapped here.<br /></td></tr>
					<?php if (!get_option('mo_wsfed_client_free_version')) { ?>
						<tr><td colspan="2"><input type="checkbox" id="dont_allow_unlisted_user_role" name="mo_wsfed_client_am_dont_allow_unlisted_user_role" value="checked" <?php echo get_option('mo_wsfed_client_am_dont_allow_unlisted_user_role'); ?> <?php if(!mo_wsfed_client_is_customer_registered_saml()) { echo "disabled"; } ?> />&nbsp;&nbsp;Do not assign role to unlisted users.<br /><br /></td></tr>
					<?php } else { ?>
						<tr><td colspan="2"><input type="checkbox" style="background: #DCDAD1;" disabled />&nbsp;&nbsp;<span style="color:red;">*</span>Do not assign role to unlisted users.<br /><br /></td></tr>
					<?php } ?>
					<tr>
						<td><strong>Default Role:</strong></td>
						<td>
							<?php
								$disabled = '';
								if(!mo_wsfed_client_is_customer_registered_saml())
									$disabled = 'disabled';
							?>
								<select id="mo_wsfed_client_am_default_user_role" name="mo_wsfed_client_am_default_user_role" <?php echo $disabled ?> style="width:150px;" >
							 <?php
								$default_role = get_option('mo_wsfed_client_am_default_user_role');
								if(empty($default_role))
									$default_role = get_option('default_role');
								echo wp_dropdown_roles( $default_role );
							?>
								</select>
							&nbsp;&nbsp;&nbsp;&nbsp;<i>Select the default role to assign to Users.</i>
						</td>
				  	</tr>
					<?php
						$is_disabled = "";
						if(!mo_wsfed_client_is_customer_registered_saml()) {
							$is_disabled = "disabled";
						}
						$wp_roles = new WP_Roles();
						$roles = $wp_roles->get_names();
						$roles_configured = get_option('mo_wsfed_client_am_role_mapping');
						foreach ($roles as $role_value => $role_name) {
							if (!get_option('mo_wsfed_client_free_version')) {
								echo '<tr><td><b>' . $role_name .'</b></td><td><input type="text" name="saml_am_group_attr_values_' . $role_value . '" value="' . $roles_configured[$role_value] .'" placeholder="Semi-colon(;) separated Group/Role value for ' . $role_name . '" style="width: 400px;"' . $is_disabled . ' /></td></tr>';
							} else {
								echo '<tr><td><span style="color:red;">*</span><b>' . $role_name .'</b></td><td><input type="text" placeholder="Semi-colon(;) separated Group/Role value for ' . $role_name . '" style="width: 400px;background: #DCDAD1" disabled /></td></tr>';
							}
						}
					?>
					<?php if (get_option('mo_wsfed_client_free_version')) { ?>
					<tr>
					  	<td colspan="2"><br /><span style="color:red;">*</span>Customized Role Mapping options are configurable in the <a href="<?php echo admin_url('admin.php?page=mo_wsfed_client_settings&tab=licensing');?>"><b>premium</b></a> version of the plugin.</td>
					</tr>
					<?php } ?>
					<tr>
						<td>&nbsp;</td>
						<td><br /><input type="submit" style="width:100px;" name="submit" value="Save" class="button button-primary button-large" <?php if(!mo_wsfed_client_is_customer_registered_saml()) echo 'disabled'?>/> &nbsp;
						<br /><br />
						</td>
					</tr>
				</table>
			</form>
	<?php
}



function mo_wsfed_client_get_test_url(){
	$url = home_url(). '/?option=testConfig';
	return $url;
}

function mo_wsfed_client_is_customer_registered_saml() {
			$email 			= get_option('mo_wsfed_client_admin_email');
			$customerKey 	= get_option('mo_wsfed_client_admin_customer_key');
			if( ! $email || ! $customerKey || ! is_numeric( trim( $customerKey ) ) ) {
				return 0;
			} else {
				return 1;
			}
}

function mo_wsfed_client_is_sp_configured() {
	$mo_wsfed_client_login_url = get_option('mo_wsfed_client_login_url');
	// $mo_wsfed_client_x509_certificate=get_option('mo_wsfed_client_x509_certificate');
	// $mo_wsfed_client_issuer=get_option('mo_wsfed_client_issuer');
	if( empty($mo_wsfed_client_login_url) ) {
		return 0;
	} else {
		return 1;
	}
}

function mo_wsfed_client_support_saml(){
?>
	<div class="mo_wsfed_client_support_layout">
		<div>
			<h3>Support</h3>
			<p>Need any help? We can help you with configuring your Identity Provider. Just send us a query and we will get back to you soon.</p>
			<form method="post" action="">
				<input type="hidden" name="option" value="mo_wsfed_client_contact_us_query_option" />
				<table class="mo_wsfed_client_settings_table">
					<tr>
						<td><input style="width:95%" type="email" class="mo_wsfed_client_table_textbox" required name="mo_wsfed_client_contact_us_email" value="<?php echo get_option("mo_wsfed_client_admin_email"); ?>" placeholder="Enter your email"></td>
					</tr>
					<tr>
						<td><input type="tel" style="width:95%" id="contact_us_phone" pattern="[\+]\d{11,14}|[\+]\d{1,4}[\s]\d{9,10}" class="mo_wsfed_client_table_textbox" name="mo_wsfed_client_contact_us_phone" value="<?php echo get_option('mo_wsfed_client_admin_phone');?>" placeholder="Enter your phone"></td>
					</tr>
					<tr>
						<td><textarea class="mo_wsfed_client_table_textbox" style="width:95%" onkeypress="mo_wsfed_client_valid_query(this)" onkeyup="mo_wsfed_client_valid_query(this)" onblur="mo_wsfed_client_valid_query(this)" required name="mo_wsfed_client_contact_us_query" rows="4" style="resize: vertical;" placeholder="Write your query here"></textarea></td>
					</tr>
				</table>
				<div style="text-align:center;">
					<input type="submit" name="submit" style="margin:15px; width:120px;" class="button button-primary button-large" />
				</div>
			</form>
		</div>
	</div>
	<script>
		jQuery("#contact_us_phone").intlTelInput();
		jQuery("#phone_contact").intlTelInput();
		function mo_wsfed_client_valid_query(f) {
			!(/^[a-zA-Z?,.\(\)\/@ 0-9]*$/).test(f.value) ? f.value = f.value.replace(
					/[^a-zA-Z?,.\(\)\/@ 0-9]/, '') : null;
		}
		function showTestWindow() {
		var myWindow = window.open("<?php echo mo_wsfed_client_get_test_url(); ?>", "TEST SAML IDP", "scrollbars=1 width=800, height=600");
		}
	</script>
<?php }



?>