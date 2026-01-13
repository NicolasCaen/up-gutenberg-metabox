<?php
/**
 * Class UGM_Code_Generator
 * Génère le code PHP des metaboxes pour le thème
 */

if (!defined('ABSPATH')) {
    exit;
}

class UGM_Code_Generator {
    
    /**
     * Chemin du dossier metabox dans le thème
     */
    private $theme_metabox_path;
    
    /**
     * Constructeur
     */
    public function __construct() {
        $this->theme_metabox_path = get_stylesheet_directory() . '/functions/metabox/';
    }
    
    /**
     * Générer le code d'une seule metabox
     */
    public function generate_single_metabox($metabox_id) {
        $metaboxes = get_option('ugm_metaboxes', array());
        $metabox = null;
        
        // Trouver la metabox par ID
        foreach ($metaboxes as $mb) {
            if ($mb['id'] === $metabox_id) {
                $metabox = $mb;
                break;
            }
        }
        
        if (!$metabox) {
            return array(
                'success' => false,
                'message' => __('Metabox introuvable.', 'up-gutenberg-metabox')
            );
        }
        
        // Créer les dossiers si nécessaire
        $this->ensure_directories();
        
        // Générer le code PHP
        $code = $this->generate_metabox_code($metabox);
        
        // Sauvegarder le fichier
        $filename = $this->theme_metabox_path . 'inc/' . sanitize_file_name($metabox_id) . '.php';
        $result = file_put_contents($filename, $code);
        
        if ($result !== false) {
            return array(
                'success' => true,
                'message' => sprintf(__('Metabox "%s" générée avec succès.', 'up-gutenberg-metabox'), $metabox['title'])
            );
        } else {
            return array(
                'success' => false,
                'message' => __('Erreur lors de la création du fichier.', 'up-gutenberg-metabox')
            );
        }
    }
    
    /**
     * Générer le fichier root.php et la classe de base
     */
    public function generate_root_file($selected_ids) {
        if (empty($selected_ids)) {
            return array(
                'success' => false,
                'message' => __('Aucune metabox sélectionnée.', 'up-gutenberg-metabox')
            );
        }
        
        $this->ensure_directories();
        
        // 1. Générer la classe de base UGM_Metabox
        $base_class_code = $this->get_base_class_code();
        file_put_contents($this->theme_metabox_path . 'class-ugm-metabox.php', $base_class_code);
        
        // 2. Générer root.php
        $code = "<?php\n";
        $code .= "/**\n";
        $code .= " * Fichier principal d'inclusion des metaboxes\n";
        $code .= " * Author : GEHIN Nicolas\n";
        $code .= " */\n\n";
        $code .= "// Empêcher l'accès direct\n";
        $code .= "if (!defined('ABSPATH')) {\n";
        $code .= "    exit;\n";
        $code .= "}\n\n";
        
        $code .= "// Inclure la classe de base\n";
        $code .= "if (file_exists(__DIR__ . '/class-ugm-metabox.php')) {\n";
        $code .= "    include_once __DIR__ . '/class-ugm-metabox.php';\n";
        $code .= "}\n\n";
        
        $code .= "// Inclure les metaboxes\n";
        
        foreach ($selected_ids as $id) {
            $filename = sanitize_file_name($id) . '.php';
            $filepath = $this->theme_metabox_path . 'inc/' . $filename;
            
            if (file_exists($filepath)) {
                $code .= "include_once __DIR__ . '/inc/{$filename}';\n";
            }
        }
        
        $result = file_put_contents($this->theme_metabox_path . 'root.php', $code);
        
        if ($result !== false) {
            return array(
                'success' => true,
                'message' => __('Fichier root.php et classe de base générés avec succès.', 'up-gutenberg-metabox')
            );
        } else {
            return array(
                'success' => false,
                'message' => __('Erreur lors de la création du fichier root.php.', 'up-gutenberg-metabox')
            );
        }
    }
    
