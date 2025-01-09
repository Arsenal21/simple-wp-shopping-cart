<?php

use TTHQ\WPSC\Lib\PayPal\PayPal_Bearer;
use TTHQ\WPSC\Lib\PayPal\PayPal_PPCP_Config;
use TTHQ\WPSC\Lib\PayPal\Onboarding\PayPal_PPCP_Onboarding;
use TTHQ\WPSC\Lib\PayPal\Onboarding\PayPal_PPCP_Onboarding_Serverside;

class WPSC_PPCP_settings_page
{

	private $settings;

	private $ppcp_connection_subtab_url = "admin.php?page=wspsc-menu-main&action=ppcp-settings&subtab=api-connection";
	private $ppcp_api_creds_subtab_url = "admin.php?page=wspsc-menu-main&action=ppcp-settings&subtab=api-credentials";
	private $ppcp_button_subtab_link = "admin.php?page=wspsc-menu-main&action=ppcp-settings&subtab=button-appreance";

	public function __construct()
	{
		if (!current_user_can('manage_options')) {
			wp_die('You do not have permission to access the settings page.');
		}

		$this->settings = PayPal_PPCP_Config::get_instance();

		$this->handle_ppcp_settings_tab();
	}

	/**
	 * Render paypal ppcp settings tab view
	 *
	 * @return void
	 */
	public function handle_ppcp_settings_tab()
	{

		do_action('wpsc_ppcp_settings_menu_tab_start');

		//Documentation link
		$ppcp_documentation_link = "https://www.tipsandtricks-hq.com/ecommerce/paypal-ppcp-setup-and-configuration-5023";
		echo '<div class="wpsc-grey-box">';
		echo '<p>';
		_e('Configure the PayPal API credentials and checkout button appearance for the PayPal Commerce Platform (PPCP).', 'wordpress-simple-paypal-shopping-cart');
		echo '&nbsp;' . '<a href="' . $ppcp_documentation_link . '" target="_blank">' . __('Read this documentation', 'wordpress-simple-paypal-shopping-cart') . '</a> ' . __('to learn how to set up and configure it.', 'wordpress-simple-paypal-shopping-cart');
		echo '</p>';
		echo '</div>';

		//Sub nav tabs related code.
		$subtab = isset($_GET['subtab']) ? sanitize_text_field($_GET['subtab']) : '';
		$selected_subtab = $subtab;
?>
		<!-- ppcp settings menu's sub nav tabs -->
		<h3 class="nav-tab-wrapper">
			<a class="nav-tab <?php echo ($subtab == '' || $subtab == 'api-connection') ? 'nav-tab-active' : ''; ?>" href="<?php echo $this->ppcp_connection_subtab_url; ?>"><?php _e('PayPal API Connection', 'wordpress-simple-paypal-shopping-cart'); ?></a>
			<a class="nav-tab <?php echo ($subtab == 'api-credentials') ? 'nav-tab-active' : ''; ?>" href="<?php echo $this->ppcp_api_creds_subtab_url; ?>"><?php _e('API Credentials', 'wordpress-simple-paypal-shopping-cart'); ?></a>			
			<a class="nav-tab <?php echo ($subtab == 'button-appreance') ? 'nav-tab-active' : ''; ?>" href="<?php echo $this->ppcp_button_subtab_link; ?>"><?php _e('Button Appearance', 'wordpress-simple-paypal-shopping-cart'); ?></a>
		</h3>
		<br />
		<?php

		// Handle PayPal ppcp checkout settings form submit
		if (isset($_POST['wpsc_ppcp_checkout_settings_submit']) && check_admin_referer('wpsc_ppcp_checkout_settings_submit_nonce')) {
			$this->settings->set_value('ppcp_checkout_enable', (isset($_POST['ppcp_checkout_enable']) ? sanitize_text_field($_POST['ppcp_checkout_enable']) : ''));

			$this->settings->save();
			echo '<div class="notice notice-success"><p>' . __('PayPal checkout settings updated successfully.', 'wordpress-simple-paypal-shopping-cart') . '</p></div>';
		}

        if (isset($_GET['wpsc_ppcp_after_onboarding'])){
            $environment_mode = isset($_GET['environment_mode']) ? sanitize_text_field($_GET['environment_mode']) : '';
            $onboarding_action_result = '<p>PayPal merchant account connection setup completed for environment mode: '. esc_attr($environment_mode) .'</p>';
            echo '<div class="notice notice-success"><p>' . $onboarding_action_result . '</p></div>';
        }

		if (isset($_GET['wpsc_ppcp_disconnect_production'])){
            //Verify nonce
            check_admin_referer( 'wpsc_ac_disconnect_nonce_production' );

            PayPal_PPCP_Onboarding_Serverside::reset_seller_api_credentials('production');
            $disconnect_action_result = __('PayPal account disconnected.', 'wordpress-simple-paypal-shopping-cart');
            echo '<div class="notice notice-success"><p>' . $disconnect_action_result . '</p></div>';
        }

        if (isset($_GET['wpsc_ppcp_disconnect_sandbox'])){
            //Verify nonce
            check_admin_referer( 'wpsc_ac_disconnect_nonce_sandbox' );

            PayPal_PPCP_Onboarding_Serverside::reset_seller_api_credentials('sandbox');
            $disconnect_action_result = __('PayPal sandbox account disconnected.', 'wordpress-simple-paypal-shopping-cart');
            echo '<div class="notice notice-success"><p>' . $disconnect_action_result . '</p></div>';
        }

		if (isset($_POST['wpsc_ppcp_api_credentials_submit']) && check_admin_referer('wpsc_ppcp_api_credentials_submit_nonce')) {
			$this->settings->set_value('paypal-live-client-id', (isset($_POST['paypal-live-client-id']) ? sanitize_text_field($_POST['paypal-live-client-id']) : ''));
			$this->settings->set_value('paypal-live-secret-key', (isset($_POST['paypal-live-secret-key']) ? sanitize_text_field($_POST['paypal-live-secret-key']) : ''));
			$this->settings->set_value('paypal-sandbox-client-id', (isset($_POST['paypal-sandbox-client-id']) ? sanitize_text_field($_POST['paypal-sandbox-client-id']) : ''));
			$this->settings->set_value('paypal-sandbox-secret-key', (isset($_POST['paypal-sandbox-secret-key']) ? sanitize_text_field($_POST['paypal-sandbox-secret-key']) : ''));

			$this->settings->save();
			echo '<div class="notice notice-success"><p>' . __('PayPal API settings updated successfully.', 'wordpress-simple-paypal-shopping-cart') . '</p></div>';
		}

		// Handle PayPal button appearance.
		if (isset($_POST['wpsc_button_appearance_submit']) && check_admin_referer('wpsc_button_appearance_submit_nonce', 'wpsc_button_appearance_submit_nonce')) {
			$this->settings->set_value('ppcp_btn_type', (isset($_POST['ppcp_btn_type']) ? sanitize_text_field($_POST['ppcp_btn_type']) : ''));
			$this->settings->set_value('ppcp_btn_shape', (isset($_POST['ppcp_btn_shape']) ? sanitize_text_field($_POST['ppcp_btn_shape']) : ''));
			$this->settings->set_value('ppcp_btn_layout', (isset($_POST['ppcp_btn_layout']) ? sanitize_text_field($_POST['ppcp_btn_layout']) : ''));
			$this->settings->set_value('ppcp_btn_height', (isset($_POST['ppcp_btn_height']) ? sanitize_text_field($_POST['ppcp_btn_height']) : ''));
			$this->settings->set_value('ppcp_btn_width', (isset($_POST['ppcp_btn_width']) ? sanitize_text_field($_POST['ppcp_btn_width']) : 250));
			$this->settings->set_value('ppcp_btn_color', (isset($_POST['ppcp_btn_color']) ? sanitize_text_field($_POST['ppcp_btn_color']) : ''));

			// Funding disable related
			$this->settings->set_value('ppcp_disable_funding_card', (isset($_POST['ppcp_disable_funding_card']) ? sanitize_text_field($_POST['ppcp_disable_funding_card']) : '0'));
			$this->settings->set_value('ppcp_disable_funding_credit', (isset($_POST['ppcp_disable_funding_credit']) ? sanitize_text_field($_POST['ppcp_disable_funding_credit']) : '0'));
			$this->settings->set_value('ppcp_disable_funding_venmo', (isset($_POST['ppcp_disable_funding_venmo']) ? sanitize_text_field($_POST['ppcp_disable_funding_venmo']) : '0'));

			$this->settings->save();
			echo '<div class="notice notice-success"><p>' . __('PayPal button appearance settings updated successfully.', 'wordpress-simple-paypal-shopping-cart') . '</p></div>';
		}

		// Handle delete token cache form submit
		if (isset($_GET['wpsc_ppcp_delete_cache'])) {
			check_admin_referer('wpsc_ppcp_delete_cache');
			/**
			 * TODO: Check if it can delete cached token properly or not.
			 *
			 * FIXME: if needed
			 */
			PayPal_Bearer::delete_cached_token();
			echo '<div class="notice notice-success"><p>' . __('PayPal API access token cache deleted successfully.', 'wordpress-simple-paypal-shopping-cart') . '</p></div>';
		}

		//Switch case for the various different sub-tabs.
		switch ($selected_subtab) {
			case 'api-credentials':
				$this->handle_ppcp_api_creds_subtab();
				break;
			case 'button-appreance':
					$this->handle_ppcp_button_appearance_subtab();
					break;				
			default: 
				// 'api-connection'
				$this->handle_ppcp_connection_settings_subtab();
				break;
		}

		//End of the payment settings menu tab.
		do_action('wpsc_ppcp_settings_menu_tab_end');

		wpsc_settings_menu_footer();
	}

