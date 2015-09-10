<!-- bugerator_admin_edit_project_admins.php -->
    <div class="error" id="error" ><?PHP echo $error_msg; ?></div>
    <div class="success" id="success"><?PHP echo $success_msg; ?></div>
    <div class="status_msg" id ="status_msg"><?PHP if (isset($status_msg)) echo $status_msg; ?></div>
<table class="bugerator bugerator_admin" style="border: none;" >
    <tr class="bugerator bugerator_admin" style="border: none;" >
	<th class="bugerator bugerator_admin" style="border: none; text-align: center;" colspan="2" >
	    Current list of <?PHP echo $field; ?>
	</th>
    </tr>
    <?PHP if (count($edit_list) > 0): 
	foreach ($edit_list as $admin_id): ?>
        <tr class="bugerator bugerator_admin no_border" style="border: none;" >
    	<td class="bugerator bugerator_admin form_left no_border" style="border: none;"  >
		<?PHP echo "$userlist[$admin_id]  "; ?>

    	</td>
    	<td class="bugerator bugerator_admin form_right no_border" style="border: none;"  >
		<?PHP
		if ($bug_user->ID != $admin_id or $field == "developers")
		    echo "<a id='$id' onClick='delete_$field($admin_id,$id)' >Remove</a>";
		?>
    	</td>
        </tr>
    <?PHP endforeach;
    else: ?>
        <tr class="bugerator bugerator_admin no_border" style="border: none;" >
    	<td class="bugerator bugerator_admin no_border center" style="border: none; text-align: center;" colspan="2" >
	    No <?PHP echo $field; ?> in project.
	</td>
	</tr>
	<?PHP endif; ?>
    <tr class="bugerator bugerator_admin no_border" style="border: none;" >
	<th class="bugerator bugerator_admin no_border center" style="border: none; text-align: center;" colspan="2" >
	    Select new <?PHP echo substr($field, 0, -1); ?>
	</th>
    </tr>
    <tr class="bugerator bugerator_admin no_border" style="border: none;" >
	<td class="bugerator bugerator_admin form_left no_border" style="border: none;"  >
	    <select id='add_new_<?PHP echo "$field"."_$id"; ?>'>
		<option value='0'> 
		    <?PHP
		    foreach ($userlist as $thisid=>$thisuser) {
		    if (array_search($thisid,$edit_list)=== false)
			echo "<option value='$thisid' >$thisuser \r\n";
		    }
		    ?>
	    </select>

	</td>
	<td class="bugerator bugerator_admin form_right no_border" style="border: none;"  >
	    <a onclick="<?PHP echo "add_new_$field($id)";?>" >Add as <?PHP echo substr($field,0,-1);?></a>
	</td>
    </tr>
</table>