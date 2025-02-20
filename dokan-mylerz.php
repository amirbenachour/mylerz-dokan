<?php

/**
 * Plugin Name: Dokan Mylerz
 * Plugin URI: https://yourwebsite.com
 * Description: Integrates WooCommerce with Mylerz API for order shipping and AWB PDF generation.
 * Version: 1.0
 * Author: amir
 * Author URI: https://yourwebsite.com
 * License: GPL2
 */

require "includes/order-handler.php";
require "includes/api-functions.php";



if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

// Define Constants
define('DOKAN_MYLERZ_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DOKAN_MYLERZ_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include Dependencies
require_once DOKAN_MYLERZ_PLUGIN_DIR . 'includes/api-functions.php';
require_once DOKAN_MYLERZ_PLUGIN_DIR . 'includes/order-handler.php';
require_once DOKAN_MYLERZ_PLUGIN_DIR . 'includes/pdf-generator.php';

// Activate the plugin
function dokan_mylerz_activate()
{
    // Code to run on activation (like database setup)
}
register_activation_hook(__FILE__, 'dokan_mylerz_activate');

// Deactivate the plugin
function dokan_mylerz_deactivate()
{
    // Code to clean up on deactivation
}
register_deactivation_hook(__FILE__, 'dokan_mylerz_deactivate');

function add_test_button_to_wc_orders()
{
    $screen = get_current_screen();

    if ($screen && $screen->id === 'woocommerce_page_wc-orders') { // Target WooCommerce Orders List Page
?>
        <script type="text/javascript">
            document.addEventListener("DOMContentLoaded", function() {
                var button = document.createElement("a");
                button.href = "#";
                button.id = "fetch_order_details";
                button.className = "button button-primary";
                button.textContent = "Get Order Details";
                button.style.marginLeft = "10px";

                var actionContainer = document.querySelector(".wp-heading-inline");
                if (actionContainer) {
                    actionContainer.appendChild(button);
                }

                button.addEventListener("click", function(event) {
                    event.preventDefault();

                    const urlParams = new URLSearchParams(window.location.search);
                    const orderId = urlParams.get('id');

                    if (!orderId) {
                        alert("Order ID not found!");
                        return;
                    }

                    fetch("<?php echo admin_url('admin-ajax.php'); ?>?action=get_wc_order_details&order_id=" + orderId)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                let pdfUrl = data.data;
                                window.open(pdfUrl, "_blank");
                            } else {
                                alert("Error: " + data.data);
                            }
                        })
                        .catch(error => console.error("Error fetching order details:", error));
                });
            });
        </script>
