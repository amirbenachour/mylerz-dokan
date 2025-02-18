<?php
if (!defined('ABSPATH')) {
    exit;
}
// 
// Function to get AWB and save PDF
function mylerz_generate_awb($order_id) {
    $token = mylerz_get_token();
    if (!$token) return false;

    $barcode = get_post_meta($order_id, '_mylerz_tracking_number', true);
    if (!$barcode) return false;

    $url = "https://integration.tunisia.mylerz.net/api/packages/GetAWB";
    $data = [
        "Barcode" => $barcode,
        "ReferenceNumber" => $order_id,
        "ParentSubId" => 042
    ];

    $response = wp_remote_post($url, [
        'body' => json_encode($data),
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token
        ]
    ]);

    $body = json_decode(wp_remote_retrieve_body($response), true);
    $pdfData = base64_decode($body['Value']);
    $filePath = DOKAN_MYLERZ_PLUGIN_DIR . 'awb-' . $order_id . '.pdf';
    file_put_contents($filePath, $pdfData);

    return $filePath;
}
