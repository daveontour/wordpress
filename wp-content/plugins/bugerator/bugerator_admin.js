/* show_admin_tab
 * Takes in the tab we want and shows it
 * uses a variable declared on the page to hide all other tags
 */
function show_admin_tab(tab) {
    admin_nav_tags.forEach(hide_all_tabs);
    document.getElementById(tab).style.display = "block";
    document.getElementById("li"+tab).className = "bug_admin_tab bug_admin_tab_active";
}
/* hide_all_tabs(value, index)
 * quickly hide the tabse before displaying a new one.
 */
function hide_all_tabs(value) {
    var element = document.getElementById(value);
    if (typeof(element) != 'undefined' && element != null) {
        document.getElementById(value).style.display = "none";
        document.getElementById("li"+value).className = "bug_admin_tab";
        document.getElementById("error").innerHTML = "";
        document.getElementById("success").innerHTML = "";
        document.getElementById("status_msg").innerHTML = "";
    }
    
}
/* show_new_project()
 * This displays the form for the new project.
 */
function show_new_project() {
    document.getElementById("new_project_form").style.display = "inline";
    document.new_project.project_name.focus();
}
/* show_projects()
 * This displays a list of existing projects.
 */
function show_projects() {
    document.getElementById("new_project_form").style.display = "none";
    document.getElementById("list_projects").style.display = "inline";
    bug_get_list(); // using ajax to get it
}

/* user_dropdown()
 * uses an ajax call to get a dropdown of all the user display names & ids
 */
function user_dropdown() {
    document.getElementById("project_owner_box").style.display = "none";
    document.getElementById("user_dropdown").style.display = "inline";
    document.forms.new_project.project_owner.value = "";
    
}

/* date_format
 * takes in the value from the input form named
 * and makes it a valid date
 */
function date_format(input_date) {
    var date = new Date(input_date);
    var output = ("0" + (date.getMonth()+1)).slice(-2)+"/"+("0" + (date.getDate())).slice(-2)+"/"+
    (date.getFullYear());
    document.forms.new_project.next_version_date.value = output;
}
/* submit_new_project()
 * Quick check of status
 * then submittal.
 */
