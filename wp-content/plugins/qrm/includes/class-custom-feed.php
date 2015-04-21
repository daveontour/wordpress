<?php
class custom_feed {
	public $feed = 'custom-xml';
	public function __construct() {
		add_action ( 'init', array (
				$this,
				'init'
		) );
		add_filter ( 'pre_get_posts', array (
				$this,
				'pre_get_posts'
		) );
	}
	public function init() {
		// feed name to access in URL eg. /feed/custom-xml/
		add_feed ( $this->feed, array (
				$this,
				'xml'
		) );
	}
	public function pre_get_posts($query) {
		if ($query->is_main_query () && $query->is_feed ( $this->feed )) {
			// modify query here eg. show all posts
			$query->set ( 'nopaging', 1 );
		}
		return $query;
	}
	public function xml() {
		// either output template & loop here or include a template
		echo "ALL OK";
		if (have_posts ()) :
		while ( have_posts () ) :
		the_post ();
		// standard loop functions can be used here
		endwhile
		;
		endif;
	}
}
