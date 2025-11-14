<?php
include 'config.php';
session_start();
if(!isset($_SESSION['admin_id'])) header('location:login.php');

// Fetch dashboard stats
function getCount($conn, $table, $col='id') {
    $res = mysqli_query($conn,"SELECT COUNT(*) AS total FROM $table");
    $row = mysqli_fetch_assoc($res);
    return $row['total'];
}

function getTotal($conn, $status=null) {
    $query = "SELECT COUNT(*) AS total FROM orders";
    if($status) $query .= " WHERE payment_status='$status'";
    $res = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($res);
    return $row['total'] ?? 0;
}

// Prepare stock data for pie chart
$stock_labels = [];
$stock_data = [];

$res = mysqli_query($conn, "SELECT name, stock FROM products");
while($row = mysqli_fetch_assoc($res)) {
    $stock_labels[] = $row['name'];
    $stock_data[] = (int)$row['stock'];
}


// Prepare chart data (last 7 days)
$chart_labels = [];
$orders_data = [];
$sales_data = [];

for($i=6; $i>=0; $i--){
    $date = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = $date;

    $res = mysqli_query($conn,"SELECT COUNT(*) AS count FROM orders WHERE placed_on LIKE '$date%'");
    $orders_data[] = mysqli_fetch_assoc($res)['count'];

    $res2 = mysqli_query($conn,"SELECT SUM(total_price) AS total FROM orders WHERE placed_on LIKE '$date%' AND payment_status='completed'");
    $sales_data[] = mysqli_fetch_assoc($res2)['total'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Panel</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="css/admin_header.css">
<link rel="stylesheet" href="css/admin_page.css">

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<?php include 'admin_header.php'; ?>

<div class="main-content">
    <section class="dashboard">
        <h1 class="title">Dashboard</h1>

        <!-- Dashboard Boxes -->
        <div class="box-container">

            <?php
            $boxes = [
                ['Pending', getTotal($conn,'pending'), 'fa-hourglass-half'],
                ['Completed', getTotal($conn,'completed'), 'fa-check-circle'],
                ['Total Orders', getCount($conn,'orders'), 'fa-shopping-cart'],
                ['Total Products', getCount($conn,'products'), 'fa-box'],
                ['Total Users', getCount($conn,'users'), 'fa-users'],
                ['Messages', getCount($conn,'message'), 'fa-envelope'],
            ];

            foreach($boxes as $box): ?>
            <div class="box">
                <div class="stat-left">
                    <i class="fas <?= $box[2] ?> stat-icon"></i>
                    <div>
                        <p class="stat-title"><?= $box[0] ?></p>
                        <p class="stat-value"><?= is_numeric($box[1]) && strpos($box[0],'Rs')===false ? $box[1] : 'Rs. '.$box[1].'/-'; ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Charts -->
        <div class="charts-container">
           <div class="chart-box">
                <h2>Book Stock</h2>
                <canvas id="stockChart"></canvas>
             </div>
            <div class="chart-box">
                <h2>Daily Orders</h2>
                <canvas id="ordersChart"></canvas>
            </div>
        </div>
    </section>
</div>

<script>
const chartLabels = <?= json_encode($chart_labels) ?>;
const ordersData = <?= json_encode($orders_data) ?>;
const salesData = <?= json_encode($sales_data) ?>;

// Orders Chart
new Chart(document.getElementById('ordersChart').getContext('2d'), {
    data: {
        labels: chartLabels,
        datasets: [
            {
                type: 'bar',
                label: 'Orders',
                data: ordersData,
                backgroundColor: 'rgba(0, 240, 255, 0.6)',
                borderColor: 'rgba(0, 240, 255, 1)',
                borderWidth: 1
            },
            {
                type: 'line',
                label: 'Sales (Rs)',
                data: salesData,
                borderColor: 'rgba(168, 75, 255, 1)',
                backgroundColor: 'rgba(168, 75, 255, 0.3)',
                fill: true,
                tension: 0.3,
                yAxisID: 'y1'
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true, position: 'left', title: { display:true, text:'Orders' } },
            y1: { beginAtZero: true, position: 'right', title: { display:true, text:'Sales (Rs)' } }
        }
    }
});


const stockLabels = <?= json_encode($stock_labels) ?>;
const stockData = <?= json_encode($stock_data) ?>;

new Chart(document.getElementById('stockChart').getContext('2d'), {
    type: 'bar', // horizontal bar for stock
    data: {
        labels: stockLabels,
        datasets: [{
            label: 'Stock Quantity',
            data: stockData,
            backgroundColor: stockLabels.map(() => `hsl(${Math.random() * 360}, 70%, 60%)`),
            borderWidth: 1
        }]
    },
    options: {
        indexAxis: 'y', // makes it horizontal
        responsive: true,
        scales: {
            x: { beginAtZero: true }
        },
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.raw + ' units';
                    }
                }
            }
        }
    }
});


</script>

</body>
</html>
