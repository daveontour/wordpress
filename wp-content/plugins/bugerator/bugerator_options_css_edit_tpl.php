<!-- bugerator_options_css_edit_tpl.php -->
<script type="text/javascript" >
    var nonce = "<?PHP echo $nonce; ?>"
    var please_wait_image = "<img src=\"<?PHP echo admin_url(); ?>images/loading.gif\" >";
    var subtab = "issue_list_css";
    var tab = "change_css";
    function delete_css_row(key,property,wp_option) {

       var data = {
            action: 'bugerator_delete_css_row',
            css_key: key,
            css_property: property,
            wp_option: wp_option,
            security: nonce
        };

        
        jQuery.post(ajaxurl, data, function(response) {
            document.getElementById("option_css_form").innerHTML = response;
        });
    }
    /* add_css_row(key,wp_option)
     * takes in the input and sends it to an ajax response to add it as a css option
     */ 
    function add_css_row(key,wp_option) {
        var property = document.getElementById("input_property_"+key).value;
        var value = document.getElementById("value_"+key).value;

       var data = {
            action: 'bugerator_add_css_row',
            css_key: key,
            css_property: property,
            css_value: value,
            wp_option: wp_option,
            security: nonce
        };

        
        jQuery.post(ajaxurl, data, function(response) {
            document.getElementById("option_css_form").innerHTML = response;
        });
    }
    /* easy_pick_css(key)
     * Runs on the select, fills in the input above it, then runs an ajax for suggestions
     */
    function easy_pick_css(key) {
        var select_id = "css_add_"+key;
        var select_object=document.getElementById(select_id);
        var index = select_object.selectedIndex;
        var selection =  select_object.options[index].value;
        document.getElementById("input_property_"+key).value = selection;
         document.getElementById("value_"+key).value = "";
         document.getElementById("span_"+key).innerHTML = please_wait_image;        
        // todo: add ajax call here to get the appropriate values that go with the property.
        var data = {
            action: 'bugerator_css_value',
            css_property: selection,
            css_key: key,
            security: nonce
        };

        
        jQuery.post(ajaxurl, data, function(response) {
            document.getElementById("span_"+key).innerHTML = response;
        });
    }
    /* easy_value_css(key)
     * Runs on the select, fills in the input above it
     */
    function easy_value_css(key) {
        var select_id = "css_value_"+key;
        var select_object=document.getElementById(select_id);
        var index = select_object.selectedIndex;
        var selection =  select_object.options[index].value; 
        document.getElementById("value_"+key).value = selection;
    }
</script>
<?PHP if (!isset($post->ID)):  ?>
<h1 style="text-align: center;">Note: Theme options may change your look.</h1>
<?PHP else: ?>
<h1 style="text-align: center;">Sample page:</h1>
<?PHP $css_message = ""; endif; ?>

<div class="css_edit" >
    <h2 style="text-align: center;"><?PHP echo $css_message;?></h2>

    <?PHP echo $page; ?>
    <br/><br/>
    <a id="option_css_view_link" onClick="document.getElementById('option_css_form').style.display='inline';
	document.getElementById('option_css_view_link').style.display='none'; 
	document.getElementById('option_css_hide_link').style.display='inline'; 
        return false;" >
        Click to edit css for issue list.</a>
    <a id="option_css_hide_link" onClick="document.getElementById('option_css_form').style.display='none';
	document.getElementById('option_css_view_link').style.display='inline'; 
	document.getElementById('option_css_hide_link').style.display='none'; 
        return false;" style="display: none;" >
        Click to hide css edit.</a>
    
    <span id="option_css_form" style="display: none; " ?>
    <?PHP echo $css_form; ?>
          <a href="">
	      <input type="button" class="button-primary" value="Refresh page" >
        </a>
    <?PHP echo $global_css_form; ?>
          <a href="">
	      <input type="button" class="button-primary" value="Refresh page" >
        </a>
    </span>	

</div><!--css_edit-->
<!-- bugerator_options_css_edit_tpl.php -->