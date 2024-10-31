<?php
/**
* Plugin Name: Safeshop
* Description: Safe shop logo Configuration module.
* Version: 1.0
* Author: Safe Shop
* License: GPLv2 or later
**/
define( 'SAFESHOP_VERSION', '1.0' );
define( 'SAFESHOP__MINIMUM_WP_VERSION', '4.0' );
define( 'SAFESHOP__PLUGIN_DIR', plugin_dir_url( __FILE__ ) );
const SAFE_SHOP_API_URL = 'https://api.safe.shop/v1';

function safeshop_register_settings() {
   register_setting( 'safeshop_options_group', 'safeshop_option_name', 'safeshop_callback' );
}
add_action( 'admin_init', 'safeshop_register_settings' );

function safeshop_adminform_page()
{
?>
  <div>
  	<?php screen_icon(); ?>
	  	<div class="wrap">
	  		<h1>Safeshop</h1>
			  <form method="post" action="" id="safeshop-form">
			  	<div class="wrap">
				  	<table class="form-table">
					  <?php settings_fields( 'safeshop_options_group' ); ?>
					  <tr>
					  	<th scope="row">
					  		<label>Show Safe shop logo</label>
					  	</th>
					  	<td>
					  		<input type="radio" name="safeshop_status" value="1" <?php if ( esc_html(get_option('safeshop_status')) ) { ?> checked <?php }?> > Enabled
							<input type="radio" name="safeshop_status" value="0" <?php if ( esc_html(!get_option('safeshop_status')) ) { ?> checked <?php }?> > Disabled
						</td>
					  </tr>
					  <tr>
					  	<th scope="row">
					  		<label>CLIENT ID</label>
					  	</th>
					  	<td>
					  		<input type="text" name="safeshop_client_id" value = "<?php esc_html_e(get_option('safeshop_client_id'))?>"/>
					  		<input type="hidden" name="safeshop_nonce" value="<?php esc_html_e(wp_create_nonce('submit_safeshop_data')) ?>"/>
					  	</td>
					  </tr>
					  <tr>
					  	<th scope="row">
					  		<label>CLIENT SECRET</label>
					  	</th>
					  	<td>
					  		<input type="text" name="safeshop_client_secret" value = "<?php esc_html_e(get_option('safeshop_client_secret'))?>"/>
					  	</td>
					  </tr>
					  <tr>
					  	<td>
					  		<?php  submit_button(); ?>
					  	</td>
					  </tr>
					</table>
				</div>
			  </form>
		</div>
	</div>
<?php
}
function safeshop_formdata_validation($client_id, $client_secret)
{
    if (empty($client_id) || empty($client_secret) ) {
        return false;
    }
    return true;
}
function safeshop_update_status() 
{
	if (isset($_POST['submit']) &&  safeshop_formdata_validation($_POST['safeshop_client_id'], $_POST['safeshop_client_secret']) ) 
	{
		if (wp_verify_nonce($_POST['safeshop_nonce'], 'submit_safeshop_data'))
		{
			$safeshop_status 		 = sanitize_text_field($_POST['safeshop_status']);
			$safeshop_client_id 	 = sanitize_text_field($_POST['safeshop_client_id']);
			$safeshop_client_secret  = sanitize_text_field($_POST['safeshop_client_secret']);
			$safeshop_update_status  = update_option('safeshop_status', $safeshop_status);
			$safeshop_update_status .= update_option('safeshop_client_id', $safeshop_client_id);
			$safeshop_update_status .= update_option('safeshop_client_secret', $safeshop_client_secret);
			if ( $safeshop_update_status ) {
				  echo __("<div class='notice notice-success is-dismissible'><p><strong>Settings updated successfully.</strong></p>
					<button type='button' class='notice-dismiss'></button>
				</div>");
			}
		}
		else
		{
			echo __("<div class='notice notice-error is-dismissible'><p><strong>client_id and client_secret are required fields.</strong></p>
						<button type='button' class='notice-dismiss'></button>
					</div>");
		}
	}	
}

add_action( 'admin_init', 'safeshop_update_status' );

function safeshop_register_options_page() 
{
	add_options_page('Page Title', 'Safeshop', 'manage_options', 'safeshop', 'safeshop_adminform_page');
}
add_action('admin_menu', 'safeshop_register_options_page');

add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'add_action_links' );

function add_action_links ( $links ) {
 	$safeshop_settings_links = array(
 		'<a href="' . admin_url( 'options-general.php?page=safeshop' ) . '">Settings</a>',
 	);

	return array_merge( $safeshop_settings_links, $links );
}

function safeshop_display_logo() 
{
	if ( get_option ( 'safeshop_status' )) {
		wp_enqueue_script( 'safeshop', 'https://merchant.safe.shop/widget.js', array( 'jquery' ), null, true );
	}
}

add_action( 'wp_enqueue_scripts', 'safeshop_display_logo' );

function safeshop_adminpage_design() 
{
    wp_register_style( 'custom_wp_admin_css', plugin_dir_url( __FILE__ ) . 'assets/css/admincss.css', false, '1.0.0' );
    wp_enqueue_style( 'custom_wp_admin_css' );
    wp_enqueue_script( 'safeshop', plugin_dir_url( __FILE__ ) . 'assets/js/adminjs.js', array( 'jquery' ), null, true );
}

add_action( 'admin_enqueue_scripts', 'safeshop_adminpage_design' );

add_action( 'woocommerce_thankyou', 'safeshop_invite_customerdata');

function safeshop_invite_customerdata( $order )
{
	$order = new WC_Order($order);
	$firstname = $order->get_shipping_first_name();
	$lastname = $order->get_shipping_last_name();
	$company = $order->get_shipping_company();
	$city = $order->get_shipping_city();
	$postcode = $order->get_shipping_postcode();
	$email = $order->get_billing_email();
	safeshop_sendInvites($email, $firstname, $lastname, $company, $city, $postcode);
}

function safeshop_getAccessToken()
{
    $client_id = get_option('safeshop_client_id');
    $client_secret = get_option('safeshop_client_secret');
    $data = array(
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'grant_type' => 'client_credentials',
        'scope' => '*'
    );

    $response = safeshop_request('/oauth/token', $data);
    if (isset($response->access_token) && $response->access_token) {
        return $response->access_token;
    } else {
        return false;
    }
}

function safeshop_sendInvites(
	$email,
    $firstname,
    $lastname,
    $company,
    $city,
    $postcode
) {
    $data = array(
        'send_datetime' => date('Y-m-d H:i'),
        'email' => $email,
        'firstname' => $firstname,
        'lastname' => $lastname,
        'company' => $company,
        'city' => $city,
        'postcode' => $postcode,
        'channel' => 'wordpress',
    );
    safeshop_request('/invites', $data, true);
}

function safeshop_request($endpoint, $data, $use_auth = false)
{
   $data_string = json_encode($data);
   if ($use_auth) 
   {
       $headers = 'Bearer '.safeshop_getAccessToken();
    }

   $args = array(
    'method' => 'POST',
    'body' => $data_string,
    'timeout' => '45',
    'redirection' => '5',
    'httpversion' => '1.0',
    'blocking' => true,
    'headers' => array(
		    'Content-Type' => 'application/json',
			'Authorization'=> $headers),
    'cookies' => array()
);
    $res = wp_remote_post(SAFE_SHOP_API_URL.$endpoint, $args);
    if ($res)
    {
    	return $res;
    }
    else
    {
    	return false;
    }
}