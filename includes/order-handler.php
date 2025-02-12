<?php
if (!defined('ABSPATH')) {
    exit;
}

// Hook into WooCommerce order completion
add_action('woocommerce_order_status_processing', 'dokan_mylerz_on_order_placed');

function dokan_mylerz_on_order_placed($order_id) {
    $response = mylerz_send_order($order_id);
    if (!$response) return;

    // Store the tracking number in order meta
    update_post_meta($order_id, '_mylerz_tracking_number', $response['Value']['Packages'][0]['Pieces'][0]['Barcode']);
}