	/**
	 * Render the paypal connection settings subtab
	 */
	public function handle_ppcp_connection_settings_subtab()
	{
		?>
		<!-- PayPal PPCP checkout enable settings postbox -->
		<div class="postbox">
			<h2 id="paypal-ppcp-checkout-enable-section"><?php _e("Enable PayPal Commerce Platform Checkout (New API)", 'wordpress-simple-paypal-shopping-cart'); ?></h2>
			<div class="inside">
				<form action="" method="POST">
					<table class="form-table" role="presentation">
						<tbody>
							<tr>
								<th scope="row"><?php _e('Enable PayPal Commerce Platform Checkout', 'wordpress-simple-paypal-shopping-cart'); ?></th>
								<td>
									<p>
										<label>
											<input type="checkbox" name="ppcp_checkout_enable" value="1" <?php echo (!empty($this->settings->get_value('ppcp_checkout_enable'))) ? ' checked' : ''; ?>>
										</label>
									</p>
									<p class="description">
										<?php _e("Enable this to offer the PayPal commerce platform (PPCP) checkout buttons as an option in the shopping cart.", 'wordpress-simple-paypal-shopping-cart'); ?>
									</p>
								</td>
							</tr>
						</tbody>
					</table>
					<?php
					//show a message if sandbox mode is enabled.
					wpsc_settings_output_sandbox_mode_msg();
					?>
					<input type="submit" name="wpsc_ppcp_checkout_settings_submit" class="button-primary" value="<?php _e('Save Changes', 'wordpress-simple-paypal-shopping-cart'); ?>" />
					<?php wp_nonce_field('wpsc_ppcp_checkout_settings_submit_nonce'); ?>
				</form>
			</div>
		</div>

		<!-- PayPal PPCP Connection postbox -->
		<div class="postbox">
			<h2 id="paypal-ppcp-connection-section"><?php _e("PayPal Account Connection", "wordpress-simple-paypal-shopping-cart"); ?></h2>
			<div class="inside">
				<?php
				$this->handle_paypal_ppcp_connection_settings();
				?>
			</div>
		</div>

		<!-- PayPal PPCP API Access Token deletion checkbox -->
		<div class="postbox">
			<h2 id="paypal-delete-token-cache-section"><?php _e("Delete PayPal API Access Token Cache", 'wordpress-simple-paypal-shopping-cart'); ?></h2>
			<div class="inside">
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row"><?php _e('Delete Access Token Cache', 'wordpress-simple-paypal-shopping-cart'); ?></th>
							<td>
								<?php
								$delete_cache_url = admin_url($this->ppcp_connection_subtab_url);
								$delete_cache_url = add_query_arg('wpsc_ppcp_delete_cache', 1, $delete_cache_url);
								$delete_cache_url_nonced = add_query_arg('_wpnonce', wp_create_nonce('wpsc_ppcp_delete_cache'), $delete_cache_url);
								echo '<p><a class="button wpsc-paypal-delete-cache-btn" href="' . esc_url_raw($delete_cache_url_nonced) . '">' . __('Delete Token Cache', 'wordpress-simple-paypal-shopping-cart') . '</a></p>';
								echo '<p class="description">' . __('This will delete the PayPal API access token cache. This is useful if you are having issues with the PayPal API after changing/updating the API credentials.', 'wordpress-simple-paypal-shopping-cart') . '</p>';
								?>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	<?php
	}