    /**
     * Obtenir le code de la classe de base UGM_Metabox
     */
    private function get_base_class_code() {
        $code = "<?php\n";
        $code .= "/**\n";
        $code .= " * Classe de base pour les metaboxes générées\n";
        $code .= " * Author : GEHIN Nicolas\n";
        $code .= " */\n\n";
        $code .= "if (!defined('ABSPATH')) {\n";
        $code .= "    exit;\n";
        $code .= "}\n\n";
        $code .= "class UGM_Metabox {\n";
        
        // Méthodes de filtres
        $code .= "    protected function apply_filter_identity(\$value) {\n";
        $code .= "        return \$value;\n";
        $code .= "    }\n\n";
        
        $code .= "    protected function apply_filter_number_thousands(\$value) {\n";
        $code .= "        if (\$value === '' || \$value === null) return '';\n";
        $code .= "        return number_format((float)\$value, 0, ',', ' ');\n";
        $code .= "    }\n\n";
        
        $code .= "    protected function apply_filter_currency_eur(\$value) {\n";
        $code .= "        if (\$value === '' || \$value === null) return '';\n";
        $code .= "        return number_format((float)\$value, 0, ',', ' ') . ' €';\n";
        $code .= "    }\n\n";
        
        $code .= "    protected function apply_filter_uppercase(\$value) {\n";
        $code .= "        return mb_strtoupper((string)\$value);\n";
        $code .= "    }\n\n";
        
        $code .= "    protected function apply_filter_lowercase(\$value) {\n";
        $code .= "        return mb_strtolower((string)\$value);\n";
        $code .= "    }\n\n";
        
        $code .= "    protected function apply_filter_date_dmy(\$value) {\n";
        $code .= "        \$ts = strtotime((string)\$value);\n";
        $code .= "        return \$ts ? date_i18n('d/m/Y', \$ts) : (string)\$value;\n";
        $code .= "    }\n";
        
        $code .= "}\n";
        
        return $code;
    }
    
    /**
     * S'assurer que les dossiers existent
     */
    private function ensure_directories() {
        if (!file_exists($this->theme_metabox_path)) {
            wp_mkdir_p($this->theme_metabox_path);
        }
        
        if (!file_exists($this->theme_metabox_path . 'inc/')) {
            wp_mkdir_p($this->theme_metabox_path . 'inc/');
        }
    }
    
