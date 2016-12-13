<?php



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

?>