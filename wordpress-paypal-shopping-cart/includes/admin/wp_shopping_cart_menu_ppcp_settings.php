<?php

use TTHQ\WPSC\Lib\PayPal\PayPal_Bearer;
use TTHQ\WPSC\Lib\PayPal\PayPal_PPCP_Config;

require_once WP_CART_PATH . 'includes/admin/wp_shopping_cart_admin_utils.php';

class WPSC_PPCP_settings_page
{

	private $settings;

	private $ppcp_connection_subtab_url = "admin.php?page=wspsc-menu-main&action=ppcp-settings&subtab=ppcp-connection";
	private $ppcp_button_subtab_link = "admin.php?page=wspsc-menu-main&action=ppcp-settings&subtab=ppcp-button";

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
		echo '<div class="wspsc_yellow_box">';
		echo '<p>';
		_e('Configure the PayPal API credentials and checkout button appearance for the new PayPal checkout.', WP_CART_TEXT_DOMAIN);
		echo '&nbsp;' . '<a href="' . $ppcp_documentation_link . '" target="_blank">' . __('Read this documentation', WP_CART_TEXT_DOMAIN) . '</a> ' . __('to learn how to setup and configure it.', WP_CART_TEXT_DOMAIN);
		echo '</p>';
		echo '</div>';
		
		//Sub nav tabs related code.
		$subtab = isset($_GET['subtab']) ? sanitize_text_field($_GET['subtab']) : '';
		$selected_subtab = $subtab;
?>
		<!-- ppcp settings menu's sub nav tabs -->
		<h3 class="nav-tab-wrapper">
			<a class="nav-tab <?php echo ($subtab == '' || $subtab == 'ppcp-connection') ? 'nav-tab-active' : ''; ?>" href="<?php echo $this->ppcp_connection_subtab_url; ?>"><?php _e('PayPal API Connection', WP_CART_TEXT_DOMAIN); ?></a>
			<a class="nav-tab <?php echo ($subtab == 'ppcp-button') ? 'nav-tab-active' : ''; ?>" href="<?php echo $this->ppcp_button_subtab_link; ?>"><?php _e('Button Appearance', WP_CART_TEXT_DOMAIN); ?></a>
		</h3>
		<br />
		<?php

		// Handle api credentials form submit
		if (isset($_POST['wpsc_ppcp_checkout_settings_submit']) && check_admin_referer('wpsc_ppcp_checkout_settings_submit_nonce')) {
			$this->settings->set_value('ppcp_checkout_enable', (isset($_POST['ppcp_checkout_enable']) ? sanitize_text_field($_POST['ppcp_checkout_enable']) : ''));
	
			$this->settings->save();
			echo '<div class="notice notice-success"><p>' . __('PayPal PPCP checkout settings updated successfully.', WP_CART_TEXT_DOMAIN) . '</p></div>';
		}
		
		if (isset($_POST['wpsc_ppcp_api_credentials_submit']) && check_admin_referer('wpsc_ppcp_api_credentials_submit_nonce')) {
			$this->settings->set_value('paypal-live-client-id', (isset($_POST['paypal-live-client-id']) ? sanitize_text_field($_POST['paypal-live-client-id']) : ''));
			$this->settings->set_value('paypal-live-secret-key', (isset($_POST['paypal-live-secret-key']) ? sanitize_text_field($_POST['paypal-live-secret-key']) : ''));
			$this->settings->set_value('paypal-sandbox-client-id', (isset($_POST['paypal-sandbox-client-id']) ? sanitize_text_field($_POST['paypal-sandbox-client-id']) : ''));
			$this->settings->set_value('paypal-sandbox-secret-key', (isset($_POST['paypal-sandbox-secret-key']) ? sanitize_text_field($_POST['paypal-sandbox-secret-key']) : ''));

			$this->settings->save();
			echo '<div class="notice notice-success"><p>' . __('PayPal PPCP API settings updated successfully.', WP_CART_TEXT_DOMAIN) . '</p></div>';
		}

