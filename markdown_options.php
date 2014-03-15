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

// s'inserer a la fin de pre_propre et post propre
$GLOBALS['spip_pipeline']['pre_propre'] = (isset($GLOBALS['spip_pipeline']['pre_propre'])?$GLOBALS['spip_pipeline']['pre_propre']:'').'||markdown_pre_propre';
$GLOBALS['spip_pipeline']['post_propre'] = (isset($GLOBALS['spip_pipeline']['post_propre'])?$GLOBALS['spip_pipeline']['post_propre']:'').'||markdown_post_propre';

/* Compat SPIP < 3.0.17 */

// - pour $source voir commentaire infra (echappe_retour)
// - pour $no_transform voir le filtre post_autobr dans inc/filtres
// http://doc.spip.org/@echappe_html
function echappe_html_3017($letexte, $source='', $no_transform=false,
$preg='') {
	if (!is_string($letexte) or !strlen($letexte))
		return $letexte;

	// si le texte recu est long PCRE risque d'exploser, on
	// fait donc un mic-mac pour augmenter pcre.backtrack_limit
	if (($len = strlen($letexte)) > 100000) {
		if (!$old = @ini_get('pcre.backtrack_limit')) $old = 100000;
		if ($len > $old) {
			$a = @ini_set('pcre.backtrack_limit', $len);
			spip_log("ini_set pcre.backtrack_limit=$len ($old)");
		}
	}

	if (($preg OR strpos($letexte,"<")!==false)
	  AND preg_match_all($preg ? $preg : _PROTEGE_BLOCS, $letexte, $matches, PREG_SET_ORDER)) {
		foreach ($matches as $regs) {
			// echappements tels quels ?
			if ($no_transform) {
				$echap = $regs[0];
			}

			// sinon les traiter selon le cas
			else if (function_exists($f = 'traiter_echap_'.strtolower($regs[1])))
				$echap = $f($regs);
			else if (function_exists($f = $f.'_dist'))
				$echap = $f($regs);

			$p = strpos($letexte,$regs[0]);
			$letexte = substr_replace($letexte,code_echappement($echap, $source, $no_transform),$p,strlen($regs[0]));
		}
	}

	if ($no_transform)
		return $letexte;

	// Gestion du TeX
	// code mort sauf si on a personalise _PROTEGE_BLOCS sans y mettre <math>
	// eviter la rupture de compat en branche 3.0
	// a supprimer en branche 3.1
	if (strpos($preg ? $preg : _PROTEGE_BLOCS,'code')!==false){
		if (strpos($letexte, "<math>") !== false) {
			include_spip('inc/math');
			$letexte = traiter_math($letexte, $source);
		}
	}

	// Echapper le php pour faire joli (ici, c'est pas pour la securite)
	// seulement si on a echappe les <script>
	// (derogatoire car on ne peut pas faire passer < ? ... ? >
	// dans une callback autonommee
	if (strpos($preg ? $preg : _PROTEGE_BLOCS,'script')!==false){
		if (strpos($letexte,"<"."?")!==false AND preg_match_all(',<[?].*($|[?]>),UisS',
		$letexte, $matches, PREG_SET_ORDER))
		foreach ($matches as $regs) {
			$letexte = str_replace($regs[0],
				code_echappement(highlight_string($regs[0],true), $source),
				$letexte);
		}
	}

	return $letexte;
}
/* Fin Compat SPIP < 3.0.17 */

