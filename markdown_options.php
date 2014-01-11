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

function markdown_pre_propre($texte){
	if (!class_exists("Parsedown")){
		include_once _DIR_PLUGIN_MARKDOWN."lib/Parsedown.php";
	}

	$texte = Parsedown::instance()->parse($texte);

	return $texte;
}

?>