<!-- bugerator_comment_edit.php -->
<div class="comment_edit" >
    <form name="edit_issue" method="post" action="">
        <input type="hidden" name="comment_edit_finished" value="true" >
        <input type="hidden" name="bugerator_comment_edit" value="<?PHP echo $comment_nonce ?>" >
        <table class="bugerator bugerator_comment_edit" >
            <tr class="bugerator bugerator_comment_edit" >
                <td class="bugerator bugerator_comment_edit form_left" >
                    Comment ID:
                </td>
                <td class="bugerator bugerator_comment_edit form_right" >
                    <?PHP echo $row->id; ?>
                </td>
                <?PHP if ($row->filename != "0"): ?>
                    <td class="bugerator bugerator_comment_edit form_left" >
                        Filename:
                    </td>
                    <td class="bugerator bugerator_comment_edit form_right" >
                        <?PHP echo substr($row->filename, 6); ?>
                    </td>
                    <td class="bugerator bugerator_comment_edit form_left" >
                        Check to delete file:
                    </td>
                    <td class="bugerator bugerator_comment_edit form_right" >
                        <input type="checkbox" name="delete_file" value="yes"/>
                    </td>
                <?PHP else: ?>
                    <td class="bugerator bugerator_comment_edit"" colspan="4" >
                        No file attached.
                </td>
            <?PHP endif; ?>
        </tr>
        <tr class="bugerator bugerator_comment_edit" >
            <td class="bugerator bugerator_comment_edit form_left" >
                Edit Note:
            </td>
            <td class="bugerator bugerator_comment_edit form_left" >
                &nbsp;
            </td>
            <td class="bugerator bugerator_comment_edit form_left"  >
                Comment Author:
            </td>
            <td class="bugerator bugerator_comment_edit"   >
                <select name="comment_author" />
        <option value="0" />Anonymous
        <?PHP foreach ($results as $result): ?>
            <option value="<?PHP echo $result->ID ?>" ><?PHP
        echo $result->display_name;
    endforeach;
        ?>
            </select>
            </td>
        <td class="bugerator bugerator_comment_edit form_left" >
            Secret edit:
        </td>
        <td class="bugerator bugerator_comment_edit form_left"  >
            <input type="checkbox" name="secret_edit" value="yes"/>
        </td>
        </tr> 
        <tr class="bugerator bugerator_comment_edit" >
            <td class="bugerator bugerator_comment_edit textarea_height form_right" colspan="6" rowspan="1"  >
                <textarea name="comment_new" rows="6" cols="60" ><?PHP echo stripslashes($row->notes); ?></textarea>
            </td>
        </tr>
        <tr class="bugerator bugerator_comment_edit" >
            <td class="bugerator bugerator_comment_edit textarea_height form_right" colspan="6" rowspan="1"  >
                <input type="submit" value="Submit" class="button-primary" />
            </td>
        </tr>
    </table>
</form>
</div>