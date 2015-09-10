<!-- bugerator_issue_edit.php -->
<div class="issue_links" style="float: left; width: 33%;">
<a id="edit_link" href="#" onClick="document.getElementById('developer_span').style.display = 'inline';
    document.getElementById('edit_link').style.display = 'none';
    return false;">Edit</a></div>
<span id="developer_span" style="display: none;" >
    <form name="edit_issue" method="post" action="">
	<input type="hidden" name="bugerator_issue_edit" value="yes">
	<input type="hidden" name="issue_id" value="<?PHP echo $result->id;?>" >
	<input type="hidden" name="bugerator" value="<?PHP echo $nonce ?>" >
	<table class="bugerator bugerator_issue_edit" >
	    <tr class="bugerator bugerator_issue_edit" >
		<td class="bugerator bugerator_issue_edit form_left" >
		    Status:
		</td>
		<td class="bugerator bugerator_issue_edit form_right" >
		<select name="status" >
		<?PHP for($x=0;$x<count($statuses);$x++) { ?>
		    <option value=<?PHP if ($x == $result->status) 
			echo "$x selected >".$statuses[$x]; 
		    else    
			echo "$x >".$statuses[$x]."\r\n"; 
		} ?>
		</select>
		</td>
		<td class="bugerator bugerator_issue_edit form_left" >
		    Priority:
		</td>
		<td class="bugerator bugerator_issue_edit form_right" >
		    <select name="priority">
			<?PHP
			for ($x=1;$x<6;$x++) {
			    echo "<option value=$x ";
			    if ($x == $result->priority) 
				echo " selected ";
			    echo " >$x \r\n";
			}
			?>
		    </select>
		</td>
		<td class="bugerator bugerator_issue_edit" >
		    Assign:
		</td>
		<td class="bugerator bugerator_issue_edit" >
		    <select name="assign" >
			<option value="0">
			<?PHP
			foreach($developers as $developer) {
			    echo "<option value=".$developer['id'];
			    if ($developer['id'] == $result->assigned)
				echo " selected ";
			    echo " >".$developer['name']."\r\n";
			}
			?>
		    </select>
		</td>
		<td class="bugerator bugerator_issue_edit form_left" >
		    Version:
		</td>
		<td class="bugerator bugerator_issue_edit form_right" >
		    <select name="version" >
			<?PHP
			for ($x=0;$x<count($versions);$x++) {
			    echo "<option value=".$x;
				if ($result->version == $x) 
				    echo " selected ";
			    echo ">".$versions[$x]."\r\n";;
			}
			?>
		    </select>
		</td>
	    </tr>
	    <tr class="bugerator bugerator_issue_edit" >
                <td class="bugerator bugerator_issue_edit form_left" >
		    Type:
		</td>
		<td class="bugerator bugerator_issue_edit form_right" >
                    <select name="type" >
                        <?PHP for($x=0;$x<count($types);$x++):
                            echo "<option value='$x' ";
                            if ($result->type == $x)
                                echo " selected ";
                            echo ">".$types[$x]."\r\n";
                        endfor; ?>
                    </select>
                </td>
		<td class="bugerator bugerator_issue_edit" colspan="6" >
		    <input type="submit" value="Submit" class="button-primary" >
		</td>
	    </tr>
	</table>
    </form>
</span>