<?php

function get_wc_order_pdf() {
    if (!isset($_GET['order_id'])) {
        wp_send_json_error("Missing order ID.");
    }

    $order_id = intval($_GET['order_id']);
    
    // Generate the PDF URL using plugins_url()
    $pdf_url = plugins_url("pdfs/2025/02/20/awb-" . $order_id . ".pdf", __FILE__);

    // Check if the file exists before returning the URL
    $pdf_path = plugin_dir_path(__FILE__) . "pdfs/2025/02/20/awb-" . $order_id . ".pdf";

    if (!file_exists($pdf_path)) {
        wp_send_json_error("PDF not found.");
    }

    wp_send_json_success($pdf_url);
}
add_action('wp_ajax_get_wc_order_details', 'get_wc_order_pdf');
add_action('wp_ajax_nopriv_get_wc_order_details', 'get_wc_order_pdf'); // Allow non-logged-in users if needed


?>