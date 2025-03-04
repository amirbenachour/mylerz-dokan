<?php
if (!defined('ABSPATH')) {
    exit;
}
// 
// Function to get AWB and save PDF
function mylerz_generate_awb($order_id, $barcode)
{
    $token = mylerz_get_token();

    if (!$token) {
        return json_encode(["success" => false, "status" => "failed", "msg" => "token unavailable"]);
    }

    // $barcode = get_post_meta($order_id, '_mylerz_tracking_number', true);
    // if (!$barcode) {
    //     return json_encode(["success" => false, "status" => "failed", "msg" => "barcode unavailable"]);
    // }

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
    
    if (!$body || !isset($body['Value'])) {
        return json_encode(["success" => false, "status" => "failed", "msg" => "Invalid API response"]);
    }

    $pdfData = base64_decode($body['Value']);
    $year  = date('Y');
    $month = date('m');
    $day   = date('d');
    $uploadDir = wp_upload_dir();
    
    $baseDir = $uploadDir['basedir'] . "/facture/$year/$month/$day/";
    $baseUrl = $uploadDir['baseurl'] . "/facture/$year/$month/$day/";
    
    if (!file_exists($baseDir)) {
        wp_mkdir_p($baseDir);
    }

    $fileName = "awb-$order_id.pdf";
    $filePath = $baseDir . $fileName;
    $fileUrl = $baseUrl . $fileName;

    file_put_contents($filePath, $pdfData);

    return json_encode(["success" => true, "file_url" => $fileUrl]);
}

