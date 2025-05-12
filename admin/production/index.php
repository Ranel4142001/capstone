<?php
require_once('../config.php');
require_once('inc/header.php');

// Get date filters from GET parameters
$date_start = $_GET['date_start'] ?? '';
$date_end = $_GET['date_end'] ?? '';
$high_production = $_GET['high_production'] ?? '';
$where = '';

// Apply date filter conditions
if (!empty($date_start) && !empty($date_end)) {
    $where = "WHERE date BETWEEN '{$date_start}' AND '{$date_end}'";
}

// Set sorting order for production
if ($high_production == 'highest') {
    $order_by = "ORDER BY quantity DESC, date DESC";
} elseif ($high_production == 'lowest') {
    $order_by = "ORDER BY quantity ASC, date ASC";
} else {
    $order_by = "ORDER BY date DESC"; // Default sorting
}
?>

<style>
    img#cimg {
        height: 15vh;
        width: 15vh;
        object-fit: cover;
        border-radius: 100%;
    }
    img#cimg2 {
        height: 50vh;
        width: 100%;
        object-fit: contain;
    }
</style>

<div class="col-lg-12">
    <div class="card card-outline card-primary">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title"><i class="fas fa-industry"></i> Production Tracker</h5>
            <button class="btn btn-sm btn-default btn-flat border-primary ml-auto" id="create_production">
                <i class="fas fa-plus-circle"></i> Add Production
            </button>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3">
                    <label><strong>Date Start</strong></label>
                    <input type="date" id="date_start" class="form-control form-control-sm" value="<?= $date_start ?>">
                </div>
                <div class="col-md-3">
                    <label><strong>Date End</strong></label>
                    <input type="date" id="date_end" class="form-control form-control-sm" value="<?= $date_end ?>">
                </div>
                <div class="col-md-3">
                    <label><strong>Filter Quantity</strong></label>
                    <select id="high_production_filter" class="form-control form-control-sm">
                        <option value="">Default (Newest First)</option>
                        <option value="highest" <?= $high_production == 'highest' ? 'selected' : '' ?>>Highest Production</option>
                        <option value="lowest" <?= $high_production == 'lowest' ? 'selected' : '' ?>>Lowest Production</option>
                    </select>
                </div>
                <div class="col-md-2 align-self-end">
                    <button class="btn btn-sm btn-primary btn-block" id="filter_btn"><i class="fas fa-filter"></i> Apply Filters</button>
                </div>
            </div>

            <?php
            $total_qry = $conn->query("SELECT SUM(quantity) as total FROM production $where");
            $total = $total_qry->fetch_assoc()['total'] ?? 0;
            ?>
            <div class="alert alert-info">Total Jars Produced: <strong><?= number_format($total) ?></strong></div>

            <table class="table table-bordered table-hover table-striped" id="production-table">
                <thead style="background-color: white; color: black;">
                    <tr>
                        <th class="text-center">#</th>
                        <th class="text-center"><i class="fas fa-calendar-alt"></i> Date</th>
                        <th class="text-center"><i class="fas fa-box"></i> Quantity (Jars)
                            <i class="fas fa-arrow-down text-primary"></i>
                        </th>
                        <th class="text-center"><i class="fas fa-cogs"></i> Action</th>
                    </tr>
                </thead>
                <tbody style="background-color: white; color: black;">
                    <?php
                    $i = 1;
                    $qry = $conn->query("SELECT * FROM production $where $order_by");
                    if($qry && $qry->num_rows > 0):
                        while($row = $qry->fetch_assoc()):
                    ?>
                    <tr class="text-center">
                        <td><?= $i++ ?></td>
                        <td><?= date("F d, Y", strtotime($row['date'])) ?></td>
                        <td><?= number_format($row['quantity']) ?></td>
                        <td>
                            <button class="btn btn-sm btn-primary edit-production"
                                    data-id="<?= $row['id'] ?>"
                                    data-date="<?= $row['date'] ?>"
                                    data-quantity="<?= $row['quantity'] ?>">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-sm btn-danger delete-production" data-id="<?= $row['id'] ?>">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </td>
                    </tr>
                    <?php
                        endwhile;
                    else:
                    ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted">No production records found for the selected criteria.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="productionModal" tabindex="-1" role="dialog" aria-labelledby="productionModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productionModalLabel">Add Production</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="production-form">
                    <input type="hidden" name="id">
                    <div class="form-group">
                        <label for="date">Date</label>
                        <input type="date" class="form-control" id="date" name="date" required>
                    </div>
                    <div class="form-group">
                        <label for="quantity">Quantity</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" form="production-form">Save Production</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    $('#create_production').click(function(){
        $('#production-form')[0].reset();
        $('#production-form input[name="id"]').val('');
        $('#productionModal .modal-title').html('<i class="fas fa-plus-circle"></i> Add Production');
        $('#productionModal').modal('show');
    });

    $('#production-table').on('click', '.edit-production', function(){
        const id = $(this).data('id');
        const date = $(this).data('date');
        const quantity = $(this).data('quantity');

        $('#production-form input[name="id"]').val(id);
        $('#production-form input[name="date"]').val(date);
        $('#production-form input[name="quantity"]').val(quantity);
        $('#productionModal .modal-title').html('<i class="fas fa-edit"></i> Edit Production');
        $('#productionModal').modal('show');
    });

    $('#production-form').submit(function(e){
        e.preventDefault();
        $.ajax({
            url: '../classes/Master.php?f=save_production',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(resp){
                if(resp.status == 'success'){
                    alert_toast('Production saved successfully!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert_toast('Error saving production.', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", status, error);
                alert_toast('An error occurred while saving.', 'error');
            }
        });
    });

    $('#production-table').on('click', '.delete-production', function(){
        const id = $(this).data('id');
        if(confirm('Are you sure you want to delete this production entry?')){
            $.ajax({
                url: '../classes/Master.php?f=delete_production',
                method: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(resp){
                    if(resp.status == 'success'){
                        alert_toast('Production deleted successfully!', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        alert_toast('Error deleting production.', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", status, error);
                    alert_toast('An error occurred while deleting.', 'error');
                }
            });
        }
    });

    $('#high_production_filter').change(function(){
        const productionOrder = $(this).val();
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('high_production', productionOrder);
        window.history.replaceState({}, '', '?' + urlParams.toString());
        location.reload();
    });

    $('#filter_btn').click(function(){
        const start = $('#date_start').val();
        const end = $('#date_end').val();
        if (start && end) {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('date_start', start);
            urlParams.set('date_end', end);
            window.history.replaceState({}, '', '?' + urlParams.toString());
            location.reload();
        } else {
            alert('Please select both Date Start and Date End.');
        }
    });
});

function alert_toast(msg, type='success'){
    var toastrColor;
    if(type === 'success'){
        toastrColor = '#28a745';
    } else {
        toastrColor = '#dc3545';
    }
    Toast = Swal.mixin({
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 5000
    });
    Toast.fire({
        icon: type === 'success' ? 'success' : 'error',
        title: msg,
        background: toastrColor,
        color: 'white'
    });
}
</script>

<?php require_once('inc/footer.php'); ?>