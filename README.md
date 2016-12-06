# Goliath doc viewer

Le principe est d'afficher l'aide utilisateur d'un theme dans l'admin de WordPress. 

Pour ce faire c'est assez simple il suffit de créer un dossier **doc/** à la racine de votre thème. Dans ce dossier vous pourrez ajouter autant de ficheri d'aide en Markdown que vous le souhaitez. Tout c'est fichier seront alors visible dans un menu "Theme Doc" dans l'admin de Wordpress.

## Aide contextuelles

Il est intéréssant de noter que ce plugin peux également ajouter votre aide dans l'aide contextuelle des différentes page de l'admin. Cela ce fait simplement par convention de nomage des fichiers Markdown Pour le moment sont pris en compte :

* archive-{post-type}.md : affiche votre aide dans la liste du post type
* single-{post-type}.md : affiche votre aide dans sur la page de modification d'un post du post type
* {modele-de-page}.md : affiche votre aide dans sur la page de modification d'une page utilisant le modéle de page avec le même nom ( modele-de-page.php )