		// Handle PayPal PPCP button appearance.
		if (isset($_POST['wpsc_button_appearance_submit']) && check_admin_referer('wpsc_button_appearance_submit_nonce', 'wpsc_button_appearance_submit_nonce')) {
			$this->settings->set_value('ppcp_btn_type', (isset($_POST['ppcp_btn_type']) ? sanitize_text_field($_POST['ppcp_btn_type']) : ''));
			$this->settings->set_value('ppcp_btn_shape', (isset($_POST['ppcp_btn_shape']) ? sanitize_text_field($_POST['ppcp_btn_shape']) : ''));
			$this->settings->set_value('ppcp_btn_layout', (isset($_POST['ppcp_btn_layout']) ? sanitize_text_field($_POST['ppcp_btn_layout']) : ''));
			$this->settings->set_value('ppcp_btn_height', (isset($_POST['ppcp_btn_height']) ? sanitize_text_field($_POST['ppcp_btn_height']) : ''));
			$this->settings->set_value('ppcp_btn_width', (isset($_POST['ppcp_btn_width']) ? sanitize_text_field($_POST['ppcp_btn_width']) : 300));
			$this->settings->set_value('ppcp_btn_color', (isset($_POST['ppcp_btn_color']) ? sanitize_text_field($_POST['ppcp_btn_color']) : ''));

			// Funding disable related
			$this->settings->set_value('ppcp_disable_funding_card', (isset($_POST['ppcp_disable_funding_card']) ? sanitize_text_field($_POST['ppcp_disable_funding_card']) : ''));
			$this->settings->set_value('ppcp_disable_funding_credit', (isset($_POST['ppcp_disable_funding_credit']) ? sanitize_text_field($_POST['ppcp_disable_funding_credit']) : ''));
			$this->settings->set_value('ppcp_disable_funding_venmo', (isset($_POST['ppcp_disable_funding_venmo']) ? sanitize_text_field($_POST['ppcp_disable_funding_venmo']) : ''));

			$this->settings->save();
			echo '<div class="notice notice-success"><p>' . __('PayPal PPCP button appearance settings updated successfully.', WP_CART_TEXT_DOMAIN) . '</p></div>';
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
			echo '<div class="notice notice-success"><p>' . __('PayPal PPCP access token cache deleted successfully.', WP_CART_TEXT_DOMAIN) . '</p></div>';
		}

		//Switch case for the various different sub-tabs.
		switch ($selected_subtab) {
			case 'ppcp-button':
				$this->handle_ppcp_button_appearance_subtab();
				break;
			default: // 'ppcp-connection'
				$this->handle_ppcp_connection_settings_subtab();
				break;
		}

		//End of the payment settings menu tab.
		do_action('wpsc_ppcp_settings_menu_tab_end');