    /**
     * Générer le code PHP pour une metabox
     */
    private function generate_metabox_code($metabox) {
        $code = "<?php\n";
        $code .= "/**\n";
        $code .= " * Metabox: {$metabox['title']}\n";
        $code .= " * Author : GEHIN Nicolas\n";
        $code .= " */\n\n";
        $code .= "// Empêcher l'accès direct\n";
        $code .= "if (!defined('ABSPATH')) {\n";
        $code .= "    exit;\n";
        $code .= "}\n\n";
        
        // Classe pour la metabox
        $class_name = 'UGM_' . str_replace('-', '_', sanitize_title($metabox['title']));
        
        $code .= "class {$class_name} extends UGM_Metabox {\n\n";
        
        // Propriétés
        $code .= "    private static \$instance = null;\n";
        $code .= "    private \$metabox_id = '{$metabox['id']}';\n";
        $code .= "    private \$metabox_title = '" . addslashes($metabox['title']) . "';\n";
        $code .= "    private \$post_types = array(" . $this->array_to_string($metabox['post_types']) . ");\n\n";
        
        // Singleton
        $code .= "    public static function get_instance() {\n";
        $code .= "        if (null === self::\$instance) {\n";
        $code .= "            self::\$instance = new self();\n";
        $code .= "        }\n";
        $code .= "        return self::\$instance;\n";
        $code .= "    }\n\n";
        
        // Constructeur
        $code .= "    private function __construct() {\n";
        $code .= "        add_action('add_meta_boxes', array(\$this, 'add_metabox'));\n";
        $code .= "        add_action('save_post', array(\$this, 'save_metabox'));\n";
        $code .= "        add_action('init', array(\$this, 'register_meta_fields'));\n";
        $code .= "    }\n\n";
        
        // Ajouter la metabox
        $code .= "    public function add_metabox() {\n";
        $code .= "        foreach (\$this->post_types as \$post_type) {\n";
        $code .= "            add_meta_box(\n";
        $code .= "                \$this->metabox_id,\n";
        $code .= "                \$this->metabox_title,\n";
        $code .= "                array(\$this, 'render_metabox'),\n";
        $code .= "                \$post_type,\n";
        $code .= "                'normal',\n";
        $code .= "                'default'\n";
        $code .= "            );\n";
        $code .= "        }\n";
        $code .= "    }\n\n";
        
        // Enregistrer les meta fields pour REST API
        $code .= "    public function register_meta_fields() {\n";
        foreach ($metabox['fields'] as $field) {
            if (!empty($field['binding_enabled'])) {
                $meta_type = $this->get_meta_type($field);
                $code .= "        foreach (\$this->post_types as \$post_type) {\n";
                $code .= "            register_post_meta(\$post_type, '{$field['name']}', array(\n";
                $code .= "                'single' => true,\n";
                $code .= "                'type' => '{$meta_type}',\n";
                $code .= "                'show_in_rest' => true,\n";
                $code .= "                'auth_callback' => function() { return current_user_can('edit_posts'); }\n";
                $code .= "            ));\n";
                
                // Meta dérivée si activée
                if (!empty($field['derived_enabled'])) {
                    $code .= "            register_post_meta(\$post_type, '{$field['name']}_formatted', array(\n";
                    $code .= "                'single' => true,\n";
                    $code .= "                'type' => 'string',\n";
                    $code .= "                'show_in_rest' => true,\n";
                    $code .= "                'auth_callback' => function() { return current_user_can('edit_posts'); }\n";
                    $code .= "            ));\n";
                }
                $code .= "        }\n";
            }
        }
        $code .= "    }\n\n";
        
        // Render metabox
        $code .= "    public function render_metabox(\$post) {\n";
        $code .= "        wp_nonce_field('save_metabox_' . \$this->metabox_id, 'metabox_nonce_' . \$this->metabox_id);\n";
        $code .= "        echo '<table class=\"form-table\">';\n";
        
        foreach ($metabox['fields'] as $field) {
            $code .= $this->generate_field_render_code($field);
        }
        
        $code .= "        echo '</table>';\n";
        $code .= "    }\n\n";
        
        // Save metabox
        $code .= "    public function save_metabox(\$post_id) {\n";
        $code .= "        // Vérifications de sécurité\n";
        $code .= "        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;\n";
        $code .= "        if (!isset(\$_POST['metabox_nonce_' . \$this->metabox_id])) return;\n";
        $code .= "        if (!wp_verify_nonce(\$_POST['metabox_nonce_' . \$this->metabox_id], 'save_metabox_' . \$this->metabox_id)) return;\n";
        $code .= "        if (!current_user_can('edit_post', \$post_id)) return;\n\n";
        
        foreach ($metabox['fields'] as $field) {
            $code .= $this->generate_field_save_code($field);
        }
        
        $code .= "    }\n";
        
        // Fermer la classe
        $code .= "}\n\n";
        
        // Initialiser la classe
        $code .= "// Initialiser\n";
        $code .= "{$class_name}::get_instance();\n";
        
        return $code;
    }
    
