<?php
include 'config.php';
session_start();

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    die("User not logged in.");
}

// Check if local order ID is passed
if (!isset($_GET['oid']) || !isset($_GET['pidx'])) {
    die("Invalid request! Missing required parameters.");
}

$local_order_id = intval($_GET['oid']);
$pidx = $_GET['pidx'];  // Khalti Payment Identifier

// Fetch the order
$order = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT * FROM orders WHERE id='$local_order_id' AND user_id='$user_id'")
);

if (!$order) {
    die("Order not found.");
}

// ----------------------------
// 🔵 VERIFY WITH KHALTI SERVER
// ----------------------------
$verify_payload = json_encode([
    "pidx" => $pidx
]);

$live_secret_key = "5e5f4dfa7f4c4e10b3f660f0f8ecd297"; // Replace with environment variable

$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => "https://a.khalti.com/api/v2/epayment/lookup/",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $verify_payload,
    CURLOPT_HTTPHEADER => [
        "Authorization: Key $live_secret_key",
        "Content-Type: application/json"
    ]
]);

$response = curl_exec($curl);
curl_close($curl);

$res = json_decode($response, true);

// -------------------------------
// 🔵 HANDLE VERIFICATION RESPONSE
// -------------------------------
if (!$res || !isset($res['status'])) {
    die("Could not verify payment. Response: <pre>" . print_r($res, true) . "</pre>");
}

$status = $res['status'];

if ($status === "Completed") {

    // 🆕 1️⃣ DECREASE PRODUCT STOCK (ADD THIS SECTION)
    $order_items = json_decode($order['order_items'], true);
    
    if($order_items && count($order_items) > 0) {
        foreach($order_items as $item) {
            $product_name = mysqli_real_escape_string($conn, $item['name']);
            $quantity = intval($item['quantity']);
            
            // Update stock in products table
            mysqli_query($conn, "UPDATE products SET stock = stock - $quantity WHERE name = '$product_name'");
            
            // Optional: Log if stock goes negative (for debugging)
            $check_stock = mysqli_query($conn, "SELECT stock, name FROM products WHERE name = '$product_name'");
            if($stock_data = mysqli_fetch_assoc($check_stock)) {
                if($stock_data['stock'] < 0) {
                    error_log("⚠️ Negative stock for: " . $stock_data['name']);
                }
            }
        }
    }

    // 2️⃣ Update payment status
    mysqli_query($conn, "
        UPDATE orders 
        SET payment_status='completed'
        WHERE id='$local_order_id'
    ");

    // 3️⃣ Clear cart
    mysqli_query($conn, "DELETE FROM cart WHERE user_id='$user_id'");

    // 4️⃣ Set success message
    $_SESSION['success_message'] = "Payment successful! Your order has been confirmed.";

    // 5️⃣ Redirect to orders page
    header("Location: orders.php");
    exit;

} elseif ($status === "Pending") {

    echo "<h2>Payment Pending</h2>";
    echo "<p>Your payment is still processing. Please refresh after some time.</p>";
    exit;

} else {

    // Payment failed or cancelled
    mysqli_query($conn, "
        UPDATE orders 
        SET payment_status='failed'
        WHERE id='$local_order_id'
    ");

    echo "<h2>Payment Failed</h2>";
    echo "<p>Your transaction was not completed.</p>";
    echo "<pre>";
    print_r($res);
    echo "</pre>";
    exit;
}
?>
