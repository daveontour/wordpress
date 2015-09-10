<script type="text/javascript" >
    var please_wait_image = "<IMG src=\"<?PHP echo admin_url(); ?>images/loading.gif\" >"; 
    var user_nonce = "<?PHP echo $nonce; ?>";
    /* remove_user(user,key)
     * simple ajax takes in a user type (dev or admin) and the key and removes them
     */
    function remove_user(user,key) {
	//put together the post data
	var data = {
	    action: 'bugerator_remove_global_user',
	    deleteme: user,
	    id: key,
	    security: user_nonce
	};
	document.getElementById("td_"+user+"_names").innerHTML = please_wait_image;
	
	jQuery.post(ajaxurl, data, function(response) {
	    document.getElementById("td_"+user+"_names").innerHTML = response;
	});
	
	
    }
    /* get_name_list(which_list)
     * gets a formatted select option list via ajax for adding a user
     */
    function get_name_list(which_list) {
	//put together the post data
	var data = {
	    action: 'bugerator_get_name_list',
	    type: which_list,
	    security: user_nonce
	};	
        document.getElementById("name_list_"+which_list).innerHTML = please_wait_image;

	jQuery.post(ajaxurl, data, function(response) {
	    document.getElementById("name_list_"+which_list).innerHTML = response;
	});
    }
    /* add_user(which_list)
     * adds a user to the admins or developers depending on which_list
     */
    function add_user(which_list) {
        var select_id = "add_"+which_list;
        var select_object=document.getElementById(select_id);
        var index = select_object.selectedIndex;
        var selection =  select_object.options[index].value;
	document.getElementById("td_"+which_list+"_names").innerHTML = please_wait_image;	
	document.getElementById("name_list_"+which_list).innerHTML = "";
	//put together the post data
	var data = {
	    action: 'bugerator_add_global_user',
	    type: which_list,
	    id: selection,
	    security: user_nonce
	};	

	jQuery.post(ajaxurl, data, function(response) {
	    document.getElementById("td_"+which_list+"_names").innerHTML = response;
	});	
    }
    // todo you are here - you have 3 javascript functions to add with ajax.
</script>
<h2 class="bugerator bugerator_users" >Add or Edit the developers and admins on all projects.</h2>
<h3 class="bugerator bugerator_users" >
    You can also have a user only be a developer or admin on a specific project in the
    project edit screen.</h3>
<table class="bugerator bugerator_users" style="min-width: 40%;">
    <tr class="bugerator bugerator_users" >
	<th class="bugerator bugerator_users" style="width: 50%;">
	    Global Admins
	</th>
	<th class="bugerator bugerator_users" style="width: 50%;">
	    Global Developers
	</th>
    </tr>
    <tr class="bugerator bugerator_users" >
	<td class="bugerator bugerator_users" id="td_admins_names" >
	    <?PHP echo $admin_names;
	    ?>
	</td>
	<td class="bugerator bugerator_users" id="td_developers_names" >
	    <?PHP echo $developer_names;
	    ?>
	</td>
    </tr>
    <tr class="bugerator bugerator_users" >
	<td class="bugerator bugerator_users" >
	    <a onclick="get_name_list('admins')" >Click to add an admin.</a><br/>
	    <span id="name_list_admins" ></span>
	</td>
	<td class="bugerator bugerator_users" >
	    <a onclick="get_name_list('developers')" >Click to add a developer.</a><br/>
	    <span id="name_list_developers" ></span>
	</td>
    </tr>

</table>