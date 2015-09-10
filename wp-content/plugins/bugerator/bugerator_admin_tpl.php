<!-- bugerator_admin.php -->
<script type="text/javascript" >
    var please_wait_image = "<IMG src=\"<?PHP echo admin_url(); ?>images/loading.gif\" >"; 
    </script>
    <div class="admin_pages" id="projects" <?PHP if ($active_tab!="projects")echo"style='display: none;'";?>>
	<br>
	<h3><a id="new_project_link" onclick="show_new_project()" >
		Click to start a new project.
	    </a></h3>
	<span id="new_project_form" style="display: none;" >
	    <form name="new_project" method="post" action="" >
		<input type="hidden" name="new_project_form" value="yes" >
		<?PHP wp_nonce_field('bugerator_new_project','new_project_nonce'); ?>
		<table class="form_table" >
		    <tr class="form" >
			<td class="form_left" >
			    Project name:
			</td>
			<td class="form_right" >
			    <input name="project_name" size="20" onchange="check_project_name(this.value)"
				   onkeypress="Javascript: if (event.keyCode==13) check_name_submit(this.value);">
			    <span id="name_please_wait"></span>
			    <br><span id="ajax_name_response" ></span>
			</td>
		    </tr>
		    <tr class="form" >
			<td class="form_left" >
			    Project description:
			</td>
			<td class="form_right" >
			    <textarea name="description" cols="80" rows="8" ></textarea>
			</td>
		    </tr>
		    <tr class="form" >
			<td class="form_left" >
			    Project owner (WP display name):
			</td>
			<td class="form_right" >
			    <span id="project_owner_box" >
				<input name="project_owner" value="<?PHP echo $current_user->display_name ?>"
				       size="20" onkeyup="user_suggestions(this.value)" 
				       onkeypress="Javascript: if (event.keyCode==13) submit_new_project();">
			    </span>
			    <span id="user_dropdown" style="display: none;">
				<?PHP wp_dropdown_users(); ?>
			    </span>
			    <br><small>Start typing for suggestions or click <a onclick="user_dropdown()">here</a> for a list.</small>
			    <br><span id="user_suggestions" ></span>

			</td>
		    </tr>
		    <tr class="form" >
			<td class="form_left" >
			    Project status:
			</td>
			<td class="form_right" >
			    <select name="project_status">
				<?PHP
				for ($x = 0; $x < count($project_statuses); $x++) {
				    echo "<option value=$x >" . $project_statuses[$x] . "</option>\r\n";
				}
				?>
			    </select>
			</td>
		    </tr>
		    <tr class="form" >
			<td class="form_left" >
			    Current version:
			</td>
			<td class="form_right" >
			    <input name="current_version" size="20" 
				   onkeypress="Javascript: if (event.keyCode==13) submit_new_project();">
			</td>
		    </tr>
		    <tr class="form" >
			<td class="form_left" >
			    Next version:
			</td>
			<td class="form_right" >
			    <input name="next_version" size="20" 
				   onkeypress="Javascript: if (event.keyCode==13) submit_new_project();">
			</td>
		    </tr>
		    <tr class="form" >
			<td class="form_left" >
			    Next version anticipated release date:
			</td>
			<td class="form_right" >
			    <input name="next_version_date" size="20" onchange="date_format(this.value)" 
				   onkeypress="Javascript: if (event.keyCode==13) format_date_submit(this.value);">
			    <br/><span id="date_message"></span>
			</td>
		    </tr>
		    <tr class="form" >
			<td class="form_left" >
			    &nbsp;
			</td>
			<td class="form_right" >
			    <input type="button" class="button-primary" name="Submit" value="Submit" onClick ="submit_new_project();">
			</td>
		    </tr>
		</table>
	    </form>
	</span><!-- new project form -->
	<h3>
	    <a id="show_project_link" onclick="show_projects();" >
		Click to show a listing of projects.
	    </a>
	</h3>
	<span id="list_projects" style="display: none;" ></span><!-- list_projects -->

    </div><!-- project_maintenance" -->

    <div class="admin_pages" id="users" <?PHP if ($active_tab!="users")echo"style='display: none;'";?> >
	<?PHP echo $users_page; ?>
    </div><!-- user_maintenance" -->
    <div class="admin_pages" id="db" <?PHP if ($active_tab!="db")echo"style='display: none;'";?>>
	<?PHP if (!isset($status_msg)): ?>
	<br/>
        <a href="<?PHP echo $self; ?>&bugerator_nav=admin&active_tab=db&export_db_or_else=sure">Export the database </a><br/>
	<br/>
	<a href="<?PHP echo $self; ?>&bugerator_nav=admin&active_tab=db&kill_and_import_db=hopefully"
	   onclick="return confirm('Are you sure you want to overwrite your database?');" >Import the database</a>
	<span class="warning" >WARNING: This will overwrite your existing database!</span>
	<?PHP endif; ?>
        
    </div><!-- db_maintenance -->
    <div class="admin_pages" id="options" <?PHP if ($active_tab!="options")echo"style='display: none;'";?> >
        <?PHP echo $options_page; ?>
    </div><!-- option_page -->
    <div class="admin_pages" id="status_stuff" <?PHP if ($active_tab!="status_stuff")echo"style='display: none;'";?> >
        <?PHP echo $status_page; ?>
    </div><!-- status_page -->
    <div class="admin_pages" id="change_css"></div><!-- this is a tab but not made from here. -->

</div><!-- bug_admin_wrapper -->
