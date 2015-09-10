<!-- bugerator_admin_view_projects.php -->
<table class="list_projects" >
    <tr class="list_projects" >
	<td colspan ="2" class="header_left list_projects " >
	    _PROJECT_NAME_ 
	</td>
	<td colspan ="2" class=" list_projects " >
	    _PROJECT_SORT_ 
	</td>
	<td colspan ="2" class="header_right list_projects  text_right" >
	    <a href="_EDIT_LINK_">Click to edit the project.</a>
	</td>

    </tr>
    <tr class="list_projects" >
	<td class="list_projects left_cell">Owner:</td>
	<td class="list_projects right_cell">_NAME_</td>
	<td class="list_projects left_cell">Version:</td>
	<td class="list_projects right_cell">_CURRENT_VERSION_</td>
	<td class="list_projects left_cell">Version Date:</td>
	<td class="list_projects right_cell">_VERSION_DATE_</td>
    </tr>
    <tr class="list_projects" >
	<td class="list_projects left_cell">Status:</td>
	<td class="list_projects right_cell">_STATUS_</td>
	<td class="list_projects left_cell">Next Version:</td>
	<td class="list_projects right_cell">_NEXT_VERSION_</td>
	<td class="list_projects left_cell">Release date goal:</td>
	<td class="list_projects right_cell">_RELEASE_</td>
    </tr>    <tr class="list_projects" >
	<td class="list_projects left_cell">Description:</td>
	<td class="list_projects right_cell" colspan="5">_DESCRIPTION_</td>
    </tr>
    <tr class="list_projects" >
	<td colspan ="3" class="list_projects dev_header" >
	    Project Administrators:<br/>
	    _ADMINS_
	</td>
	<td colspan ="3" class="list_projects dev_header" >
	    Project Developers:<br/>
	    _DEVELOPERS_

	</td>
    </tr>
    <tr class="list_projects" >
	<td colspan ="3" class="list_projects list_devs" >
	    <a id="a_edit_admin__ID_" onclick="edit_admins(_ID_)">Click to add/edit administrators.</a>
	    <span id="edit_admins_span__ID_" ></span>
	</td>
	<td colspan ="3" class="list_projects list_devs" >
	    <a id="a_edit_devs__ID_" onclick="edit_devs(_ID_) ">Click to add/edit developers.</a>
	    <span id="edit_devs_span__ID_" ></span>
	</td>
    </tr>

</table>
<br/>
