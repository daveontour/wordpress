<script type="text/javascript" >
function show_new_status() {
    document.getElementById("new_status_table").style.display = "table-row";
    document.getElementById("new_status_table2").style.display = "table-row";
    document.getElementById("show_new_status_button").style.display ="none";
}
</script>
<div class="issue_list_css">

    <h2>Choose Colors for the statuses</h2>
    <p>Any css color codes are valid. Ex: "Red" works the same as "#FF0000".
    <table style="text-align: center;">

        <form name="bugerator_form" method="post" action="<?PHP echo $page; ?>" >

        <input type="hidden" name="bugerator_options_nonce" value="<?PHP echo $nonce; ?>"/>

            <th width:20%;>Status</th>
            <th width:40%;>Background Color</th>
            <th width:40%;>Text color</th>
            <?PHP
            for ($x = 0; $x < count($statuses); $x++) {
                if (array_search($x, $statuses_used) !== false) {
                    ?> 
                    <tr>
                        <td  class="bugerator_option_colors"
                             style="background: <?PHP echo $status_colors[$x] ?>; 
                             color: <?PHP echo $status_text_colors[$x] ?>;" >

                            <?PHP // can change if it is more than the default
                            if($x > 10): ?>
                                <?PHP echo $statuses[$x]?>&nbsp;&nbsp;&nbsp;
                                <input id="statname<?PHP echo $x; ?>"name=status_name_choice[<?PHP echo $x; ?>] 
                                       value="<?PHP echo $statuses[$x]; ?>">
                            <?PHP else: echo $statuses[$x];
                             endif;
                             ?>
                        </td>
                        <td>
                            <input name=color_choice["<?PHP echo $x; ?>"] size=8 value="<?PHP echo $status_colors[$x]; ?>">
                        </td>
                        <td>
                            <input name=text_color_choice["<?PHP echo$x; ?>"] size=8 value="<?PHP echo$status_text_colors[$x]; ?>">
                        </td>
                    </tr>
                <?PHP } else { ?>
                    <input type=hidden name=color_choice["<?PHP echo $x; ?>"] value=" <?PHP echo $status_colors[$x]; ?>" >
                    <input type=hidden name=text_color_choice"<?PHP echo $x; ?>"] value="<?PHP echo $status_colors[$x]; ?>">
                    <?PHP
                }
            }
            
            ?>
                    
            <input type=hidden name="bugerator_color_form" value="yup">
            <tr><td colspan=3 style="text-align: left" >
                    <input type="submit" name="Submit" class="button-primary" value="Save Changes"/>
                </td>
            </tr>
 
        </form>
    <tr id="show_new_status_button">
        <td colspan="3" >
    <input type="submit" class="button-primary" value="Add new status" onClick="show_new_status();"/>
        </td>
    </tr>
        <form name="bugerator_new_status" id="bugerator_new_status"  method="post" action="<?PHP echo $page; ?>" >
        <input type="hidden" name="bugerator_options_nonce" value="<?PHP echo $nonce; ?>"/>
        <input type=hidden name="bugerator_new_status_form" value="yup">
             <tr id="new_status_table" style="display:none;">
                <td  class="bugerator_option_colors"
                     style="background: #CCCCCC; 
                     color: #000000;" >
                    <input name="new_status_name" value="New Status" />
                </td>
                <td>
                    <input name=new_color_choice size=8 value="#CCCCCC" />
                </td>
                <td>
                    <input name=new_text_color_choice size=8 value="#000000" />
                </td>
            </tr>
            <tr id="new_status_table2" style="display: none;">
                <td colspan=3 style="text-align: left" >
                    <input type="submit" name="Submit" class="button-primary" value="Add new status"/>
                </td>
            </tr>
        </form>
    </table>

    <table style="text-align: center;">
        <tr>
            <td>
                <form name="bugerator_color_reset" method="post" action="" >
                    <input type="hidden" name="bugerator_options_nonce" value="<?PHP echo $nonce; ?>"/>
                    <input type=hidden name="bugerator_reset_colors" value="yes please" >
                    <input type=submit name=submit class="button-primary" value="Reset Colors to default" >
                </form>
                
            </td>
        </tr>
    </table>

</div><!--issue_list_css-->
