<!-- bugerator_updates_display_tpl.php -->
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
    </tr>
    <tr class="bugerator bugerator_list " style="<?PHP echo $header_row['style'] ?>" >
        <td class="bugerator bugerator_list" style="<?PHP echo $header_row['style'] ?>" >
                <?PHP echo $header_row['link'] . $header_row['id']; ?></a>
        </td>
        <td class="bugerator bugerator_list" style="<?PHP echo $header_row['style'] ?>" >
                <?PHP echo $header_row['status']; ?>
        </td>
        <td class="bugerator bugerator_list" style="<?PHP echo $header_row['style'] ?>" >
                <?PHP echo $header_row['type']; ?>
        </td>
        <td class="bugerator bugerator_list" style="<?PHP echo $header_row['style'] ?>" title="<?PHP echo $header_row['description'];?>" >
                <?PHP echo $header_row['link'] . $header_row['title']; ?></a>
        </td>
        <td class="bugerator bugerator_list" style="<?PHP echo $header_row['style'] ?>" >
                <?PHP echo $header_row['submitter']; ?>
        </td>
        <td class="bugerator bugerator_list" style="<?PHP echo $header_row['style'] ?>" >
                <?PHP echo $header_row['assigned']; ?>
        </td>
        <td class="bugerator bugerator_list" style="<?PHP echo $header_row['style'] ?>" >
                <?PHP echo $header_row['date']; ?>
        </td>
        <td class="bugerator bugerator_list" style="<?PHP echo $header_row['style'] ?>" >
                <?PHP echo $header_row['priority']; ?>
        </td>    	    
        <td class="bugerator bugerator_list" style="<?PHP echo $header_row['style'] ?>" >
                <?PHP echo $header_row['version']; ?>
    </tr>
    <tr  class="bugerator bugerator_list ">
        <td colspan="2" class="bugerator bugerator_comment" >
            Date:
        </td>
        <td colspan="5" class="bugerator bugerator_comment" >
            Comment:
        </td>
        <td class="bugerator bugerator_comment" >
            Submitted by:
        </td>
        <td class="bugerator bugerator_comment" >
            File:
        </td>
        
    </tr>
    <?PHP foreach($output_array as $comment): ?>
    <tr  class="bugerator bugerator_list ">
    	<td class="bugerator bugerator_comment" colspan=2>
		    <?PHP
		    echo date($bugerator_date_format, strtotime($comment->time));
		    ?>
    	</td>
        <td class="bugerator bugerator_comment" colspan="5" >
    	    <?PHP
	$thiscomment = $comment->notes;
	echo stripslashes($thiscomment);
	    ?>
    	</td>
        <td class="bugerator bugerator_comment">
            <?PHP
            if ($comment->user > 0) {
                $thisuser = get_userdata($comment->user);
                echo $thisuser->display_name;
            } else {
                echo "Anonymous";
            }
            ?>
    	</td>
        <?PHP if ($bugerator_upload_files and $comment->filename <> "0"): ?>
		<td class="bugerator bugerator_comment"  >
		    <?PHP echo "<a onclick = 'show_file_notes_$comment->id();'>" .
			urldecode(substr($comment->filename, 6)) . "</a>"; ?>
		</td>
        <?PHP else: ?>
            <td class="bugerator bugerator_comment">
                &nbsp;
            </td>
        <?PHP endif; ?>
    </tr>
    <?PHP endforeach; ?>
</table>  
<!-- end bugerator_updates_display -->