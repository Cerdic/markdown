## MarkDown pour SPIP (experimental)

Ce plugin permet d'utiliser la syntaxe markdown dans un article SPIP.
Le texte à interpréter en markdown doit être entre `<md>...</md>`

Les corrections typographiques de SPIP (liées à la langue) sont appliquées dans le MarkDown.

Les raccourcis de liens SPIP et les modèles sont interprétés dans le markdown,
ce qui permet d'écrire des liens indifférement avec la syntaxe SPIP ou la syntaxe markdown
(globalement fonctionnel, test suite à écrire)

TODO :
Proposer un formulaire de configuration qui permette de choisir le fonctionnement du plugin :
- Appliquer la syntaxe SPIP par défaut et la syntaxe MarkDown dans les blocs `<md>..</md>`
- Appliquer la syntaxe MarkDown par défaut et la syntaxe SPIP dans les blocs `<spip>..</spip>`
  Cette dernière configuration est particulièrement intéressante dans le cadre d'un nouveau site
  mais nécessiterait une migration des contenus d'un site existant.