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

require __DIR__ . '/vendor/autoload.php';

class Another_Unit_Converter_Plugin {

    private $currency_parser;

    public function __construct( $currency_parser ) {
        $this->currency_parser = $currency_parser;
    }

    public function plugins_loaded() {
        add_action( 'init', array( $this, 'init' ) );
    }

    public function init() {
        add_filter( 'the_content', array( $this, 'extract_currency_amounts' ) );
    }

    public function extract_currency_amounts( $content ) {
        $filtered_content = strip_tags( $content );

        var_dump( $content, $filtered_content );

        $regexp = '/(*UTF8)([A-Z]{0,4}[^\w\d\s]?)(\d{4,}|\d{1,3}(?:[,.]\d{1,3})*)/u';

        if ( ! preg_match_all( $regexp, $filtered_content, $matches, PREG_OFFSET_CAPTURE ) ) {
            return $content;
        }

        $extracted_amounts = array();

        foreach ( $matches[0] as $index => $match ) {
            $amount_text = $match[0];
            $amount_symbol = $matches[1][ $index ][0];
            $amount_number = $matches[2][ $index ][0];

            try {
                $extracted_amounts[ $amount_text ] = $this->currency_parser->parse_amount(
                    $amount_text,
                    $amount_symbol,
                    $amount_number
                );
            } catch ( AUCP_Exception $e ) {
                continue;
            }
        }

        var_dump( $extracted_amounts );

        return $content;
    }
}

function aucp_load_another_unit_converter_plugin() {
    $plugin = new Another_Unit_Converter_Plugin(
        new AUCP_Currency_Parser( new AUCP_Currencies() )
    );

    $plugin->plugins_loaded();
}
add_action( 'plugins_loaded', 'aucp_load_another_unit_converter_plugin' );
