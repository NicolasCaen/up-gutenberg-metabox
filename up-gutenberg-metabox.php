<?php
/**
 * Plugin Name: Up Gutenberg Metabox
 * Plugin URI: https://github.com/nicolasgehin/up-gutenberg-metabox
 * Description: Plugin pour ajouter facilement des metaboxes personnalisées aux sites FSE (Full Site Editing). Permet de créer des champs meta personnalisés pour différents post types.
 * Version: 1.3.2
 * Author: Nicolas GEHIN
 * Author URI: https://nicolasgehin.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: up-gutenberg-metabox
 * Domain Path: /languages
 * Requires at least: 5.9
 * Tested up to: 6.6
 * Requires PHP: 7.4
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Définir les constantes du plugin
define('UGM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('UGM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('UGM_PLUGIN_VERSION', '1.3.2');

// Inclure les classes
require_once UGM_PLUGIN_PATH . 'includes/class-code-generator.php';
require_once UGM_PLUGIN_PATH . 'includes/class-code-importer.php';

/**
 * Classe principale du plugin Up Gutenberg Metabox
 */
class UpGutenbergMetabox {
    /**
     * Registre des filtres de données dérivées.
     * @var array<string,array{label:string,callback:callable}>
     */
    private static $derived_filters = array();
    
    /**
     * Instance unique de la classe
     */
    private static $instance = null;
    