	/**
	 * Render the paypal api credentials settings subtab
	 */
	public function handle_ppcp_api_creds_subtab() {
		// Paypal PPCP API credentials settings
		$paypal_live_client_id = $this->settings->get_value('paypal-live-client-id');
		$paypal_live_secret_key = $this->settings->get_value('paypal-live-secret-key');
		$paypal_sandbox_client_id = $this->settings->get_value('paypal-sandbox-client-id');
		$paypal_sandbox_secret_key = $this->settings->get_value('paypal-sandbox-secret-key');

		?>
		<!-- PayPal PPCP API credentials settings postbox -->
		<div class="postbox">
			<h2><?php _e("PayPal API Credentials for PPCP", 'wordpress-simple-paypal-shopping-cart'); ?></h2>
				<div class="inside">

					<?php
					echo '<p class="description">';
					$ppcp_documentation_link = "https://www.tipsandtricks-hq.com/ecommerce/getting-paypal-api-credentials-for-paypal-commerce-platform-5027";
					_e("If you have used the automatic option to connect and get your PayPal API credentials from the 'PayPal API Connection' tab, they will be displayed below. The following section also allows for manual entry of your PayPal API credentials in case the automatic option is non-functional for your PayPal account.", "wordpress-simple-paypal-shopping-cart");
					echo '&nbsp;' . '<a href="' . $ppcp_documentation_link . '" target="_blank">' . __('Read this documentation', 'wordpress-simple-paypal-shopping-cart') . '</a> ' . __('to learn how to manually set up the API credentials.', 'wordpress-simple-paypal-shopping-cart');
					echo '</p>';
					?>

					<form action="" method="POST">
						<table class="form-table">
							<tr valign="top">
								<th scope="row">
									<label>
										<?php _e('Live Client ID', 'wordpress-simple-paypal-shopping-cart'); ?>
									</label>
								</th>
								<td>
									<input type="text" name="paypal-live-client-id" size="100" value="<?php echo $paypal_live_client_id; ?>">
									<p class="description">
										<?php _e('Enter your PayPal Client ID for live mode.', 'wordpress-simple-paypal-shopping-cart'); ?>
									</p>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row">
									<label>
										<?php _e('Live Secret Key', 'wordpress-simple-paypal-shopping-cart'); ?>
									</label>
								</th>
								<td>
									<input type="text" name="paypal-live-secret-key" size="100" value="<?php echo $paypal_live_secret_key; ?>">
									<p class="description">
										<?php _e('Enter your PayPal Secret Key for live mode.', 'wordpress-simple-paypal-shopping-cart'); ?>
									</p>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row">
									<label>
										<?php _e('Sandbox Client ID', 'wordpress-simple-paypal-shopping-cart'); ?>
									</label>
								</th>
								<td>
									<input type="text" name="paypal-sandbox-client-id" size="100" value="<?php echo $paypal_sandbox_client_id; ?>">
									<p class="description">
										<?php _e('Enter your PayPal Client ID for sandbox mode.', 'wordpress-simple-paypal-shopping-cart'); ?>
									</p>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row">
									<label>
										<?php _e('Sandbox Secret Key', 'wordpress-simple-paypal-shopping-cart'); ?>
									</label>
								</th>
								<td>
									<input type="text" name="paypal-sandbox-secret-key" size="100" value="<?php echo $paypal_sandbox_secret_key; ?>">
									<p class="description">
										<?php _e('Enter your PayPal Secret Key for sandbox mode.', 'wordpress-simple-paypal-shopping-cart'); ?>
									</p>
								</td>
							</tr>
						</table>
						<input type="submit" name="wpsc_ppcp_api_credentials_submit" class="button-primary" value="<?php _e('Save Changes', 'wordpress-simple-paypal-shopping-cart'); ?>" />
						<?php wp_nonce_field('wpsc_ppcp_api_credentials_submit_nonce'); ?>
					</form>
				</div>
		</div>
		<?php
	}

