<?php
/**
 * Class UGM_Code_Importer
 * Importe les metaboxes depuis les fichiers du thème
 */

if (!defined('ABSPATH')) {
    exit;
}

class UGM_Code_Importer {
    
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
     * Importer les metaboxes depuis le thème
     */
    public function import_from_theme() {
        $inc_path = $this->theme_metabox_path . 'inc/';
        
        if (!file_exists($inc_path)) {
            return array(
                'success' => false,
                'message' => __('Le dossier functions/metabox/inc/ n\'existe pas dans le thème.', 'up-gutenberg-metabox')
            );
        }
        
        $files = glob($inc_path . '*.php');
        
        if (empty($files)) {
            return array(
                'success' => false,
                'message' => __('Aucun fichier trouvé dans functions/metabox/inc/.', 'up-gutenberg-metabox')
            );
        }
        
        $imported_count = 0;
        $metaboxes = array();
        
        foreach ($files as $file) {
            $metabox_data = $this->parse_metabox_file($file);
            if ($metabox_data) {
                $metaboxes[] = $metabox_data;
                $imported_count++;
            }
        }
        
        if ($imported_count > 0) {
            // Fusionner avec les metaboxes existantes
            $existing = get_option('ugm_metaboxes', array());
            
            // Filtrer les doublons basés sur l'ID
            foreach ($metaboxes as $new_mb) {
                $exists = false;
                foreach ($existing as $key => $existing_mb) {
                    if ($existing_mb['id'] === $new_mb['id']) {
                        // Remplacer l'existante
                        $existing[$key] = $new_mb;
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    $existing[] = $new_mb;
                }
            }
            
            update_option('ugm_metaboxes', $existing);
            
            return array(
                'success' => true,
                'message' => sprintf(__('%d metabox(es) importée(s) avec succès.', 'up-gutenberg-metabox'), $imported_count)
            );
        } else {
            return array(
                'success' => false,
                'message' => __('Aucune metabox valide trouvée dans les fichiers.', 'up-gutenberg-metabox')
            );
        }
    }
    
    /**
     * Parser un fichier de metabox pour extraire les données
     */
    private function parse_metabox_file($filepath) {
        $content = file_get_contents($filepath);
        if (!$content) return null;
        
        $metabox_data = array(
            'id' => '',
            'title' => '',
            'post_types' => array(),
            'fields' => array()
        );
        
        // Extraire l'ID de la metabox
        if (preg_match('/private\s+\$metabox_id\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
            $metabox_data['id'] = $matches[1];
        } else {
            // Utiliser le nom du fichier comme fallback
            $metabox_data['id'] = basename($filepath, '.php');
        }
        
        // Extraire le titre
        if (preg_match('/private\s+\$metabox_title\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
            $metabox_data['title'] = stripslashes($matches[1]);
        }
        
        // Extraire les post types
        if (preg_match('/private\s+\$post_types\s*=\s*array\(([^)]+)\)/', $content, $matches)) {
            preg_match_all('/[\'"]([^\'"]+)[\'"]/', $matches[1], $types);
            if (!empty($types[1])) {
                $metabox_data['post_types'] = $types[1];
            }
        }
        
        // Extraire les champs depuis la méthode render_metabox
        $fields = $this->extract_fields_from_render($content);
        if (!empty($fields)) {
            $metabox_data['fields'] = $fields;
        }
        
        // Valider que nous avons au minimum un titre et des post types
        if (empty($metabox_data['title']) || empty($metabox_data['post_types'])) {
            return null;
        }
        
        return $metabox_data;
    }
    
    /**
     * Extraire les champs depuis le code de rendu
     */
    private function extract_fields_from_render($content) {
        $fields = array();
        
        // Pattern pour trouver les champs
        // On cherche le get_post_meta pour récupérer la vraie clé (meta_key)
        // Format généré : $value_nom_var = get_post_meta($post->ID, 'nom-meta', true);
        if (preg_match_all('/get_post_meta\(\s*\$post->ID\s*,\s*[\'"]([^\'"]+)[\'"]\s*,\s*true\s*\)/', $content, $matches)) {
            $found_keys = $matches[1];
        } else {
            return $fields;
        }
        
        foreach ($found_keys as $field_name) {
            $field = array(
                'name' => $field_name,
                'label' => '',
                'type' => 'text',
                'description' => '',
                'binding_enabled' => 0,
                'derived_enabled' => 0
            );
            
            // Extraire le label
            if (preg_match('/<label[^>]*for=[\'"]' . preg_quote($field_name, '/') . '[\'"][^>]*>([^<]+)</', $content, $matches)) {
                $field['label'] = stripslashes(trim($matches[1]));
            } else {
                $field['label'] = ucfirst(str_replace('_', ' ', $field_name));
            }
            
            // Déterminer le type de champ
            if (strpos($content, '<textarea id="' . $field_name . '"') !== false ||
                strpos($content, '<textarea name="' . $field_name . '"') !== false) {
                $field['type'] = 'textarea';
            } elseif (strpos($content, 'type="number"[^>]*name="' . $field_name . '"') !== false ||
                     strpos($content, 'name="' . $field_name . '"[^>]*type="number"') !== false) {
                $field['type'] = 'number';
            } elseif (strpos($content, 'type="checkbox"[^>]*name="' . $field_name . '"') !== false ||
                     strpos($content, 'name="' . $field_name . '"[^>]*type="checkbox"') !== false) {
                $field['type'] = 'checkbox';
            } elseif (strpos($content, 'type="email"[^>]*name="' . $field_name . '"') !== false ||
                     strpos($content, 'name="' . $field_name . '"[^>]*type="email"') !== false) {
                $field['type'] = 'email';
            } elseif (strpos($content, 'type="url"[^>]*name="' . $field_name . '"') !== false ||
                     strpos($content, 'name="' . $field_name . '"[^>]*type="url"') !== false) {
                $field['type'] = 'url';
            } elseif (strpos($content, '<select[^>]*name="' . $field_name . '"') !== false ||
                     strpos($content, '<select[^>]*id="' . $field_name . '"') !== false) {
                $field['type'] = 'select';
                
                // Extraire les options du select
                if (preg_match('/<select[^>]*(?:name|id)=[\'"]' . preg_quote($field_name, '/') . '[\'"][^>]*>(.+?)<\/select>/s', $content, $select_match)) {
                    preg_match_all('/<option[^>]*value=[\'"]([^\'"]+)[\'"][^>]*>([^<]+)<\/option>/', $select_match[1], $options);
                    if (!empty($options[1])) {
                        $field['options'] = array();
                        foreach ($options[1] as $i => $value) {
                            $field['options'][html_entity_decode($value)] = html_entity_decode($options[2][$i]);
                        }
                    }
                }
            }
            
            // Vérifier si le binding est activé (chercher dans register_meta_fields)
            if (preg_match('/register_post_meta\([^,]+,\s*[\'"]' . preg_quote($field_name, '/') . '[\'"]/', $content)) {
                $field['binding_enabled'] = 1;
            }
            
            // Vérifier si la donnée dérivée est activée
            if (strpos($content, "'{$field_name}_formatted'") !== false) {
                $field['derived_enabled'] = 1;
            }
            
            $fields[] = $field;
        }
        
        return $fields;
    }
}
