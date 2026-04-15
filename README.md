# Up Gutenberg Metabox

Un plugin WordPress pour ajouter facilement des metaboxes personnalisées aux sites FSE (Full Site Editing). Permet de créer des champs meta personnalisés pour différents post types et taxonomies avec une interface d'administration intuitive.

## Fonctionnalités

- ✅ Interface d'administration simple et intuitive
- ✅ Support de multiples metaboxes
- ✅ Configuration par post type et par taxonomie
- ✅ Types de champs variés (texte, textarea, select, checkbox, nombre, email, URL, galerie)
- ✅ Validation des données côté client et serveur
- ✅ Compatible avec les sites FSE WordPress
- ✅ Interface responsive
- ✅ Sécurisation avec nonces WordPress
- ✅ Block Binding Gutenberg (`core/post-meta` et `ugm/term-meta`)

## Installation

1. Téléchargez le plugin
2. Décompressez le fichier dans le dossier `/wp-content/plugins/`
3. Activez le plugin depuis l'interface d'administration WordPress

## Utilisation

### Configuration des Metaboxes

1. Allez dans **UG Metabox** dans le menu d'administration
2. Cliquez sur **"Ajouter une Metabox"**
3. Configurez votre metabox :
   - **Titre** : Le nom qui apparaîtra dans l'éditeur
   - **Post Types** : Sélectionnez les types de contenu où la metabox apparaîtra
   - **Taxonomies** : Sélectionnez les taxonomies (catégories, tags, etc.) où la metabox apparaîtra
   - **Champs** : Ajoutez autant de champs que nécessaire

### Types de Champs Disponibles

- **Texte** : Champ de saisie simple
- **Zone de Texte** : Champ multiligne
- **Liste Déroulante** : Menu avec options personnalisées
- **Case à Cocher** : Champ booléen
- **Nombre** : Champ numérique
- **Email** : Champ avec validation email
- **URL** : Champ avec validation URL
- **Galerie** : Sélection multiple d'images avec aperçu, réordonnable, valeur enregistrée en CSV d'IDs

### Récupération des Données

Pour récupérer les valeurs des champs meta dans vos thèmes :

**Pour les post types :**

```php
// Récupérer une valeur meta
$value = get_post_meta(get_the_ID(), 'nom_du_champ', true);

// Afficher la valeur
echo esc_html($value);

// Vérifier si une checkbox est cochée
$is_checked = get_post_meta(get_the_ID(), 'nom_checkbox', true) === '1';
```

**Pour les taxonomies (termes) :**

```php
// Récupérer une valeur meta depuis un terme
$term_id = get_queried_object_id(); // Sur une archive de taxonomie
$value = get_term_meta($term_id, 'nom_du_champ', true);

// Afficher la valeur
echo esc_html($value);

// Vérifier si une checkbox est cochée
$is_checked = get_term_meta($term_id, 'nom_checkbox', true) === '1';
```

## Structure du Plugin

```
up-gutenberg-metabox/
├── up-gutenberg-metabox.php      # Fichier principal
├── includes/
│   ├── admin-page.php            # Interface d'administration
│   ├── generate-page.php         # Page de génération de code
│   ├── class-code-generator.php  # Générateur de code pour le thème
│   ├── class-code-importer.php   # Importateur de code depuis le thème
│   └── filters/                  # Filtres de données dérivées (optionnels)
├── assets/
│   ├── css/
│   │   ├── admin.css            # Styles d'administration (config)
│   │   └── metabox-gallery.css  # Styles du champ galerie (éditeur)
│   └── js/
│       ├── admin.js             # Scripts d'administration (config)
│       ├── metabox-gallery.js   # Scripts du champ galerie (éditeur)
│       └── metababox-binding-copy.js  # Boutons de copie Block Binding
└── README.md                    # Documentation
```

## Hooks et Filtres

Le plugin utilise les hooks WordPress standards :

- `add_meta_boxes` : Pour ajouter les metaboxes aux post types
- `save_post` : Pour sauvegarder les données des post types
- `{taxonomy}_add_form_fields` : Pour ajouter les champs sur l'écran d'ajout de terme
- `{taxonomy}_edit_form_fields` : Pour ajouter les champs sur l'écran d'édition de terme
- `created_{taxonomy}` : Pour sauvegarder les données lors de la création d'un terme
- `edited_{taxonomy}` : Pour sauvegarder les données lors de l'édition d'un terme
- `admin_menu` : Pour ajouter le menu d'administration
- `admin_enqueue_scripts` : Pour charger les assets
- `init` : Pour enregistrer les meta fields (REST API) et la source de binding `ugm/term-meta`