		wpspsc_settings_menu_footer();
	}

	/**
	 * Render paypal ppcp settings subtab view
	 *
	 * @return void
	 */
	public function handle_ppcp_connection_settings_subtab()
	{
		// Paypal PPCP settings
		$paypal_live_client_id = $this->settings->get_value('paypal-live-client-id');
		$paypal_live_secret_key = $this->settings->get_value('paypal-live-secret-key');
		$paypal_sandbox_client_id = $this->settings->get_value('paypal-sandbox-client-id');
		$paypal_sandbox_secret_key = $this->settings->get_value('paypal-sandbox-secret-key');
		?>

		<div class="postbox">
			<h2 id="paypal-ppcp-checkout-enable-section"><?php _e("Enable PayPal PPCP (New API) Checkout", WP_CART_TEXT_DOMAIN); ?></h2>
			<div class="inside">
				<form action="" method="POST">
					<table class="form-table" role="presentation">
						<tbody>
							<tr>
								<th scope="row"><?php _e('Enable PayPal PPCP Checkout', WP_CART_TEXT_DOMAIN); ?></th>
								<td>
									<p>
										<label>
											<input type="checkbox" name="ppcp_checkout_enable" value="1" <?php echo (!empty($this->settings->get_value('ppcp_checkout_enable'))) ? ' checked' : ''; ?>>
										</label>
									</p>
									<p class="description">
										<?php _e("Enable this to use new paypal ppcp api during checkout.", WP_CART_TEXT_DOMAIN); ?>
									</p>
								</td>
							</tr>
						</tbody>
					</table>
					<input type="submit" name="wpsc_ppcp_checkout_settings_submit" class="button-primary" value="<?php _e('Save Changes', WP_CART_TEXT_DOMAIN); ?>" />
					<?php wp_nonce_field('wpsc_ppcp_checkout_settings_submit_nonce'); ?>
				</form>
			</div>
		</div>

		<!-- PayPal PPCP Connection Settings postbox -->
		<div class="postbox">
			<h2><?php _e("PayPal PPCP API Credentials", WP_CART_TEXT_DOMAIN); ?></h3>
				<div class="inside">
					<form action="" method="POST">
						<table class="form-table">
							<tr valign="top">
								<th scope="row">
									<label>
										<?php _e('Live Client ID', WP_CART_TEXT_DOMAIN); ?>
									</label>
								</th>
								<td>
									<input type="text" name="paypal-live-client-id" size="100" value="<?php echo $paypal_live_client_id; ?>">
									<p class="description">
										<?php _e('Enter your PayPal Client ID for live mode.', WP_CART_TEXT_DOMAIN); ?>
									</p>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row">
									<label>
										<?php _e('Live Secret Key', WP_CART_TEXT_DOMAIN); ?>
									</label>
								</th>
								<td>
									<input type="text" name="paypal-live-secret-key" size="100" value="<?php echo $paypal_live_secret_key; ?>">
									<p class="description">
										<?php _e('Enter your PayPal Secret Key for live mode.', WP_CART_TEXT_DOMAIN); ?>
									</p>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row">
									<label>
										<?php _e('Sandbox Client ID', WP_CART_TEXT_DOMAIN); ?>
									</label>
								</th>
								<td>
									<input type="text" name="paypal-sandbox-client-id" size="100" value="<?php echo $paypal_sandbox_client_id; ?>">
									<p class="description">
										<?php _e('Enter your PayPal Client ID for sandbox mode.', WP_CART_TEXT_DOMAIN); ?>
									</p>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row">
									<label>
										<?php _e('Sandbox Secret Key', WP_CART_TEXT_DOMAIN); ?>
									</label>
								</th>
								<td>
									<input type="text" name="paypal-sandbox-secret-key" size="100" value="<?php echo $paypal_sandbox_secret_key; ?>">
									<p class="description">
										<?php _e('Enter your PayPal Secret Key for sandbox mode.', WP_CART_TEXT_DOMAIN); ?>
									</p>
								</td>
							</tr>
						</table>
						<input type="submit" name="wpsc_ppcp_api_credentials_submit" class="button-primary" value="<?php _e('Save Changes', WP_CART_TEXT_DOMAIN); ?>" />
						<?php wp_nonce_field('wpsc_ppcp_api_credentials_submit_nonce'); ?>
					</form>
				</div>
		</div>

		<div class="postbox">
			<h2 id="paypal-delete-token-cache-section"><?php _e("Delete PayPal PPCP API Access Token Cache", WP_CART_TEXT_DOMAIN); ?></h2>
			<div class="inside">
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row"><?php _e('Delete PPCP Token Cache', WP_CART_TEXT_DOMAIN); ?></th>
							<td>
								<?php
								$delete_cache_url = admin_url($this->ppcp_connection_subtab_url);
								$delete_cache_url = add_query_arg('wpsc_ppcp_delete_cache', 1, $delete_cache_url);
								$delete_cache_url_nonced = add_query_arg('_wpnonce', wp_create_nonce('wpsc_ppcp_delete_cache'), $delete_cache_url);
								echo '<p><a class="button swpm-paypal-delete-cache-btn" href="' . esc_url_raw($delete_cache_url_nonced) . '">' . __('Delete Token Cache', WP_CART_TEXT_DOMAIN) . '</a></p>';
								echo '<p class="description">' . __('This will delete the PayPal API access token cache. This is useful if you are having issues with the PayPal API after changing/updating the API credentials.', WP_CART_TEXT_DOMAIN) . '</p>';
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
	 * Render paypal ppcp button appearance subtab view
	 *
	 * @return void
	 */
	public function handle_ppcp_button_appearance_subtab()
	{
	?>
		<div class="postbox">
			<h3 class="hndle"><label for="title"><?php _e('PayPal PPCP button appearance settings', WP_CART_TEXT_DOMAIN); ?></label></h3>
			<div class="inside">
				<p>
					<?php _e('Configure the button appearance for the PayPal PPCP checkout.', WP_CART_TEXT_DOMAIN); ?>
				</p>

				<form id="ppcp_button_config_form" method="post">
					<table class="form-table" width="100%" border="0" cellspacing="0" cellpadding="6">
						<tr valign="top">
							<th scope="row"><?php _e("Button Type/Label", WP_CART_TEXT_DOMAIN); ?></th>
							<td>
								<select name="ppcp_btn_type" style="min-width: 150px;">
									<option value="checkout" <?php echo ($this->settings->get_value('ppcp_btn_type') === 'checkout') ? ' selected' : ''; ?>><?php _e("Checkout", WP_CART_TEXT_DOMAIN); ?></option>
									<option value="pay" <?php echo ($this->settings->get_value('ppcp_btn_type') === 'pay') ? ' selected' : ''; ?>><?php _e("Pay", WP_CART_TEXT_DOMAIN); ?></option>
									<option value="paypal" <?php echo ($this->settings->get_value('ppcp_btn_type') === 'paypal') ? ' selected' : ''; ?>><?php _e("PayPal", WP_CART_TEXT_DOMAIN); ?></option>
									<option value="buynow" <?php echo ($this->settings->get_value('ppcp_btn_type') === 'buynow') ? ' selected' : ''; ?>><?php _e("Buy Now", WP_CART_TEXT_DOMAIN); ?></option>
									<option value="subscribe" <?php echo ($this->settings->get_value('ppcp_btn_type') === 'subscribe') ? ' selected' : ''; ?>><?php _e("Subscribe", WP_CART_TEXT_DOMAIN); ?></option>
								</select>
								<p class="description"><?php _e("Select button type/label.", WP_CART_TEXT_DOMAIN); ?></p>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e("Button Shape", WP_CART_TEXT_DOMAIN); ?></th>
							<td>
								<p><label><input type="radio" name="ppcp_btn_shape" value="rect" <?php echo ($this->settings->get_value('ppcp_btn_shape') === 'rect' || empty($bt_opts['ppcp_btn_shape'])) ? ' checked' : ''; ?>> <?php _e("Rectangular", WP_CART_TEXT_DOMAIN); ?></label></p>
								<p><label><input type="radio" name="ppcp_btn_shape" value="pill" <?php echo ($this->settings->get_value('ppcp_btn_shape') === 'pill') ? ' checked' : ''; ?>> <?php _e("Pill", WP_CART_TEXT_DOMAIN); ?></label></p>
								<p class="description"><?php _e("Select button shape.", WP_CART_TEXT_DOMAIN); ?></p>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e("Button Layout", WP_CART_TEXT_DOMAIN); ?></th>
							<td>
								<p><label><input type="radio" name="ppcp_btn_layout" value="vertical" <?php echo ($this->settings->get_value('ppcp_btn_layout') === 'vertical' || empty($bt_opts['ppcp_btn_layout'])) ? ' checked' : ''; ?>> <?php _e("Vertical", WP_CART_TEXT_DOMAIN); ?></label></p>
								<p><label><input type="radio" name="ppcp_btn_layout" value="horizontal" <?php echo ($this->settings->get_value('ppcp_btn_layout') === 'horizontal') ? ' checked' : ''; ?>> <?php _e("Horizontal", WP_CART_TEXT_DOMAIN); ?></label></p>
								<p class="description"><?php _e("Select button layout.", WP_CART_TEXT_DOMAIN); ?></p>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e("Button Height", WP_CART_TEXT_DOMAIN); ?></th>
							<td>
								<select name="ppcp_btn_height" style="min-width: 150px;">
									<option value="small" <?php echo ($this->settings->get_value('ppcp_btn_height') === 'small') ? ' selected' : ''; ?>><?php _e("Small", WP_CART_TEXT_DOMAIN); ?></option>
									<option value="medium" <?php echo ($this->settings->get_value('ppcp_btn_height') === 'medium') ? ' selected' : ''; ?>><?php _e("Medium", WP_CART_TEXT_DOMAIN); ?></option>
									<option value="large" <?php echo ($this->settings->get_value('ppcp_btn_height') === 'large') ? ' selected' : ''; ?>><?php _e("Large", WP_CART_TEXT_DOMAIN); ?></option>
									<option value="extra-large" <?php echo ($this->settings->get_value('ppcp_btn_height') === 'extra-large') ? ' selected' : ''; ?>><?php _e("Extra Large", WP_CART_TEXT_DOMAIN); ?></option>
								</select>
								<p class="description"><?php _e("Select button height.", WP_CART_TEXT_DOMAIN); ?></p>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e('Button Width', WP_CART_TEXT_DOMAIN); ?></th>
							<td>
								<input type="number" step="1" min="0" size="10" name="ppcp_btn_width" value="<?php echo ($this->settings->get_value('ppcp_btn_width') !== '') ? esc_attr($this->settings->get_value('ppcp_btn_width')) : 300; ?>" style="min-width: 150px;" />
								<p class="description"><?php _e("Select button width.", WP_CART_TEXT_DOMAIN); ?></p>
							</td>
						</tr>
						<tr valign="top">
                        <th scope="row"><?php _e("Button Color", WP_CART_TEXT_DOMAIN); ?></th>
                        <td>
                            <select name="ppcp_btn_color" style="min-width: 150px;">
                                <option value="gold"<?php echo ($this->settings->get_value('ppcp_btn_color') === 'gold') ? ' selected' : ''; ?>><?php _e("Gold", WP_CART_TEXT_DOMAIN); ?></option>
                                <option value="blue"<?php echo ($this->settings->get_value('ppcp_btn_color') === 'blue') ? ' selected' : ''; ?>><?php _e("Blue", WP_CART_TEXT_DOMAIN); ?></option>
                                <option value="silver"<?php echo ($this->settings->get_value('ppcp_btn_color') === 'silver') ? ' selected' : ''; ?>><?php _e("Silver", WP_CART_TEXT_DOMAIN); ?></option>
                                <option value="white"<?php echo ($this->settings->get_value('ppcp_btn_color') === 'white') ? ' selected' : ''; ?>><?php _e("White", WP_CART_TEXT_DOMAIN); ?></option>
                                <option value="black"<?php echo ($this->settings->get_value('ppcp_btn_color') === 'black') ? ' selected' : ''; ?>><?php _e("Black", WP_CART_TEXT_DOMAIN); ?></option>
                            </select>
                            <p class="description"><?php _e("Select button color.", WP_CART_TEXT_DOMAIN); ?></p>
                        </td>
                    </tr>
						<tr valign="top">
							<th scope="row"><?php _e("Disable Funding", WP_CART_TEXT_DOMAIN); ?></th>
							<td>
								<p><label><input type="checkbox" name="ppcp_disable_funding_card" value="1" <?php echo (!empty($this->settings->get_value('ppcp_disable_funding_card'))) ? ' checked' : ''; ?>> <?php _e("Credit or debit cards", WP_CART_TEXT_DOMAIN); ?></label></p>
								<p><label><input type="checkbox" name="ppcp_disable_funding_credit" value="1" <?php echo (!empty($this->settings->get_value('ppcp_disable_funding_credit'))) ? ' checked' : ''; ?>> <?php _e("PayPal Credit", WP_CART_TEXT_DOMAIN); ?></label></p>
								<p><label><input type="checkbox" name="ppcp_disable_funding_venmo" value="1" <?php echo (!empty($this->settings->get_value('ppcp_disable_funding_venmo'))) ? ' checked' : ''; ?>> <?php _e("Venmo", WP_CART_TEXT_DOMAIN); ?></label></p>
								<p class="description"><?php _e("By default, funding source eligibility is smartly decided based on a variety of factors. You can force disable funding options by selecting them here.", WP_CART_TEXT_DOMAIN); ?></p>
							</td>
						</tr>
					</table>

					<?php wp_nonce_field('wpsc_button_appearance_submit_nonce', 'wpsc_button_appearance_submit_nonce') ?>
					<input type="submit" name="wpsc_button_appearance_submit" class="button-primary" value="<?php _e('Save Changes', WP_CART_TEXT_DOMAIN); ?>">
				</form>
			</div>
		</div>
<?php
	}
}
