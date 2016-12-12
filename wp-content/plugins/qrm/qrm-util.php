<?php 

class LoremIpsumGenerator {
	/**
	 * Copyright (c) 2009, Mathew Tinsley (tinsley@tinsology.net)
	 * All rights reserved.
	 *
	 * Redistribution and use in source and binary forms, with or without
	 * modification, are permitted provided that the following conditions are met:
	 * * Redistributions of source code must retain the above copyright
	 * notice, this list of conditions and the following disclaimer.
	 * * Redistributions in binary form must reproduce the above copyright
	 * notice, this list of conditions and the following disclaimer in the
	 * documentation and/or other materials provided with the distribution.
	 * * Neither the name of the organization nor the
	 * names of its contributors may be used to endorse or promote products
	 * derived from this software without specific prior written permission.
	 *
	 * THIS SOFTWARE IS PROVIDED BY MATHEW TINSLEY ''AS IS'' AND ANY
	 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
	 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
	 * DISCLAIMED. IN NO EVENT SHALL <copyright holder> BE LIABLE FOR ANY
	 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
	 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
	 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
	 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
	 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
	 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
	 */
	private $words, $wordsPerParagraph, $wordsPerSentence;
	function __construct($wordsPer = 100) {
		$this->wordsPerParagraph = $wordsPer;
		$this->wordsPerSentence = 24.460;
		$this->words = array (
				'lorem',
				'ipsum',
				'dolor',
				'sit',
				'amet',
				'consectetur',
				'adipiscing',
				'elit',
				'curabitur',
				'vel',
				'hendrerit',
				'libero',
				'eleifend',
				'blandit',
				'nunc',
				'ornare',
				'odio',
				'ut',
				'orci',
				'gravida',
				'imperdiet',
				'nullam',
				'purus',
				'lacinia',
				'a',
				'pretium',
				'quis',
				'congue',
				'praesent',
				'sagittis',
				'laoreet',
				'auctor',
				'mauris',
				'non',
				'velit',
				'eros',
				'dictum',
				'proin',
				'accumsan',
				'sapien',
				'nec',
				'massa',
				'volutpat',
				'venenatis',
				'sed',
				'eu',
				'molestie',
				'lacus',
				'quisque',
				'porttitor',
				'ligula',
				'dui',
				'mollis',
				'tempus',
				'at',
				'magna',
				'vestibulum',
				'turpis',
				'ac',
				'diam',
				'tincidunt',
				'id',
				'condimentum',
				'enim',
				'sodales',
				'in',
				'hac',
				'habitasse',
				'platea',
				'dictumst',
				'aenean',
				'neque',
				'fusce',
				'augue',
				'leo',
				'eget',
				'semper',
				'mattis',
				'tortor',
				'scelerisque',
				'nulla',
				'interdum',
				'tellus',
				'malesuada',
				'rhoncus',
				'porta',
				'sem',
				'aliquet',
				'et',
				'nam',
				'suspendisse',
				'potenti',
				'vivamus',
				'luctus',
				'fringilla',
				'erat',
				'donec',
				'justo',
				'vehicula',
				'ultricies',
				'varius',
				'ante',
				'primis',
				'faucibus',
				'ultrices',
				'posuere',
				'cubilia',
				'curae',
				'etiam',
				'cursus',
				'aliquam',
				'quam',
				'dapibus',
				'nisl',
				'feugiat',
				'egestas',
				'class',
				'aptent',
				'taciti',
				'sociosqu',
				'ad',
				'litora',
				'torquent',
				'per',
				'conubia',
				'nostra',
				'inceptos',
				'himenaeos',
				'phasellus',
				'nibh',
				'pulvinar',
				'vitae',
				'urna',
				'iaculis',
				'lobortis',
				'nisi',
				'viverra',
				'arcu',
				'morbi',
				'pellentesque',
				'metus',
				'commodo',
				'ut',
				'facilisis',
				'felis',
				'tristique',
				'ullamcorper',
				'placerat',
				'aenean',
				'convallis',
				'sollicitudin',
				'integer',
				'rutrum',
				'duis',
				'est',
				'etiam',
				'bibendum',
				'donec',
				'pharetra',
				'vulputate',
				'maecenas',
				'mi',
				'fermentum',
				'consequat',
				'suscipit',
				'aliquam',
				'habitant',
				'senectus',
				'netus',
				'fames',
				'quisque',
				'euismod',
				'curabitur',
				'lectus',
				'elementum',
				'tempor',
				'risus',
				'cras'
		);
	}
	function getContent($count, $format = 'html', $loremipsum = true) {
		$format = strtolower ( $format );

		if ($count <= 0)
			return '';

			switch ($format) {
				case 'txt' :
					return $this->getText ( $count, $loremipsum );
				case 'plain' :
					return $this->getPlain ( $count, $loremipsum );
				default :
					return $this->getHTML ( $count, $loremipsum );
			}
	}
	private function getWords(&$arr, $count, $loremipsum) {
		$i = 0;
		if ($loremipsum) {
			$i = 2;
			$arr [0] = 'lorem';
			$arr [1] = 'ipsum';
		}

		for($i; $i < $count; $i ++) {
			$index = array_rand ( $this->words );
			$word = $this->words [$index];
			// echo $index . '=>' . $word . '<br />';
				
			if ($i > 0 && $arr [$i - 1] == $word)
				$i --;
				else
					$arr [$i] = $word;
		}
	}
	private function getPlain($count, $loremipsum, $returnStr = true) {
		$words = array ();
		$this->getWords ( $words, $count, $loremipsum );
		// print_r($words);

		$delta = $count;
		$curr = 0;
		$sentences = array ();
		while ( $delta > 0 ) {
			$senSize = $this->gaussianSentence ();
			// echo $curr . '<br />';
			if (($delta - $senSize) < 4)
				$senSize = $delta;
					
				$delta -= $senSize;
					
				$sentence = array ();
				for($i = $curr; $i < ($curr + $senSize); $i ++)
					$sentence [] = $words [$i];
						
					$this->punctuate ( $sentence );
					$curr = $curr + $senSize;
					$sentences [] = $sentence;
		}

		if ($returnStr) {
			$output = '';
			foreach ( $sentences as $s )
				foreach ( $s as $w )
					$output .= $w . ' ';
						
					return $output;
		} else
			return $sentences;
	}
	private function getText($count, $loremipsum) {
		$sentences = $this->getPlain ( $count, $loremipsum, false );
		$paragraphs = $this->getParagraphArr ( $sentences );

		$paragraphStr = array ();
		foreach ( $paragraphs as $p ) {
			$paragraphStr [] = $this->paragraphToString ( $p );
		}

		$paragraphStr [0] = "\t" . $paragraphStr [0];
		return implode ( "\n\n\t", $paragraphStr );
	}
	private function getParagraphArr($sentences) {
		$wordsPer = $this->wordsPerParagraph;
		$sentenceAvg = $this->wordsPerSentence;
		$total = count ( $sentences );

		$paragraphs = array ();
		$pCount = 0;
		$currCount = 0;
		$curr = array ();

		for($i = 0; $i < $total; $i ++) {
			$s = $sentences [$i];
			$currCount += count ( $s );
			$curr [] = $s;
			if ($currCount >= ($wordsPer - round ( $sentenceAvg / 2.00 )) || $i == $total - 1) {
				$currCount = 0;
				$paragraphs [] = $curr;
				$curr = array ();
				// print_r($paragraphs);
			}
			// print_r($paragraphs);
		}

		return $paragraphs;
	}
	private function getHTML($count, $loremipsum) {
		$sentences = $this->getPlain ( $count, $loremipsum, false );
		$paragraphs = $this->getParagraphArr ( $sentences );
		// print_r($paragraphs);

		$paragraphStr = array ();
		foreach ( $paragraphs as $p ) {
			$paragraphStr [] = "<p>" . $this->paragraphToString ( $p, true ) . '</p>';
		}

		// add new lines for the sake of clean code
		return implode ( " ", $paragraphStr );
	}
	private function paragraphToString($paragraph, $htmlCleanCode = false) {
		$paragraphStr = '';
		foreach ( $paragraph as $sentence ) {
			foreach ( $sentence as $word )
				$paragraphStr .= $word . ' ';
					
				if ($htmlCleanCode)
					$paragraphStr .= " ";
		}
		return $paragraphStr;
	}

