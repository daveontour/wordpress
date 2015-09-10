<!-- bugerator_comment_display.php -->
<?PHP foreach ($comments as $comment) { ?>
<script type="text/javascript" >
    <?PHP if ($comment->filename <> "0")
	echo $this->display_attachment('notes', $comment->id,$comment->filename);
    ?>
</script>
<br/>
    <h4 class="comment_display">Note ID# <?PHP echo $comment->id ?>:</h4>
    <?PHP
    if ($im_an_admin === true):
        echo "<a href='$permalink&bugerator_nav=comment&bug_project=$bugerator_project_id&issue=$issue_id&comment_delete=$comment->id' style='text-align: center;' >Delete</a>";
        echo "<div class='admin_edit' style='float:right;' >\r\n" .
	"<a href='$permalink&bugerator_nav=comment&bug_project=$bugerator_project_id&issue=$issue_id&comment_edit=$comment->id' >Edit</a>" .
	"</div>";
        endif;
    ?>
    <table class="bugerator" >
        <tr class="bugerator bugerator_comment" >
    	<td class="bugerator bugerator_comment comment_left"  >
    	    User:
    	</td>
    	<td class="bugerator bugerator_comment" >
		<?PHP
		if ($comment->user > 0) {
		    $thisuser = get_userdata($comment->user);
		    echo $thisuser->display_name;
		} else {
		    echo "Anonymous";
		}
		?>
    	</td>
    	<td class="bugerator bugerator_comment comment_left"  >
    	    Submitted:
    	</td>
    	<td class="bugerator bugerator_comment" 
	    <?PHP if (!$bugerator_upload_files or $comment->filename == "0") echo "colspan=3"; ?>  
    	    >
		    <?PHP
		    echo date($bugerator_date_format, strtotime($comment->time));
		    ?>
    	</td>
	    <?PHP if ($bugerator_upload_files and $comment->filename <> "0"): ?>
		<td class="bugerator bugerator_comment comment_left" >
		    File attached:
		</td>
		<td class="bugerator bugerator_comment"  >
		    <?PHP echo "<a onclick = 'show_file_notes_$comment->id();'>" .
			urldecode(substr($comment->filename, 6)) . "</a>"; ?>
		</td>
	    <?PHP endif; ?>
        </tr>
        <tr class="bugerator bugerator_comment" >
    	<td class="bugerator bugerator_comment comment_left" >
    	    Notes:
    	</td>
    	<td class="bugerator bugerator_comment" colspan="5" >
    	    <?PHP
	$thiscomment = $comment->notes;
	echo stripslashes($thiscomment);
	    ?>
    	</td>
        </tr>
    </table>
    <span id="file_attach_notes_<?PHP echo $comment->id;?>"></span>
<?PHP } ?>