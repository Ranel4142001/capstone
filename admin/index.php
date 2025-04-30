<?php 
session_start();
require_once('../config.php');


?>
<!DOCTYPE html>
<html lang="en" style="height: auto;">
<?php require_once('inc/header.php') ?>
<body class="sidebar-mini layout-fixed control-sidebar-slide-open layout-navbar-fixed sidebar-mini-md sidebar-mini-xs" style="height: auto;">
    <div class="wrapper">
        <?php require_once('inc/topBarNav.php') ?>
        <?php require_once('inc/navigation.php') ?>

        <?php $page = isset($_GET['page']) ? $_GET['page'] : 'home'; ?>
        <!-- Content Wrapper -->
        <div class="content-wrapper pt-3" style="min-height: 567.854px;">
            <!-- Main content -->
            <section class="content text-dark">
                <div class="container-fluid">
                    <?php 
                        if(!file_exists($page.".php") && !is_dir($page)){
                            include '404.html';
                        }else{
                            if(is_dir($page))
                                include $page.'/index.php';
                            else
                                include $page.'.php';
                        }
                    ?>
                </div>
            </section>
        </div>
        
        <!-- Modals -->
        <div class="modal fade" id="confirm_modal" role="dialog">
            <div class="modal-dialog modal-md modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirmation</h5>
                    </div>
                    <div class="modal-body">
                        <div id="delete_content"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" id="confirm">Continue</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="uni_modal" role="dialog">
            <div class="modal-dialog modal-md modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"></h5>
                    </div>
                    <div class="modal-body"></div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" id="submit" onclick="$('#uni_modal form').submit()">Save</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="viewer_modal" role="dialog">
            <div class="modal-dialog modal-md" role="document">
                <div class="modal-content">
                    <button type="button" class="btn-close" data-dismiss="modal"><span class="fa fa-times"></span></button>
                    <img src="" alt="">
                </div>
            </div>
        </div>
    </div>

    <?php require_once('inc/footer.php') ?>
</body>
</html>
