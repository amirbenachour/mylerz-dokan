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
function mylerz_send_order($order_id) {
    $token = mylerz_get_token();
    if (!$token) return false;

    $order = wc_get_order($order_id);
    if (!$order) return false;

    // Prepare data for Mylerz
    $order_data = [
        [
            "WarehouseName" => "Maadi",
            "PickupDueDate" => date("Y-m-d\TH:i:s"),
            "Package_Serial" => $order_id,
            "Reference" => $order->get_order_number(),
            "Description" => "WooCommerce Order #$order_id",
            "Total_Weight" => 1,
            "Service_Type" => "DTD",
            "Service" => "SD",
            "ServiceDate" => date("Y-m-d\TH:i:s"),
            "Service_Category" => "Delivery",
            "Payment_Type" => "COD",
            "COD_Value" => $order->get_total(),
            "Customer_Name" => $order->get_billing_first_name(),
            "Mobile_No" => $order->get_billing_phone(),
            "Building_No" => "5",
            "Street" => $order->get_billing_address_1(),
            "Floor_No" => "2",
            "Apartment_No" => "4",
            "Country" => "Tunisia",
            "Neighborhood" => "TUN",
            "GeoLocation" => "20.2020,40.4040",
            "Pieces" => [
                [
                    "PieceNo" => 1,
                    "Weight" => "1",
                    "ItemCategory" => "General",
                    "Dimensions" => "20*30*40",
                    "Special_Notes" => "Handle with care"
                ]
            ]
        ]
    ];

    $url = "https://integration.tunisia.mylerz.net/api/Orders/AddOrders";
    $response = wp_remote_post($url, [
        'body' => json_encode($order_data),
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token
        ]
    ]);

    return json_decode(wp_remote_retrieve_body($response), true);
}
