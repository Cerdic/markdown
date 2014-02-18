## MarkDown pour SPIP (experimental)

Ce plugin permet d'utiliser la syntaxe MarkDown dans un article SPIP.
Le texte � interpr�ter en MarkDown doit �tre entre `<md>...</md>`

Les corrections typographiques de SPIP (li�es � la langue) sont appliqu�es dans le MarkDown.

Les raccourcis de liens SPIP et les mod�les sont interpr�t�s dans le MarkDown,
ce qui permet d'�crire des liens indiff�rement avec la syntaxe SPIP ou la syntaxe MarkDown.

Les raccourcis de notes de bas de page de SPIP sont �galement interpr�t�s dans le MarkDown,
les notes sont num�rot�es continuement, ind�pendamment qu'elles soient dans le SPIP ou dans le MarkDown.

Un formulaire de configuration permet de choisir le fonctionnement du plugin :
  - Appliquer la syntaxe SPIP par d�faut et la syntaxe MarkDown dans les blocs `<md>..</md>`
  - Appliquer la syntaxe MarkDown par d�faut et la syntaxe SPIP dans les blocs `<spip>..</spip>`
  Dans ce dernier cas les contenus existant ne sont pas migr�s, et peuvent �tre rendus de mani�re incorrecte.


### Tests unitaires

Le plugin repose sur la librairie http://parsedown.org/ dont il reprend les tests unitaires qui sont tous valides
dans un raccourci `<md>..</md>`, ce qui garanti qu'on ne perturbe pas la syntaxe MarkDown par la prise en charge
de la typographie ainsi que des raccourcis de lien SPIP (Tests `parsedown`).

Un jeu de tests compl�mentaire concerne aussi le respect de la syntaxe MarkDown, mais porte sur des cas limites
g�n�r�s par l'interaction entre le moteur SPIP et le moteur Parsedown (Tests `MarkDown`).

Un jeu de tests unitaires assure que le corrections typographiques sont bien appliqu�es l� et uniquement l�
o� c'est attendu dans le texte au format MarkDown (Tests `MarkDown_typo`).

Un jeu de tests unitaires concerne la prise en charge des raccourcis de liens SPIP dans le MarkDown (Tests `MarkDown_liens_spip`).

Deux jeux de tests unitaires concernent l'utilisation de modeles SPIP de type inline et block pris en charge MarkDown
tout en pr�servant le paragraphage (cas typique des modeles de document).

### TODO

- Proposer un scenario de migration des contenus d'un site existant pour utiliser la syntaxe MarkDown par defaut

- Adapter la prise en charge de la syntaxe dans le porte-plume (markitup est initialement con�u pour MarkDown
  donc il s'agit surtout de pouvoir switcher de syntaxe manuellement ou automatiquement)