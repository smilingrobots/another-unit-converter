<?php

namespace AUCP\Tests;

use AUCP\Tests\TestCase;

use AUCP_Currencies;

class CurrenciesTest extends TestCase {

    public function test_find_currencies_by_symbol() {
        $currencies = new AUCP_Currencies();

        $currencies_found = $currencies->find_currencies_by_symbol( '$' );
        $currency_codes = array();

        foreach( $currencies_found as $currency ) {
            $currency_codes[] = $currency['code'];
        }

        $this->assertContains( 'USD', $currency_codes );
        $this->assertContains( 'AUD', $currency_codes );
        $this->assertContains( 'COP', $currency_codes );
    }

    public function test_find_currencies_by_symbol_returns_usd_first() {
        $currencies = new AUCP_Currencies();

        $currencies_found = $currencies->find_currencies_by_symbol( '$' );

        $this->assertEquals( 'USD', $currencies_found[0]['code'] );
    }

    public function test_find_currencies_by_symbol_returns_gbp_first() {
        $currencies = new AUCP_Currencies();

        $currencies_found = $currencies->find_currencies_by_symbol( '£' );

        $this->assertEquals( 'GBP', $currencies_found[0]['code'] );
    }
}
