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
    private $currency_conversion;
    private $currencies;
    private $resources;

    public function __construct( $currency_parser, $currency_conversion, $currencies, $resources ) {
        $this->currency_parser = $currency_parser;
        $this->currency_conversion = $currency_conversion;
        $this->currencies = $currencies;
        $this->resources = $resources;
    }

    public function plugins_loaded() {
        add_action( 'init', array( $this, 'init' ) );

        add_action( 'wp_enqueue_scripts', array( $this->resources, 'register_scripts_and_styles' ) );

        if ( ! defined( 'DOING_AJAX' ) && ! is_admin() ) {
            $this->frontend_init();
        }

        add_action( 'wp_ajax_aucp_get_rates', array( $this, 'ajax_get_rates' ) );
        add_action( 'wp_ajax_nopriv_aucp_get_rates', array( $this,'ajax_get_rates' ) );
        add_action( 'wp_ajax_aucp_convert', array( $this, 'ajax_convert' ) );
        add_action( 'wp_ajax_nopriv_aucp_convert', array( $this,'ajax_convert' ) );
        add_action( 'wp_ajax_aucp_batch_convert', array( $this, 'ajax_batch_convert' ) );
        add_action( 'wp_ajax_nopriv_aucp_batch_convert', array( $this,'ajax_batch_convert' ) );
    }

    public function init() {
        add_filter( 'the_content', array( $this, 'format_currency_amounts' ) );
    }

    public function frontend_init() {
        add_action( 'wp_enqueue_scripts', array( $this->resources, 'enqueue_frontend_scripts_and_styles' ) );
        add_action( 'wp_footer', array( $this, 'maybe_print_currency_switcher_template' ) );
    }

    public function maybe_print_currency_switcher_template() {
        if ( $this->resources->are_frontend_scripts_enqueued() ) {
            $currencies = $this->currencies->get_currencies();
            include( __DIR__ . '/templates/currency-switcher.tpl.php' );
        }
    }

    public function format_currency_amounts( $content ) {
        $currency_amounts = $this->currency_parser->get_currency_amounts( $content );

        if ( ! $currency_amounts ) {
            return $content;
        }

        $offset = 0;

        foreach ( $currency_amounts as $index => $currency_amount ) {
            $currency_info = $currency_amount['currencies'][0];

            $start_position = $currency_info['position']['start'] + $offset;
            $end_position = $currency_info['position']['end'] + $offset;

            $amount_length = $end_position - $start_position;
            $amount_text = mb_substr( $content, $start_position, $amount_length );
            $amount = esc_attr( $currency_info['amount'] );
            $symbol = esc_attr( $currency_info['currency']['symbol'] );
            $code = esc_attr( $currency_info['currency']['code'] );

            $formatted_text = sprintf(
                '<span class="aucp-currency-amount" data-unit-converter-currency-amount="%1$s" data-unit-converter-currency-symbol="%2$s" data-unit-converter-currency-code="%3$s" data-unit-conveter-amount-text="%4$s">%5$s</span>',
                $amount,
                $symbol,
                $code,
                esc_attr( $amount_text ),
                esc_html( $amount_text )
            );

            $content = mb_substr( $content, 0, $start_position ) . $formatted_text . mb_substr( $content, $end_position );
            $offset = $offset + mb_strlen( $formatted_text ) - $amount_length;
        }

        wp_enqueue_script( 'another-unit-converter-frontend' );

        return $content;
    }

    public function ajax_get_rates() {
        $response = array( 'success' => false );
        $codes = array();

        foreach ( array( 'code', 'codes' ) as $id ) {
            if ( ! empty( $_REQUEST[ $id ] ) ) {
                foreach ( (array) $_REQUEST[ $id ] as $code ) {
                    $codes = array_merge( $codes, explode( ',', $code ) );
                }
            }
        }
        $codes = array_map( 'strtoupper', $codes );
        $all_rates = $this->currency_conversion->get_rates();

        $response['success'] = true;

        if ( ! $codes )
            $response['rates'] = $all_rates;
        else
            $response['rates'] = wp_array_slice_assoc( $all_rates, $codes );

        echo json_encode( $response );
        exit;
    }

    public function ajax_convert() {
        $response = array( 'success' => false );

        $from = ! empty( $_REQUEST['from'] ) ? trim( $_REQUEST['from'] ) : '';
        $to = ! empty( $_REQUEST['to'] ) ? trim( $_REQUEST['to'] ) : '';
        $amount = ! empty( $_REQUEST['amount'] ) ? floatval( $_REQUEST['amount'] ) : '';

        $conversion = $this->currency_conversion->convert( $from, $to, $amount );

        if ( false !== $conversion ) {
            $response['success'] = true;
            $response['from'] = $from;
            $response['to'] = $to;
            $response['amount'] = $amount;
            $response['result'] = $conversion;
            $response['rate'] = $this->currency_conversion->convert( $from, $to, 1.0 );
        }

        echo json_encode( $response );
        exit;
    }

    public function ajax_batch_convert() {
        $response = array( 'success' => false );

        if ( isset( $_REQUEST['items'] ) && is_array( $_REQUEST['items'] ) ) {
            $response['success'] = true;
            $response['results'] = array();

            foreach ( $_REQUEST['items'] as $item ) {
                $from = ! empty( $item['from'] ) ? strtoupper( $item['from'] ) : '';
                $to = ! empty( $item['to'] ) ? strtoupper( $item['to'] ) : '';
                $amount = isset( $item['amount'] ) ? $item['amount'] : '';

                $result_item = array(
                    'from'    => $from,
                    'to'      => $to,
                    'amount'  => $amount,
                    'success' => false
                );

                if ( $result = $this->currency_conversion->convert( $from, $to, $amount ) ) {
                    $result_item['success'] = true;
                    $result_item['result'] = $result;
                    $result_item['rate'] = $this->currency_conversion->convert( $from, $to, 1.0 );
                }

                $response[] = $result_item;
            }
        }

        echo json_encode( $response );
        exit;
    }
}

function aucp_load_another_unit_converter_plugin() {
    $currencies = new AUCP_Currencies();

    $plugin = new Another_Unit_Converter_Plugin(
        new AUCP_Currency_Parser( $currencies ),
        new AUCP_Currency_Conversion(),
        $currencies,
        new AUCP_Resources( plugin_dir_url( __FILE__ ) )
    );

    $plugin->plugins_loaded();
}
add_action( 'plugins_loaded', 'aucp_load_another_unit_converter_plugin' );
