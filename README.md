## MarkDown pour SPIP (experimental)

Ce plugin permet d'utiliser la syntaxe markdown dans un article SPIP.
Le texte à interpréter en markdown doit être entre `<md>...</md>`

Les corrections typographiques de SPIP (liées à la langue) sont appliquées dans le MarkDown.

Les raccourcis de liens SPIP et les modèles sont interprétés dans le markdown,
ce qui permet d'écrire des liens indifférement avec la syntaxe SPIP ou la syntaxe markdown
(globalement fonctionnel, tests unitaires à écrire et à valider).

### Tests unitaires

Le plugin repose sur la librairie http://parsedown.org/ dont il reprend les tests unitaires qui sont tous valides
dans un raccourci `<md>..</md>`, ce qui garanti qu'on ne perturbe pas la syntaxe MarkDown par la prise en charge
de la typographie ainsi que des raccourcis de lien SPIP.

Un jeu de test unitaire complémentaire assure que le corrections typographiques sont bien appliquées là et uniquement là
où c'est attendu dans le texte au format MarkDown.

Un troisième jeu de test unitaire reste à écrire sur la prise en charge des raccourcis de liens SPIP et des modèles dans le texte MarkDown.

### TODO
Proposer un formulaire de configuration qui permette de choisir le fonctionnement du plugin :
- Appliquer la syntaxe SPIP par défaut et la syntaxe MarkDown dans les blocs `<md>..</md>`
- Appliquer la syntaxe MarkDown par défaut et la syntaxe SPIP dans les blocs `<spip>..</spip>`
  Cette dernière configuration est particulièrement intéressante dans le cadre d'un nouveau site
  mais nécessiterait une migration des contenus d'un site existant.