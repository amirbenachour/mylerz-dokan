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
                                window.location.href = data.file_url; // Forces download
                            } else {
                                alert("Error: " + data.msg);
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

    // Collect all order items in description
    $description = "";
    foreach ($order->get_items() as $item_id => $item) {
        $product = $item->get_product();
        $product_name = $item->get_name();
        $quantity = $item->get_quantity();

        // Add item details to description
        $description .= "Title: {$product_name} \n Quantity: {$quantity} \n";

        // Store item details
        $order_data['items'][] = [
            'item_id'    => $item_id,
            'product_id' => $product ? $product->get_id() : null,
            'name'       => $product_name,
            'quantity'   => $quantity,
            'subtotal'   => $item->get_subtotal(),
            'total'      => $item->get_total(),
        ];
    }
    // $description= "";
    // $length = count($order_data['items']);
    // for ($i = 0; $i++; $i <= $length) {
    //     $description =$description +" Title: {$order_data['items'][$i]['name']} \n Quantity: {$order_data['items'][$i]['quantity']} \n";
    // }

    $data_to_send = [
        [
            "PickupDueDate" => date("Y-m-d\TH:i:s"),
            "Package_Serial" => $order_id,
            "Reference" => $order->get_order_number(),
            "Description" => $description,
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
    $barcode = dokan_mylerz_on_order_placed($order_id, $data_to_send);
    $response = mylerz_generate_awb($order_id, $barcode);

    wp_send_json(json_decode($response, true));
}


add_action('wp_ajax_get_wc_order_details', 'get_wc_order_details');


add_action('dokan_order_detail_after_order_items', 'dokan_mylerz_add_button_to_order_details', 10, 1);

function dokan_mylerz_add_button_to_order_details($order)
{
    $order_id = $order->get_id();
    ?>
    <div class="dokan-mylerz-button-wrap" style="margin-top: 15px;">
        <a href="<?php echo esc_url(admin_url('admin-ajax.php?action=get_wc_order_details&order_id=' . $order_id)); ?>"
            class="dokan-btn dokan-btn-theme"
            id="dokan-mylerz-send-order-btn">
            Send to Mylerz
        </a>
    </div>

    <script>
        jQuery(document).ready(function($) {
            $('#dokan-mylerz-send-order-btn').on('click', function(e) {
                e.preventDefault();
                var button = $(this);
                button.text('Sending...').prop('disabled', true);

                $.ajax({
                    url: button.attr('href'),
                    type: 'GET',
                    success: function(response) {
                        button.text('Sent').prop('disabled', true);

                        window.location.href = response.file_url; // Forces download

                    },
                    error: function() {
                        alert('Failed to send order.');
                        button.text('Send to Mylerz').prop('disabled', false);
                    }
                });
            });
        });
    </script>
    <?php
}

/**
 * Add a button to the Dokan vendor page to get vendor info.
 */
/*
function mylerz_print_screen_id_to_console() {
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log("Current Screen ID: <?php echo get_current_screen()->id; ?>");
        });
    </script>
    <?php
}
add_action('admin_footer', 'mylerz_print_screen_id_to_console');
*/

function add_test_button_to_vendors()
{
    $screen = get_current_screen();

    if ($screen && $screen->id === 'toplevel_page_dokan') {
    ?>
        <script type="text/javascript">
            document.addEventListener("DOMContentLoaded", function() {
                var button = document.createElement("a");
                button.href = "#";
                button.id = "fetch_vendor_details";
                button.className = "button button-primary";
                button.textContent = "Get Vendor Details";
                button.style.marginLeft = "10px";

                var actionContainer = document.querySelector(".dokan-vendor-single");
                if (actionContainer) {
                    actionContainer.appendChild(button);
                }

                button.addEventListener("click", function(event) {
                    event.preventDefault();

                    const url = new URL(window.location.href);
                    const vendorId = url.hash.split("/").pop();

                    if (!vendorId) {
                        alert("Vendor ID not found!");
                        return;
                    }

                    fetch(ajaxurl, {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/x-www-form-urlencoded",
                            },
                            body: new URLSearchParams({
                                action: "get_vendor_details",
                                vendor_id: vendorId,
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            console.log(data);

                        })
                        .catch(error => console.error("Error:", error));
                });
            });
        </script>
<?php
    }
}
add_action('admin_footer', 'add_test_button_to_vendors');

function get_vendor_details_callback()
{
    if (!isset($_POST['vendor_id'])) {
        wp_send_json_error("Vendor ID is missing");
    }

    $vendor_id = intval($_POST['vendor_id']);
    $vendor = dokan()->vendor->get($vendor_id);

    if (!$vendor) {
        wp_send_json_error("Vendor not found");
    }

    $shop_info = $vendor->get_shop_info();


    $address = '';
    if (isset($shop_info['address']) && is_array($shop_info['address'])) {
        $address_parts = [];
        if (!empty($shop_info['address']['street_1'])) {
            $address_parts[] = $shop_info['address']['street_1'];
        }
        if (!empty($shop_info['address']['street_2'])) {
            $address_parts[] = $shop_info['address']['street_2'];
        }
        if (!empty($shop_info['address']['city'])) {
            $address_parts[] = $shop_info['address']['city'];
        }
        if (!empty($shop_info['address']['state'])) {
            $state = $shop_info['address']['state'];
        }
        if (!empty($shop_info['address']['zip'])) {
            $zip = $shop_info['address']['zip'];
        }
        $address = implode(', ', $address_parts);
    }

    $phone = isset($shop_info['phone']) ? $shop_info['phone'] : '';

    $vendor_details =  [[
        'Name'        => $vendor->get_shop_name(),
        'ContactEmail' => $vendor->get_email(),
        'Address'     => $address,
        'ZoneCode'       => "TUN",
        'Zip'         => $zip,
        'PhoneNumber'       => $phone,
        "ContactName" => $vendor->get_shop_name(),
    ]];

    $response = mylerz_send_vendor($vendor_details);

    wp_send_json_success($response);
}

add_action('wp_ajax_get_vendor_details', 'get_vendor_details_callback');
