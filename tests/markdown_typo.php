<?php
/**
 * Test unitaire des raccourcis Markdown dans SPIP
 *
 */

	$test = 'markdown_typo';
	$remonte = "../";
	while (!is_dir($remonte."ecrire"))
		$remonte = "../$remonte";
	require $remonte.'tests/test.inc';
	find_in_path("inc/texte.php",'',true);

	$GLOBALS['spip_lang'] = 'fr'; // corrections typo
	$GLOBALS['class_spip_plus'] = '';
	$GLOBALS['class_spip'] = '';
	define('_MARKDOWN_HMINI',1);
	define('_MARKDOWN_SYNTAXE_PAR_DEFAUT','spip');

	//
	// hop ! on y va
	//
	$err = tester_fun('propre', essais_markdown_typo());
	
	// si le tableau $err est pas vide ca va pas
	if ($err) {
		die ('<dl>' . join('', $err) . '</dl>');
	}

	echo "OK";
	

	function essais_markdown_typo(){

		$tests = preg_files(_DIR_PLUGIN_MARKDOWN."tests/data/typo/",'\.md$');

		$markdown = $expected = "";
		$essais = array ();

		foreach($tests as $t){
			lire_fichier($t,$markdown);
			lire_fichier(substr($t,0,-3).".html",$expected);
			$expected = str_replace("\r\n", "\n", $expected);
			$expected = str_replace("\r", "\n", $expected);
			$essais[basename($t,".md")] = array(
				$expected , "<md>$markdown</md>"
			);
		}

		return $essais;
	}



?>