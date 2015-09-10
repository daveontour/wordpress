<!-- bugerator_add_issue.php -->
<div class="add_project" id="add" >
    <h2 class="add_project">
        You are reporting an issue for <?PHP echo $project_name; ?>.
    </h2>
    <p class="add_project">Please fill out the information below as completely and accurately as possible.  
        We will get to it as soon as possible.</p>
    <form enctype="multipart/form-data" name="add_project" method="post" action="">
        <input type="hidden" name="add_issue_form" value="yes" />
        <input type="hidden" name="bugerator_add_nonce" value="<?PHP echo $nonce; ?>" />
        <table class="add_project">
            <tr class="add_title">
                <td class="title_left" >
                    Enter Title:
                </td>
                <td class="title_right" colspan="5">
                    <input name="project_title" size="60"/>
                </td>
            </tr>
            <tr class="add_project" >
                <td class="add_project_left" >
                    Issue type:
                </td>
                <td class="add_project_right">
                    <select name="project_type">
                        <?PHP
                            for($x=0;$x<count($types);$x++) {
                                echo "<option value='$x' >$types[$x]\r\n";
                            }
                        ?>
                    </select>
                </td>
                <?PHP if ($admin == true ) {?>
                <td class="add_project_left" >
                    Assign to version:
                </td>
                <td class="add_project_right" >
                    <select name="project_version">
                        <?PHP 
                        for ($x=0;$x<count($versions);$x++) {
                            echo "<option value='$x' ";
			    if ($default_version == $x)
				echo "SELECTED ";
			    echo ">$versions[$x]\r\n";
                        }
                        ?>
                    </select>
                </td>
                <td class="add_project_left" >
                    Priority:
                </td>    
                <td class="add_project_right" >
                    <select name="project_priority" >
                        <option value="1">1
                        <option value="2">2
                        <option value="3" selected >3
                        <option value="4">4
                        <option value="5">5
                    </select>
                </td>
                <?PHP } ?>
            </tr> 
	    <?PHP if ($admin == true): ?>
            <tr class="add_project">
                <td class="add_project_left" >
		    Project Status:
		</td>
		<td class="add_project_right" colspan="1">
		<select name="status" >
		<?PHP for($x=0;$x<count($statuses);$x++): ?>
		    <option value=<?PHP echo "$x >".$statuses[$x]."\r\n"; 
		endfor; ?>
		</select>		    
		</td>
                <td class="add_project_left" >
		    Assigned to:
		</td>
		<td class="add_project_right" colspan="3">
		    <select name="assign" >
			<option value="0">
			<?PHP
			foreach($developers as $developer) {
			    echo "<option value=".$developer['id'].
			    " >".$developer['name']."\r\n";
			}
			?>
		    </select>		    
		</td>
	    </tr>
	    <?PHP endif; ?>
            <?PHP if ($bugerator_upload_files == true ) {?>
            <tr class="add_project">
                <td class="add_project_left" >
                    Attach File (optional):
                </td>
                <td class="add_project_right" colspan="5" >
                    <input type="hidden" name="MAX_FILE_SIZE" value="<?PHP echo $bugerator_filesize; ?>" />
                    <input type="file" name="project_file" class="button_primary" />
                </td>
            </tr>
            <?PHP } ?>
            <tr class="add_project">
                <td class="add_project_left">
                    Description:
                </td>
                <td class="add_project_right" colspan="5" rowspan="2">
                    <textarea name="project_description" cols="60" rows="6" ></textarea>
                </td>
            </tr>
            <tr>
                <td class="add_project_left">
                    <input type="submit" value="Submit" class="button-primary" />
                </td>
            </tr>
        </table>
    </form>    
</div>