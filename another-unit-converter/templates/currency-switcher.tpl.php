<script id="aucp-currency-switcher-template" type="template">
<div class="aucp-currency-switcher">
    <div class="aucp-currency-switcher-title"><?php _e( 'Choose currency', 'another-unit-converter' ); ?></div>
    <div class="aucp-currency-switcher-separator"></div>
    <div class="aucp-currency-switcher-search-field-container">
        <input class="aucp-currency-switcher-search-field" type="text" placeholder="<?php esc_attr_e( 'Currency code or name', 'another-unit-converter' ); ?>" />
    </div>
    <ul class="aucp-currency-switcher-currencies-list">
        <?php foreach ( $currencies as $currency ): ?>
        <li class="aucp-currency-switcher-currencies-list-item" data-content="<?php echo strtolower( $currency['code'] . '|' . $currency['symbol'] . '|' . $currency['name'] ); ?>" data-code="<?php echo $currency['code']; ?>">
            <div>
                <span class="aucp-currency-switcher-currencies-list-item-name"><?php echo $currency['code']; ?></span>
                <span class="aucp-currency-switcher-currencies-list-item-name"><?php echo $currency['name']; ?></span>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
</script>
