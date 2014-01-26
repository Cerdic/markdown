<?php
/**
 * Fonctions utiles au plugin Markdown
 *
 * @plugin     Markdown
 * @copyright  2014
 * @author     Cédric
 * @licence    GNU/GPL
 * @package    SPIP\Markdown\Fonctions
 */

if (!defined('_ECRIRE_INC_VERSION')) return;

// s'inserer a la fin de pre_propre
$GLOBALS['spip_pipeline']['pre_propre'] = (isset($GLOBALS['spip_pipeline']['pre_propre'])?$GLOBALS['spip_pipeline']['pre_propre']:'').'||markdown_pre_propre';

/**
 * Appliquer un filtre aux portions <md>...</md> du texte
 * @param string $texte
 * @param string $filtre
 * @return string
 */
function markdown_filtre_portions_md($texte,$filtre){
	if (strpos($texte,"<md>")!==false){
		preg_match_all(",<md>(.*)</md>,Uims",$texte,$matches,PREG_SET_ORDER);
		foreach($matches as $m){
			$t = $filtre($m[1]);
			$p = strpos($texte,$m[1]);
			$texte = substr_replace($texte,$t,$p-4,strlen($m[1])+9);
		}
	}
	return $texte;
}

/**
 * Pre typo : echapper le code pour le proteger des corrections typo
 * @param string $texte
 * @return string
 */
function markdown_pre_typo($texte){
	return markdown_filtre_portions_md($texte,"markdown_echappe_code");
}
function markdown_echappe_code($texte){
	if (strpos($texte,"```")!==false){
		//var_dump(preg_match(',^```\w+?\s.*\s```,Uims',$texte,$m));
		//var_dump($m);
		$texte = echappe_html($texte,'md',true,',^```\w+?\s.*\s```,Uims');
	}
	if (strpos($texte,"`")!==false){
		$texte = echappe_html($texte,'md',true,',`.*`,Uims');
	}
	return "<md>$texte</md>";
}

/**
 * Post typo : retablir le code md echappe en pre-typo
 * @param $texte
 * @return mixed
 */
function markdown_post_typo($texte){
	if (strpos($texte,"<md>")!==false){
		$texte = echappe_retour($texte,"md");
	}
	return $texte;
}


/**
 * Pre-propre : traiter les raccourcis markdown
 * @param string $texte
 * @return string
 */
function markdown_pre_propre($texte){
	if (!class_exists("Parsedown")){
		include_once _DIR_PLUGIN_MARKDOWN."lib/parsedown/Parsedown.php";
	}

	$texte = markdown_filtre_portions_md($texte,"markdown_raccourcis");
	return $texte;
}
function markdown_raccourcis($texte){

	// redresser les raccourcis liens moisis par les autoliens
	$md = preg_replace_callback(",(\[[^]]*\])\((<a[^)]*</a>)\),Uims","markdown_link_repair",$texte);
	// redresser les raccourcis liens moisis par espaces insecables et/ou autoliens
	$md = preg_replace_callback(",^(\s*\[[^]]*\])(&nbsp;)?(:\s*?)(<a[^)]*</a>|[^<].*)$,Uims","markdown_link_repair2",$md);

	// marker les ul/ol explicites qu'on ne veut pas modifier
	if (stripos($md,"<ul")!==false OR stripos($md,"<ol")!==false OR stripos($md,"<li")!==false)
		$md = preg_replace(",<(ul|ol|li)(\s),Uims","<$1 html$2",$md);

	// tous les &truc; sont masques pour ne pas etre transformes en &amp;
	if (strpos($md,'&') !== false)
		$md = preg_replace(',&(#?[a-z0-9]+;),iS', "\x1"."$1", $md);

	// parser le markdown
	$md = Parsedown::instance()->parse($md);

	// retablir les &
	if (strpos($md,"\x1") !== false)
		$md = str_replace("\x1","&", $md);

	// class spip sur ul et ol et retablir les ul/ol explicites d'origine
	$md = str_replace(array("<ul>","<ol>","<li>"),array('<ul'.$GLOBALS['class_spip_plus'].'>','<ol'.$GLOBALS['class_spip_plus'].'>','<li'.$GLOBALS['class_spip'].'>'),$md);
	$md = str_replace(array("<ul html","<ol html","<li html"),array('<ul','<ol','<li'),$md);

	//var_dump($md);

	// echapper le markdown
	return code_echappement($md);
}
function markdown_link_repair($r){
	$href = extraire_attribut($r[2],"href");
	return $r[1]."($href)";
}
function markdown_link_repair2($r){
	$href = ((strpos($r[4],"<a")!==false)?extraire_attribut($r[4],"href"):$r[4]);
	return $r[1].$r[3]."$href";
}


/*
 * DEBUG
$apres = Parsedown::instance()->parse($texte);

if (_request('var_dbmarkdown') AND $apres!==$texte){

include_once _DIR_PLUGIN_MARKDOWN.'lib/finediff.php';
$diff = new FineDiff($texte, $apres);
var_dump($apres);
echo "<style>
del {
background: none repeat scroll 0 0 #FFDDDD;
color: #FF0000;
text-decoration: none;
}
ins {
background: none repeat scroll 0 0 #DDFFDD;
color: #008000;
text-decoration: none;
}
</style>";
echo "<pre>".$diff->renderDiffToHTML()."</pre>";
die();
}*/

?>