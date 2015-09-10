<!-- bugerator_updates_menu_tpl.php -->
<form action="<?PHP echo $page; ?>" method="post" name="update_menu" >
    <p>Show all updates in the last
        <select name="update_number">
            <?PHP for($x=1;$x<61;$x++): ?>
                <?PHP if(isset($default_update_number)): ?>
                    <option value=<?PHP echo $x; if($default_update_number == $x) echo " SELECTED "; ?> ><?PHP echo $x; ?></option> 
                <?PHP else:?>
                    <option value=<?PHP echo $x; if(7 == $x) echo " SELECTED "; ?> ><?PHP echo $x; ?></option> 
                <?PHP endif; ?>
            <?PHP endfor; ?>
        </select>
        <select name="update_period">
            <?PHP if(isset($update_period)): ?>
                <option value="minutes" <?PHP if ("minutes" == $update_period) echo " SELECTED "; ?>>Minutes</option>
                <option value="hours" <?PHP if ("hours" == $update_period) echo " SELECTED "; ?>>Hours</option>
                <option value="days" <?PHP if ("days" == $update_period) echo " SELECTED "; ?>>Days</option>
                <option value="weeks" <?PHP if ("weeks" == $update_period) echo " SELECTED "; ?>>Weeks</option>
                <option value="months" <?PHP if ("months" == $update_period) echo " SELECTED "; ?>>Months</option>
            <?PHP else: ?>
                <option value="minutes">Minutes</option>
                <option value="hours">Hours</option>
                <option value="days" SELECTED >Days</option>
                <option value="weeks" >Weeks</option>
                <option value="months">Months</option>
            <?PHP endif; ?>
        </select>
    </p>
    <input class="button-primary" type="submit" value="Go"/>
</form>
<hr>
<!-- end bugerator_updates_menu -->