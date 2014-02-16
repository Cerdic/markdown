<?php
/**
 * Test unitaire des raccourcis Markdown dans SPIP
 *
 */

	$test = 'markdown_modeles_spip_block';
	$remonte = "../";
	while (!is_dir($remonte."ecrire"))
		$remonte = "../$remonte";
	require $remonte.'tests/test.inc';
	find_in_path("inc/texte.php",'',true);

	$GLOBALS['spip_lang'] = 'en'; // corrections typo
	$GLOBALS['class_spip_plus'] = '';
	$GLOBALS['class_spip'] = '';

  // ajouter le dossier squelettes de test au chemin
  _chemin(_DIR_PLUGIN_MARKDOWN."tests/squelettes/");

	//
	// hop ! on y va
	//
	$err = tester_fun('propre', essais_markdown_modeles_spip_inline());
	
	// si le tableau $err est pas vide ca va pas
	if ($err) {
		die ('<dl>' . join('', $err) . '</dl>');
	}

	echo "OK";
	

	function essais_markdown_modeles_spip_inline(){

		$tests = preg_files(_DIR_PLUGIN_MARKDOWN."tests/data/modeles_spip_block/",'\.md$');

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