	/*
	 * Inserts commas and periods in the given
	 * word array.
	 */
	private function punctuate(& $sentence) {
		$count = count ( $sentence );
		$sentence [$count - 1] = $sentence [$count - 1] . '.';

		if ($count < 4)
			return $sentence;

			$commas = $this->numberOfCommas ( $count );

			for($i = 1; $i <= $commas; $i ++) {
				$index = ( int ) round ( $i * $count / ($commas + 1) );
					
				if ($index < ($count - 1) && $index > 0) {
					$sentence [$index] = $sentence [$index] . ',';
				}
			}
	}

	/*
	 * Determines the number of commas for a
	 * sentence of the given length. Average and
	 * standard deviation are determined superficially
	 */
	private function numberOfCommas($len) {
		$avg = ( float ) log ( $len, 6 );
		$stdDev = ( float ) $avg / 6.000;

		return ( int ) round ( $this->gauss_ms ( $avg, $stdDev ) );
	}

	/*
	 * Returns a number on a gaussian distribution
	 * based on the average word length of an english
	 * sentence.
	 * Statistics Source:
	 * http://hearle.nahoo.net/Academic/Maths/Sentence.html
	 * Average: 24.46
	 * Standard Deviation: 5.08
	 */
	private function gaussianSentence() {
		$avg = ( float ) 24.460;
		$stdDev = ( float ) 5.080;

		return ( int ) round ( $this->gauss_ms ( $avg, $stdDev ) );
	}