	/**
	 * Render the new paypal checkout button appearance subtab
	 */
	public function handle_ppcp_button_appearance_subtab()
	{
	?>
		<div class="postbox">
			<h3 class="hndle"><label for="title"><?php _e('PayPal Button Appearance Settings', 'wordpress-simple-paypal-shopping-cart'); ?></label></h3>
			<div class="inside">
				<p>
					<?php _e('Configure the button appearance for the PayPal Commerce Platform checkout option. The default options are optimized for a quick start.', 'wordpress-simple-paypal-shopping-cart'); ?>
				</p>

				<form id="ppcp_button_config_form" method="post">
					<table class="form-table" width="100%" border="0" cellspacing="0" cellpadding="6">
						<tr valign="top">
							<th scope="row"><?php _e("Button Type/Label", 'wordpress-simple-paypal-shopping-cart'); ?></th>
							<td>
								<select name="ppcp_btn_type" style="min-width: 150px;">
									<option value="checkout" <?php echo ($this->settings->get_value('ppcp_btn_type') === 'checkout') ? ' selected' : ''; ?>><?php _e("Checkout", 'wordpress-simple-paypal-shopping-cart'); ?></option>
									<option value="pay" <?php echo ($this->settings->get_value('ppcp_btn_type') === 'pay') ? ' selected' : ''; ?>><?php _e("Pay", 'wordpress-simple-paypal-shopping-cart'); ?></option>
									<option value="paypal" <?php echo ($this->settings->get_value('ppcp_btn_type') === 'paypal') ? ' selected' : ''; ?>><?php _e("PayPal", 'wordpress-simple-paypal-shopping-cart'); ?></option>
									<option value="buynow" <?php echo ($this->settings->get_value('ppcp_btn_type') === 'buynow') ? ' selected' : ''; ?>><?php _e("Buy Now", 'wordpress-simple-paypal-shopping-cart'); ?></option>
									<option value="subscribe" <?php echo ($this->settings->get_value('ppcp_btn_type') === 'subscribe') ? ' selected' : ''; ?>><?php _e("Subscribe", 'wordpress-simple-paypal-shopping-cart'); ?></option>
								</select>
								<p class="description"><?php _e("Select button type/label.", 'wordpress-simple-paypal-shopping-cart'); ?></p>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e("Button Shape", 'wordpress-simple-paypal-shopping-cart'); ?></th>
							<td>
								<p><label><input type="radio" name="ppcp_btn_shape" value="rect" <?php echo ($this->settings->get_value('ppcp_btn_shape') === 'rect' || empty($bt_opts['ppcp_btn_shape'])) ? ' checked' : ''; ?>> <?php _e("Rectangular", 'wordpress-simple-paypal-shopping-cart'); ?></label></p>
								<p><label><input type="radio" name="ppcp_btn_shape" value="pill" <?php echo ($this->settings->get_value('ppcp_btn_shape') === 'pill') ? ' checked' : ''; ?>> <?php _e("Pill", 'wordpress-simple-paypal-shopping-cart'); ?></label></p>
								<p class="description"><?php _e("Select button shape.", 'wordpress-simple-paypal-shopping-cart'); ?></p>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e("Button Layout", 'wordpress-simple-paypal-shopping-cart'); ?></th>
							<td>
								<p><label><input type="radio" name="ppcp_btn_layout" value="vertical" <?php echo ($this->settings->get_value('ppcp_btn_layout') === 'vertical' || empty($bt_opts['ppcp_btn_layout'])) ? ' checked' : ''; ?>> <?php _e("Vertical", 'wordpress-simple-paypal-shopping-cart'); ?></label></p>
								<p><label><input type="radio" name="ppcp_btn_layout" value="horizontal" <?php echo ($this->settings->get_value('ppcp_btn_layout') === 'horizontal') ? ' checked' : ''; ?>> <?php _e("Horizontal", 'wordpress-simple-paypal-shopping-cart'); ?></label></p>
								<p class="description"><?php _e("Select button layout.", 'wordpress-simple-paypal-shopping-cart'); ?></p>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e("Button Height", 'wordpress-simple-paypal-shopping-cart'); ?></th>
							<td>
								<select name="ppcp_btn_height" style="min-width: 150px;">
									<option value="small" <?php echo ($this->settings->get_value('ppcp_btn_height') === 'small') ? ' selected' : ''; ?>><?php _e("Small", 'wordpress-simple-paypal-shopping-cart'); ?></option>
									<option value="medium" <?php echo ($this->settings->get_value('ppcp_btn_height') === 'medium') ? ' selected' : ''; ?>><?php _e("Medium", 'wordpress-simple-paypal-shopping-cart'); ?></option>
									<option value="large" <?php echo ($this->settings->get_value('ppcp_btn_height') === 'large') ? ' selected' : ''; ?>><?php _e("Large", 'wordpress-simple-paypal-shopping-cart'); ?></option>
									<option value="extra-large" <?php echo ($this->settings->get_value('ppcp_btn_height') === 'extra-large') ? ' selected' : ''; ?>><?php _e("Extra Large", 'wordpress-simple-paypal-shopping-cart'); ?></option>
								</select>
								<p class="description"><?php _e("Select button height.", 'wordpress-simple-paypal-shopping-cart'); ?></p>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e('Button Width', 'wordpress-simple-paypal-shopping-cart'); ?></th>
							<td>
								<input type="number" step="1" min="0" size="10" name="ppcp_btn_width" value="<?php echo ($this->settings->get_value('ppcp_btn_width') !== '') ? esc_attr($this->settings->get_value('ppcp_btn_width')) : 250; ?>" style="min-width: 150px;" />
								<p class="description"><?php _e("Select button width.", 'wordpress-simple-paypal-shopping-cart'); ?></p>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e("Button Color", 'wordpress-simple-paypal-shopping-cart'); ?></th>
							<td>
								<select name="ppcp_btn_color" style="min-width: 150px;">
									<option value="gold"<?php echo ($this->settings->get_value('ppcp_btn_color') === 'gold') ? ' selected' : ''; ?>><?php _e("Gold", 'wordpress-simple-paypal-shopping-cart'); ?></option>
									<option value="blue"<?php echo ($this->settings->get_value('ppcp_btn_color') === 'blue') ? ' selected' : ''; ?>><?php _e("Blue", 'wordpress-simple-paypal-shopping-cart'); ?></option>
									<option value="silver"<?php echo ($this->settings->get_value('ppcp_btn_color') === 'silver') ? ' selected' : ''; ?>><?php _e("Silver", 'wordpress-simple-paypal-shopping-cart'); ?></option>
									<option value="white"<?php echo ($this->settings->get_value('ppcp_btn_color') === 'white') ? ' selected' : ''; ?>><?php _e("White", 'wordpress-simple-paypal-shopping-cart'); ?></option>
									<option value="black"<?php echo ($this->settings->get_value('ppcp_btn_color') === 'black') ? ' selected' : ''; ?>><?php _e("Black", 'wordpress-simple-paypal-shopping-cart'); ?></option>
								</select>
								<p class="description"><?php _e("Select button color.", 'wordpress-simple-paypal-shopping-cart'); ?></p>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e("Disable Funding", 'wordpress-simple-paypal-shopping-cart'); ?></th>
							<td>
								<p><label><input type="checkbox" name="ppcp_disable_funding_card" value="1" <?php echo (!empty($this->settings->get_value('ppcp_disable_funding_card'))) ? ' checked' : ''; ?>> <?php _e("Credit or debit cards", 'wordpress-simple-paypal-shopping-cart'); ?></label></p>
								<p><label><input type="checkbox" name="ppcp_disable_funding_credit" value="1" <?php echo (!empty($this->settings->get_value('ppcp_disable_funding_credit'))) ? ' checked' : ''; ?>> <?php _e("PayPal Credit", 'wordpress-simple-paypal-shopping-cart'); ?></label></p>
								<p><label><input type="checkbox" name="ppcp_disable_funding_venmo" value="1" <?php echo (!empty($this->settings->get_value('ppcp_disable_funding_venmo'))) ? ' checked' : ''; ?>> <?php _e("Venmo", 'wordpress-simple-paypal-shopping-cart'); ?></label></p>
								<p class="description"><?php _e("By default, funding source eligibility is smartly decided based on a variety of factors. You can force disable funding options by selecting them here.", 'wordpress-simple-paypal-shopping-cart'); ?></p>
							</td>
						</tr>
					</table>

					<?php wp_nonce_field('wpsc_button_appearance_submit_nonce', 'wpsc_button_appearance_submit_nonce') ?>
					<input type="submit" name="wpsc_button_appearance_submit" class="button-primary" value="<?php _e('Save Changes', 'wordpress-simple-paypal-shopping-cart'); ?>">
				</form>
			</div>
		</div>
	<?php
	}

