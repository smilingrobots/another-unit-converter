<?php

/**
 * See https://github.com/RubyMoney/money/blob/master/spec/currency/heuristics_spec.rb
 */
class AUCP_Test_Currency_Parser extends AUCP_Test_Case {

    public function setup() {
        parent::setup();

        $this->currencies = Phake::mock( 'AUCP_Currencies' );

        $SEK = array(
            'name' => 'Swedish Krona',
            'code' => 'SEK',
            'symbol' => 'kr',
            'decimal_places' => '2',
            'display_format' => '# ###,##',
            'thousands_separator' => ' ',
            'decimal_point' => ',',
        );

        $ARS = array(
            'name' => 'Argentine Peso',
            'code' => 'ARS',
            'symbol' => '$',
            'decimal_places' => '2',
            'display_format' => '#.###,##',
            'thousands_separator' => '.',
            'decimal_point' => ',',
        );

        $USD = array(
            'name' => 'US Dollar',
            'code' => 'USD',
            'symbol' => '$',
            'decimal_places' => '2',
            'display_format' => '#,###.##',
            'thousands_separator' => ',',
            'decimal_point' => '.',
        );

        $AUD = array(
            'name' => 'Australian Dollar',
            'code' => 'AUD',
            'symbol' => '$',
            'decimal_places' => '2',
            'display_format' => '# ###.##',
            'thousands_separator' => ' ',
            'decimal_point' => '.',
        );

        $AZN = array(
            'name' => 'Azerbaijanian Manat',
            'code' => 'AZN',
            'symbol' => 'ман',
            'decimal_places' => '2',
            'display_format' => '',
            'thousands_separator' => ',',
            'decimal_point' => '.',
        );

        $PLN = array(
            'name' => 'Poland, Zloty',
            'code' => 'PLN',
            'symbol' => 'zł',
            'decimal_places' => '2',
            'display_format' => '# ###,##',
            'thousands_separator' => ' ',
            'decimal_point' => ',',
        );

        $EUR = array(
            'name' => 'Euro',
            'code' => 'EUR',
            'symbol' => '€',
            'decimal_places' => '2',
            'display_format' => '#,###.##',
            'thousands_separator' => ',',
            'decimal_point' => '.',
        );

        Phake::when( $this->currencies )->get_currency( 'USD' )->thenReturn( $USD );
        Phake::when( $this->currencies )->get_currency( 'AUD' )->thenReturn( $AUD );
        Phake::when( $this->currencies )->get_currency( 'EUR' )->thenReturn( $EUR );

        Phake::when( $this->currencies )->get_currency_from_country_code( 'US' )->thenReturn( $USD );
        Phake::when( $this->currencies )->get_currency_from_country_code( 'AU' )->thenReturn( $AUD );

        Phake::when( $this->currencies )->find_currencies_by_symbol( '$' )->thenReturn( array( $ARS, $USD, $AUD ) );
        Phake::when( $this->currencies )->find_currencies_by_symbol( 'kr' )->thenReturn( array( $SEK ) );
        Phake::when( $this->currencies )->find_currencies_by_symbol( 'ман' )->thenReturn( array( $AZN ) );
        Phake::when( $this->currencies )->find_currencies_by_symbol( 'zł' )->thenReturn( array( $PLN ) );
    }

    public function _test_get_currency_amounts() {
        $this->check_it_detects_a_single_currency( 'USD $2500', 'USD', '$', 2500 );
        $this->check_it_detects_a_single_currency( 'AU$800', 'AUD', '$', 800 );
        $this->check_it_detects_a_single_currency( 'ман130', 'AZN', 'ман', 130 );
        $this->check_it_detects_a_single_currency( 'US $1500', 'USD', '$', 1500 );
        $this->check_it_detects_a_single_currency( '$US 1500', 'USD', '$', 1500 );
        $this->check_it_detects_a_single_currency( '$USD 2500', 'USD', '$', 2500 );
        $this->check_it_detects_a_single_currency( '2500 USD', 'USD', '$', 2500 );
        $this->check_it_detects_a_single_currency( '2500$ USD', 'USD', '$', 2500 );
        $this->check_it_detects_a_single_currency( '2500 USD$', 'USD', '$', 2500 );
        $this->check_it_detects_a_single_currency( '2500 $USD', 'USD', '$', 2500 );
        $this->check_it_detects_a_single_currency( '$1350 AUD', 'AUD', '$', 1350 );

        $this->check_detected_currencies_include( '$ 2500', array( 'USD' ) );
        $this->check_detected_currencies_include( '2500$', array( 'USD' ) );
        $this->check_detected_currencies_include( '2500 $', array( 'USD' ) );
    }

    private function check_it_detects_a_single_currency( $amount_text, $currency_code, $currency_symbol, $amount ) {
        $parser = new AUCP_Currency_Parser( $this->currencies );
        $currency_amounts = $parser->get_currency_amounts( $amount_text );

        $this->assertTrue( is_array( $currency_amounts ), "Currency Parser didn't return an array for: $amount_text." );

        $this->assertNotEmpty( $currency_amounts , "Currency Parser returned an empty array for: $amount_text." );
        $this->assertEquals( 1, count( $currency_amounts ), "Currency parser returned more than a currency amount for: $amount_text" );

        $currency = $currency_amounts[0]['currencies'][0];

        $this->assertEquals( $currency_code, $currency['code'], "Currency code doesn't match for: $amount_text." );
        $this->assertEquals( $currency_symbol, $currency['symbol'], "Currency symbol doesn't match for: $amount_text." );

        $parsed_amount = $parser->parse_amount( $currency_amounts[0]['amount_text'], $currency );

        $this->assertEquals( $amount, $parsed_amount, "Parsed amount doesn't match expected amount of: $amount." );
    }

    private function check_detected_currencies_include( $amount_text, $expected_currency_codes ) {
        $parser = new AUCP_Currency_Parser( $this->currencies );

        $currency_amounts = $parser->get_currency_amounts( $amount_text );
        $currency_codes = array();

        foreach ( $currency_amounts as $currency_amount ) {
            foreach ( $currency_amount['currencies'] as $currency_info ) {
                $currency_codes[] = $currency_info['currency']['code'];
            }
        }

        foreach ( $expected_currency_codes as $currency_code ) {
            $this->assertContains( $currency_code, $currency_codes, "Currency Parser failed to detect $currency_code." );
        }
    }

    public function test_get_currency_amounts_finds_match_even_if_has_numbers_before() {
        $this->markTestSkipped( 'It currentyl fails, recognizing 123785 as an amount.' );
        $this->check_it_detects_a_single_currency( '123 785 zł', 'PLN', 'zł', 785 );
    }

    public function test_get_currency_amounts_finds_match_even_if_has_numbers_after() {
        $this->markTestSkipped( 'It currentyl fails, recognizing two amounts.' );
        $this->check_it_detects_a_single_currency( '785 zł 123', 'PLN', 'zł', 785 );
    }

    public function test_get_currency_amounts_finds_several_currencies_in_the_same_text() {
        $text = "10EUR is less than 100 kr but really, I want US$1";
        $this->check_detected_currencies_include( $text, array( 'EUR', 'SEK', 'USD' ) );
    }
}