	/*
	 * The following three functions are used to
	 * compute numbers with a guassian distrobution
	 * Source:
	 * http://us.php.net/manual/en/function.rand.php#53784
	 */
	private function gauss() { // N(0,1)
		// returns random number with normal distribution:
		// mean=0
		// std dev=1

		// auxilary vars
		$x = $this->random_0_1 ();
		$y = $this->random_0_1 ();

		// two independent variables with normal distribution N(0,1)
		$u = sqrt ( - 2 * log ( $x ) ) * cos ( 2 * pi () * $y );
		$v = sqrt ( - 2 * log ( $x ) ) * sin ( 2 * pi () * $y );

		// i will return only one, couse only one needed
		return $u;
	}
	private function gauss_ms($m = 0.0, $s = 1.0) {
		return $this->gauss () * $s + $m;
	}
	private function random_0_1() {
		return ( float ) rand () / ( float ) getrandmax ();
	}
}

class QRMMatrix {
	
	static function mat($w, $h, $tolString, $maxProb, $maxImpact, $uProb = null, $uImpact = null, $tProb = null, $tImpact = null) {
		$cw = $w / $maxImpact;
		$ch = $h / $maxProb;
		$im = imagecreatetruecolor ( $w, $h );
		$black = imagecolorallocate ( $im, 0, 0, 0 );
		$white = imagecolorallocate ( $im, 255, 255, 255 );
		$blue = imagecolorallocate ( $im, 0, 0, 255 );
		$red = imagecolorallocate ( $im, 255, 0, 0 );
		$green = imagecolorallocate ( $im, 0, 255, 0 );
		$yellow = imagecolorallocate ( $im, 255, 255, 0 );
		$orange = imagecolorallocate ( $im, 255, 165, 0 );
	
		$x = 0;
		// Draw the cells
		for($i = 0; $i < $maxProb; $i ++) {
			for($j = 0; $j < $maxImpact; $j ++) {
				switch (substr ( $tolString, $x, 1 )) {
					case "1" :
						imagefilledrectangle ( $im, $j * $cw, $i * $ch, ($j + 1) * $cw, ($i + 1) * $ch, $blue );
						break;
					case "2" :
						imagefilledrectangle ( $im, $j * $cw, $i * $ch, ($j + 1) * $cw, ($i + 1) * $ch, $green );
						break;
					case "3" :
						imagefilledrectangle ( $im, $j * $cw, $i * $ch, ($j + 1) * $cw, ($i + 1) * $ch, $yellow );
						break;
					case "4" :
						imagefilledrectangle ( $im, $j * $cw, $i * $ch, ($j + 1) * $cw, ($i + 1) * $ch, $orange );
						break;
					case "5" :
						imagefilledrectangle ( $im, $j * $cw, $i * $ch, ($j + 1) * $cw, ($i + 1) * $ch, $red );
						break;
				}
				$x = $x + 1;
			}
		}
	
		// Draw vertical lines
		for($j = 0; $j < $maxImpact; $j ++) {
			imageline ( $im, $j * $cw, 0, $j * $cw, $h, $black );
		}
		// Draw horizontal lines
		for($j = 0; $j < $maxProb; $j ++) {
			imageline ( $im, 0, $j * $ch, $w, $j * $ch, $black );
		}
		// Draw border lines
		imageline ( $im, 0, $h - 1, $w, $h - 1, $black );
		imageline ( $im, $w - 1, 0, $w - 1, $h, $black );
	
		if ($uProb != null) {
			imagesetthickness ( $im, 3 );
			$p = (floor ( $uProb ) - 1) * $ch;
			$i = (floor ( $uImpact ) - 1) * $cw;
			imagefilledellipse ( $im, $i + $cw / 2, $p + $ch / 2, $cw - 6, $ch - 6, $white );
			imageellipse ( $im, $i + $cw / 2, $p + $ch / 2, $cw - 8, $ch - 8, $red );
			imageline ( $im, $i + $cw * 0.25, $p + $ch / 4, $i + $cw * 0.75, $p + $ch * 0.75, $red );
			imageline ( $im, $i + $cw * 0.25, $p + $ch * 0.75, $i + $cw * 0.75, $p + $ch * 0.25, $red );
	
			$p = (floor ( $tProb ) - 1) * $ch;
			$i = (floor ( $tImpact ) - 1) * $cw;
			imagefilledellipse ( $im, $i + $cw / 2, $p + $ch / 2, $cw - 6, $ch - 6, $white );
			imageellipse ( $im, $i + $cw / 2, $p + $ch / 2, $cw - 8, $ch - 8, $blue );
			imageline ( $im, $i + $cw * 0.25, $p + $ch / 4, $i + $cw * 0.75, $p + $ch * 0.75, $blue );
			imageline ( $im, $i + $cw * 0.25, $p + $ch * 0.75, $i + $cw * 0.75, $p + $ch * 0.25, $blue );
		}
	
		// Put it in the correct orientation
		imageflip ( $im, IMG_FLIP_VERTICAL );
	
		return $im;
	}
	static function getMatImageString($w, $h, $tolString, $maxProb, $maxImpact, $uProb, $uImpact, $tProb, $tImpact) {
		$mat = QRMMatrix::mat ( $w, $h, $tolString, $maxProb, $maxImpact, $uProb, $uImpact, $tProb, $tImpact );
		ob_start ();
		imagepng ( $mat );
		imagedestroy ( $mat );
		$stringdata = ob_get_contents (); // read from buffer
		ob_end_clean (); // delete buffer
		return $stringdata;
	}
	static function outputMatImage($w, $h, $tolString, $maxProb, $maxImpact, $uProb, $uImpact, $tProb, $tImpact) {
		$mat = QRMMatrix::mat ( $w, $h, $tolString, $maxProb, $maxImpact, $uProb, $uImpact, $tProb, $tImpact );
	
		header ( 'Content-Type: image/png' );
		imagepng ( $mat );
		imagedestroy ( $mat );
	}
}

