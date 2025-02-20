<?php
if (!defined('ABSPATH')) {
    exit;
}

// Hook into WooCommerce order completion
add_action('woocommerce_order_status_processing', 'dokan_mylerz_on_order_placed');

function dokan_mylerz_on_order_placed($order_id, $data)
{
    $response = mylerz_send_order($data);
    if (!$response) return;

    // Store the tracking number in order meta
    if (update_post_meta($order_id, '_mylerz_tracking_number', $response['Value']['Packages'][0]['BarCode'])) {
        $msg = array('status' => 'success' , 'barcode' => $response['Value']['Packages'][0]['BarCode']) ;
        return json_encode($msg);
    } else {

        $msg = array('status' => 'failed', 'msg' => 'error adding barcode into the order');

        return json_encode($msg);
    }
}
