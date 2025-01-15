<?php
/**
 * Plugin Name: WooCommerce reCAPTCHA Checkout
 * Description: Adds Google reCAPTCHA to WooCommerce checkout page.
 * Version: 1.0
 * Author: NihalCY
 */

// Enqueue reCAPTCHA script
function wcr_enqueue_recaptcha() {
    wp_enqueue_script('google-recaptcha', 'https://www.google.com/recaptcha/api.js', array(), null, true);
}
add_action('wp_enqueue_scripts', 'wcr_enqueue_recaptcha');

// Display reCAPTCHA at checkout
function wcr_show_recaptcha() {
    $site_key = esc_attr(get_option('wcr_recaptcha_site_key'));
    if ($site_key) {
        echo '<div class="g-recaptcha" data-sitekey="' . $site_key . '"></div>';
    }
}
add_action('woocommerce_checkout_order_review', 'wcr_show_recaptcha', 10);

// Validate reCAPTCHA response
function wcr_validate_recaptcha() {
    if (isset($_POST['g-recaptcha-response'])) {
        $response = $_POST['g-recaptcha-response'];
        $remote_ip = $_SERVER['REMOTE_ADDR'];
        $secret_key = get_option('wcr_recaptcha_secret_key');
        
        $response_keys = json_decode(file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secret_key}&response={$response}&remoteip={$remote_ip}"), true);
        
        if (intval($response_keys["success"]) !== 1) {
            wc_add_notice(__('Please complete the reCAPTCHA verification.', 'woocommerce'), 'error');
        }
    } else {
        wc_add_notice(__('Please complete the reCAPTCHA verification.', 'woocommerce'), 'error');
    }
}
add_action('woocommerce_checkout_process', 'wcr_validate_recaptcha');

// Create settings menu
function wcr_create_settings_menu() {
    add_options_page('reCAPTCHA Settings', 'reCAPTCHA Settings', 'manage_options', 'wcr-recaptcha-settings', 'wcr_settings_page');
}
add_action('admin_menu', 'wcr_create_settings_menu');

// Register settings
function wcr_register_settings() {
    register_setting('wcr-settings-group', 'wcr_recaptcha_site_key');
    register_setting('wcr-settings-group', 'wcr_recaptcha_secret_key');
}
add_action('admin_init', 'wcr_register_settings');

// Settings page HTML
function wcr_settings_page() {
    $secret_key = get_option('wcr_recaptcha_secret_key');
    $masked_key = '';

    // Mask the secret key if it exists
    if (!empty($secret_key)) {
        $masked_key = substr($secret_key, 0, 4) . str_repeat('*', strlen($secret_key) - 4);
    }

    ?>
    <div class="wrap">
        <h1>WooCommerce reCAPTCHA Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('wcr-settings-group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">reCAPTCHA Site Key</th>
                    <td>
                        <input type="text" name="wcr_recaptcha_site_key" 
                               value="<?php echo esc_attr(get_option('wcr_recaptcha_site_key')); ?>" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">reCAPTCHA Secret Key</th>
                    <td>
                        <?php if (!empty($secret_key)) : ?>
                            <input type="text" name="wcr_recaptcha_secret_key" 
                                   value="" placeholder="<?php echo esc_attr($masked_key); ?>" />
                            <p class="description">
                                The secret key is partially hidden for security reasons. Enter a new key to update it.
                            </p>
                        <?php else : ?>
                            <input type="text" name="wcr_recaptcha_secret_key" 
                                   value="" placeholder="Enter your Secret Key" />
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
