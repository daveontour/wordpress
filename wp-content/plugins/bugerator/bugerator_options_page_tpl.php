<!-- bugerator_options_page.php -->
<?PHP if (isset($post->ID)): // this is from the app 
    $nav_tab = "bugerator_nav_tab";?>

<?PHP else: $nav_tab = "bugerator_option_nav_tab"; endif; ?>
<div class="bugerator_options_page">
<div class="bugerator_option_content" >
<div class="options_page_wrapper" ->
    <div class='bugerator_option_nav_tab_wrapper' >
	<div id="icon-themes" class="icon32" style="margin-top: -2px;"><br /></div>
	<h2 class="nav-tab-wrapper bugerator_option_nav_tab_wrapper" 
	    <?PHP //if(isset($post->ID)) echo "style='font-size: .8em;'";?> >
	    <?PHP 
	    foreach ($tabs as $tab => $name):
		$class = ($tab == $navigation ) ? " nav-tab-active $nav_tab"."_active " : "";
		?>

    	    <a class="nav-tab<?PHP echo "$class $nav_tab";?>" 
               <?PHP if (!isset($post->ID)): // this is from the dashboard ?>
    	       href="?page=bugerator_menu&tab=<?PHP echo $tab; ?>" >
                <?PHP else: 
                echo "href='$page&bugerator_nav=admin&bug_project=$project&active_tab=$tab&tab=$tab' >";
		   endif; echo $name; ?></a>      
	    <?PHP endforeach; ?>
	</h2>
        <?PHP if($navigation == "change_css"): ?>
        <h3 class="nav-tab-wrapper <?PHP echo $nav_tab;?>_wrapper"
	    <?PHP if(isset($post->ID)) echo "style='font-size: .6em;'";?>>
	    <?PHP 
	    foreach ($subtabs as $sub => $sub_name):
		$class = ($sub == $subnavigation ) ? " nav-tab-active $nav_tab"."_active" : "";
		?>
    	    <a class="nav-tab<?PHP echo "$class $nav_tab"; ?> " 
            <?PHP if (!isset($post->ID)): // this is from the dashboard ?>
    	       href="?page=bugerator_menu&tab=change_css&subtab=<?PHP echo $sub; ?>" >
                <?PHP else: 
                echo "href='$page&bugerator_nav=admin&bug_project=$project&active_tab=change_css&subtab=$sub' >";
		   endif; echo $sub_name; ?></a>      
	    <?PHP endforeach;  ?>
        </h3>
        <?PHP endif; ?>
    </div><!-- bugerator_nav_tab_wrapper -->
<?PHP echo $content; ?>
</div><!-- bugerator_content -->
</div><!-- bugerator_page -->
</div><!--options_page_wrapper-->
<?PHP if (isset($post->ID)): // this is from the options menu ?>
<!-- We don't double-div in the options menu so only undiv if that's where we are.-->

<?PHP else: ?>

<?PHP endif; ?>

<!-- end bugerator_options_page.php -->