<?php
/**
 * Plugin Name: Another Unit Converter
 * Plugin URI:  http://smilingrobots.com/plugins/another-unit-converter
 * Description: The easiest way to do currency conversions in your website, allowing visitors to see amounts on their preferred currency.
 * Version:     1.1.1
 * Author:      Smiling Robots
 * Author URI:  http://smilingrobots.com
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: another-unit-converter
 * Domain Path: /languages
 */

if ( ! function_exists( 'mb_strcut' ) ) {
    add_action( 'admin_init', 'aucp_add_notice_and_deactivate_plugin' );

    function aucp_add_notice_and_deactivate_plugin() {
        add_action( 'admin_notices', 'aucp_missing_required_mbstring_extension' );
        deactivate_plugins( plugin_basename( __FILE__ ) );
    }

    function aucp_missing_required_mbstring_extension() {
        $message = esc_html__( 'The {plugin-name} plugin requires the {multibyte-link}Multibyte String{/multibyte-link} PHP extension to work. Please ask your web host to install or enable it for your website.', 'another-unit-converter' );

        $message = str_replace( '{plugin-name}', '<strong>Another Unit Converter</strong>', $message );
        $message = str_replace( '{multibyte-link}', '<a href="http://php.net/manual/en/book.mbstring.php">', $message );
        $message = str_replace( '{/multibyte-link}', '</a>', $message );

        echo '<div class="aucp-notice notice notice-error"><p>' . $message . '</p></div>';
    }

    return;
}

require_once( __DIR__ . '/includes/class-another-unit-converter-plugin.php' );
require_once( __DIR__ . '/includes/class-admin.php' );
require_once( __DIR__ . '/includes/class-convert-currency-shortcode.php' );
require_once( __DIR__ . '/includes/class-currencies.php' );
require_once( __DIR__ . '/includes/class-currency-conversion.php' );
require_once( __DIR__ . '/includes/class-currency-parser.php' );
require_once( __DIR__ . '/includes/class-resources.php' );
require_once( __DIR__ . '/includes/class-settings.php' );

function AUCP() {
    return Another_Unit_Converter_Plugin::instance();
}

// Get things going.
AUCP();
