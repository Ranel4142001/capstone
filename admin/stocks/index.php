<?php
require_once('../config.php');
require_once('inc/header.php');

// Initial filter and sort values (for URL persistence)
$filter_category = $_GET['category'] ?? '';
$sort_order = $_GET['sort_order'] ?? '';
?>

<style>
/* Styles remain the same, but improved for clarity */
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

.stock-quantity {
    font-weight: bold;
    transition: color 0.3s ease;
}

.stock-quantity.low-stock {
    color: red;
}

.stock-quantity.normal-stock {
    color: green;
}

.stock-quantity.updating {
    color: blue;
    font-style: italic;
}
</style>

<div class="col-lg-12">
    <div class="card card-outline card-primary">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title"><i class="fas fa-boxes"></i> Stock Management</h5>
            <button class="btn btn-sm btn-default btn-flat border-primary ml-auto" id="add_stock">
                <i class="fas fa-plus-circle"></i> Add Stock
            </button>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label><strong>Category</strong></label>
                    <select id="category_filter" class="form-control form-control-sm">
                        <option value="">All Categories</option>
                        <option value="Water" <?= $filter_category == 'Water' ? 'selected' : '' ?>>Water</option>
                        <option value="Chemicals" <?= $filter_category == 'Chemicals' ? 'selected' : '' ?>>Chemicals</option>
                        <option value="Jars" <?= $filter_category == 'Jars' ? 'selected' : '' ?>>Jars</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label><strong>Sort by Quantity</strong></label>
                    <select id="sort_order_filter" class="form-control form-control-sm">
                        <option value="">Default (A-Z)</option>
                        <option value="highest" <?= $sort_order == 'highest' ? 'selected' : '' ?>>Highest Stock</option>
                        <option value="lowest" <?= $sort_order == 'lowest' ? 'selected' : '' ?>>Lowest Stock</option>
                    </select>
                </div>
                <div class="col-md-2 align-self-end">
                    <button class="btn btn-sm btn-primary btn-block" id="filter_btn"><i class="fas fa-filter"></i> Apply Filters</button>
                </div>
            </div>

            <div class="alert alert-info" id="total-stock-display">
                Total Items in Stock: <strong id="total-stock-count">0</strong>
            </div>

            <table class="table table-bordered table-hover table-striped" id="stock-table">
                <thead style="background-color: white; color: black;">
                    <tr>
                        <th class="text-center">#</th>
                        <th class="text-center"><i class="fas fa-box"></i> Product Name</th>
                        <th class="text-center"><i class="fas fa-sort-amount-up"></i> Quantity
                            <i class="fas fa-arrow-down text-primary"></i>
                        </th>
                        <th class="text-center"><i class="fas fa-cogs"></i> Action</th>
                    </tr>
                </thead>
                <tbody style="background-color: white; color: black;" id="stock-table-body">
                    <tr>
                        <td colspan="4" class="text-center text-muted">Loading stock data...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="stockModal" tabindex="-1" role="dialog" aria-labelledby="stockModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="stockModalLabel"><i class="fas fa-plus-circle"></i> Add Stock</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="stock-form">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="name">Product Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                        <input type="hidden" name="id"> </div>
                    <div class="form-group">
                        <label for="quantity">Quantity</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" value="0" required min="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const stockTable = $('#stock-table-body');
const totalStockCountDisplay = $('#total-stock-count');
const lowStockThreshold = 5;
let updatingIntervalId = null; // To store the interval ID

