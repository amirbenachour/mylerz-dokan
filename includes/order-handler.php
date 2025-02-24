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

add_action('wp_ajax_dokan_mylerz_send_order', 'dokan_mylerz_send_order');
add_action('wp_ajax_nopriv_dokan_mylerz_send_order', 'dokan_mylerz_send_order');

function dokan_mylerz_send_order() {
    if (!isset($_GET['order_id'])) {
        wp_send_json_error(['message' => 'Invalid order ID']);
    }

    $order_id = intval($_GET['order_id']);
    $order = wc_get_order($order_id);

    if (!$order) {
        wp_send_json_error(['message' => 'Order not found']);
    }

    // Call your API function to send order data
    $response = mylerz_send_order($order);

    if ($response['success']) {
        wp_send_json_success(['message' => 'Order sent successfully']);
    } else {
        wp_send_json_error(['message' => 'Error sending order']);
    }
}