## Sécurité

- Validation et nettoyage de toutes les données d'entrée
- Utilisation de nonces WordPress pour la sécurité CSRF
- Vérification des permissions utilisateur
- Échappement des données en sortie

## Compatibilité

- **WordPress** : 5.9 ou supérieur
- **PHP** : 7.4 ou supérieur
- **Compatible** avec les thèmes FSE (Full Site Editing)
- **Testé** jusqu'à WordPress 6.6

## Filtres personnalisés

### Créer un filtre personnalisé

1. Créez un fichier PHP dans `wp-content/plugins/up-gutenberg-metabox/includes/filters/` (ex: `mon-filtre.php`)
2. Utilisez le hook `add-gutenberg-metabox-filter` pour enregistrer votre filtre :

```php
<?php
// Fichier: wp-content/plugins/up-gutenberg-metabox/includes/filters/mon-filtre.php
add_action('add-gutenberg-metabox-filter', function() {
    if (!class_exists('UpGutenbergMetabox')) {
        return;
    }

    UpGutenbergMetabox::register_derived_filter(
        'mon_filtre', // ID du filtre
        __('Mon Filtre Personnalisé', 'up-gutenberg-metabox'), // Nom affiché
        function($value) {
            // Votre logique de transformation ici
            return $value; // Valeur transformée
        }
    );
});
```

### Exemple: Transformer un textarea en liste à puces

```php
// Fichier: includes/filters/text-to-list.php
add_action('add-gutenberg-metabox-filter', function() {
    if (!class_exists('UpGutenbergMetabox')) return;

    UpGutenbergMetabox::register_derived_filter(
        'text_to_list',
        __('Texte vers liste à puces', 'up-gutenberg-metabox'),
        function($value) {
            if (empty($value)) return '';
            $lines = array_filter(array_map('trim', explode("\n", $value)));
            if (empty($lines)) return '';
            
            $items = array_map(function($line) {
                return '<li>' . esc_html($line) . '</li>';
            }, $lines);
            
            return '<ul>' . implode('', $items) . '</ul>';
        }
    );
});
```

## Support

Pour toute question ou problème :

1. Vérifiez que votre version de WordPress et PHP sont compatibles
2. Assurez-vous que le plugin est correctement activé
3. Consultez les logs d'erreur WordPress

## Développement

### Structure de Données

Les metaboxes sont stockées dans l'option `ugm_metaboxes` avec la structure suivante :

```php
array(
    array(
        'id' => 'metabox_id',
        'title' => 'Titre de la Metabox',
        'post_types' => array('post', 'page'),
        'taxonomies' => array('category', 'post_tag'),
        'fields' => array(
            array(
                'name' => 'nom_du_champ',
                'label' => 'Libellé du Champ',
                'type' => 'text',
                'description' => 'Description optionnelle',
                'options' => array(), // Pour les champs select
                'binding_enabled' => true, // Pour Gutenberg Block Binding
                'derived_enabled' => false, // Pour les données dérivées
                'derived_filter' => 'identity' // Filtre de données dérivées
            )
        )
    )
)
```

### Raccourcis Clavier

- **Ctrl/Cmd + S** : Sauvegarder la configuration
- **Ctrl/Cmd + N** : Ajouter une nouvelle metabox

## Changelog

### Version 1.5.0
- Nouveau: Support complet des taxonomies — les metaboxes peuvent désormais être assignées aux taxonomies (catégories, tags, taxonomies personnalisées) en plus des post types
- Nouveau: Interfaces d'ajout et d'édition de terme avec rendu des champs taxonomy
- Nouveau: Sauvegarde des term meta avec nonce et sanitisation complète
- Nouveau: Enregistrement `register_term_meta` pour l'API REST (binding Gutenberg sur les taxonomies)
- Nouveau: Source de binding personnalisée `ugm/term-meta` pour lire les term meta sur les archives de taxonomie
- Nouveau: Boutons de copie Block Binding sur les écrans de terme (génère snippets `ugm/term-meta`)
- Nouveau: Générateur de code étendu pour inclure les hooks taxonomy et les assets binding sur les écrans taxonomy
- Nouveau: Colonne "Taxonomies" dans la page de génération de code
- Nouveau: Option pour inclure automatiquement `root.php` dans `functions.php` du thème
- Documentation: README mis à jour pour documenter le support taxonomy

