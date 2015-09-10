<!-- bugerator_issue_list -->
<script type="text/javascript" >

    /* bug_get_list(project_start = 0)
     * Refreshes the page with the proper size
     */
    function bug_get_list(page_start, num_things ,show_hidden) {
	// default values
	page_start = typeof page_start !== 'undefined' ? page_start : 0; 
	num_things = typeof num_things !== 'undefined' ? num_things : 10; 
	show_hidden = typeof show_hidden !== 'undefined' ? show_hidden : 0; 

	var new_page ='<?PHP echo $permalink; ?>&bugerator_nav=list&bug_project=<?PHP echo $project ?>&page_start='+
	    page_start +'&num_things=' + num_things + '&show_hidden=' + show_hidden;

	window.location = new_page;
    }

    function bug_change_view(page_start,show_hidden) {
	var selection = document.getElementById('page_view');
	var num_projects = selection.options[selection.selectedIndex].value;
	bug_get_list(page_start,num_projects,show_hidden);
    }

    // Javascript below is for the bulk edit section.
    // decide which spans to show	
    function show_list_additional() {
	var select_object=document.getElementById("list_action");
	var index = select_object.selectedIndex;
	var selection =  select_object.options[index].value;

	switch(selection) {
	    case "":
		hide_all_choices();
		break;
	    case "assign":
		show_assignment();
		break;
	    case "status":
		show_status();
		break;
	    case "priority":
		show_priority();
		break;
	    case "version":
		show_version();
		break;
	    default:
		hide_all_choices();
	}
    }
    // hide everything
    function hide_all_choices() {
	document.getElementById("assign_span").style.display="none";
	document.getElementById("status_span").style.display="none";
	document.getElementById("priority_span").style.display="none";
	document.getElementById("version_span").style.display="none";
    }
    function show_assignment() {
	hide_all_choices();
	document.getElementById("assign_span").style.display="inline";
    }
    function show_status() {
	hide_all_choices();
	document.getElementById("status_span").style.display="inline";
    }
    function show_priority() {
	hide_all_choices();
	document.getElementById("priority_span").style.display="inline";
    }
    function show_version() {
	hide_all_choices();
	document.getElementById("version_span").style.display="inline";
    }

</script>

<div class="error" id="error" ><?PHP echo $error; ?></div>
<div class="success" id="success"><?PHP echo $message; ?></div>
<span class="issue_list" >
    <?PHP echo $navigation_links; ?>
    <?PHP if ($is_admin): ?>
        <form name="issue_list" method="post" action="" >
            <input type="hidden" name="bulk_edit_issue_list" value="yes" >
            <input type="hidden" name="issue_list_nonce" value="<?PHP echo $nonce; ?>" >
	<?PHP endif; ?>
	<table class="sortable bugerator bugerator_list" id="issue_list"  >
	    <tr class="bugerator bugerator_list_header" >
		<th class="bugerator bugerator_list_header">
		    ID#
		</th>
		<th class="bugerator bugerator_list_header">
		    Status:
		</th>
		<th class="bugerator bugerator_list_header">
		    Type:
		</th>
		<th class="bugerator bugerator_list_header">
		    Title:
		</th>
		<th class="bugerator bugerator_list_header">
		    Submitter:
		</th>
		<th class="bugerator bugerator_list_header">
		    Assigned:
		</th>
		<th class="bugerator bugerator_list_header">
		    Date:
		</th>
		<th class="bugerator bugerator_list_header">
		    Priority:
		</th>
		<th class="bugerator bugerator_list_header">
		    Version:
		</th>
		<?PHP if ($is_admin): ?>
    		<th class="bugerator bugerator_list_header">
    		    Select:
    		</th>
		<?PHP endif; ?>
	    </tr>
	    <?PHP foreach ($output_array as $row) { ?>
    	    <tr class="bugerator bugerator_list <?PHP //echo $row['completed'] ?>" style="<?PHP echo $row['style'] ?>" >
    		<td class="bugerator bugerator_list" style="<?PHP echo $row['style'] ?>" >
			<?PHP echo $row['link'] . $row['id']; ?></a>
    		</td>
    		<td class="bugerator bugerator_list" style="<?PHP echo $row['style'] ?>" >
			<?PHP echo $row['status']; ?>
    		</td>
    		<td class="bugerator bugerator_list" style="<?PHP echo $row['style'] ?>" >
			<?PHP echo $row['type']; ?>
    		</td>
    		<td class="bugerator bugerator_list" style="<?PHP echo $row['style'] ?>" title="<?PHP echo $row['description'];?>" >
			<?PHP echo $row['link'] . $row['title']; ?></a>
    		</td>
    		<td class="bugerator bugerator_list" style="<?PHP echo $row['style'] ?>" >
			<?PHP echo $row['submitter']; ?>
    		</td>
    		<td class="bugerator bugerator_list" style="<?PHP echo $row['style'] ?>" >
			<?PHP echo $row['assigned']; ?>
    		</td>
    		<td class="bugerator bugerator_list" style="<?PHP echo $row['style'] ?>" >
			<?PHP echo $row['date']; ?>
    		</td>
    		<td class="bugerator bugerator_list" style="<?PHP echo $row['style'] ?>" >
			<?PHP echo $row['priority']; ?>
    		</td>    	    
    		<td class="bugerator bugerator_list" style="<?PHP echo $row['style'] ?>" >
			<?PHP echo $row['version']; ?>
    		</td>
		    <?PHP if ($is_admin): ?>
			<td class="bugerator bugerator_list" style="<?PHP echo $row['style'] ?>" >
			    <input type="checkbox" name="edit_list_select[]" value="<?PHP echo $row['id']; ?>" >
			</td>
		    <?PHP endif; ?>
    	    </tr>
	    <?PHP } ?>
	</table>
	<?PHP if ($is_admin): ?>
            With selected: 
            <select id="list_action" name="list_action" onChange="show_list_additional()" >
    	    <option value=" " />&nbsp;
    	    <option value="assign" />Assign to user
    	    <option value="status" />Change status
    	    <option value="priority" />Change priority
    	    <option value="version" />Change version
    	    <option value="delete" />Delete
            </select>
            <span id="assign_span" style="display: none;">
    	    <select name="new_assigned_user" >
    		<option value="0" >
			<?PHP
			foreach ($big_user_list as $id => $name):
			    echo "\t\t\t<option value=\"$id\" ";
			    if ($id == $user_id)
				echo " SELECTED ";
			    echo " >" . $name . "\r\n";
			endforeach;
			?>
    	    </select>
            </span>
            <span id="status_span" style="display: none;">
    	    <select name="new_status" >
		    <?PHP
		    foreach ($statuses_in_use as $status_num) {
			echo "<option value=\"$status_num\" >" . $statuses[$status_num];
		    }
		    ?>
    	    </select>
            </span>
            <span id="priority_span" style="display: none;">
    	    <select name="new_priority" >
    		<option value="1">1
    		<option value="2">2
    		<option value="3">3
    		<option value="4">4
    		<option value="5">5
    	    </select>
            </span>
            <span id="version_span" style="display: none;">
    	    <select name="new_version" >
		    <?PHP
		    for ($x = 0; $x < count($version_list); $x++):
			echo "<option value=\"" . $x . "\">" . $version_list[$x] . "\r\n";
		    endfor;
		    ?>
    	    </select>
            </span>
            <input value="Submit" type="Submit" class="button-primary" />
            <form/>
	<?PHP endif; ?>
</span><!-- issue_list -->
