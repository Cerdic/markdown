## MarkDown pour SPIP (experimental)

Ce plugin permet d'utiliser la syntaxe markdown dans un article SPIP.
Le texte � interpr�ter en markdown doit �tre entre `<md>...</md>`

Les corrections typographiques de SPIP (li�es � la langue) sont appliqu�es dans le MarkDown.

Les raccourcis de liens SPIP et les mod�les sont interpr�t�s dans le markdown,
ce qui permet d'�crire des liens indiff�rement avec la syntaxe SPIP ou la syntaxe markdown
(globalement fonctionnel, test suite � �crire)

TODO :
Proposer un formulaire de configuration qui permette de choisir le fonctionnement du plugin :
- Appliquer la syntaxe SPIP par d�faut et la syntaxe MarkDown dans les blocs `<md>..</md>`
- Appliquer la syntaxe MarkDown par d�faut et la syntaxe SPIP dans les blocs `<spip>..</spip>`
  Cette derni�re configuration est particuli�rement int�ressante dans le cadre d'un nouveau site
  mais n�cessiterait une migration des contenus d'un site existant.