function markdown_pre_echappe_html_propre($texte){
	static $syntaxe_defaut = null;

	if (is_null($syntaxe_defaut)){
		// lever un flag pour dire que ce pipeline est bien OK
		if (!defined('_pre_echappe_html_propre_ok'))
			define('_pre_echappe_html_propre_ok',true);
		// on peut forcer par define, utile pour les tests unitaires
		if (defined('_MARKDOWN_SYNTAXE_PAR_DEFAUT'))
			$syntaxe_defaut = _MARKDOWN_SYNTAXE_PAR_DEFAUT;
		else {
			include_spip('inc/config');
			$syntaxe_defaut = lire_config("markdown/syntaxe_par_defaut","spip");
		}
	}

	// si syntaxe par defaut est markdown et pas de <md> dans le texte on les introduits
	if ($syntaxe_defaut==="markdown"
		// est-ce judicieux de tester cette condition ?
	  AND strpos($texte,"<md>")===false
	  ){
		$texte = str_replace(array("<spip>","</spip>"),array("</md>","<md>"),$texte);
		$texte = "<md>$texte</md>";
		$texte = str_replace("<md></md>","",$texte);
	}

	if (strpos($texte,"<md>")!==false){
		// Compat SPIP <3.0.17
		if (!function_exists("traiter_echap_math_dist")){
			$texte = echappe_html_3017($texte,"mdblocs",false,',<(md)(\s[^>]*)?'.'>(.*)</\1>,UimsS');
		}
		else {
			// echapper les blocs <md>...</md> car on ne veut pas toucher au <html>, <code>, <script> qui sont dedans !
			$texte = echappe_html($texte,"mdblocs",false,',<(md)(\s[^>]*)?'.'>(.*)</\1>,UimsS');
		}
	}


	return $texte;
}

/**
 * fonction appelee par echappe_html sur les balises <md></md>
 *
 * @param array $regs
 * @return string
 */
function traiter_echap_md_dist($regs){
	// echapons le code dans le markdown
	$texte = markdown_echappe_code($regs[3]);
	$texte = markdown_echappe_liens($texte);
	$texte = markdown_echappe_del($texte);
	return "<md".$regs[2].">$texte</md>";
}

/**
 * Echapper les blocs de code dans le MarkDown
 * @param $texte
 * @return string
 */
function markdown_echappe_code($texte){
	$texte = echappe_retour($texte);

	// tous les paragraphes indentes par 4 espaces ou une tabulation
	// mais qui ne sont pas la suite d'une liste ou d'un blockquote
	preg_match_all(",(^(    |\t|\* |\+ |- |> |\d+\.)(.*)$(\s*^(\s+|\t).*$)*?),Uims",$texte,$matches,PREG_SET_ORDER);
	foreach($matches as $match){
		if (!strlen(trim($match[2]))){
			#var_dump($match[0]);
			$p = strpos($texte,$match[0]);
			$texte = substr_replace($texte,code_echappement($match[0], 'md', true),$p,strlen($match[0]));
		}
	}

	if (strpos($texte,"```")!==false OR strpos($texte,"~~~")!==false){
		$texte = echappe_html($texte,'md',true,',^(```|~~~)\w*?\s.*\s(\1),Uims');
	}
	if (strpos($texte,"``")!==false){
		$texte = echappe_html($texte,'md',true,',``.*``,Uims');
	}
	if (strpos($texte,"`")!==false){
		$texte = echappe_html($texte,'md',true,',`.*`,Uims');
	}

	// escaping
	if (strpos($texte,"\\")!==false){
		$texte = echappe_html($texte,'md',true,',\\\\[\\`*_{}\[\]\(\)>+.!-],Uims');
	}

	return $texte;
}

/**
 * Echapper les ~~ qui sont transformes en <del> par MarkDown
 * mais en &nbsp; par la typo SPIP qui passe avant si on laisse tel quel
 * @param string $texte
 * @return string
 */
function markdown_echappe_del($texte){
	if (strpos($texte,"~~")!==false){
		$texte = echappe_html($texte,'md',true,',~~,Uims');
	}

	return $texte;
}

/**
 * Echapper les raccourcis de type lien dans MarkDown
 * pour proterger les morceaux qui risquent d'etre modifies par la typo SPIP
 * (URL, :,...)
 * @param string $texte
 * @return string
 */
