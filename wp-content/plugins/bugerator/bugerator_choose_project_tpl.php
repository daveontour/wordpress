<!-- bugerator_choose_project.php -->
<div class="choose_project" id="choose">
    <h2 class="choose_project" >
	Please choose which project you would like to <?PHP echo $source ?></h2><br/><br/>
	<table class="choose_project">
	    <tr class="choose_project">
		<th class="choose_project">
		    Project Name
		</th>
		<th class="choose_project">
		    Status
		</th>
		<th class="choose_project">
		    Current Version
		</th>
		<th class="choose_project">
		    Next Version
		</th>
		<th class="choose_project">
		    Next Release Date
		</th>
	    </tr>
	    <?PHP
	    foreach ($results as $result) {
                // eliminate those hidden by default
                if(strpos($bugerator_project_display,$result->id) === false)
                        continue;
	    ?>
	    <tr class="choose_project">
		<td class="choose_project">
		    <a class="choose_project" href="<?PHP echo $permalink?>&bugerator_nav=<?PHP 
		    echo $my_source; ?>&bug_project=<?PHP 
		    echo $result->id ?>" ><?PHP echo $result->name ;?></a>
		</td>
		<td class="choose_project">
		    <?PHP echo $project_statuses[$result->status]; ?>
		</td>
		<td class="choose_project">
		    <?PHP echo $result->thisversion; ?>
		</td>
		<td class="choose_project">
		    <?PHP echo $result->next_version; ?>
		</td>
		<td class="choose_project">
		    <?PHP echo $result->next_date; ?>
		</td>
	    </tr>
	    <?PHP } ?>
	</table>
    </h2>
</div><!-- choose -->