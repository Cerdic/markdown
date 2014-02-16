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

// echapper les blocs <md>...</md> avant les autres blocs html
define('_PROTEGE_BLOCS', ',<(md|html|code|cadre|frame|script)(\s[^>]*)?>(.*)</\1>,UimsS');
define('_PROTEGE_BLOCS_SPIP', ',<(html|code|cadre|frame|script)(\s[^>]*)?>(.*)</\1>,UimsS');

// fonction appelee par echappe_html sur les balises <md></md>
function traiter_echap_md_dist($regs){
	// echapons le code dans le markdown
	$texte = markdown_echappe_code($regs[3]);
	$texte = markdown_echappe_liens($texte);
	$texte = markdown_echappe_del($texte);
	return "<md".$regs[2].">$texte</md>";
}

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

function markdown_echappe_del($texte){
	if (strpos($texte,"~~")!==false){
		$texte = echappe_html($texte,'md',true,',~~,Uims');
	}

	return $texte;
}

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


function markdown_pre_liens($texte){
	// si pas de base64 dans le texte, rien a faire
	if (strpos($texte,"base64")!==false) {
		// on des-echappe : on recupere tout a l'identique
		// sauf le code du markdown echappe
		$texte = echappe_retour($texte);
		// on reechappe les blocs html
		// dans le code SPIP uniquement
		// sans transformation cette fois, puisque deja faite
		if (strpos($texte,"<md>")===false){
			$texte = echappe_html($texte,'',true,_PROTEGE_BLOCS_SPIP);
		}
		else {
			$splits = preg_split(",(<md>.*</md>),Uims",$texte,-1,PREG_SPLIT_DELIM_CAPTURE);
			foreach($splits as $k=>$s){
				if (strlen($s) AND strncmp($s,"<md>",4)!==0)
					$splits[$k] = echappe_html($s,'',true,_PROTEGE_BLOCS_SPIP);
			}
			$texte = implode('',$splits);
		}
	}

	// ici on a le html du code SPIP echappe, mais sans avoir touche au code MD qui est echappe aussi
	return $texte;
}

/**
 * Pre typo : echapper les ~~ pour ne pas les transformer en &nbsp;
 * @param string $texte
 * @return string
 */
function markdown_pre_typo($texte){
	return $texte;
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

	$md = $texte;

	// enlever les \n\n apres <div class="base64...."></div>
	// et surtout le passer en <p> car ca perturbe moins Markdown
	if (strpos($md,'<div class="base64')!==false){
		$md = preg_replace(",(<div (class=\"base64[^>]*>)</div>)\n\n,Uims","<p \\2</p>",$md);
	}

	// marker les ul/ol explicites qu'on ne veut pas modifier
	if (stripos($md,"<ul")!==false OR stripos($md,"<ol")!==false OR stripos($md,"<li")!==false)
		$md = preg_replace(",<(ul|ol|li)(\s),Uims","<$1 html$2",$md);

	// parser le markdown
	$md = Parsedown::instance()->parse($md);


	// class spip sur ul et ol et retablir les ul/ol explicites d'origine
	$md = str_replace(array("<ul>","<ol>","<li>"),array('<ul'.$GLOBALS['class_spip_plus'].'>','<ol'.$GLOBALS['class_spip_plus'].'>','<li'.$GLOBALS['class_spip'].'>'),$md);
	$md = str_replace(array("<ul html","<ol html","<li html"),array('<ul','<ol','<li'),$md);

	// Si on avait des <p class="base64' les repasser en div
	// et reparagrapher car MD n'est pas tres fort et fait de la soupe <p><div></div></p>
	if (strpos($md,'<p class="base64')!==false){
		$md = preg_replace(",(<p (class=\"base64[^>]*>)</p>),Uims","<div \\2</div>",$md);
		$md = paragrapher($md);
		if (_AUTO_BR AND strpos($md,_AUTOBR)!==false){
			$md = str_replace(_AUTOBR,'',$md);
		}
		// et les doubles \n<p
		if (strpos($md,">\n\n<p")!==false){
			$md = str_replace(">\n\n<p",">\n<p",$md);
		}
	}

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