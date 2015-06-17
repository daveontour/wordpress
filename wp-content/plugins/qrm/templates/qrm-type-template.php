<!DOCTYPE html>
<html ng-app="inspinia">

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Page title set in pageTitle directive -->
    <title page-title></title>
    
     <script type="text/javascript">
		var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
		var pluginurl = '<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/" ?>';
		var postID = <?php echo $post->ID ?>;
		var postType = '<?php echo $type ?>';
		<?php if(isset($projectID))echo 'var projectID = '.$projectID.';' ?>
	</script>

    <!-- Font awesome -->
    <link rel="stylesheet" href="<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/font-awesome/css/font-awesome.css" ?>">

    <!-- Bootstrap -->
    <link rel="stylesheet" href="<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/css/bootstrap.min.css" ?>">

    <!-- All the QRM -->

    <link rel="stylesheet" href='<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/css/plugins/dropzone/dropzone.css" ?>'>
    <link rel="stylesheet" href='<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/css/plugins/ui-grid/ui-grid-unstable.css" ?>'>
    <link rel="stylesheet" href='<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/css/plugins/angular-notify/angular-notify.min.css" ?>'>
    <link rel="stylesheet" href='<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/css/plugins/iCheck/custom.css" ?>'>

    <link rel="stylesheet" href='<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/css/plugins/ngNotify/ng-notify.min.css" ?>'>
    <link rel="stylesheet" href='<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/css/plugins/ngDialog/ngDialog.min.css" ?>'>
    <link rel="stylesheet" href='<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/css/plugins/ngDialog/ngDialog-theme-default.min.css" ?>'>
    <link rel="stylesheet" href='<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/css/plugins/select/select.css" ?>'>

    <link rel="stylesheet" href='http://cdnjs.cloudflare.com/ajax/libs/select2/3.4.5/select2.css'>
    <link rel="stylesheet" href='http://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.8.5/css/selectize.default.css'>

    <link rel="stylesheet" href='<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/css/plugins/textAngular/textAngular.css" ?>'>
    <link rel="stylesheet" href='<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/css/plugins/loading-bar/loading-bar.min.css" ?>'>
    <link rel="stylesheet" href='<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/css/plugins/nv/nv.d3.min.css" ?>'>
    <link rel="stylesheet" href='<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/css/plugins/metisMenu/metisMenu.css" ?>'>
    <link rel="stylesheet" href='<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/css/daterangepicker-bs3.css" ?>'>
    
    <!-- Main Inspinia CSS files -->
    <link rel="stylesheet" href="<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/css/animate.css" ?>">
    <link rel="stylesheet" id="loadBefore" href="<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/css/style.css" ?>">

    <link rel="stylesheet" href='<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/css/qrm_angular.css" ?>'>
    <link rel="stylesheet" href='<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/css/qrm_styles.css" ?>'>

</head>

<body ng-controller="MainCtrl as main" class="cbp-spmenu-push" style="height:calc(100vh - 61px)">

		<!-- Navigation Window -->
		<nav class="cbp-spmenu cbp-spmenu-vertical cbp-spmenu-left" id="cbp-spmenu-s1">
			<h3>Quay Risk Manager</h3>
            <a ui-sref="explorer">Risk Explorer</a>
            <a ui-sref="calender">Exposure Calender</a>
            <a ui-sref="rank">Risk Ranking</a>
            <a ui-sref="matrix">Tolerance Matrix</a>
            <a ui-sref="analysis">Dashboard</a>
            <a ui-sref="incidentExpl">Incidents</a>
            <a ui-sref="reviewExpl">Reviews</a>
		</nav>
 
        <!-- Page Content -->
        <div id="page-content-wrapper" class="container">
        	
        	<!-- Sticky Header -->
            <div id="header_container">
                <div id="header" style="padding-right:10px">
                    <button id="menu-toggle" class="btn btn-sm btn-primary" style="border-radius:5px" dropdown-toggle><i class="fa fa-bars"></i>
                    </button> <span id="qrm-title" class="hidden-qrm"><strong>Q</strong>uay <strong>R</strong>isk <strong>M</strong>anager</span><span id="qrm-titleSM"><strong>QRM</strong></span>
                    <div id="welcome-name" class="pull-right hidden-qrm">Welcome, {{main.userName}}</div>
                </div>
            </div>
            
            <!-- Main Body -->
            <div id="container">
                <div id="content">
                    <div ui-view></div>
                </div>
            </div>

            <!-- Sticky Footer (not used) -->
            <!--<div id="footer_container"><div id="footer">Footer Content</div></div>-->
        </div>

    <!-- jQuery and Bootstrap -->
    <script src="<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/js/jquery/jquery-2.1.1.min.js" ?>"></script>
    <script src="<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/js/plugins/jquery-ui/jquery-ui.js" ?>"></script>
    <script src="<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/js/bootstrap/bootstrap.min.js" ?>"></script>

    <!-- MetsiMenu -->
    <script src="<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/js/plugins/metisMenu/metisMenu.min.js" ?>"></script>

    <!-- Custom and plugin javascript -->
    <script src="<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/js/inspinia.js" ?>"></script>

    <!-- Main Angular scripts-->
    <script src="<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/js/angular/angular.min.js" ?>"></script>
    <script src='<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/js/angular/angular-animate.min.js" ?>'></script>
    <script src="<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/js/plugins/oclazyload/dist/ocLazyLoad.min.js" ?>"></script>
    <script src="<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/js/ui-router/angular-ui-router.min.js" ?>"></script>