function markdown_echappe_liens($texte){
	//[blabla](http://...) et ![babla](http://...)
	if (strpos($texte,"](")!==false){
		preg_match_all(",([!]?\[[^]]*\])(\([^)]*\)),Uims",$texte,$matches,PREG_SET_ORDER);
		foreach($matches as $match){
			#var_dump($match);
			$p = strpos($texte,$match[0]);
			$pre = $match[1];
			if (strncmp($pre,"!",1)==0){
				$pre = code_echappement("!", 'md', true).substr($pre,1);
			}
			$texte = substr_replace($texte,$pre.code_echappement($match[2], 'md', true),$p,strlen($match[0]));
		}
	}
	//    [blabla]: http://....
	if (strpos($texte,"[")!==false){
		preg_match_all(",^(\s*\[[^]]*\])(:[ \t]+[^\s]*(\s+(\".*\"|'.*'|\(.*\)))?)\s*$,Uims",$texte,$matches,PREG_SET_ORDER);
		foreach($matches as $match){
			#var_dump($match);
			$p = strpos($texte,$match[0])+strlen($match[1]);
			$texte = substr_replace($texte,code_echappement($match[2], 'md', true),$p,strlen($match[2]));
		}
	}
	// ![Markdown Logo][image]
	if (strpos($texte,"![")!==false){
		preg_match_all(",^(!\[[^]]*\])(\[[^]]*\])$,Uims",$texte,$matches,PREG_SET_ORDER);
		foreach($matches as $match){
			#var_dump($match);
			$p = strpos($texte,$match[0]);
			$texte = substr_replace($texte,code_echappement("!", 'md', true),$p,1);
		}
	}
	// <http://...>
	$texte = echappe_html($texte,'md',true,',' . '<https?://[^<]*>'.',UimsS');

	return $texte;
}

/**
 * Avant le traitemept typo et liens :
 * - des-echapper les blocs <md> qui ont ete echappes au tout debut
 *
 * @param string $texte
 * @return string
 */
function markdown_pre_liens($texte){
	// si pas de base64 dans le texte, rien a faire
	if (strpos($texte,"base64mdblocs")!==false) {
		// il suffit de desechapper les blocs <md> (mais dont on a echappe le code)
		$texte = echappe_retour($texte,'mdblocs');
	}

	// ici on a le html du code SPIP echappe, mais sans avoir touche au code MD qui est echappe aussi
	return $texte;
}


/**
 * Pre typo : Rien a faire on dirait
 * @param string $texte
 * @return string
 */
function markdown_pre_typo($texte){
	return $texte;
}


/**
 * Post typo : retablir les blocs de code dans le MarkDown
 * qui ont ete echappes en pre-liens
 * La on retrouve tout le contenu MarkDown initial, qui a beneficie des corrections typo
 * @param string $texte
 * @return string
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

	$mes_notes = "";
	// traiter les notes ici si il y a du <md> pour avoir une numerotation coherente
	if (strpos($texte,"<md>")!==false
	  AND strpos($texte,"[[")!==false){
		$notes = charger_fonction('notes', 'inc');
		// Gerer les notes (ne passe pas dans le pipeline)
		list($texte, $mes_notes) = $notes($texte);
	}

	$texte = markdown_filtre_portions_md($texte,"markdown_raccourcis");

	if ($mes_notes)
		$notes($mes_notes,'traiter');

	return $texte;
}


/**
 * Appliquer un filtre aux portions <md>...</md> du texte
 * utilise dans pre_propre()
 *
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
 * Appliquer Markdown sur un morceau de texte
 * @param $texte
 * @return string
 */
function markdown_raccourcis($texte){

	$md = $texte;

	// enlever les \n\n apres <div class="base64...."></div>
	// et surtout le passer en <p> car ca perturbe moins Markdown
	if (strpos($md,'<div class="base64')!==false){
		$md = preg_replace(",(<div (class=\"base64[^>]*>)</div>)\n\n,Uims","<p \\2</p>",$md);
	}

	// marquer les ul/ol explicites qu'on ne veut pas modifier
	if (stripos($md,"<ul")!==false OR stripos($md,"<ol")!==false OR stripos($md,"<li")!==false)
		$md = preg_replace(",<(ul|ol|li)(\s),Uims","<$1 html$2",$md);

	// parser le markdown
	$md = Parsedown::instance()->parse($md);

	// class spip sur ul et ol et retablir les ul/ol explicites d'origine
	$md = str_replace(array("<ul>","<ol>","<li>"),array('<ul'.$GLOBALS['class_spip_plus'].'>','<ol'.$GLOBALS['class_spip_plus'].'>','<li'.$GLOBALS['class_spip'].'>'),$md);
	$md = str_replace(array("<ul html","<ol html","<li html"),array('<ul','<ol','<li'),$md);

	// Si on avait des <p class="base64"></p> les repasser en div
	// et reparagrapher car MD n'est pas tres fort et fait de la soupe <p><div></div></p>
	if (strpos($md,'<p class="base64')!==false){
		$md = preg_replace(",(<p (class=\"base64[^>]*>)</p>),Uims","<div \\2</div>",$md);
		$md = paragrapher($md);
		// pas d'autobr introduit par paragrapher
		if (_AUTO_BR AND strpos($md,_AUTOBR)!==false){
			$md = str_replace(_AUTOBR,'',$md);
		}
		// eviter les >\n\n<p : un seul \n
		if (strpos($md,">\n\n<p")!==false){
			$md = str_replace(">\n\n<p",">\n<p",$md);
		}
	}

	// echapper le markdown pour que SPIP n'y touche plus
	return code_echappement($md,"md");
}

