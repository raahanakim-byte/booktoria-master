<?php
// debug_cart.php
include 'config.php';
session_start();

$user_id = $_SESSION['user_id'];
echo "<h3>Current Cart State for User: $user_id</h3>";

$result = $conn->query("SELECT name, quantity FROM cart WHERE user_id = '$user_id'");
echo "<table border='1'>";
echo "<tr><th>Product Name</th><th>Quantity</th></tr>";

while($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['name'] . "</td>";
    echo "<td>" . $row['quantity'] . "</td>";
    echo "</tr>";
}
echo "</table>";
?>