<!--  <script src="<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/js/bootstrap/ui-bootstrap-0.13.0.min.js" ?>"></script> -->   
    <script src="<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/js/bootstrap/ui-bootstrap-tpls-0.12.0.min.js" ?>"></script>
    <script src="<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/js/plugins/angular-idle/angular-idle.js" ?>"></script>


    <!-- QRM Customisations -->
    <script src='<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/js/plugins/ui-grid/ui-grid-unstable.js" ?>'></script>
    <script src='<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/js/plugins/iCheck/icheck.min.js" ?>'></script>
    <script src='<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/js/plugins/angular-notify/angular-notify.min.js" ?>'></script>
    <script src='<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/js/plugins/dropzone/dropzone.js" ?>'></script>
    <script src='<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/js/moment.js" ?>'></script>
    <script src='<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/js/plugins/ngDialog/ngDialog.min.js" ?>'></script>
    <script src='<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/js/plugins/textAngular/textAngular.min.js" ?>'></script>
    <script src='<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/js/plugins/textAngular/textAngular-rangy.min.js" ?>'></script>
    <script src='<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/js/plugins/textAngular/textAngular-sanitize.min.js" ?>'></script>


    <!--  Watch out for dependency order -->
    <script src='<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/js/plugins/d3/d3.min.js" ?>'></script>
    <script src='<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/js/plugins/nv/nv.d3.js" ?>'></script>
    <script src='<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/js/qrm-common.js" ?>'></script>
    <script src='<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/js/services.js" ?>'></script>
    <script src='<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/js/plugins/ngNotify/ng-notify.min.js" ?>'></script>
    <script src='<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/js/plugins/select/select.min.js" ?>'></script>
    <script src='<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/js/plugins/sanitize/angular-sanitize.min.js" ?>'></script>
    <script src='<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/js/plugins/loading-bar/loading-bar.min.js" ?>'></script>
    
    <!-- Anglar App Script -->
    <script src="<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/js/app.js" ?>"></script>
    <script src="<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/js/config.js" ?>"></script>
    <script src="<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/js/directives.js" ?>"></script>
    <script src="<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/js/controllers.js" ?>"></script>
  
    <script src="<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/js/daterangepicker.js" ?>"></script>
    
    <script>
        // Space in the global name space, so I can easily find thinngs
        function QRM() {

            this.matrixController = null;
            this.expController = null;
            this.rankController = null;
            this.calenderController = null;
            this.mainController = null;

            this.resizer = function () {

                try {
                    if (qrm.matrixController != null) qrm.matrixController.resize();
                } catch (e) {
                    //
                }
                try {
                    if (qrm.rankController != null) qrm.rankController.resize();
                } catch (e) {
                    //
                }
                try {
                    if (qrm.calenderController != null) qrm.calenderController.resize();
                } catch (e) {
                    //
                }
            }
            $(window).resize(this.resizer);
        }

        var qrm = new QRM();
    </script>
    <!-- Experimental -->
    <script>
        $(function () {
            $(window).bind("load resize", function () {
                closeMenu();
                winWidth = $(window).width() - 10;
                $("#container").css("width", winWidth + "px");
            })
        });
        $("#container").css("width", $(window).width() + "px");
        $("#menu-toggle").click(function (e) {
			toggleMenu();
        });
    </script>
</body>

</html>