function submit_new_project() {
    if ( (document.getElementById("ajax_name_response").innerHTML != "" &&
	document.getElementById("ajax_name_response").innerHTML != String.fromCharCode(10) )
	 ||
	document.forms.new_project.project_name.value == "" ) {
	return;
    }
    document.forms["new_project"].submit();
}
    /* check_name_submit()
 * checks the name before submitting
 */
    function check_name_submit(project_name) {
	check_project_name(project_name);
	submit_new_project();
    }
    /* format_date_submit()
 * formats the date before submitting.
 */
    function format_date_submit(date) {
	date_format(date);
	submit_new_project();
    }

    /* change css
 * use a name field to change the css tag so it is no longer hidden.
 */ 
    function change_css(css_name) {
	var stuff = document.getElementsByName(css_name);
	for (i in stuff) {
	    if (stuff[i].className == undefined )
		continue;
	    stuff[i].className = stuff[i].className.replace( /(?:^|\s)hidden(?!\S)/ , '' )
	}
    }




    /*****************************************
 * Ajax functions below
 *****************************************/ 
    /* check_project_name()
 * an Ajaxy script to make sure our project name is unique.
 */
    function check_project_name(name) {
	var data = {
	    action: 'bugerator_project_name',
	    project_name: name,
	    security: nonce
	};
	// show the please wait
	document.getElementById("name_please_wait").innerHTML = please_wait_image;

    
	jQuery.post(ajaxurl, data, function(response) {
	    document.getElementById("ajax_name_response").innerHTML = response;
	    document.getElementById("name_please_wait").innerHTML = "";
	});

    }




    /* user_suggestions()
 * Ajax function to suggest possible user names
 */
    function user_suggestions(display_name) {

	var data = {
	    action: 'bugerator_display_name',
	    display_name: display_name,
	    security: nonce
	};


    
	jQuery.post(ajaxurl, data, function(response) {
	    document.getElementById("user_suggestions").innerHTML = response;
	});
    }
    /* fill_in_name()
 * After the user suggestions this will fill the name in the display box
 * and kill the suggestions.
 */
    function fill_in_name(name_to_fill) {
	document.getElementById("user_suggestions").innerHTML = "";
	if(document.forms.new_project != undefined )
	    document.forms.new_project.project_owner.value = name_to_fill;
	if(document.forms.project_form != undefined )
	    document.forms.project_form.project_owner.value = name_to_fill;
    }

    /* bug_get_list(project_start = 0)
 * makes an ajax call to the PHP server which pieces together the tables for the different
 * projects and returns them complete with potential links and such.
 */
    function bug_get_list(project_start, num_projects ,show_hidden ) {
	// default values
	project_start = typeof project_start !== 'undefined' ? project_start : 0; 
	num_projects = typeof num_projects !== 'undefined' ? num_projects : 5; 
	show_hidden = typeof show_hidden !== 'undefined' ? show_hidden : 0;
	document.getElementById("list_projects").innerHTML = please_wait_image;
	var data = {
	    action: 'bugerator_get_project_list',
	    page_start: project_start,
	    num_things: num_projects,
	    show_hidden: show_hidden,
	    post_id: post_id,
	    security: nonce
	};


	jQuery.post(ajaxurl, data, function(response) {
	    document.getElementById("list_projects").innerHTML = response;
	});
    }

    function bug_change_view(project_start,show_hidden) {
	show_hidden = typeof show_hidden !== 'undefined' ? show_hidden : 0;
	var selection = document.getElementById('page_view');
	var num_projects = selection.options[selection.selectedIndex].value;
	bug_get_list(project_start,num_projects,show_hidden);
    }


    /* add_admin(id) 
 * Will add the possibility to add or edit the admin list for a project
 */

    function edit_admins(id) {
	var data = {
	    action: 'bugerator_edit_project_admins',
	    id: id,
	    security: nonce
	};
	document.getElementById("edit_admins_span_"+id).innerHTML = please_wait_image;

	jQuery.post(ajaxurl, data, function(response) {
	    document.getElementById("edit_admins_span_"+id).innerHTML = response;
	});    
    }


    /* add_devs(id) 
 * Will add the possibility to add or edit the developer list for a project
 */

    function edit_devs(id) {
	var data = {
	    action: 'bugerator_edit_project_devs',
	    id: id,
	    security: nonce
	};
	document.getElementById("edit_devs_span_"+id).innerHTML = please_wait_image;

	jQuery.post(ajaxurl, data, function(response) {
	    document.getElementById("edit_devs_span_"+id).innerHTML = response;
	});    
    
    }
    /* add_new_developer(id)
 * Adds a new developer to a specific project
 */
    function add_new_developers(id) {
	// get the select value

	var select_object=document.getElementById("add_new_developers_"+id);
	var index = select_object.selectedIndex;
	var selection =  select_object.options[index].value;   
	//put together the post data
	var data = {
	    action: 'bugerator_edit_project_devs',
	    id: id,
	    selection: selection,
	    security: nonce
	};
	document.getElementById("edit_devs_span_"+id).innerHTML = please_wait_image;


	jQuery.post(ajaxurl, data, function(response) {
	    document.getElementById("edit_devs_span_"+id).innerHTML = response;
	});    
    }
    /* add_new_admin(id)
 * Adds a new admin to a specific project
 */
    function add_new_admins(id) {
	// get the select value

	var select_object=document.getElementById("add_new_admins_"+id);
	var index = select_object.selectedIndex;
	var selection =  select_object.options[index].value;   
	//put together the post data
	var data = {
	    action: 'bugerator_edit_project_admins',
	    id: id,
	    selection: selection,
	    security: nonce
	};

	document.getElementById("edit_admins_span_"+id).innerHTML = please_wait_image;

	jQuery.post(ajaxurl, data, function(response) {
	    document.getElementById("edit_admins_span_"+id).innerHTML = response;
	});    
    }
    /* delete_developers(id)
 * takes somebody off the developer list
 */
    function delete_developers(dev,project){
  
	//put together the post data
	var data = {
	    action: 'bugerator_delete_project_devs',
	    deleteme: dev,
	    id: project,
	    bug_project: project,
	    security: nonce
	};
	document.getElementById("edit_devs_span_"+project).innerHTML = please_wait_image;

	jQuery.post(ajaxurl, data, function(response) {
	    document.getElementById("edit_devs_span_"+project).innerHTML = response;
	}); 
    }
    /* delete_admins(id)
 * takes somebody off the project admin list
 */
    function delete_admins(dev,project){
  
	//put together the post data
	var data = {
	    action: 'bugerator_delete_project_admins',
	    deleteme: dev,
	    id: project,
	    bug_project: project,
	    security: nonce
	};
	document.getElementById("edit_admins_span_"+project).innerHTML = please_wait_image;

	jQuery.post(ajaxurl, data, function(response) {
	    document.getElementById("edit_admins_span_"+project).innerHTML = response;
	}); 
    }
    
    
    
    /* changeSortOrder(key)
     * Sends an ajax request to change the sort order.
     */
    
    function changeSortOrder(project_key) {
        
        var select_id = "sort_selection_"+project_key;
	var selection = document.getElementById(select_id);
	var sort_value = selection.options[selection.selectedIndex].value;
        
	document.getElementById("list_projects").innerHTML = please_wait_image;
	var data = {
	    action: 'bugerator_sort_project_list',
	    project_key: project_key,
            sort_value: sort_value,
	    security: nonce
	};


	jQuery.post(ajaxurl, data, function(response) {
	    document.getElementById("list_projects").innerHTML = response;
	});        
    }
