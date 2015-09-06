<!DOCTYPE html>
<html ng-app="qrm">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Page title set in pageTitle directive -->
    <title page-title></title>
    
     <script type="text/javascript">
		var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
		var pluginurl = '<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/" ?>';
		var postID = <?php echo $post->ID ?>;
		var lostPasswordURL = '<?php echo wp_lostpassword_url(); ?>';
		var siteURL = '<?php echo site_url(); ?>';
		var postType = '<?php if ($type){
							echo $type;
		} else {
			echo "firstproject";
		}
							
	?>';

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

    <link rel="stylesheet" href='<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/css/select2.css" ?>'>
    <link rel="stylesheet" href='<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/css/selectize.default.css" ?>'>

    <link rel="stylesheet" href='<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/css/plugins/textAngular/textAngular.css" ?>'>
    <link rel="stylesheet" href='<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/css/plugins/loading-bar/loading-bar.min.css" ?>'>
    <link rel="stylesheet" href='<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/css/plugins/nv/nv.d3.min.css" ?>'>
    <link rel="stylesheet" href='<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/css/daterangepicker-bs3.css" ?>'>
    
    <!-- Main Inspinia CSS files -->
    <link rel="stylesheet" href="<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/css/animate.css" ?>">
    <link rel="stylesheet" id="loadBefore" href="<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/css/style.css" ?>">

    <link rel="stylesheet" href='<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/css/qrm_angular.css" ?>'>
    <link rel="stylesheet" href='<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/css/qrm_styles.css" ?>'>
</head>

<body ng-controller="MainCtrl as main" class="cbp-spmenu-push" style="height:calc(100vh - 61px)">

	<div ui-view></div>
	
    <!-- jQuery and Bootstrap -->
    <script src="<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/js/jquery/jquery-2.1.1.min.js" ?>"></script>
    <script src="<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/js/plugins/jquery-ui/jquery-ui.js" ?>"></script>
    <script src="<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/js/bootstrap/bootstrap.min.js" ?>"></script>
   
    <!-- Main Angular scripts-->
    <script src="<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/js/angular/angular.min.js" ?>"></script>
    <script src='<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/js/angular/angular-animate.min.js" ?>'></script>
    <script src="<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/js/plugins/oclazyload/dist/ocLazyLoad.min.js" ?>"></script>
    <script src="<?php echo plugin_dir_url (__FILE__)."../includes/qrmmainapp/js/ui-router/angular-ui-router.min.js" ?>"></script>
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
    <form id="reportForm" method="post" style="display: none;" target="qrmIframe">
	    <input type="hidden" name="reportEmail" />
	    <input type="hidden" name="reportData" />
	    <input type="hidden" name="reportID" />
	    <input type="hidden" name="action" />
    </form>
    <form id="getReportForm" method="post" style="display: none;" target="qrmIframe">
	    <input type="hidden" name="userEmail" />
	    <input type="hidden" name="userLogin" />
	    <input type="hidden" name="siteKey" />
	    <input type="hidden" name="id" />
	    <input type="hidden" name="action" value="get_report"/>
    </form>
    <iframe name="qrmIframe" style="display: none"></iframe>
</body>
</html>

