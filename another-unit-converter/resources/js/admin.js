jQuery(function($) {
    $( '#aucp-api-key-notice' ).on( 'click', '.notice-dismiss', function() {
        $.post( ajaxurl, { action: 'aucp_dismissed_api_key_notice' } );
    });
});
