# WP Doc Viewer

Le principe est d'afficher l'aide utilisateur d'un theme dans l'admin de WordPress. 

Pour ce faire, créer un dossier **doc/** à la racine de votre thème. Dans ce dossier vous pourrez ajouter autant de ficheir d'aide en Markdown que vous le souhaitez. Tout ces fichiers seront alors visible dans un menu "Theme Doc" dans l'admin de Wordpress.

Ajouter d'autre dossier que le plugin va parser avec le filtre suivant :

```php
add_filter('mdv/doc-paths', static function( $path){

    $path[] = plugin_dir_path(__FILE__) . 'doc';
    return $path;
} );
```

## Meta Markdown

Chaque fichier markdown peut déclarer ( ou non ) les metas suivantes :
```
---
title: 'Icones SVG'
order: 10
section: 'Blocs'
---
```

* `title` : Le titre qui sera afficher dans le menu et la table des matière
* `order` : Permet de gérer l'ordre d'affichage des l'aides au sein de sa section
* `section` : Nom de la section ou l'aide dois s'afficher ( si aucune section n’est déclaré le fichier d’aide s’affiche dans la section générale )

## Section

Toutes les fichiers d’aides sont organisé en section. Pour gérer la section utiliser soit les metas soit arganiser votre dossier "doc" avec des sous-dossier. Votre hierachie de dossier sera transposer en section. 

## Aide contextuelles

Cce plugin peux également ajouter votre aide dans l'aide contextuelle des différentes page de l'admin. Cela ce fait par convention de nomage des fichiers Markdown Pour le moment sont pris en compte :

* archive-{post-type}.md : affiche votre aide dans la liste du post type
* single-{post-type}.md : affiche votre aide dans sur la page de modification d'un post du post type ( ne marche plus avec le nouvelle éditeur en bloc )
* {modele-de-page}.md : affiche votre aide dans sur la page de modification d'une page utilisant le modéle de page avec le même nom ( modele-de-page.php )

