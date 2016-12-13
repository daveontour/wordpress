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
		var showGuestLogin = <?php if (isset($GLOBALS["showGuestLogin"])){if($GLOBALS["showGuestLogin"]){ echo $GLOBALS["showGuestLogin"]; } else {echo 'false';}}else {echo 'false';} ?>;
		var postType = '<?php if (isset($type)){echo $type;} else {echo "firstproject";}?>';
		<?php if(isset($projectID))echo 'var projectID = '.$projectID.';' ?>
	</script>
	
<?php wp_print_styles(["q1","q2","q3","q4","q5","q6","q7","q8","q9","q10","q11","q12","q13","q14","q15","q16","q17","q18","q19","q20"]); ?>
</head>
<body ng-controller="MainCtrl as main" class="cbp-spmenu-push" style="height:calc(100vh - 61px)">
	<div ui-view></div>

	<?php wp_print_scripts(["jquery","s3","s4","s5","s6","s7","s8","s9","s10","s11","s12","s13","s14","s15","s16","s17","s18","s19","s20", 
 			"s23","s24","s25","s26","s30",
			's32'
			
	]) ?>

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
    <form id="reportForm" method="post" style="display: none;" target="qrm_report">
	    <input type="hidden" name="reportParam1" />
	    <input type="hidden" name="reportParam2" />
    	<input type="hidden" name="prob" />
    	<input type="hidden" name="impact" />
    	<input type="hidden" name="manager" />
    	<input type="hidden" name="owner" />
    	<input type="hidden" name="subprojects" />
    	<input type="hidden" name="treated" />
    	<input type="hidden" name="untreated" />
    	<input type="hidden" name="inactive" />
    	<input type="hidden" name="active" />
    	<input type="hidden" name="pending" />
    	<input type="hidden" name="extreme" />
    	<input type="hidden" name="high" />
    	<input type="hidden" name="significant" />
    	<input type="hidden" name="moderate" />
    	<input type="hidden" name="low" />


    	<input type="hidden" name="projectID" />
    	<input type="hidden" name="userID" />
    	<input type="hidden" name="riskID" />
    	<input type="hidden" name="incidentID" />
    	<input type="hidden" name="reviewID" />   	
	    
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