<table class="bugerator bugerator_map" >
    <a href="<?PHP 
    echo $permalink;?>&bugerator_nav=map&bug_project=<?PHP echo $project;?>&showall=1" >Click to see all versions</a>
        <?PHP
        $numbertoshow = (count($version_list)-1)-3;
        if(isset($_GET['showall']))
            $numbertoshow = -1;
        for($x=(count($version_list)-1);$x>$numbertoshow;$x--): 
            
    ?>
<tr><td colspan="5" style="border: none;">&nbsp;</td></tr>
    <tr class="bugerator bugerator_map" >
	<th class="bugerator bugerator_map" style="width: 20%;" >
	    Version: <?PHP echo $version_list[$x]; ?>
	</th>
	<th class="bugerator bugerator_map" colspan ="2" style="width: 40%;" >
	    <?PHP echo $project_info->name; ?>
	</th>
	<th class="bugerator bugerator_map" colspan ="2" style="width: 40%;" >
	    <?PHP if ($x >= $project_info->current_version): ?>
	    Anticipated release date: <?PHP if ($goal_list[$x]<>"")
	    echo date($bugerator_date_format,strtotime($goal_list[$x])); ?>
	    <?PHP else: ?>
	    Release date: <?PHP if ($goal_list[$x]<>"")
		echo date($bugerator_date_format,strtotime($goal_list[$x])); ?>
	    <?PHP endif; ?>
	</th>
    </tr>
</table>
<table class="bugerator bugerator_map" >
    <?PHP
    for ($i=0;$i<count($results);$i++):
	if ($results[$i]['version'] == $version_index[$x]):?>
    <tr class="bugerator bugerator_map" style="<?PHP
	    if ($results[$i]['status'] == "5" or
		    $results[$i]['status'] == "6" or
		    $results[$i]['status'] == "7" or
		    $results[$i]['status'] == "8" or
		    $results[$i]['status'] == "9" or
		    $results[$i]['status'] == "10")
		 echo "text-decoration: line-through; ";
	echo $style[$results[$i]['status']]; ?>" >
	<td class="bugerator bugerator_map" >
	    <?PHP echo $results[$i]['id']; ?>
	</td>
	<td class="bugerator bugerator_map" >
	    <?PHP echo $types[$results[$i]['type']]; ?>
	</td>
	<td class="bugerator bugerator_map" TITLE="<?PHP echo htmlspecialchars(stripslashes($results[$i]['description'])); ?>" >
	    <a class='bugerator_issue_link' href='<?PHP 
	    echo $permalink;?>&bugerator_nav=display&bug_project=<?PHP echo $project;?>&issue=<?PHP 
	    echo $results[$i]['id'];?>'><?PHP echo stripslashes($results[$i]['title']); ?></a>
	</td>
	<td class="bugerator bugerator_map" >
	    <?PHP echo $statuses[$results[$i]['status']]; ?>
	</td>
	<td class="bugerator bugerator_map" >
	    <?PHP echo $results[$i]['priority']; ?>
	</td>
    </tr>
    <?PHP endif; endfor; endfor; ?>
</table>
    