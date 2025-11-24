<?php
include 'config.php';
session_start();

$user_id = $_SESSION['user_id'] ?? null;
if(!$user_id){
    die('User not logged in');
}

// Fetch cart items
$cart_query = mysqli_query($conn, "SELECT * FROM cart WHERE user_id='$user_id'");
$cart_items = [];
$grand_total = 0;
while($item = mysqli_fetch_assoc($cart_query)){
    $item['sub_total'] = $item['price'] * $item['quantity'];
    $grand_total += $item['sub_total'];
    $cart_items[] = $item;
}

if(empty($cart_items)){
    die('Cart is empty');
}

// -----------------------------
// 🔵 CASH ON DELIVERY
// -----------------------------
if(isset($_POST['cod'])){
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);

    $order_items = [];
    foreach($cart_items as $ci){
        $order_items[] = $ci;
    }

    $json_items = mysqli_real_escape_string($conn, json_encode($order_items));
    $placed_on = date('Y-m-d H:i:s');

    mysqli_query($conn, "INSERT INTO orders 
        (user_id,name,number,email,method,address,order_items,total_price,placed_on,payment_status)
        VALUES ('$user_id','$name','$phone','$email','Cash on Delivery','$address','$json_items','$grand_total','$placed_on','pending')
    ");

    mysqli_query($conn,"DELETE FROM cart WHERE user_id='$user_id'");
    header('Location: orders.php');
    exit;
}


// -----------------------------
// 🔵 KHALTI PAYMENT
// -----------------------------
if (isset($_POST['khalti'])) {

    // Validate
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);

    if (!$name || !$email || !$phone || !$address) {
        die("Missing required fields");
    }

    // Calculate total thrift items again
    $total_thrift_items = 0;
    foreach ($cart_items as $ci) {
        if ($ci['type'] === 'thrift') {
            $total_thrift_items += $ci['quantity'];
        }
    }

    // JSON encode cart
    $order_items_json = mysqli_real_escape_string($conn, json_encode($cart_items));

    // Generate local order_id (used only for Khalti)
    $purchase_order_id = 'order_' . time();
    $placed_on = date('Y-m-d H:i:s');

    // STEP 1: Save order to DB with unpaid status
    $insert = mysqli_query($conn, "
        INSERT INTO orders
        (user_id, name, number, email, method, address,
         order_items, total_price, total_thrift_items,
         placed_on, payment_status)
        VALUES (
            '$user_id',
            '$name',
            '$phone',
            '$email',
            'Khalti',
            '$address',
            '$order_items_json',
            '$grand_total',
            '$total_thrift_items',
            '$placed_on',
            'khalti_initiated'
        )
    ");

    if (!$insert) {
        die('Order insert failed: ' . mysqli_error($conn));
    }

    // GET the inserted order ID
    $local_order_id = mysqli_insert_id($conn);

    // Convert Rs → Paisa
    $amount_paisa = $grand_total * 100;

    // Create Khalti payment payload
    $payload = [
        "return_url" => "http://localhost:8080/booktoria-master/verify_khalti.php?oid={$local_order_id}",
        "website_url" => "http://localhost:8080/booktoria-master/",
        "amount" => $amount_paisa,
        "purchase_order_id" => $purchase_order_id,
        "purchase_order_name" => "Bookstore Order",
        "customer_info" => [
            "name" => $name,
            "email" => $email,
            "phone" => $phone
        ]
    ];

    $payload_json = json_encode($payload);

    // Load secret (replace with getenv)
    $live_secret_key = "5e5f4dfa7f4c4e10b3f660f0f8ecd297";

    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => "https://a.khalti.com/api/v2/epayment/initiate/",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload_json,
        CURLOPT_HTTPHEADER => [
            "Authorization: Key $live_secret_key",
            "Content-Type: application/json"
        ]
    ]);

    $response = curl_exec($curl);
    curl_close($curl);

    $res = json_decode($response, true);

    if (isset($res['payment_url'])) {
        header("Location: " . $res['payment_url']);
        exit;
    } else {
        echo "<h3>Khalti Payment Error</h3>";
        echo "<pre>";
        print_r($res);
        echo "</pre>";
        exit;
    }
}

?>
