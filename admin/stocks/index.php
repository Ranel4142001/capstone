<?php
require_once('../config.php');
require_once('inc/header.php');
require_once('../classes/Master.php');

$Master = new Master();
$stock_data = json_decode($Master->get_total_stock(), true); // Use get_total_stock()
$current_stock = $stock_data['current_stock'] ?? 0;
?>

<div class="col-lg-12">
    <div class="card card-outline card-info">
        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-warehouse"></i> Remaining Stocks(Jars)</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                Current Total Stock: <strong><?= number_format($current_stock) ?> Jars</strong>
            </div>
        </div>
    </div>
</div>

<?php require_once('inc/footer.php'); ?>

<script>
    $(document).ready(function() {
        // Optional: Implement real-time updates using AJAX
        /*
        function fetchStockLevel() {
            $.ajax({
                url: 'ajax_get_total_stock.php', // Create this file
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    $('.alert-info strong').text(data.current_stock + ' Jars');
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching stock level:", error);
                }
            });
        }

        setInterval(fetchStockLevel, 5000);
        */
    });
</script>