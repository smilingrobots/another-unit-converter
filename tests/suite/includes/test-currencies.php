<?php

class AUCP_Test_Currencies extends AUCP_Test_Case {

    public function test_find_currencies_by_symbol() {
        $currencies = new AUCP_Currencies();

        $currencies_found = $currencies->find_currencies_by_symbol( '$' );
        $currency_codes = wp_list_pluck( $currencies_found, 'code' );

        $this->assertContains( 'USD', $currency_codes );
        $this->assertContains( 'AUD', $currency_codes );
        $this->assertContains( 'COP', $currency_codes );
    }
}