### Version 1.3.3
- Nouveau: Support complet du champ type "gallery" dans le code généré pour le thème (assets JS/CSS inclus, rendu et sauvegarde fonctionnels).
- Correctif: Génération des assets gallery dans le dossier `functions/metabox/assets/` du thème.

### Version 1.3.2
- Correctif: Quand le plugin est actif, les assets "Block Binding copy" générés dans le thème ne sont plus chargés (évite les doublons)

### Version 1.3.1
- Correctif: Normalisation des noms de variables PHP générées (remplacement des tirets par des underscores)
- Nouveau: Mutualisation des filtres via une classe de base `UGM_Metabox` générée dans le thème (`functions/metabox/class-ugm-metabox.php`)
- Amélioration: Les fichiers générés utilisent désormais un en-tête `Author : GEHIN Nicolas`

### Version 1.3.0
- Nouveau: Système de génération de code pour exporter les metaboxes vers le thème
- Nouveau: Page "Générer" dans l'administration pour générer le code PHP des metaboxes
- Nouveau: Génération automatique des fichiers dans `functions/metabox/inc/` du thème
- Nouveau: Génération du fichier `root.php` avec inclusion des metaboxes sélectionnées
- Nouveau: Import/réimport des metaboxes depuis les fichiers du thème
- Nouveau: Classes `UGM_Code_Generator` et `UGM_Code_Importer` pour la gestion du code
- Amélioration: Le plugin peut maintenant servir uniquement à créer les metaboxes, puis être désactivé
- Documentation: Instructions pour l'utilisation du système de génération de code

### Version 1.2.0
- Nouveau: Type de champ « Galerie » avec sélection depuis la médiathèque, aperçu des miniatures, réordonnable (drag & drop), suppression d’images.
- Données: Enregistrement sécurisé sous forme de CSV d’IDs (sanitisation `absint`).
- Assets: Ajout `assets/js/metabox-gallery.js` et `assets/css/metabox-gallery.css`, chargés automatiquement sur les écrans d’édition (`post.php`, `post-new.php`).
- Docs: README et interface mis à jour pour inclure le type « Galerie ».

### Version 1.1.2
- Nouveau: Système de filtres personnalisables via `includes/filters/*.php` (ex: transformer un textarea en liste `<ul><li>`).
- Documentation: Ajout d'une section "Filtres personnalisés" dans le README.
- Compatibilité: Mise à jour de la version testée (WordPress 6.6).

### Version 1.1.1
- Correctifs HTML dans `includes/admin-page.php` (fermetures `div` manquantes/orphelines) pour restaurer une structure DOM valide.
- Amélioration de la robustesse du tri (jQuery UI Sortable) via un nettoyage défensif de la structure des champs dans `assets/js/admin.js` (aplatissement de `.ugm-field-config` imbriqués par erreur).
- UX: ouverture automatique des détails de la nouvelle metabox et des options du nouveau champ.
- Petites améliorations UI (alignements/actions/hover) dans `assets/css/admin.css`.

### Version 1.1.0
- Documentation: page de documentation intégrée et simplifiée (`includes/docs-page.php`) avec Quick Start pour le Block Binding.
- Binding Gutenberg: enregistrement automatique des metas en REST selon le type de champ pour `metadata.bindings`.
- UI: petites améliorations de l'interface d’admin et styles de code (`assets/css/admin.css`).
- Qualité: nettoyage HTML (suppression d’un `</details>` orphelin) et exemples lisibles (snippets échappés).

### Version 1.0.0
- Version initiale
- Interface d'administration complète
- Support de tous les types de champs de base
- Validation et sécurisation
- Documentation complète

## Licence

GPL v2 ou ultérieure

## Auteur

**Nicolas GEHIN**
- Site Web : https://nicolasgehin.com
- GitHub : https://github.com/nicolasgehin

---

*Ce plugin a été développé pour simplifier la création de metaboxes personnalisées dans WordPress, particulièrement pour les sites utilisant l'éditeur de blocs (Gutenberg) et les thèmes FSE.*
