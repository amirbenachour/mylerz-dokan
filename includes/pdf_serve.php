<?php

// Load WordPress core
require_once( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php' );

// Get the order ID
if (!isset($_GET['order_id'])) {
    die("Missing order ID.");
}

$order_id = intval($_GET['order_id']);
$pdf_path = plugin_dir_path(__FILE__) . "pdfs/2025/02/20/awb-" . $order_id . ".pdf";

if (!file_exists($pdf_path)) {
    die("File not found.");
}

// Serve the PDF with proper headers
header("Content-Type: application/pdf");
header("Content-Disposition: inline; filename=awb-" . $order_id . ".pdf");
readfile($pdf_path);
exit;

?>