/**
 * @param $texte
 * @return string
 */
function markdown_post_propre($texte){
	static $hreplace=null;
	static $hmini=null;
	if (is_null($hreplace)){
		$hreplace = false;
		// on peut forcer par define, utile pour les tests unitaires
		if (defined('_MARKDOWN_HMINI'))
			$hmini = _MARKDOWN_HMINI;
		else {
			include_spip('inc/config');
			$hmini = lire_config("markdown/hmini",1);
		}
		if ($hmini>1){
			$hreplace = array();
			for ($i=5;$i>=1;$i--){
				$ir = min($i+1,6);
				$hreplace[1]["<h$i"] = "<h$ir";
				$hreplace[1]["</h$i"] = "</h$ir";
				$ir = min($i+2,6);
				$hreplace[2]["<h$i"] = "<h$ir";
				$hreplace[2]["</h$i"] = "</h$ir";
			}
		}
	}

	// blocs <md></md> echappes
	if (strpos($texte,'<div class="base64md')!==false){
		$texte = echappe_retour($texte,"md");
	}


	// la globale $GLOBALS['markdown_inh_hreplace'] permet d'inhiber le replace
	// utilisee dans le titrage automatique
	if (!isset($GLOBALS['markdown_inh_hreplace'])
		AND $hreplace AND strpos($texte,"</h")!==false){
		// si on veut h3 au plus haut et qu'il y a des h1, on decale de 2 vers le bas
		if ($hmini==3 AND strpos($texte,"</h1")){
			$texte = str_replace(array_keys($hreplace[2]),array_values($hreplace[2]),$texte);
		}
		// sinon si on veut h2 et qu'il y a h1 ou si on veut h3 et qu'il y a h2, on decale de 1 vers le bas
		elseif ( ($hmini==2 AND strpos($texte,"</h1"))
			OR ($hmini==3 AND strpos($texte,"</h2")) ){
			$texte = str_replace(array_keys($hreplace[1]),array_values($hreplace[1]),$texte);
		}
	}

	return $texte;
}



/**
 * Determiner un titre automatique,
 * a partir des champs textes de contenu
 *
 * @param array $champs_contenu
 *   liste des champs contenu textuels
 * @param array|null $c
 *   tableau qui contient les valeurs des champs de contenu
 *   si null on utilise les valeurs du POST
 * @param int $longueur
 *   longueur de coupe
 * @return string
 */
function inc_titrer_contenu($champs_contenu, $c=null, $longueur=80){
	// prendre la concatenation des champs texte
	$t = "";
	foreach($champs_contenu as $champ){
		$t .= _request($champ,$c)."\n\n";
	}

	if ($t){
		$GLOBALS['markdown_inh_hreplace'] = true;
		include_spip("inc/texte");
		$t = propre($t);
		unset($GLOBALS['markdown_inh_hreplace']);
		if (strpos($t,"</h1>")!==false
		  AND preg_match(",<h1[^>]*>(.*)</h1>,Uims",$t,$m)){
			$t = $m[1];
		}
		else {
			$t = couper($t,$longueur,"...");
		}
	}

	return $t;
}