<?php
    }
}
add_action('admin_footer', 'add_test_button_to_wc_orders');
function get_wc_order_details()
{

    if (!isset($_GET['order_id'])) {
        wp_send_json_error("Missing order ID.");
    }

    $order_id = intval($_GET['order_id']);
    $order = wc_get_order($order_id);

    if (!$order) {
        wp_send_json_error("Invalid order ID.");
    }

    $order_data = [
        'order_id'      => $order->get_id(),
        'status'        => $order->get_status(),
        'date_created'  => $order->get_date_created()->date('Y-m-d H:i:s'),
        'total'         => $order->get_total(),
        'currency'      => $order->get_currency(),
        'payment_method' => $order->get_payment_method_title(),

        'billing'  => [
            'first_name' => $order->get_billing_first_name(),
            'last_name'  => $order->get_billing_last_name(),
            'company'    => $order->get_billing_company(),
            'address_1'  => $order->get_billing_address_1(),
            'address_2'  => $order->get_billing_address_2(),
            'city'       => $order->get_billing_city(),
            'state'      => $order->get_billing_state(),
            'postcode'   => $order->get_billing_postcode(),
            'country'    => $order->get_billing_country(),
            'email'      => $order->get_billing_email(),
            'phone'      => $order->get_billing_phone(),
        ],

        'shipping' => [
            'first_name' => $order->get_shipping_first_name(),
            'last_name'  => $order->get_shipping_last_name(),
            'company'    => $order->get_shipping_company(),
            'address_1'  => $order->get_shipping_address_1(),
            'address_2'  => $order->get_shipping_address_2(),
            'city'       => $order->get_shipping_city(),
            'state'      => $order->get_shipping_state(),
            'postcode'   => $order->get_shipping_postcode(),
            'country'    => $order->get_shipping_country(),
        ],

        'items' => [],
    ];

    foreach ($order->get_items() as $item_id => $item) {
        $product = $item->get_product();
        $order_data['items'][] = [
            'item_id'    => $item_id,
            'product_id' => $product ? $product->get_id() : null,
            'name'       => $item->get_name(),
            'quantity'   => $item->get_quantity(),
            'subtotal'   => $item->get_subtotal(),
            'total'      => $item->get_total(),
        ];
    }
    $data_to_send = [
        [
            "PickupDueDate" => date("Y-m-d\TH:i:s"),
            "Package_Serial" => $order_id,
            "Reference" => $order->get_order_number(),
            "Description" => "Title: {$order_data['items'][0]['name']} \n Quantity: {$order_data['items'][0]['quantity']}",
            "Total_Weight" => 1,
            "Service_Type" => "DTD",
            "Service" => "SD",
            "Service_Category" => "Delivery",
            "Payment_Type" => "COD",
            "COD_Value" => $order->get_total(),
            "Customer_Name" => "{$order->get_billing_first_name()} {$order->get_billing_last_name()}",
            "Mobile_No" => $order->get_billing_phone(),
            "Street" => $order->get_billing_address_1(),
            "Country" => "Tunisia",
            "Neighborhood" => "TUN",
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
    // $data = json_encode($order_data);
    $response = dokan_mylerz_on_order_placed($order_id, $data_to_send);
    $response = mylerz_generate_awb($order_id);

    wp_send_json_success($response);
}


add_action('wp_ajax_get_wc_order_details', 'get_wc_order_details');


// function get_wc_order_details()
// {
//     if (!isset($_GET['order_id'])) {
//         wp_send_json_error("Missing order ID.");
//     }

//     $order_id = intval($_GET['order_id']);
//     $order = wc_get_order($order_id);

//     if (!$order) {
//         wp_send_json_error("Invalid order ID.");
//     }

//     $order_data = [
//         'order_id'      => $order->get_id(),
//         'status'        => $order->get_status(),
//         'date_created'  => $order->get_date_created()->date('Y-m-d H:i:s'),
//         'total'         => $order->get_total(),
//         'currency'      => $order->get_currency(),
//         'payment_method' => $order->get_payment_method_title(),

//         'billing'  => [
//             'first_name' => $order->get_billing_first_name(),
//             'last_name'  => $order->get_billing_last_name(),
//             'company'    => $order->get_billing_company(),
//             'address_1'  => $order->get_billing_address_1(),
//             'address_2'  => $order->get_billing_address_2(),
//             'city'       => $order->get_billing_city(),
//             'state'      => $order->get_billing_state(),
//             'postcode'   => $order->get_billing_postcode(),
//             'country'    => $order->get_billing_country(),
//             'email'      => $order->get_billing_email(),
//             'phone'      => $order->get_billing_phone(),
//         ],

//         'shipping' => [
//             'first_name' => $order->get_shipping_first_name(),
//             'last_name'  => $order->get_shipping_last_name(),
//             'company'    => $order->get_shipping_company(),
//             'address_1'  => $order->get_shipping_address_1(),
//             'address_2'  => $order->get_shipping_address_2(),
//             'city'       => $order->get_shipping_city(),
//             'state'      => $order->get_shipping_state(),
//             'postcode'   => $order->get_shipping_postcode(),
//             'country'    => $order->get_shipping_country(),
//         ],

//         'items' => [],
//     ];

//     // Loop through order items and add to array
//     foreach ($order->get_items() as $item_id => $item) {
//         $product = $item->get_product();
//         $order_data['items'][] = [
//             'item_id'    => $item_id,
//             'product_id' => $product ? $product->get_id() : null,
//             'name'       => $item->get_name(),
//             'quantity'   => $item->get_quantity(),
//             'subtotal'   => $item->get_subtotal(),
//             'total'      => $item->get_total(),
//         ];
//     }
//     $data_to_send = [
//         [
//             "PickupDueDate" => date("Y-m-d\TH:i:s"),
//             "Package_Serial" => $order_id,
//             "Reference" => $order->get_order_number(),
//             "Description" => "Title: {$order_data['items']['name']} \n Quantity: {$order_data['items']['quantity']}",
//             "Total_Weight" => 1,
//             "Service_Type" => "DTD",
//             "Service" => "SD",
//             "Service_Category" => "Delivery",
//             "Payment_Type" => "COD",
//             "COD_Value" => $order->get_total(),
//             "Customer_Name" => $order->get_billing_first_name() + "  " + $order->get_billing_last_name(),
//             "Mobile_No" => $order->get_billing_phone(),
//             "Street" => $order->get_billing_address_1(),
//             "Country" => "Tunisia",
//             "Neighborhood" => "TUN",
//             "Pieces" => [
//                 [
//                     "PieceNo" => 1,
//                     "Weight" => "1",
//                     "ItemCategory" => "General",
//                     "Dimensions" => "20*30*40",
//                     "Special_Notes" => "Handle with care"
//                 ]
//             ]
//         ]
//     ];
//     // wp_send_json_success($data_to_send);
//     wp_send_json_success($order_data);
//     return $order_data;
// }
