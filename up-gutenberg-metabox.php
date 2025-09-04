<?php
/**
 * Plugin Name: Up Gutenberg Metabox
 * Plugin URI: https://github.com/nicolasgehin/up-gutenberg-metabox
 * Description: Plugin pour ajouter facilement des metaboxes personnalisées aux sites FSE (Full Site Editing). Permet de créer des champs meta personnalisés pour différents post types.
 * Version: 1.0.0
 * Author: Nicolas GEHIN
 * Author URI: https://nicolasgehin.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: up-gutenberg-metabox
 * Domain Path: /languages
 * Requires at least: 5.9
 * Tested up to: 6.3
 * Requires PHP: 7.4
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Définir les constantes du plugin
define('UGM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('UGM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('UGM_PLUGIN_VERSION', '1.0.0');

/**
 * Classe principale du plugin Up Gutenberg Metabox
 */
class UpGutenbergMetabox {
    
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
        
        // Ajouter le menu d'administration
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Enregistrer les scripts et styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Initialiser les metaboxes
        add_action('add_meta_boxes', array($this, 'add_custom_metaboxes'));
        
        // Sauvegarder les données des metaboxes
        add_action('save_post', array($this, 'save_metabox_data'));
        
        // Hook d'activation
        register_activation_hook(__FILE__, array($this, 'activate'));
        
        // Hook de désactivation
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Charger les traductions
     */
    public function load_textdomain() {
        load_plugin_textdomain('up-gutenberg-metabox', false, dirname(plugin_basename(__FILE__)) . '/languages');
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
    }
    
    /**
     * Enregistrer les scripts et styles d'administration
     */
    public function enqueue_admin_scripts($hook) {
        if ('toplevel_page_up-gutenberg-metabox' === $hook) {
            wp_enqueue_script('up-gutenberg-metabox-admin', UGM_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), UGM_PLUGIN_VERSION, true);
            wp_enqueue_style('up-gutenberg-metabox-admin', UGM_PLUGIN_URL . 'assets/css/admin.css', array(), UGM_PLUGIN_VERSION);
            
            // Localiser le script pour AJAX
            wp_localize_script('up-gutenberg-metabox-admin', 'ugm_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ugm_nonce'),
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
     * Ajouter les metaboxes personnalisées
     */
    public function add_custom_metaboxes() {
        $metaboxes = get_option('ugm_metaboxes', array());
        
        foreach ($metaboxes as $metabox) {
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
        }
        
        if (!empty($field['description'])) {
            echo '<p class="description">' . esc_html($field['description']) . '</p>';
        }
        
        echo '</td>';
        echo '</tr>';
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
                        $value = sanitize_text_field($_POST[$field['name']]);
                        update_post_meta($post_id, $field['name'], $value);
                    } else {
                        // Pour les checkboxes non cochées
                        if ($field['type'] === 'checkbox') {
                            update_post_meta($post_id, $field['name'], '0');
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
