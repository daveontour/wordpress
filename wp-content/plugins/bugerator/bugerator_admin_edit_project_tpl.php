<!-- bugerator_admin_edit_project.php -->
<?PHP 
global $post;
if ("" == $post)
    echo "<div class=\"option_edit_project\">";
?>
<br><br>
<form action="" method="post" id="bugerator_edit_project_form" name="project_form" >
    <input type=hidden name="bugerator_admin_edit_project" value="<?PHP echo $bugerator_project_id; ?>" />
    <input type=hidden name="bugerator_admin_edit_nonce" value="<?PHP echo $nonce; ?>" />
    <table id="edit_project" class="bugerator form_table" >
	<tr class="bugerator form_table "  >
	    <td class="bugerator left_cell" colspan="5" >
		Project name:
	    </td>
	    <td class="bugerator right_cell" colspan="10" >
		<input name="project_name" value="<?PHP echo htmlspecialchars(stripslashes($result->name)); ?>" width="30" />

	    </td>
	    <td class="bugerator left_cell" colspan="5">
		Owner:
	    </td>
	    <td class="bugerator right_cell" colspan="10">
		<input name="project_owner" value="<?PHP echo $display_names[$result->owner]; ?>"
		       width="20" onKeyup ="user_suggestions(this.value)" />
		<span id="user_suggestions" ></span>
	    </td>
	</tr>
	<tr class="bugerator form_table" >
	    <td class="bugerator left_cell" colspan="5">
		Status:
	    </td>
	    <td class="bugerator right_cell" 
		<?PHP echo (!isset($version_list) ? "colspan=\"10\"" : "colspan=\"10\""); ?> >

		<select name="project_status" >
		    <?PHP
		    for ($x = 0; $x < count($statuses); $x++) {
			echo "<option value=\"$x\" ";
			if ($x == $result->status)
			    echo " SELECTED ";
			echo " >" . $statuses[$x] . "\r\n";
		    }
		    ?>
		</select>
	    </td>
	    <td class="bugerator left_cell" 
		<?PHP echo (!isset($version_list) ? "colspan=\"10\"" : "colspan=\"9\""); ?> >
		Delete Project:
	    </td>
	    <td class="bugerator right_cell" colspan="6" >
		<input type="radio" name="delete" value="no" <?PHP if($result->hidden ==0) echo "checked"; ?> /> No<br/>
		<input type="radio" name="delete" value="yes" <?PHP if($result->hidden ==1) echo "checked"; ?>  /> Yes<br/>
	    </td>
	</tr>
	    
	    
	    <?PHP if (isset($version_list)): ?>
	<tr class="bugerator form_table" >
    	    <td class="bugerator left_cell" colspan="8" >
    		Current Version:
    	    </td>
    	    <td class="bugerator right_cell" colspan="7" >
    		<select name="current_version" >
			<?PHP
			for ($x = 0; $x < count($version_list); $x++) {
			    echo "<option value=\"$x\" ";
			    if ($x == $result->current_version)
				echo " SELECTED ";
			    echo ">" . $version_list[$x] . "\r\n";
			}
			?>
    		</select>
    	    </td>
    	    <td class="bugerator left_cell" colspan="8" >
    		Default issue version:
    	    </td>
    	    <td class="bugerator right_cell" colspan="7" >
    		<select name="default_version" >
		    <option value="" > 
			<?PHP
			for ($x = 0; $x < count($version_list); $x++) {
			    echo "<option value=\"$x\" ";
			    if ($x == $result->default_version)
				echo " SELECTED ";
			    echo ">" . $version_list[$x] . "\r\n";
			}
			?>
    		</select>
    	    </td>
	</tr>

	    <?PHP endif; ?>

	    
	    <tr class="bugerator form_table" >
	    <td class="bugerator  bold" colspan="15" >
		Version list:
	    </td>
	    <td class="bugerator bold" colspan="15" >
		Version release date (goal) list:
	    </td>
	</tr>
	<?PHP for ($x = 0; $x < count($version_list) + 8; $x++): ?>
    	<tr class="bugerator form_table <?PHP if ($x > (count($version_list) + 2)) echo "hidden\" name=\"hidden"; ?>" >
    	    <td class="bugerator form_left" colspan="7" >
    		Version:
    	    </td>
    	    <td class="bugerator form_right" colspan="8" >
    		<input name="version_array[]" value="<?PHP if (isset($version_list[$x])) echo $version_list[$x]; ?>" width="6" />
    	    </td>
    	    <td class="bugerator form_left" colspan="3" >
    		Goal Date:
    	    </td>
    	    <td class="bugerator form_right" colspan="6" >
    		<input name="goal_dates[]" value="<?PHP if (isset($version_goal_list[$x]) and 0 != $version_goal_list[$x]) 
		    echo date($bugerator_date_format,strtotime($version_goal_list[$x])); ?>" />		    
    	    </td>
    	    <td class="bugerator form_right" colspan="6" >
    		Delete row: <input type="checkbox" name="version_goal_delete[]" value="<?PHP echo $x; ?>" />		    
    	    </td>
    	</tr>
    <?PHP if ($x == (count($version_list) + 2)): ?>
		<tr id="check_for_more" style="bugerator" >
		    <td class="bugerator" colspan="30" >
			<a onClick="change_css('hidden','display','inline'); 
			    document.getElementById('check_for_more').style.display='none';"
			   >Click for more rows.</a>
		    </td>
		</tr>

    <?PHP endif; endfor; ?>
        <tr class="bugerator form_table" >
	    <td class="bugerator  bold" colspan="7" >
		Description:
	    </td>
	    <td class="bugerator bold" colspan="22" >
                <textarea cols="80" rows="8" name="description"><?PHP
                    if(isset($result->description)) echo $result->description;
                    ?></textarea>
	    </td>
	</tr>
	<tr>
	    <td class="bugerator form_right" colspan="30">
		<input type="submit" value="Submit" class="button-primary" />
	    </td>
	</tr>
    </table>


</form>

</div> <!--bug_admin_wrapper -->