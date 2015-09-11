<?php
$type = "risk";
$projectID = get_post_meta($post->ID, "projectID", true);
include dirname(__FILE__).'/qrm-type-template.php';
