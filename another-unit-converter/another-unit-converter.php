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
        add_filter( 'the_content', array( $this, 'format_currency_amounts' ) );
    }

    public function format_currency_amounts( $content ) {
        $currency_amounts = $this->find_currency_amounts( $content );

        if ( ! $currency_amounts ) {
            return $content;
        }

        foreach ( $currency_amounts as $amount_text => $formatted_text ) {
            $content = str_replace( $amount_text, $formatted_text, $content );
        }

        return $content;
    }

    private function find_currency_amounts( $content ) {
        $currency_amounts = array();

        $regexp = '/(*UTF8)([A-Z]{0,4}[^\w\d\s]?)(\d{4,}|\d{1,3}(?:[,.]\d{1,3})*)/u';

        if ( ! preg_match_all( $regexp, strip_tags( $content ), $matches, PREG_OFFSET_CAPTURE ) ) {
            return $currency_amounts;
        }

        foreach ( $matches[0] as $index => $match ) {
            $amount_text = $match[0];
            $amount_symbol = $matches[1][ $index ][0];
            $amount_number = $matches[2][ $index ][0];

            try {
                $extracted_amount = $this->currency_parser->parse_amount(
                    $amount_text,
                    $amount_symbol,
                    $amount_number
                );

                // TODO: Why the try-catch if we are returning null on failure?
                if ( is_null( $extracted_amount ) ) {
                    continue;
                }

                $formatted_text = sprintf(
                    '<span data-unit-converter-currency-amount="%s" data-unit-converter-currency-symbol="%s" data-unit-converter-currency-code="%s">%s</span>',
                    esc_attr( $extracted_amount['amount'] ),
                    esc_attr( $extracted_amount['symbol'] ),
                    esc_attr( $extracted_amount['code'] ),
                    $amount_text
                );

                $currency_amounts[ $amount_text ] = $formatted_text;
            } catch ( AUCP_Exception $e ) {
                continue;
            }
        }

        return $currency_amounts;
    }
}

function aucp_load_another_unit_converter_plugin() {
    $plugin = new Another_Unit_Converter_Plugin(
        new AUCP_Currency_Parser( new AUCP_Currencies() )
    );

    $plugin->plugins_loaded();
}
add_action( 'plugins_loaded', 'aucp_load_another_unit_converter_plugin' );