    /**
     * Obtenir l'instance unique de la classe
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructeur privé pour empêcher l'instanciation directe
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialiser le plugin
     */
    private function init() {
        // Charger les traductions
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // Charger les filtres personnalisés (chaque fichier se branche sur le hook 'add-gutenberg-metabox-filter')
        $this->load_custom_filters();

        // Ajouter le menu d'administration
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Enregistrer les scripts et styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Initialiser les metaboxes
        add_action('add_meta_boxes', array($this, 'add_custom_metaboxes'));
        
        // Sauvegarder les données des metaboxes
        add_action('save_post', array($this, 'save_metabox_data'));

        // Enregistrer les metas pour le binding Gutenberg (REST)
        add_action('init', array($this, 'register_binding_meta'));

        // Initialiser les filtres dérivés et exposer un hook pour en ajouter
        add_action('init', array($this, 'init_derived_filters'), 5);
        
        // Hook d'activation
        register_activation_hook(__FILE__, array($this, 'activate'));
        
        // Hook de désactivation
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    /**
     * Charger automatiquement les fichiers de filtres personnalisés
     * présents dans includes/filters/*.php
     */
    private function load_custom_filters() {
        $dir = trailingslashit(UGM_PLUGIN_PATH . 'includes/filters');
        if (!is_dir($dir)) {
            return;
        }
        foreach (glob($dir . '*.php') as $file) {
            // Chaque fichier est censé appeler add_action('add-gutenberg-metabox-filter', ...)
            // pour enregistrer un filtre via UpGutenbergMetabox::register_derived_filter(...)
            require_once $file;
        }
    }
    
    /**
     * Charger les traductions
     */
    public function load_textdomain() {
        load_plugin_textdomain('up-gutenberg-metabox', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    /**
     * Initialiser les filtres de données dérivées et permettre l'extensibilité.
     */
    public function init_derived_filters() {
        // Filtres par défaut
        self::register_derived_filter('identity', __('(Aucun) valeur inchangée', 'up-gutenberg-metabox'), function($v){ return $v; });
        self::register_derived_filter('number_thousands', __('Nombre avec séparateur de milliers', 'up-gutenberg-metabox'), function($v){
            if ($v === '' || $v === null) return '';
            return number_format((float)$v, 0, ',', ' ');
        });
        self::register_derived_filter('surface_m2', __('Surface (m²)', 'up-gutenberg-metabox'), function($v){
            if ($v === '' || $v === null) return '';
            if (is_numeric($v)) {
                return number_format((float)$v, 0, ',', ' ') . ' M2';
            }
            return trim((string)$v) . ' M2';
        });
        self::register_derived_filter('currency_eur', __('Montant en € (milliers + suffixe)', 'up-gutenberg-metabox'), function($v){
            if ($v === '' || $v === null) return '';
            return number_format((float)$v, 0, ',', ' ') . ' €';
        });
        self::register_derived_filter('currency_eur_2dec', __('Montant en € (2 décimales)', 'up-gutenberg-metabox'), function($v){
            if ($v === '' || $v === null) return '';
            return number_format((float)$v, 2, ',', ' ') . ' €';
        });
        self::register_derived_filter('yes_no', __('Checkbox vers Oui / Non', 'up-gutenberg-metabox'), function($v){
            return ((string)$v === '1') ? __('Oui', 'up-gutenberg-metabox') : __('Non', 'up-gutenberg-metabox');
        });
        self::register_derived_filter('uppercase', __('Texte en MAJUSCULES', 'up-gutenberg-metabox'), function($v){ return mb_strtoupper((string)$v); });
        self::register_derived_filter('lowercase', __('Texte en minuscules', 'up-gutenberg-metabox'), function($v){ return mb_strtolower((string)$v); });
        self::register_derived_filter('date_dmy', __('Date Y-m-d vers d/m/Y', 'up-gutenberg-metabox'), function($v){
            $ts = strtotime((string)$v);
            return $ts ? date_i18n('d/m/Y', $ts) : (string)$v;
        });
        self::register_derived_filter('datetime_dmy_hi', __('Date/heure vers d/m/Y H:i', 'up-gutenberg-metabox'), function($v){
            $ts = strtotime((string)$v);
            return $ts ? date_i18n('d/m/Y H:i', $ts) : (string)$v;
        });

        // Hook public: les extensions peuvent enregistrer leurs filtres ici
        // Exemple: add_action('add-gutenberg-metabox-filter', function(){ UpGutenbergMetabox::register_derived_filter('slug','Label', function($v){...}); });
        do_action('add-gutenberg-metabox-filter');
    }

    /**
     * Enregistrer un filtre de donnée dérivée.
     */
    public static function register_derived_filter($slug, $label, $callback) {
        if (!is_string($slug) || $slug === '' || !is_callable($callback)) {
            return;
        }
        self::$derived_filters[$slug] = array(
            'label' => (string)$label,
            'callback' => $callback,
        );
    }

    /**
     * Obtenir la liste des filtres disponibles (slug => label).
     */
    public static function get_derived_filters_labels() {
        $labels = array();
        foreach (self::$derived_filters as $k => $def) {
            $labels[$k] = isset($def['label']) ? $def['label'] : $k;
        }
        return $labels;
    }

    /**
     * Appliquer un filtre à une valeur.
     */
    private function apply_derived_filter($slug, $value) {
        if (isset(self::$derived_filters[$slug]['callback']) && is_callable(self::$derived_filters[$slug]['callback'])) {
            return call_user_func(self::$derived_filters[$slug]['callback'], $value);
        }
        return $value;
    }
    
    /**
     * Ajouter le menu d'administration
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Up Gutenberg Metabox', 'up-gutenberg-metabox'),
            __('UG Metabox', 'up-gutenberg-metabox'),
            'manage_options',
            'up-gutenberg-metabox',
            array($this, 'admin_page'),
            'dashicons-layout',
            30
        );

        // Sous-menu Réglages
        add_submenu_page(
            'up-gutenberg-metabox',
            __('Réglages', 'up-gutenberg-metabox'),
            __('Réglages', 'up-gutenberg-metabox'),
            'manage_options',
            'ugm-settings',
            array($this, 'settings_page')
        );

        // Sous-menu Documentation
        add_submenu_page(
            'up-gutenberg-metabox',
            __('Documentation', 'up-gutenberg-metabox'),
            __('Documentation', 'up-gutenberg-metabox'),
            'manage_options',
            'ugm-documentation',
            array($this, 'docs_page')
        );

        // Sous-menu Generate
        add_submenu_page(
            'up-gutenberg-metabox',
            __('Générer', 'up-gutenberg-metabox'),
            __('Générer', 'up-gutenberg-metabox'),
            'manage_options',
            'ugm-generate',
            array($this, 'generate_page')
        );
    }
    
    /**
     * Enregistrer les scripts et styles d'administration
     */
    public function enqueue_admin_scripts($hook) {
        if ('toplevel_page_up-gutenberg-metabox' === $hook) {
            wp_enqueue_script('up-gutenberg-metabox-admin', UGM_PLUGIN_URL . 'assets/js/admin.js', array('jquery', 'jquery-ui-sortable'), UGM_PLUGIN_VERSION, true);
            wp_enqueue_style('up-gutenberg-metabox-admin', UGM_PLUGIN_URL . 'assets/css/admin.css', array(), UGM_PLUGIN_VERSION);
            
            // Localiser le script pour AJAX
            wp_localize_script('up-gutenberg-metabox-admin', 'ugm_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ugm_nonce'),
            ));
        }

        // En file d'attente des scripts pour les écrans d'édition des contenus (metaboxes)
        if (in_array($hook, array('post.php', 'post-new.php'), true)) {
            // Media frame (WP)
            wp_enqueue_media();

            // Injecter le bouton "copier snippet Block Binding" directement dans les metabox
            wp_enqueue_script(
                'up-gutenberg-metabox-binding-copy',
                UGM_PLUGIN_URL . 'assets/js/metabox-binding-copy.js',
                array('jquery'),
                UGM_PLUGIN_VERSION,
                true
            );
            // Script et style pour le champ galerie
            wp_enqueue_script(
                'up-gutenberg-metabox-gallery',
                UGM_PLUGIN_URL . 'assets/js/metabox-gallery.js',
                array('jquery', 'jquery-ui-sortable'),
                UGM_PLUGIN_VERSION,
                true
            );
            wp_enqueue_style(
                'up-gutenberg-metabox-gallery',
                UGM_PLUGIN_URL . 'assets/css/metabox-gallery.css',
                array(),
                UGM_PLUGIN_VERSION
            );

            // Un peu de CSS partagé (alignements)
            wp_enqueue_style('up-gutenberg-metabox-admin', UGM_PLUGIN_URL . 'assets/css/admin.css', array(), UGM_PLUGIN_VERSION);
            wp_localize_script('up-gutenberg-metabox-gallery', 'ugm_gallery_i18n', array(
                'selectImages' => __('Choisir des images', 'up-gutenberg-metabox'),
                'remove' => __('Retirer', 'up-gutenberg-metabox'),
            ));
        }
    }
    
    /**
     * Afficher la page d'administration
     */
    public function admin_page() {
        include UGM_PLUGIN_PATH . 'includes/admin-page.php';
    }

    /**
     * Page Documentation
     */
    public function docs_page() {
        include UGM_PLUGIN_PATH . 'includes/docs-page.php';
    }

    /**
     * Page Generate
     */
    public function generate_page() {
        include UGM_PLUGIN_PATH . 'includes/generate-page.php';
    }

    /**
     * Page Réglages
     */
    public function settings_page() {
        include UGM_PLUGIN_PATH . 'includes/settings-page.php';
    }

    /**
     * Obtenir la source configurée pour une metabox (plugin/theme).
     */
    private function get_metabox_source($metabox_id) {
        $sources = get_option('ugm_metabox_sources', array());
        if (isset($sources[$metabox_id]) && in_array($sources[$metabox_id], array('plugin', 'theme'), true)) {
            return $sources[$metabox_id];
        }
        return 'plugin';
    }
    
    /**
     * Ajouter les metaboxes personnalisées
     */
    public function add_custom_metaboxes() {
        $metaboxes = get_option('ugm_metaboxes', array());
        
        foreach ($metaboxes as $metabox) {
            if (!empty($metabox['id']) && $this->get_metabox_source($metabox['id']) === 'theme') {
                continue;
            }
            if (!empty($metabox['post_types']) && !empty($metabox['title'])) {
                foreach ($metabox['post_types'] as $post_type) {
                    add_meta_box(
                        'ugm_metabox_' . $metabox['id'],
                        $metabox['title'],
                        array($this, 'render_metabox'),
                        $post_type,
                        'normal',
                        'default',
                        $metabox
                    );
                }
            }
        }
    }
    
    /**
     * Afficher le contenu d'une metabox
     */
    public function render_metabox($post, $metabox) {
        $metabox_data = $metabox['args'];
        wp_nonce_field('ugm_save_metabox_' . $metabox_data['id'], 'ugm_metabox_nonce_' . $metabox_data['id']);
        
        echo '<table class="form-table ugm-metabox-table">';
        
        if (!empty($metabox_data['fields'])) {
            foreach ($metabox_data['fields'] as $field) {
                $field_value = get_post_meta($post->ID, $field['name'], true);
                $this->render_field($field, $field_value);
            }
        }
        
        echo '</table>';
    }
    
    /**
     * Afficher un champ de metabox
     */
    private function render_field($field, $value = '') {
        echo '<tr>';
        echo '<th scope="row"><label for="' . esc_attr($field['name']) . '">' . esc_html($field['label']) . '</label></th>';
        echo '<td>';
        
        switch ($field['type']) {
            case 'text':
                echo '<input type="text" id="' . esc_attr($field['name']) . '" name="' . esc_attr($field['name']) . '" value="' . esc_attr($value) . '" class="regular-text" />';
                break;
                
            case 'textarea':
                echo '<textarea id="' . esc_attr($field['name']) . '" name="' . esc_attr($field['name']) . '" rows="5" cols="50" class="large-text">' . esc_textarea($value) . '</textarea>';
                break;
                
            case 'select':
                echo '<select id="' . esc_attr($field['name']) . '" name="' . esc_attr($field['name']) . '">';
                if (!empty($field['options'])) {
                    foreach ($field['options'] as $option_value => $option_label) {
                        echo '<option value="' . esc_attr($option_value) . '"' . selected($value, $option_value, false) . '>' . esc_html($option_label) . '</option>';
                    }
                }
                echo '</select>';
                break;
                
            case 'checkbox':
                echo '<input type="checkbox" id="' . esc_attr($field['name']) . '" name="' . esc_attr($field['name']) . '" value="1"' . checked($value, '1', false) . ' />';
                break;
                
            case 'number':
                echo '<input type="number" id="' . esc_attr($field['name']) . '" name="' . esc_attr($field['name']) . '" value="' . esc_attr($value) . '" class="small-text" />';
                break;
                
            case 'email':
                echo '<input type="email" id="' . esc_attr($field['name']) . '" name="' . esc_attr($field['name']) . '" value="' . esc_attr($value) . '" class="regular-text" />';
                break;
            
            case 'url':
                echo '<input type="url" id="' . esc_attr($field['name']) . '" name="' . esc_attr($field['name']) . '" value="' . esc_attr($value) . '" class="regular-text" />';
                break;

            case 'gallery':
                // Render a media-managed gallery: hidden CSV input + sortable thumbs
                $ids = array_filter(array_map('absint', array_filter(array_map('trim', explode(',', (string)$value)))));
                echo '<div class="ugm-gallery-field" data-input="#' . esc_attr($field['name']) . '">';
                echo '<button type="button" class="button ugm-gallery-select">' . esc_html__('Choisir des images', 'up-gutenberg-metabox') . '</button>';
                echo '<ul class="ugm-gallery-list" data-name="' . esc_attr($field['name']) . '">';
                if (!empty($ids)) {
                    foreach ($ids as $att_id) {
                        $thumb = wp_get_attachment_image($att_id, array(80,80), true);
                        if ($thumb) {
                            echo '<li class="ugm-gallery-item" data-id="' . esc_attr($att_id) . '">';
                            echo '<span class="ugm-thumb">' . $thumb . '</span>';
                            echo '<button type="button" class="button-link ugm-remove" aria-label="' . esc_attr__('Retirer', 'up-gutenberg-metabox') . '">&times;</button>';
                            echo '</li>';
                        }
                    }
                }
                echo '</ul>';
                echo '<input type="hidden" id="' . esc_attr($field['name']) . '" name="' . esc_attr($field['name']) . '" value="' . esc_attr(implode(',', $ids)) . '" />';
                echo '</div>';
                break;
        }
        
        if (!empty($field['description'])) {
            echo '<p class="description">' . esc_html($field['description']) . '</p>';
        }
        echo '</td>';
        echo '</tr>';
    }

    /**
     * Enregistrer les metas en REST pour le Block Binding
     */
    public function register_binding_meta() {
        $metaboxes = get_option('ugm_metaboxes', array());
        if (empty($metaboxes) || !is_array($metaboxes)) {
            return;
        }

        foreach ($metaboxes as $metabox) {
            if (!empty($metabox['id']) && $this->get_metabox_source($metabox['id']) === 'theme') {
                continue;
            }
            if (empty($metabox['fields']) || empty($metabox['post_types'])) {
                continue;
            }

            foreach ($metabox['fields'] as $field) {
                if (empty($field['name']) || empty($field['binding_enabled'])) {
                    continue;
                }

                // Déterminer le type de schéma REST
                $schema_type = 'string';
                if (!empty($field['binding_type'])) {
                    $schema_type = $field['binding_type'];
                } else if (!empty($field['type'])) {
                    switch ($field['type']) {
                        case 'checkbox':
                            $schema_type = 'boolean';
                            break;
                        case 'number':
                            $schema_type = 'number';
                            break;
                        default:
                            $schema_type = 'string';
                    }
                }

                // Déterminer le type de meta DB (aligner avec schema si possible)
                $meta_type = in_array($schema_type, array('boolean','number','integer'), true) ? $schema_type : 'string';

                $args = array(
                    'single' => true,
                    'type' => $meta_type,
                    'show_in_rest' => array(
                        'schema' => array(
                            'type' => $schema_type,
                        )
                    ),
                    'auth_callback' => function() { return current_user_can('edit_posts'); }
                );

                // Si select, tenter d'exposer enum
                if (!empty($field['type']) && $field['type'] === 'select' && !empty($field['options']) && is_array($field['options'])) {
                    $args['show_in_rest']['schema']['enum'] = array_keys($field['options']);
                }

                foreach ((array) $metabox['post_types'] as $post_type) {
                    register_post_meta($post_type, $field['name'], $args);
                }

                // Enregistrer la meta dérivée en REST si activée
                if (!empty($field['derived_enabled'])) {
                    $derived_key = $field['name'] . '_formatted';
                    foreach ((array)$metabox['post_types'] as $post_type) {
                        register_post_meta($post_type, $derived_key, array(
                            'single' => true,
                            'type' => 'string',
                            'show_in_rest' => array(
                                'schema' => array('type' => 'string')
                            ),
                            'auth_callback' => function(){ return current_user_can('edit_posts'); }
                        ));
                    }
                }
            }
        }
    }
    
    /**
     * Sauvegarder les données des metaboxes
     */
    public function save_metabox_data($post_id) {
        // Vérifier si c'est un autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Vérifier les permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        $metaboxes = get_option('ugm_metaboxes', array());
        
        foreach ($metaboxes as $metabox) {
            if (!empty($metabox['id']) && $this->get_metabox_source($metabox['id']) === 'theme') {
                continue;
            }
            $nonce_name = 'ugm_metabox_nonce_' . $metabox['id'];
            $nonce_action = 'ugm_save_metabox_' . $metabox['id'];
            
            // Vérifier le nonce
            if (!isset($_POST[$nonce_name]) || !wp_verify_nonce($_POST[$nonce_name], $nonce_action)) {
                continue;
            }
            
            // Sauvegarder les champs
            if (!empty($metabox['fields'])) {
                foreach ($metabox['fields'] as $field) {
                    if (isset($_POST[$field['name']])) {
                        // Enlever les slashs ajoutés automatiquement par WP avant sanitisation
                        $raw_value = wp_unslash($_POST[$field['name']]);
                        // Préserver les retours à la ligne pour les textarea
                        if (isset($field['type']) && $field['type'] === 'textarea') {
                            $value = sanitize_textarea_field($raw_value);
                        } elseif (isset($field['type']) && $field['type'] === 'gallery') {
                            // Normaliser en liste d'IDs entiers
                            $ids = array_filter(array_map('absint', array_filter(array_map('trim', explode(',', (string)$raw_value)))));
                            $value = implode(',', $ids);
                        } else {
                            $value = sanitize_text_field($raw_value);
                        }
                        update_post_meta($post_id, $field['name'], $value);

                        // Gestion de la donnée dérivée
                        if (!empty($field['derived_enabled'])) {
                            $derived_key = $field['name'] . '_formatted';
                            $filter_slug = isset($field['derived_filter']) ? $field['derived_filter'] : 'identity';
                            $derived_value = $this->apply_derived_filter($filter_slug, $value);
                            if ($derived_value === '' || $derived_value === null) {
                                delete_post_meta($post_id, $derived_key);
                            } else {
                                update_post_meta($post_id, $derived_key, $derived_value);
                            }
                        }
                    } else {
                        // Pour les checkboxes non cochées
                        if ($field['type'] === 'checkbox') {
                            update_post_meta($post_id, $field['name'], '0');
                        }
                        // Même si non envoyé, si dérivé activé et case décochée pour une checkbox, recalculer depuis nouvelle valeur
                        if (!empty($field['derived_enabled']) && $field['type'] === 'checkbox') {
                            $derived_key = $field['name'] . '_formatted';
                            $filter_slug = isset($field['derived_filter']) ? $field['derived_filter'] : 'identity';
                            $derived_value = $this->apply_derived_filter($filter_slug, '0');
                            if ($derived_value === '' || $derived_value === null) {
                                delete_post_meta($post_id, $derived_key);
                            } else {
                                update_post_meta($post_id, $derived_key, $derived_value);
                            }
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Activation du plugin
     */
    public function activate() {
        // Créer les options par défaut
        if (!get_option('ugm_metaboxes')) {
            add_option('ugm_metaboxes', array());
        }
    }
    
    /**
     * Désactivation du plugin
     */
    public function deactivate() {
        // Nettoyer si nécessaire
    }
}

// Initialiser le plugin
UpGutenbergMetabox::get_instance();
