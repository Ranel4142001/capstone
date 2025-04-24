<?php 
require_once('../config.php');
require_once('inc/header.php');

// Get date filters from GET parameters
$date_start = $_GET['date_start'] ?? '';
$date_end = $_GET['date_end'] ?? '';
$where = '';

if (!empty($date_start) && !empty($date_end)) {
    $where = "WHERE date BETWEEN '{$date_start}' AND '{$date_end}'";
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
            <!-- Filter Section -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <label><strong>Date Start</strong></label>
                    <input type="date" id="date_start" class="form-control form-control-sm" value="<?= $date_start ?>">
                </div>
                <div class="col-md-3">
                    <label><strong>Date End</strong></label>
                    <input type="date" id="date_end" class="form-control form-control-sm" value="<?= $date_end ?>">
                </div>
                <div class="col-md-2 align-self-end">
                    <button class="btn btn-sm btn-primary btn-block" id="filter_btn"><i class="fas fa-filter"></i> Filter</button>
                </div>
            </div>

            <!-- Total Production Summary -->
            <?php
            $total_qry = $conn->query("SELECT SUM(quantity) as total FROM production $where");
            $total = $total_qry->fetch_assoc()['total'] ?? 0;
            ?>
            <div class="alert alert-info">Total Jars Produced: <strong><?= number_format($total) ?></strong></div>

            <!-- Production Table -->
            <table class="table table-bordered table-hover table-striped" id="production-table">
                <thead style="background-color: white; color: black;">
                    <tr>
                        <th class="text-center">#</th>
                        <th class="text-center"><i class="fas fa-calendar-alt"></i> Date</th>
                        <th class="text-center"><i class="fas fa-box"></i> Quantity (Jars)</th>
                        <th class="text-center"><i class="fas fa-cogs"></i> Action</th>
                    </tr>
                </thead>
                <tbody style="background-color: white; color: black;">
                    <?php 
                    $i = 1;
                    $qry = $conn->query("SELECT * FROM production $where ORDER BY date DESC");
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
                        <td colspan="4" class="text-center text-muted">No production records found for the selected date range.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Production Modal -->
<div class="modal fade" id="productionModal">
    <div class="modal-dialog">
        <form id="production-form" class="modal-content">
            <div class="modal-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Add Production</h5>
                <button class="btn btn-sm btn-light border-dark ml-auto" id="view_production" type="button">
                    <i class="fas fa-box"></i> Production
                </button>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id">
                <div class="form-group">
                    <label class="text-dark"><i class="fas fa-calendar-alt"></i> Date</label>
                    <input type="date" name="date" class="form-control form-control-sm border-primary" required>
                </div>
                <div class="form-group">
                    <label class="text-dark"><i class="fas fa-box"></i> Quantity</label>
                    <input type="number" name="quantity" class="form-control form-control-sm border-primary" required min="1" placeholder="Enter number of jars">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-sm btn-primary" type="submit"><i class="fas fa-save"></i> Save</button>
                <button class="btn btn-sm btn-secondary" type="button" data-dismiss="modal"><i class="fas fa-times"></i> Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
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
        }
    });
});

$('#production-table').on('click', '.delete-production', function(){
    const id = $(this).data('id');
    if(confirm("Are you sure you want to delete this production entry?")) {
        $.ajax({
            url: '../classes/Master.php?f=delete_production',
            method: 'POST',
            data: { id: id },
            dataType: 'json',
            success: function(resp){
                if(resp.status === 'success'){
                    alert_toast('Production entry deleted.', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert_toast('Error deleting production entry.', 'error');
                }
            }
        });
    }
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
</script>
