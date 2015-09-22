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
		var postType = '<?php if ($type){echo $type;} else {echo "firstproject";}?>';
		<?php if(isset($projectID))echo 'var projectID = '.$projectID.';' ?>
	</script>
	
<?php wp_print_styles(["q1","q2","q3","q4","q5","q6","q7","q8","q9","q10","q11","q12","q13","q14","q15","q16","q17","q18","q19","q20"]); ?>
</head>
<body ng-controller="MainCtrl as main" class="cbp-spmenu-push" style="height:calc(100vh - 61px)">
	<div ui-view></div>

	<?php wp_print_scripts(["s1","s2","s3","s4","s5","s6","s7","s8","s9","s10","s11","s12","s13","s14","s15","s16","s17","s18","s19","s20", "s21","s22","s23","s24","s25","s26","s27","s28","s29","s30",'s31' ]) ?>

    <script>
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
            jQuery(window).resize(this.resizer);
        }

        var qrm = new QRM();

        jQuery(function () {
        	jQuery(window).bind("load resize", function () {
                closeMenu();
                winWidth = jQuery(window).width() - 10;
                jQuery("#container").css("width", winWidth + "px");
            })
        });
        jQuery("#container").css("width", jQuery(window).width() + "px");
        jQuery("#menu-toggle").click(function (e) {
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