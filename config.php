<?php
$conn = mysqli_connect('localhost', 'root', '', 'booktoria_db') or die('connection failed');

// Prevent duplicate declaration if config.php is loaded multiple times
if(!function_exists('checkStockBeforeAdd')) {

    function checkStockBeforeAdd($stock, $quantity = 1) {
        if ($stock <= 0) {
            return "❌ This item is out of stock!";
        }
        if ($quantity > $stock) {
            return "⚠ Only $stock item(s) available. Reduce quantity.";
        }
        return true;
    }

}
?>
