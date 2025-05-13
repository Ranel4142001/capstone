<?php
require_once('../config.php');

// Grab year from query string (e.g. ?year=2024); sanitize to integer
$year = isset($_GET['year']) ? (int)$_GET['year'] : null;

// Build optional WHERE clauses
$salesYearWhere = $year ? "WHERE YEAR(s.date_created) = {$year}" : "";
$productionYearWhere = $year ? "WHERE YEAR(p.date) = {$year}" : "";

// Function to safely fetch a single value from the database
function fetch_single_value($conn, $query) {
    $result = $conn->query($query);
    return $result ? ($result->fetch_assoc()['total'] ?? 0) : 0;
}

// Get total sales and total production (filtered by year if set)
$totalSales = fetch_single_value(
    $conn,
    $year
        ? "SELECT SUM(si.total_amount) AS total FROM sales_items si JOIN sales s ON si.sales_id = s.id WHERE YEAR(s.date_created) = {$year}"
        : "SELECT SUM(total_amount) as total FROM sales_items"
);

$totalProduction = fetch_single_value(
    $conn,
    $year
        ? "SELECT SUM(quantity) AS total FROM production p WHERE YEAR(p.date) = {$year}"
        : "SELECT SUM(quantity) as total FROM production"
);

// Months array
$months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun",
            "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

// Initialize monthly arrays
$monthlySales = array_fill(0, 12, 0);
$monthlyProduction = array_fill(0, 12, 0);

// Query monthly sales (filtered by year if set)
$salesPerMonth = $conn->query("
    SELECT MONTH(s.date_created) AS month, SUM(si.total_amount) AS total
    FROM sales_items si
    JOIN sales s ON si.sales_id = s.id
    {$salesYearWhere}
    GROUP BY MONTH(s.date_created)
");

if ($salesPerMonth) { // Check if the query was successful
    while ($row = $salesPerMonth->fetch_assoc()) {
        $index = (int)$row['month'] - 1;
        $monthlySales[$index] = round($row['total'], 2); // Round sales data
    }
}

// Query monthly production (filtered by year if set)
$productionPerMonth = $conn->query("
    SELECT MONTH(p.date) AS month, SUM(p.quantity) AS total
    FROM production p
    {$productionYearWhere}
    GROUP BY MONTH(p.date)
");

if ($productionPerMonth) { // Check if the query was successful
    while ($row = $productionPerMonth->fetch_assoc()) {
        $index = (int)$row['month'] - 1;
        $monthlyProduction[$index] = (int)$row['total']; // Ensure integer for production
    }
}

// Available years (dynamically generate from database)
$yearsQuery = $conn->query("
  SELECT DISTINCT YEAR(date_created) AS year FROM sales
  UNION
  SELECT DISTINCT YEAR(date) AS year FROM production
  ORDER BY year ASC
");
$available_years = [];
if ($yearsQuery) {
    while ($row = $yearsQuery->fetch_assoc()) {
        $available_years[] = $row['year'];
    }
} else {
    $available_years = ["2025", "2024", "2023"];
}
if(empty($available_years)){
    $available_years = ["2025", "2024", "2023"];
}

// Check if there is data for the selected year.
$hasDataQuery = $conn->query($year ? "SELECT 1 FROM sales WHERE YEAR(date_created) = {$year} UNION SELECT 1 FROM production WHERE YEAR(date) = {$year}" : "SELECT 1 FROM sales UNION SELECT 1 FROM production");
$hasData = $hasDataQuery && $hasDataQuery->num_rows > 0;

if (!$hasData) {
    $totalSales = 0;
    $totalProduction = 0;
    $monthlySales = array_fill(0, 12, 0);
    $monthlyProduction = array_fill(0, 12, 0);
}


// Output JSON
echo json_encode([
    'available_years' => $available_years,
    'total_production' => max(0, (int)$totalProduction), // Ensure >= 0 and is an integer
    'total_sales' => max(0, round($totalSales, 2)), // Ensure >= 0 and is rounded
    'months' => $months,
    'monthly_sales' => $monthlySales,
    'monthly_production' => $monthlyProduction,
    'selectedYear' => $year
]);
?>
