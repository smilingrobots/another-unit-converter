<?php

/**
 * Plugin Name: Another Unit Converter
 * Plugin URI:  http://anotherunitconverter.com
 * Description: A universal unit converter for WordPress content.
 * Version:     0.1-dev-1
 * Author:      Smiling Robots
 * Author URI:  http://smilingrobots.com
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path: /languages
 * Text Domain: another-unit-converter
*/

class Another_Unit_Converter_Plugin {

    public function plugins_loaded() {
        add_action( 'init', array( $this, 'init' ) );
    }

    public function init() {
    }
}

function aucp_load_another_unit_converter_plugin() {
    $plugin = new Another_Unit_Converter_Plugin();
    $plugin->plugins_loaded();
}
add_action( 'plugins_loaded', 'aucp_load_another_unit_converter_plugin' );
