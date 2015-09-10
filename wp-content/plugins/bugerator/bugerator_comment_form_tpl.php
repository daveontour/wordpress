<!-- bugerator_comment_form.php -->
<div class="issue_links" style="float: right; ">
<?PHP if(true == $bugerator_anonymous_comments or true == $am_I_logged_in): ?>
    <a id="comment_link" href="#" onClick="document.getElementById('comment_div').style.display = 'block';
    document.getElementById('comment_link').style.display = 'none';
    return false;" >Add a Comment.</a></div>
<?PHP endif; ?>
<div id="comment_div" style="display: none; width: 100%; float: left;" >
    <form enctype="multipart/form-data" name="add_project" method="post" action="">
	<input type="hidden" name="bugerator_comment_add" value="yes">
	<input type="hidden" name="issue_id" value="<?PHP echo $result->id;?>" >
	<input type="hidden" name="bugerator" value="<?PHP echo $nonce ?>" >
	<table class="bugerator bugerator_comment_add" >
	    <?PHP
	    global $bugerator_upload_files;
	    if ($bugerator_upload_files) { ?>
	    <tr class="bugerator bugerator_comment_add" >
		<td class="bugerator bugerator_comment_add form_left" >
		    Attach File:
		</td>
		<td class="bugerator bugerator_comment_add form_right" >
                    <input type="hidden" name="MAX_FILE_SIZE" value="<?PHP echo $bugerator_filesize; ?>" />
		    <input type="file" name="comment_file" class="button_primary" />
		</td>
	    </tr>
	    <?PHP } ?>
	    <tr class="bugerator bugerator_comment_add" >
		<td class="bugerator bugerator_comment_add form_left" >
		    Notes:
		</td>
		<td class="bugerator bugerator_comment_add form_right" >
		    <textarea name="comments" cols="30" rows="5" ></textarea>
		</td>
	    </tr>	    
	    <tr class="bugerator bugerator_comment_add" >
		<td class="bugerator bugerator_comment_add" colspan="8" >
		    <input type="submit" value="Submit" class="button-primary" >
		</td>
	    </tr>
	</table>
    </form>
</div>