	/**
	 * TODO:
	 *
	 * FIXME: Need to handle unused variables.
	 * 
	 * @return void
	 */
	public function handle_paypal_ppcp_connection_settings()
	{
		$ppcp_onboarding_instance = PayPal_PPCP_Onboarding::get_instance();

		//If all API credentials are missing, show a message.
		$all_api_creds_missing = false;
		if ( empty($this->settings->get_value('paypal-sandbox-client-id')) && 
			empty($this->settings->get_value('paypal-sandbox-secret-key')) && 
			empty($this->settings->get_value('paypal-live-client-id')) && 
			empty($this->settings->get_value('paypal-live-secret-key')) ) {
			$all_api_creds_missing = true;
		}

		echo '<p class="description">';
		$ppcp_documentation_link = "https://www.tipsandtricks-hq.com/ecommerce/paypal-ppcp-setup-and-configuration-5023";
		_e("Use the buttons below to connect and obtain the necessary PayPal API credentials automatically to offer the PayPal Commerce Platform checkout option.", "wordpress-simple-paypal-shopping-cart");
		echo '&nbsp;' . '<a href="' . $ppcp_documentation_link . '" target="_blank">' . __('Read this documentation', 'wordpress-simple-paypal-shopping-cart') . '</a> ' . __('to learn how to set up and configure it.', 'wordpress-simple-paypal-shopping-cart');
		echo '</p>';
		?>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><?php _e("Live Account Connnection Status", "wordpress-simple-paypal-shopping-cart"); ?></th>
					<td>
						<?php
						// Check if the live account is connected
						$live_account_connection_status = 'connected';
						if (empty($this->settings->get_value('paypal-live-client-id')) || empty($this->settings->get_value('paypal-live-secret-key'))) {
							//Sandbox API keys are missing. Account is not connected.
							$live_account_connection_status = 'not-connected';
						}

						if ($live_account_connection_status == 'connected') {
							//Production account connected
							echo '<div class="wpsc-paypal-live-account-status"><span class="dashicons dashicons-yes" style="color:green;"></span>&nbsp;';
							_e("Live account is connected. If you experience any issues, please disconnect and reconnect.", "wordpress-simple-paypal-shopping-cart");
							echo '</div>';
							// Show disconnect option for live account.
							$ppcp_onboarding_instance->output_production_ac_disconnect_link();
						} else {
							//Production account is NOT connected.
							echo '<div class="wpsc-paypal-live-account-status"><span class="dashicons dashicons-no" style="color: red;"></span>&nbsp;';
							_e("Live PayPal account is not connected. Click the button below to authorize the app and acquire API credentials from your PayPal account.", "wordpress-simple-paypal-shopping-cart");
							echo '</div>';

							// Show the onboarding link
							$ppcp_onboarding_instance->output_production_onboarding_link_code();
						}
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e("Sandbox Account Connnection Status", "wordpress-simple-paypal-shopping-cart"); ?></th>
					<td>
						<?php
						//Check if the sandbox account is connected
						$sandbox_account_connection_status = 'connected';
						if (empty($this->settings->get_value('paypal-sandbox-client-id')) || empty($this->settings->get_value('paypal-sandbox-secret-key'))) {
							//Sandbox API keys are missing. Account is not connected.
							$sandbox_account_connection_status = 'not-connected';
						}

						if ($sandbox_account_connection_status == 'connected') {
							//Test account connected
							echo '<div class="wpsc-paypal-sandbox-account-status"><span class="dashicons dashicons-yes" style="color:green;"></span>&nbsp;';
							_e("Sandbox account is connected. If you experience any issues, please disconnect and reconnect.", "wordpress-simple-paypal-shopping-cart");
							echo '</div>';
							//Show disconnect option for sandbox account.
							$ppcp_onboarding_instance->output_sandbox_ac_disconnect_link();
						} else {
							//Sandbox account is NOT connected.
							echo '<div class="wpsc-paypal-sandbox-account-status"><span class="dashicons dashicons-no" style="color: red;"></span>&nbsp;';
							_e("Sandbox PayPal account is not connected.", "wordpress-simple-paypal-shopping-cart");
							echo '</div>';

							//Show the onboarding link for sandbox account.
							$ppcp_onboarding_instance->output_sandbox_onboarding_link_code();
						}
						?>
					</td>
				</tr>

			</tbody>
		</table>
<?php
	}
}
