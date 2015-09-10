<!-- bugerator_admin_nav.php -->
<script type="text/javascript">
<?PHP   echo "var post_id = \"$permalink\";\r\n"; ?>
    var admin_nav_tags = new Array(
<?PHP
$count = count($admin_tabs);
$x = 0;
foreach (array_keys($admin_tabs) as $tab) {
    echo '"'.$tab.'"';
    $x++;
    if ($x < $count)
	echo ",";
}

?> );
    var please_wait_image = "<IMG src=\"<?PHP echo admin_url(); ?>images/loading.gif\" >"; 


   var nonce = "<?PHP echo $nonce; ?>";
</script>
<div class="bug_admin_wrapper" >
    <div class="bug_admin_nav_wrapper"  >
	<ul class="bug_admin_nav_wrapper" >
	    <?PHP
// Assign $admin_tabs before including this page. This is a list of tabs
// $active_tab needs to be assigned too which is the $_GET call
	    foreach ($admin_tabs as $tab => $name) {
		if ($tab == $active_tab) {
		    $class = " bug_admin_tab_active ";
		} else {
		    $class = "";
		}
		if ($tab == "change_css")
		    echo "	<li class='bug_admin_tab $class' id=li$tab ><a href='".
			$bugerator_proper_admin_url."&bugerator_nav=admin&bug_project=1&issue=3&active_tab=change_css'>$name</a>\r\n";
		elseif ("db" == $tab)
		    echo "	<li class='bug_admin_tab $class' id=li$tab ><a href='".
			$bugerator_proper_admin_url."&bugerator_nav=admin&bug_project=1&issue=3&active_tab=db'>$name</a>\r\n";
                elseif ("projects" == $tab and isset($_GET['bugerator_edit_project'])) 
                    echo "	<li class='bug_admin_tab $class' id=li$tab ><a href='".
			$bugerator_proper_admin_url."&bugerator_nav=admin&bug_project=1&issue=3&active_tab=projects'>$name</a>\r\n";
		else
		    echo "	<li class='bug_admin_tab $class' id=li$tab >".
			"<a onclick='show_admin_tab(\"$tab\")' >$name</a>\r\n";
	    }
	    ?>
	</ul>
    </div><!-- bug_admin_nav_wrapper -->
    <div class="error" id="error" ><?PHP echo $error_msg; ?></div>
    <div class="success" id="success"><?PHP echo $success_msg; ?></div>
    <div class="status_msg" id ="status_msg"><?PHP if (isset($status_msg)) echo $status_msg; ?></div>
    