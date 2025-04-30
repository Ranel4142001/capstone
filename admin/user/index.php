<?php
require_once('../config.php'); // ✅ Ensure database connection is available

// ✅ Fetch User Data Correctly
$user = $conn->query("SELECT * FROM users WHERE id ='".$_settings->userdata('id')."'");

if ($user && $user->num_rows > 0) { // ✅ Check if query returned results
    $meta = $user->fetch_assoc(); // ✅ Use fetch_assoc() for proper structured data
} else {
    $meta = []; // ✅ Set default empty array to prevent errors
}
?>


<?php if($_settings->chk_flashdata('success')): ?>
<script>
    alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>
<div class="card card-outline card-primary">
    <div class="card-body">
        <div class="container-fluid">
            <div id="msg"></div>
            <form action="" id="manage-user">   
                <input type="hidden" name="id" value="<?php echo $_settings->userdata('id') ?>">
                <div class="form-group">
                    <label for="name">First Name</label>
                    <input type="text" name="firstname" id="firstname" class="form-control" value="<?php echo isset($meta['firstname']) ? $meta['firstname']: '' ?>" required>
                </div>
                <div class="form-group">
                    <label for="name">Last Name</label>
                    <input type="text" name="lastname" id="lastname" class="form-control" value="<?php echo isset($meta['lastname']) ? $meta['lastname']: '' ?>" required>
                </div>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" name="username" id="username" class="form-control" value="<?php echo isset($meta['username']) ? $meta['username']: '' ?>" required  autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" class="form-control" value="" autocomplete="off">
                    <small><i>Leave this blank if you dont want to change the password.</i></small>
                </div>
                <div class="form-group">
                    <label for="" class="control-label">Avatar</label>
                    <div class="custom-file">
                      <input type="file" class="custom-file-input rounded-circle" id="customFile" name="img" onchange="displayImg(this,$(this))">
                      <label class="custom-file-label" for="customFile">Choose file</label>
                    </div>
                </div>
                <div class="form-group d-flex justify-content-center">
                    <img src="<?php echo validate_image(isset($meta['avatar']) ? $meta['avatar'] :'default-avatar.png') ?>" alt="" id="cimg" class="img-fluid img-thumbnail">
                </div>
            </form>
        </div>
    </div>
    <div class="card-footer">
            <div class="col-md-12">
			<div class="row justify-content-between">
                    <button class="btn btn-sm btn-success" form="manage-user">Add Admin</button>
					<button class="btn btn-sm btn-primary" form="manage-user">Update</button>
                </div>
            </div>
        </div>
</div>


<style>
    img#cimg{
        height: 15vh;
        width: 15vh;
        object-fit: cover;
        border-radius: 100% 100%;
    }
</style>
<script>
    function displayImg(input,_this) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                $('#cimg').attr('src', e.target.result);
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    $('#manage-user').submit(function(e){
    e.preventDefault();
    start_loader();
    console.log("User ID being sent:", formData.get("id"));
    var formData = new FormData($(this)[0]);
    console.log(formData.get("id")); // ✅ Debugging: Ensure user ID is passed

    $.ajax({
        url: _base_url_+'classes/Users.php?f=update',
        data: formData,
        cache: false,
        contentType: false,
        processData: false,
        method: 'POST',
        success: function(resp){
            console.log(resp); // ✅ Debugging SQL errors
            if (resp == 1) {
                location.reload();
            } else {
                $('#msg').html('<div class="alert alert-danger">Error updating user. Please try again.</div>');
                end_loader();
            }
        }
    });
});


</script>
