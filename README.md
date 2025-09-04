# Up Gutenberg Metabox

Un plugin WordPress pour ajouter facilement des metaboxes personnalisées aux sites FSE (Full Site Editing). Permet de créer des champs meta personnalisés pour différents post types avec une interface d'administration intuitive.

## Fonctionnalités

- ✅ Interface d'administration simple et intuitive
- ✅ Support de multiples metaboxes
- ✅ Configuration par post type
- ✅ Types de champs variés (texte, textarea, select, checkbox, nombre, email, URL)
- ✅ Validation des données côté client et serveur
- ✅ Compatible avec les sites FSE WordPress
- ✅ Interface responsive
- ✅ Sécurisation avec nonces WordPress

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
   - **Champs** : Ajoutez autant de champs que nécessaire

### Types de Champs Disponibles

- **Texte** : Champ de saisie simple
- **Zone de Texte** : Champ multiligne
- **Liste Déroulante** : Menu avec options personnalisées
- **Case à Cocher** : Champ booléen
- **Nombre** : Champ numérique
- **Email** : Champ avec validation email
- **URL** : Champ avec validation URL

### Récupération des Données

Pour récupérer les valeurs des champs meta dans vos thèmes :

```php
// Récupérer une valeur meta
$value = get_post_meta(get_the_ID(), 'nom_du_champ', true);

// Afficher la valeur
echo esc_html($value);

// Vérifier si une checkbox est cochée
$is_checked = get_post_meta(get_the_ID(), 'nom_checkbox', true) === '1';
```

## Structure du Plugin

```
up-gutenberg-metabox/
├── up-gutenberg-metabox.php    # Fichier principal
├── includes/
│   └── admin-page.php          # Interface d'administration
├── assets/
│   ├── css/
│   │   └── admin.css          # Styles d'administration
│   └── js/
│       └── admin.js           # Scripts d'administration
└── README.md                  # Documentation
```

## Hooks et Filtres

Le plugin utilise les hooks WordPress standards :

- `add_meta_boxes` : Pour ajouter les metaboxes
- `save_post` : Pour sauvegarder les données
- `admin_menu` : Pour ajouter le menu d'administration
- `admin_enqueue_scripts` : Pour charger les assets

## Sécurité

- Validation et nettoyage de toutes les données d'entrée
- Utilisation de nonces WordPress pour la sécurité CSRF
- Vérification des permissions utilisateur
- Échappement des données en sortie

## Compatibilité

- **WordPress** : 5.9 ou supérieur
- **PHP** : 7.4 ou supérieur
- **Compatible** avec les thèmes FSE (Full Site Editing)
- **Testé** jusqu'à WordPress 6.3

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
        'fields' => array(
            array(
                'name' => 'nom_du_champ',
                'label' => 'Libellé du Champ',
                'type' => 'text',
                'description' => 'Description optionnelle',
                'options' => array() // Pour les champs select
            )
        )
    )
)
```

### Raccourcis Clavier

- **Ctrl/Cmd + S** : Sauvegarder la configuration
- **Ctrl/Cmd + N** : Ajouter une nouvelle metabox

## Changelog

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