class QRMUtil {
	
	static function dropReportTables() {
	
		global $wpdb;
	
		$wpdb->query ( "DROP TABLE IF EXISTS " . $wpdb->prefix . 'qrm_controls' );
		$wpdb->query ( "DROP TABLE IF EXISTS " . $wpdb->prefix . 'qrm_mitplan' );
		$wpdb->query ( "DROP TABLE IF EXISTS " . $wpdb->prefix . 'qrm_respplan' );
		$wpdb->query ( "DROP TABLE IF EXISTS " . $wpdb->prefix . 'qrm_projectusers' );
		$wpdb->query ( "DROP TABLE IF EXISTS " . $wpdb->prefix . 'qrm_objective' );
		$wpdb->query ( "DROP TABLE IF EXISTS " . $wpdb->prefix . 'qrm_incidentrisks' );
		$wpdb->query ( "DROP TABLE IF EXISTS " . $wpdb->prefix . 'qrm_category' );
		$wpdb->query ( "DROP TABLE IF EXISTS " . $wpdb->prefix . 'qrm_reviewrisks' );
		$wpdb->query ( "DROP TABLE IF EXISTS " . $wpdb->prefix . 'qrm_reviewriskcomments' );
		$wpdb->query ( "DROP TABLE IF EXISTS " . $wpdb->prefix . 'qrm_reviewcomments' );
		$wpdb->query ( "DROP TABLE IF EXISTS " . $wpdb->prefix . 'qrm_reports' );
		$wpdb->query ( "DROP TABLE IF EXISTS " . $wpdb->prefix . 'qrm_projectproject' );
		$wpdb->query ( "DROP TABLE IF EXISTS " . $wpdb->prefix . 'qrm_audit' );
		$wpdb->query ( "DROP TABLE IF EXISTS " . $wpdb->prefix . 'qrm_riskobjectives' );
		$wpdb->query ( "DROP TABLE IF EXISTS " . $wpdb->prefix . 'qrm_risk' );
		$wpdb->query ( "DROP TABLE IF EXISTS " . $wpdb->prefix . 'qrm_review' );
		$wpdb->query ( "DROP TABLE IF EXISTS " . $wpdb->prefix . 'qrm_incident' );
		$wpdb->query ( "DROP TABLE IF EXISTS " . $wpdb->prefix . 'qrm_projectowners' );
		$wpdb->query ( "DROP TABLE IF EXISTS " . $wpdb->prefix . 'qrm_projectmanagers' );
		$wpdb->query ( "DROP TABLE IF EXISTS " . $wpdb->prefix . 'qrm_project' );
		
	}
	
