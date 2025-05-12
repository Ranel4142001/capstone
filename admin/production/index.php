<?php
require_once('../config.php');
require_once('inc/header.php');

// --- Pagination Setup ---
$records_per_page = isset($_GET['entries']) ? intval($_GET['entries']) : 10;
$current_page = isset($_GET['page_no']) ? intval($_GET['page_no']) : 1;
$offset = ($current_page - 1) * $records_per_page;

// --- Filters and Sorting ---
$date_start = $_GET['date_start'] ?? '';
$date_end = $_GET['date_end'] ?? '';
$high_production = $_GET['high_production'] ?? '';
$where_clauses = [];
$query_params = [];

if (!empty($date_start) && !empty($date_end)) {
    $where_clauses[] = "date BETWEEN ?";
    $query_params[] = $date_start;
    $query_params[] = $date_end;
}

$where = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

if ($high_production == 'highest') {
    $order_by = "ORDER BY quantity DESC, date DESC";
} elseif ($high_production == 'lowest') {
    $order_by = "ORDER BY quantity ASC, date ASC";
} else {
    $order_by = "ORDER BY date DESC"; // Default sorting
}

// --- Fetch Production Data with Pagination ---
$sql = "SELECT * FROM production $where $order_by LIMIT ?, ?";
$stmt = $conn->prepare($sql);

// Bind parameters dynamically
$types = '';
$bind_params = [];
foreach ($query_params as $param) {
    $types .= 's'; // Assuming all filter parameters are strings (adjust if needed)
    $bind_params[] = &$param;
}
$types .= 'ii'; // For offset and limit (integers)
$bind_params[] = &$offset;
$bind_params[] = &$records_per_page;

if ($stmt) {
    array_unshift($bind_params, $types);
    mysqli_stmt_bind_param($stmt, ...$bind_params);
    $stmt->execute();
    $result = $stmt->get_result();
    $productions = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    die("Error preparing statement: " . $conn->error);
}

// --- Count Total Records for Pagination ---
$total_sql = "SELECT COUNT(*) as total FROM production $where";
$total_stmt = $conn->prepare($total_sql);
if ($total_stmt) {
    if (!empty($query_params)) {
        $types_total = str_repeat('s', count($query_params));
        mysqli_stmt_bind_param($total_stmt, $types_total, ...$query_params);
    }
    $total_stmt->execute();
    $total_result = $total_stmt->get_result();
    $total_records = $total_result->fetch_assoc()['total'];
    $total_stmt->close();
} else {
    die("Error preparing total count statement: " . $conn->error);
}
$total_pages = ceil($total_records / $records_per_page);
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
    .pagination {
        display: flex;
        justify-content: center;
        margin-top: 20px;
    }
    .pagination .page-item {
        margin: 0 5px;
    }
    .pagination .page-link {
        padding: 8px 12px;
        border: 1px solid #ccc;
        text-decoration: none;
        color: #333;
        border-radius: 4px;
    }
    .pagination .page-item.active .page-link {
        background-color: #007bff;
        color: white;
        border-color: #007bff;
    }
    .pagination .page-item.disabled .page-link {
        color: #6c757d;
        background-color: #e9ecef;
        border-color: #dee2e6;
        cursor: not-allowed;
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
                <div class="col-md-2">
                    <label><strong>Show Entries</strong></label>
                    <select id="entriesPerPage" class="form-control form-control-sm">
                        <option value="10" <?= $records_per_page == 10 ? 'selected' : '' ?>>10</option>
                        <option value="25" <?= $records_per_page == 25 ? 'selected' : '' ?>>25</option>
                        <option value="50" <?= $records_per_page == 50 ? 'selected' : '' ?>>50</option>
                        <option value="100" <?= $records_per_page == 100 ? 'selected' : '' ?>>100</option>
                    </select>
                </div>
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
                <div class="col-md-1 align-self-end">
                    <button class="btn btn-sm btn-primary btn-block" id="filter_btn"><i class="fas fa-filter"></i> Apply</button>
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
                            <?php if ($high_production == 'highest'): ?>
                                <i class="fas fa-arrow-up text-success"></i>
                            <?php elseif ($high_production == 'lowest'): ?>
                                <i class="fas fa-arrow-down text-danger"></i>
                            <?php else: ?>
                                <i class="fas fa-arrow-down text-primary"></i>
                            <?php endif; ?>
                        </th>
                        <th class="text-center"><i class="fas fa-cogs"></i> Action</th>
                    </tr>
                </thead>
                <tbody style="background-color: white; color: black;">
                    <?php
                    if (!empty($productions)):
                        $i = ($current_page - 1) * $records_per_page + 1;
                        foreach ($productions as $row):
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
                        endforeach;
                    else:
                    ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted">No production records found for the selected criteria.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <nav aria-label="Production Pagination">
                <ul class="pagination">
                    <li class="page-item <?= $current_page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=production&page_no=<?= $current_page - 1 ?>&date_start=<?= $date_start ?>&date_end=<?= $date_end ?>&high_production=<?= $high_production ?>&entries=<?= $records_per_page ?>">Previous</a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=production&page_no=<?= $i ?>&date_start=<?= $date_start ?>&date_end=<?= $date_end ?>&high_production=<?= $high_production ?>&entries=<?= $records_per_page ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $current_page >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=production&page_no=<?= $current_page + 1 ?>&date_start=<?= $date_start ?>&date_end=<?= $date_end ?>&high_production=<?= $high_production ?>&entries=<?= $records_per_page ?>">Next</a>
                    </li>
                </ul>
            </nav>
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
        urlParams.set('page_no', 1); // Reset to the first page when sorting changes
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
            urlParams.set('page_no', 1); // Reset to the first page when filtering dates
            window.history.replaceState({}, '', '?' + urlParams.toString());
            location.reload();
        } else {
            alert('Please select both Date Start and Date End to apply date filters.');
        }
    });

    $('#entriesPerPage').change(function(){
        const entries = $(this).val();
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('entries', entries);
        urlParams.set('page_no', 1); // Reset to the first page when entries per page changes
        window.history.replaceState({}, '', '?' + urlParams.toString());
        location.reload();
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