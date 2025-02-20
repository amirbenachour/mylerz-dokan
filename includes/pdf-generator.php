<?php
if (!defined('ABSPATH')) {
    exit;
}
// 
// Function to get AWB and save PDF
function mylerz_generate_awb($order_id)
{
    $token = mylerz_get_token();
  
    if (!$token) {
        $msg = array("success" => false, "status" => "failed", "msg" => "token unavailable");
        return json_encode($msg);
    }

    $barcode = get_post_meta($order_id, '_mylerz_tracking_number', true);
    if (!$barcode) {
        $msg = array("success" => false, "status" => "failed", "msg" => "barcode unavailable");
        return json_encode($msg);
    }

    $url = "https://integration.tunisia.mylerz.net/api/packages/GetAWB";
    $data = [
        "Barcode" => $barcode,
        "ReferenceNumber" => $order_id,
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
    $year  = date('Y'); // e.g., 2025
    $month = date('m'); // e.g., 02
    $day   = date('d'); // e.g., 20
    $uploadDir = wp_upload_dir(); 
    $baseDir = $uploadDir['basedir'] . '/facture/' . $year . '/' . $month . '/' . $day . '/';
    if (!file_exists($baseDir)) {
        wp_mkdir_p($baseDir); 
    }
    $filePath = $baseDir . 'awb-' . $order_id . '.pdf';
    file_put_contents($filePath, $pdfData);

    return $filePath;
}