	static function commonJSON($projectIDs = array(), $riskIDs = array(), $basicsOnly = false) {
		global $post;
	
		$args = array (
				'post_type' => 'riskproject',
				'posts_per_page' => - 1
		);
		// Restrict Selection to just the selected project
		if (sizeof ( $projectIDs ) > 0) {
			$args ['post__in'] = $projectIDs;
		}
	
		$the_query = new WP_Query ( $args );
		$projects = array ();
	
		while ( $the_query->have_posts () ) :
		$the_query->the_post ();
		$project = json_decode ( get_post_meta ( $post->ID, "projectdata", true ) );
		array_push ( $projects, $project );
		endwhile
		;
	
		// Restrict selection just to the selected risks
		if (sizeof ( $riskIDs ) > 0) {
				
			$args = array (
					'post_type' => 'risk',
					'post__in' => $riskIDs,
					'posts_per_page' => - 1
			);
		} else if (sizeof ( $projectIDs ) > 0) {
				
			$args = array (
					'post_type' => 'risk',
					'posts_per_page' => - 1,
					'meta_query' => array (
							array (
									'key' => 'projectID',
									'value' => $projectIDs,
									'compare' => 'IN'
							)
					)
			);
		} else {
				
			$args = array (
					'post_type' => 'risk',
					'posts_per_page' => - 1
			);
		}
	
		$the_query = new WP_Query ( $args );
		$risks = array ();
		while ( $the_query->have_posts () ) :
		$the_query->the_post ();
		$risk = json_decode ( get_post_meta ( $post->ID, "riskdata", true ) );
			
		if (! $basicsOnly) {
			$risk->audit = json_decode ( get_post_meta ( $post->ID, "audit", true ) );
			$risk->incidents = get_post_meta ( $post->ID, "incident" );
			$risk->reviews = get_post_meta ( $post->ID, "review" );
			$risk->comments = get_comments ( array (
					'post_id' => $post->ID
			) );
		}
		$risk->projectID = get_post_meta ( $post->ID, "projectID", true );
		$risk->rank = get_post_meta ( $post->ID, "rank", true );
		$risk->ID = $post->ID;
		if ( ! isset($risk->primcat)) {
			$risk->primcat = new stdObject ();
		}
		if ( ! isset($risk->seccat)) {
			$risk->seccat = new stdObject ();
		}
		if (isset ( $risk->response->respPlan )) {
			foreach ( $risk->response->respPlan as $step ) {
				if ($step->cost == "No Cost Allocated") {
					$step->cost = 0;
				}
			}
		}
		array_push ( $risks, $risk );
		endwhile
		;
	
		$args = array (
				'post_type' => 'review',
				'post_per_page' => - 1
		);
	
		if (! $basicsOnly) {
			$args ["post_type"] = 'review';
			$the_query = new WP_Query ( $args );
			$reviews = array ();
			while ( $the_query->have_posts () ) :
			$the_query->the_post ();
			$review = json_decode ( get_post_meta ( $post->ID, "reviewdata", true ) );
			array_push ( $reviews, $review );
			endwhile
			;
				
			$args = array (
					'post_type' => 'incident',
					'post_per_page' => - 1
			);
				
			$the_query = new WP_Query ( $args );
			$incidents = array ();
			while ( $the_query->have_posts () ) :
			$the_query->the_post ();
			$incident = json_decode ( get_post_meta ( $post->ID, "incidentdata", true ) );
			array_push ( $incidents, $incident );
			endwhile
			;
		}
	
		$export = new stdObject ();
		$export->projects = $projects;
		$export->risks = $risks;
	
		if (! $basicsOnly) {
			$export->incidents = $incidents;
			$export->reviews = $reviews;
		}
	
		$users = array ();
		$user_query = new WP_User_Query ( array (
				'fields' => "all"
		) );
		foreach ( $user_query->results as $user ) {
			$u = new stdObject ();
			$u->id = $user->data->ID;
			$u->display_name = $user->data->display_name;
			$u->user_email = $user->data->user_email;
			array_push ( $users, $u );
		}
	
		$export->users = $users;
	
		return $export;
	}
	
}
?>