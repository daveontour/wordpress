<!-- bugerator_issue_detail.php -->
<script type="text/javascript" >
_JAVASCRIPT_

</script>
    <div class="error" id="error" >_ERROR_</div>
    <div class="success" id="success">_MESSAGE_</div>
<table class="bugerator bugerator_issue_detail" >
    <tbody>
	<tr class="bugerator bugerator_title" >
            <td class="bugerator bugerator_title" style="TITLE_STYLE" >
                Issue ID# _ID_
            </td>
	    <td colspan="2" class="bugerator bugerator_title" style="TITLE_STYLE" >
		_TITLE_
	    </td>
	</tr>
	<tr class="bugerator bugerator_status" >
	    <td class="bugerator bugerator_status" >
		Status: _STATUS_
	    </td>
	    <td class="bugerator bugerator_status" >
		Version: _VERSION_
	    </td>
	    <td class="bugerator bugerator_status" >
		Priority: _PRIORITY_
	    </td>
	</tr>
	<tr class="bugerator bugerator_user"  >
	    <td class="bugerator bugerator_user"  >
		Assigned to: _ASSIGNED_USER_
	    </td>
	    <td class="bugerator bugerator_user"<?PHP if (!$bugerator_upload_files) echo "colspan=2";?> >
		Submitted by: _SUBMITTED_USER_
	    </td>
	    <?PHP if ($bugerator_upload_files) { ?>
	    <td class="bugerator bugerator_user" >
		Attached file: _FILE_ATTACHED_
	    </td>
	    <?PHP } ?>
	</tr>
	<tr class="bugerator bugerator_status" >
	    <td class="bugerator bugerator_status" >
		Type: _TYPE_
	    </td>
	    <td class="bugerator bugerator_status" >
		Submitted:<br> _SUBMITTED_DATE_
	    </td>
	    <td class="bugerator bugerator_status" >
		Last Update:<br> _UPDATED_
	    </td>
	</tr>
	<tr class="bugerator bugerator_description" >
	    <td colspan="3" class="bugerator bugerator_description" >
		Description: _DESCRIPTION_
	    </td>
	</tr>
    </tbody>
</table>
<span id="hold_attachment" >_FILE_TEXT_</span>