    /**
     * Générer le code de rendu pour un champ
     */
    private function generate_field_render_code($field) {
        $var_name = str_replace('-', '_', $field['name']);
        
        $code = "        // Champ: {$field['label']}\n";
        $code .= "        \$value_{$var_name} = get_post_meta(\$post->ID, '{$field['name']}', true);\n";
        $code .= "        echo '<tr>';\n";
        $code .= "        echo '<th scope=\"row\"><label for=\"{$field['name']}\">" . addslashes($field['label']) . "</label></th>';\n";
        $code .= "        echo '<td>';\n";
        
        switch ($field['type']) {
            case 'text':
            case 'email':
            case 'url':
                $code .= "        echo '<input type=\"{$field['type']}\" id=\"{$field['name']}\" name=\"{$field['name']}\" value=\"' . esc_attr(\$value_{$var_name}) . '\" class=\"regular-text\" />';\n";
                break;
                
            case 'textarea':
                $code .= "        echo '<textarea id=\"{$field['name']}\" name=\"{$field['name']}\" rows=\"5\" cols=\"50\" class=\"large-text\">' . esc_textarea(\$value_{$var_name}) . '</textarea>';\n";
                break;
                
            case 'number':
                $code .= "        echo '<input type=\"number\" id=\"{$field['name']}\" name=\"{$field['name']}\" value=\"' . esc_attr(\$value_{$var_name}) . '\" class=\"small-text\" />';\n";
                break;
                
            case 'checkbox':
                $code .= "        echo '<input type=\"checkbox\" id=\"{$field['name']}\" name=\"{$field['name']}\" value=\"1\"' . checked(\$value_{$var_name}, '1', false) . ' />';\n";
                break;
                
            case 'select':
                $code .= "        echo '<select id=\"{$field['name']}\" name=\"{$field['name']}\">';\n";
                if (!empty($field['options'])) {
                    foreach ($field['options'] as $value => $label) {
                        $code .= "        echo '<option value=\"" . esc_attr($value) . "\"' . selected(\$value_{$var_name}, '" . esc_attr($value) . "', false) . '>" . esc_html($label) . "</option>';\n";
                    }
                }
                $code .= "        echo '</select>';\n";
                break;
        }
        
        if (!empty($field['description'])) {
            $code .= "        echo '<p class=\"description\">" . addslashes($field['description']) . "</p>';\n";
        }
        
        $code .= "        echo '</td>';\n";
        $code .= "        echo '</tr>';\n\n";
        
        return $code;
    }
    
    /**
     * Générer le code de sauvegarde pour un champ
     */
    private function generate_field_save_code($field) {
        $var_name = str_replace('-', '_', $field['name']);
        
        $code = "        // Sauvegarder: {$field['name']}\n";
        
        if ($field['type'] === 'checkbox') {
            $code .= "        \$value_{$var_name} = isset(\$_POST['{$field['name']}']) ? '1' : '0';\n";
        } else {
            $code .= "        if (isset(\$_POST['{$field['name']}'])) {\n";
            
            if ($field['type'] === 'textarea') {
                $code .= "            \$value_{$var_name} = sanitize_textarea_field(wp_unslash(\$_POST['{$field['name']}']));\n";
            } else {
                $code .= "            \$value_{$var_name} = sanitize_text_field(wp_unslash(\$_POST['{$field['name']}']));\n";
            }
            
            $code .= "        } else {\n";
            $code .= "            \$value_{$var_name} = '';\n";
            $code .= "        }\n";
        }
        
        $code .= "        update_post_meta(\$post_id, '{$field['name']}', \$value_{$var_name});\n";
        
        // Gérer les données dérivées si activées
        if (!empty($field['derived_enabled'])) {
            $code .= "        // Donnée dérivée\n";
            $filter = isset($field['derived_filter']) ? $field['derived_filter'] : 'identity';
            
            $code .= "        \$formatted_value = \$this->apply_filter_{$filter}(\$value_{$var_name});\n";
            $code .= "        update_post_meta(\$post_id, '{$field['name']}_formatted', \$formatted_value);\n";
        }
        
        $code .= "\n";
        
        return $code;
    }
    
    /**
     * Obtenir le type de meta pour un champ
     */
    private function get_meta_type($field) {
        if (!empty($field['binding_type'])) {
            return $field['binding_type'];
        }
        
        switch ($field['type']) {
            case 'checkbox':
                return 'boolean';
            case 'number':
                return 'number';
            default:
                return 'string';
        }
    }
    
    /**
     * Convertir un array PHP en string pour le code
     */
    private function array_to_string($array) {
        $items = array();
        foreach ($array as $item) {
            $items[] = "'" . addslashes($item) . "'";
        }
        return implode(', ', $items);
    }
}
