<?php
require_once('../config.php'); // Database Connection
require_once('inc/header.php');

// Define a fixed start year (e.g., business started in 2020)
$default_start_year = 2020;
$current_year = (int)date('Y');

// Get the earliest year from actual sales data
$year_query = $conn->query("SELECT MIN(YEAR(date_created)) as min_year FROM sales");
$min_sales_year = ($year_query && $year_query->num_rows > 0) ? $year_query->fetch_assoc()['min_year'] ?? $default_start_year : $default_start_year;

// Determine range of years for filtering
$start_year = min($default_start_year, $min_sales_year);
$available_years = range($start_year, $current_year);

// Capture selected year from URL parameters
$selected_year = isset($_GET['year']) && is_numeric($_GET['year']) ? (int)$_GET['year'] : $current_year;

// Initialize monthly sales data with all zero values
$sales_data = array_fill(1, 12, 0);
$labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

// Fetch monthly sales for the selected year
$query = $conn->query("SELECT MONTH(date_created) as month, COALESCE(SUM(amount), 0) as total_sales FROM sales WHERE YEAR(date_created) = {$selected_year} GROUP BY MONTH(date_created)");

$total_year_sales = 0;
if ($query && $query->num_rows > 0) {
    while ($row = $query->fetch_assoc()) {
        $month = (int)$row['month'];
        $amount = (float)$row['total_sales'];
        $sales_data[$month] = $amount;
        $total_year_sales += $amount;
    }
}

// Ensure graph displays correctly even if no sales data exists
if ($total_year_sales == 0) {
    $sales_data = array_fill(1, 12, 0); // Force a flat line at zero sales
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Analytics</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .chart-container { width: 90%; margin: auto; padding-top: 20px; }
        .page-header { margin-top: 20px; text-align: center; }
        .filter-options { margin-top: 20px; text-align: center; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="page-header">
        <h2 class="text-center"><i class="fas fa-chart-line"></i> Sales Analytics</h2>
    </div>

    <div class="filter-options">
        <form id="yearFilterForm">
            <label for="year">Select Year:</label>
            <select name="year" id="year" class="form-control-sm d-inline-block w-auto">
                <?php foreach ($available_years as $year): ?>
                    <option value="<?= $year ?>" <?= $selected_year == $year ? 'selected' : '' ?>><?= $year ?></option>
                <?php endforeach; ?>
            </select>
            <button type="button" class="btn btn-primary btn-sm ml-2" id="applyFilter">Apply</button>
        </form>
    </div>

    <div class="chart-container">
        <canvas id="salesChart"></canvas>
    </div>

    <div class="text-center mt-3">
    <h5>Total Sales for <?= $selected_year ?>: <strong id="totalSalesDisplay">₱<?= number_format($total_year_sales, 2) ?></strong></h5>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('salesChart').getContext('2d');

    // Force zero values if no sales exist
    const salesData = <?= json_encode(array_values($sales_data)) ?>;
    const hasSales = salesData.some(value => value > 0); // Check if any non-zero values exist

    if (!hasSales) {
        salesData.fill(0); // Ensures a flat zero line when no sales exist
    }

    // Initialize Chart.js with consistent y-axis scaling
    const salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [{
                label: 'Total Sales in <?= $selected_year ?> (₱)',
                data: salesData,
                borderColor: 'blue',
                backgroundColor: 'rgba(0, 123, 255, 0.2)',
                borderWidth: 2,
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.parsed.y || 0;
                            return '₱' + value.toLocaleString();
                        }
                    }
                }
            },
            scales: {
                x: { title: { display: true, text: 'Month' } },
                y: {
                    beginAtZero: true, 
                    min: 0, 
                    max: 5000, // Ensures fixed max range
                    ticks: {
                        stepSize: 1000, // Ensures increments match reference
                        callback: function(value) {
                            return '₱' + value.toLocaleString(); // Format correctly
                        }
                    },
                    title: { display: true, text: 'Sales Amount (₱)' }
                }
            }
        }
    });

    // Apply filtering with URLSearchParams
    document.getElementById('applyFilter').addEventListener('click', function() {
        const selectedYear = document.getElementById('year').value;
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('year', selectedYear);
        window.history.replaceState({}, '', '?' + urlParams.toString());
        location.reload();
    });
});
</script>


<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