// Function to fetch and update stock data
function updateStockData() {
    const category = $('#category_filter').val();
    const sortOrder = $('#sort_order_filter').val();

    $.ajax({
        url: '../classes/Master.php?f=get_stock',
        method: 'GET',
        dataType: 'json',
        data: {
            category: category,
            sort_order: sortOrder
        },
        success: function(response) {
            if (response.status === 'success') {
                let html = '';
                let totalStock = 0;
                if (response.data.length > 0) {
                    response.data.forEach((item, index) => {
                        totalStock += item.quantity;
                        const quantityClass = item.quantity <= lowStockThreshold ? 'low-stock' : 'normal-stock';
                        html += `
                            <tr class="text-center">
                                <td>${index + 1}</td>
                                <td>${item.name}</td>
                                <td><span class="stock-quantity ${quantityClass}" data-product-id="${item.id}">${item.quantity}</span></td>
                                <td>
                                    <button class="btn btn-sm btn-primary edit-stock"
                                            data-id="${item.id}"
                                            data-name="${item.name}"
                                            data-quantity="${item.quantity}">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-sm btn-danger delete-stock" data-id="${item.id}">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                    stockTable.html(html);
                    totalStockCountDisplay.text(totalStock);
                } else {
                    stockTable.html('<tr><td colspan="4" class="text-center text-muted">No stock records found.</td></tr>');
                    totalStockCountDisplay.text(0);
                }
            } else {
                stockTable.html('<tr><td colspan="4" class="text-center text-danger">Error fetching stock data.</td></tr>');
                totalStockCountDisplay.text(0);
                console.error(response.error);
            }
        },
        error: function(xhr, status, error) {
            stockTable.html('<tr><td colspan="4" class="text-center text-danger">Error fetching stock data. Please check your network connection.</td></tr>');
            totalStockCountDisplay.text(0);
            console.error(error);
        }
    });
}

// Function to highlight quantity being updated
function highlightUpdatingQuantity(productId) {
    const quantityElement = $(`span[data-product-id="${productId}"]`);
    if (quantityElement.length) {
        quantityElement.addClass('updating');
        setTimeout(() => {
            quantityElement.removeClass('updating');
        }, 1500);
    }
}

// Initial load of stock data
updateStockData();

// Start real-time updates (adjust interval as needed)
updatingIntervalId = setInterval(updateStockData, 5000);

$('#add_stock').click(function() {
    $('#stock-form')[0].reset();
    $('#stock-form input[name="id"]').val('');
    $('#stockModal .modal-title').html('<i class="fas fa-plus-circle"></i> Add Stock');
    $('#stockModal').modal('show');
});

$('#stock-table').on('click', '.edit-stock', function() {
    const id = $(this).data('id');
    const name = $(this).data('name');
    const quantity = $(this).data('quantity');

    $('#stock-form input[name="id"]').val(id);
    $('#stock-form input[name="name"]').val(name);
    $('#stock-form input[name="quantity"]').val(quantity);
    $('#stockModal .modal-title').html('<i class="fas fa-edit"></i> Edit Stock');
    $('#stockModal').modal('show');
});

$('#stock-form').submit(function(e) {
    e.preventDefault();

    const form = $(this);
    const formData = form.serialize();
    const productId = $('#stock-form input[name="id"]').val();


    $.ajax({
        url: '../classes/Master.php?f=save_stock',
        method: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.status == 'success') {
                alert_toast('Stock saved successfully!', 'success');
                $('#stockModal').modal('hide');
                highlightUpdatingQuantity(productId);
                updateStockData();

            } else {
                alert_toast('Error saving stock: ' + response.error, 'error');
            }
        },
        error: function(xhr, status, error) {
            alert_toast('Error saving stock. Please check your network connection.', 'error');
            console.error(error);
        }
    });
});

$('#stock-table').on('click', '.delete-stock', function() {
    const id = $(this).data('id');
    if (confirm('Are you sure you want to delete this stock item?')) {
        $.ajax({
            url: '../classes/Master.php?f=delete_stock',
            method: 'POST',
            data: {
                id: id
            },
            dataType: 'json',
            success: function(resp) {
                if (resp.status == 'success') {
                    alert_toast('Stock deleted successfully!', 'success');
                    updateStockData();
                } else {
                    alert_toast('Error deleting stock: ' + resp.error, 'error');
                }
            },
            error: function(xhr, status, error) {
                alert_toast('Error deleting stock. Please check your network connection.', 'error');
                console.error(error);
            }
        });
    }
});

$('#filter_btn, #sort_order_filter, #category_filter').change(function() {
    const category = $('#category_filter').val();
    const sortOrder = $('#sort_order_filter').val();

    const urlParams = new URLSearchParams();
    if (category) urlParams.set('category', category);
    if (sortOrder) urlParams.set('sort_order', sortOrder);
    const newURL = window.location.pathname + '?' + urlParams.toString();
    window.history.replaceState({}, '', newURL);
    updateStockData();
});
</script>
