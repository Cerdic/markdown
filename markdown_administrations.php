<?php
/**
 * Fichier gérant l'installation et désinstallation du plugin Markdown
 *
 * @plugin     Markdown
 * @copyright  2014
 * @author     Cédric
 * @licence    GNU/GPL
 * @package    SPIP\Markdown\Installation
 */


// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Fonction d'installation et de mise à jour du plugin Markdown
 *
 * @param string $nom_meta_base_version
 *     Nom de la meta informant de la version du schéma de données du plugin installé dans SPIP
 * @param string $version_cible
 *     Version du schéma de données dans ce plugin (déclaré dans paquet.xml)
 * @return void
**/
function markdown_upgrade($nom_meta_base_version, $version_cible) {
	$maj = array();

	$maj['create'] = array(array('markdown_type_document_md'));

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}


/**
 * Fonction de désinstallation du plugin Markdown
 *
 * @param string $nom_meta_base_version
 *     Nom de la meta informant de la version du schéma de données du plugin installé dans SPIP
 * @return void
**/
function markdown_vider_tables($nom_meta_base_version) {

	effacer_meta($nom_meta_base_version);
}


/**
 * Ajouter la ligne pour les fichiers Markdown dans la table des types de document `spip_types_documents`
 *
 * Informations sur le mime type tirées de Shared Mime Info http://cgit.freedesktop.org/xdg/shared-mime-info/tree/freedesktop.org.xml.in#n5433
 *
 * @return void
 */
function markdown_type_document_md() {

	include_spip('base/abstract_sql');

	// ajouter la ligne
	if (!sql_countsel("spip_types_documents", "extension=".sql_quote('md'))) {
		sql_insertq("spip_types_documents", array(
			"extension"    => "md",
			"titre"        => "Markdown document",
			"mime_type"    => "text/x-markdown",
			"inclus"       => "embed",
			"upload"       => "oui",
			"media_defaut" => "file"
		));
	}

}


?>
