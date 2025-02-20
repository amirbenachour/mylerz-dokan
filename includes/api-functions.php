<?php
if (!defined('ABSPATH')) {
    exit;
}
 // git comment 
// Function to get the API token
function mylerz_get_token() {
    $url = "https://integration.tunisia.mylerz.net/token";
    $data = [
        "grant_type" => "password",
        "username" => "Tdiscount BLK",
        "password" => "Tdiscount2025@"
    ];

    $response = wp_remote_post($url, [
        'body' => http_build_query($data),
        'headers' => [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ]
    ]);

    if (is_wp_error($response)) {
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    return $body['access_token'] ?? false;
}

// Function to send orders to Mylerz
function mylerz_send_order($data) {
    $token = mylerz_get_token();
    if (!$token) return false;



    // Prepare data for Mylerz
    

    $url = "https://integration.tunisia.mylerz.net/api/Orders/AddOrders";
    $response = wp_remote_post($url, [
        'body' => json_encode($data),
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token
        ]
    ]);

    return json_decode(wp_remote_retrieve_body($response), true);
}

