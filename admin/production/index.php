<?php
require_once('../config.php');
require_once('inc/header.php');
if (!in_array($_settings->userdata('role'), ['admin', 'super_admin'])) exit('Access denied');
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
            <table class="table table-bordered table-hover table-striped" id="production-table">
                <thead style="background-color: white; color: black;">
                    <tr>
                        <th class="text-center">#</th>
                        <th class="text-center"><i class="fas fa-calendar-alt"></i> Date</th>
                        <th class="text-center"><i class="fas fa-box"></i> Quantity (Jars)</th>
                    </tr>
                </thead>
                <tbody style="background-color: white; color: black;">
                    <?php 
                    $i = 1;
                    $qry = $conn->query("SELECT * FROM production ORDER BY date DESC");
                    while($row = $qry->fetch_assoc()):
                    ?>
                    <tr class="text-center">
                        <td><?= $i++ ?></td>
                        <td><?= date("F d, Y", strtotime($row['date'])) ?></td>
                        <td><?= number_format($row['quantity']) ?></td>
                    </tr>
                    <?php endwhile; ?>
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
                <button class="btn btn-sm btn-light border-dark ml-auto" id="view_production">
                    <i class="fas fa-box"></i> Production
                </button>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
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
    $('#productionModal').modal('show');
});

$('#production-form').submit(function(e){
    e.preventDefault();
    $.ajax({
        url: '../classes/Master.php?f=record_production_entry',
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
</script>
