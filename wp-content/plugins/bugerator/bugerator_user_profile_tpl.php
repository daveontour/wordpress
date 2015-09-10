<script type="text/javascript" >
    var please_wait_image = "<IMG src=\"<?PHP echo admin_url(); ?>images/loading.gif\" >"; 
    var nonce = "<?PHP echo $nonce; ?>";
    function update_email(which) {
	var data = {
	    action: 'bugerator_email_preference',
	    which_preference: which,
	    security: nonce
	};
	document.getElementById("email_preference").innerHTML = please_wait_image;
	jQuery.post(ajaxurl, data, function(response) {
	    document.getElementById("email_preference").innerHTML = response;
	});
    }
</script>
<h1 class='bugerator'>This is for managing your project / issue subscriptions.</h1>
<h4 class='bugerator'><a class='bugerator' href='<?PHP echo admin_url(); ?>profile.php' >
	To manage your email address and other Wordpress options click here.</a>
</h4>
<br/>
<h2>Subscription preference:</h2>
<input type='radio' name='sub' value='one' <?PHP echo $one; ?> onChange='update_email("one")'>
I only want to receive email if a project changed since I last logged in.<br/>
<input type='radio' name='sub' value='all' <?PHP echo $all; ?> onChange='update_email("all")'>
I want to receive an email every time a project changes.<br/>
<span id="email_preference" ></span>
<?PHP echo $message; ?>
<?PHP if (0 == count($results)): ?>
    <h2>You have no subscriptions.</h2>
<?PHP else: ?>
    <h2>You have the following subscriptions.</h2>
    <?PHP foreach ($results as $row): ?>
	<?PHP if ("Projects" == $row->type): ?>
	    <ul class='bugerator bugerator_profile'>
	        <li class='bugerator bugerator_profile bold'><strong>Project: </strong>
		    <?PHP echo stripslashes($project_names[$row->foreign_id]); ?> 
	    	<a href=<?PHP echo $permalink; ?>&bugerator_nav=profile&bug_project=<?PHP echo $bugerator_project_id; ?>&unsubscribe=Projects&id=<?PHP echo $row->id; ?>&nonce=<?PHP echo $nonce; ?> >
	    	   Click to unsubscribe
	        </a>
	<?PHP else: ?>
	    <li class='bugerator bugerator_profile'>
	        <strong>Issue:</strong> <?PHP echo stripslashes($issue_names[$row->foreign_id]); ?>
	        <a href=<?PHP echo $permalink; ?>&bugerator_nav=profile&bug_project=<?PHP echo $bugerator_project_id; ?>&unsubscribe=Issues&id=<?PHP echo $row->id; ?>&nonce=<?PHP echo $nonce; ?> >
	           Click to unsubscribe
	    </a>
	       <?PHP endif;
	   endforeach;
	   ?>
    </ul>

<?PHP endif; ?>
<?PHP echo $subscribe_message; ?>
<p class='bugerator'>To subscribe to other projects then choose that project and return 
    to this page to add that subscription. To subscribe to issues then click in to the issue 
    detail and subscribe from there.
