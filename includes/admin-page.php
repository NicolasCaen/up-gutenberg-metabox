<?php
// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Traitement des actions AJAX
if (isset($_POST['action']) && $_POST['action'] === 'save_metabox_config') {
    if (wp_verify_nonce($_POST['ugm_nonce'], 'ugm_save_config')) {
        // WordPress ajoute des slashs aux données POST. On les enlève avant sanitisation.
        $metaboxes = isset($_POST['metaboxes']) ? wp_unslash($_POST['metaboxes']) : array();
        
        // Nettoyer et valider les données
        $clean_metaboxes = array();
        foreach ($metaboxes as $index => $metabox) {
            if (!empty($metabox['title'])) {
                $clean_metabox = array(
                    'id' => sanitize_key($metabox['title']) . '_' . $index,
                    'title' => sanitize_text_field($metabox['title']),
                    'post_types' => array_map('sanitize_text_field', $metabox['post_types']),
                    'fields' => array()
                );
                
                if (!empty($metabox['fields'])) {
                    foreach ($metabox['fields'] as $field) {
                        if (!empty($field['name']) && !empty($field['label'])) {
                            $clean_field = array(
                                'name' => sanitize_key($field['name']),
                                'label' => sanitize_text_field($field['label']),
                                'type' => sanitize_text_field($field['type']),
                                'description' => sanitize_text_field($field['description'])
                            );
                            // Binding Gutenberg
                            $clean_field['binding_enabled'] = !empty($field['binding_enabled']) ? 1 : 0;
                            // Type de binding (optionnel, par défaut auto depuis type de champ)
                            if (!empty($field['binding_type'])) {
                                $allowed_binding_types = array('string','boolean','number','integer');
                                $binding_type = sanitize_text_field($field['binding_type']);
                                $clean_field['binding_type'] = in_array($binding_type, $allowed_binding_types, true) ? $binding_type : null;
                            }
                            
                            if ($field['type'] === 'select' && !empty($field['options'])) {
                                $clean_field['options'] = array();
                                foreach ($field['options'] as $option) {
                                    if (!empty($option['value']) && !empty($option['label'])) {
                                        $clean_field['options'][sanitize_text_field($option['value'])] = sanitize_text_field($option['label']);
                                    }
                                }
                            }
                            
                            $clean_metabox['fields'][] = $clean_field;
                        }
                    }
                }
                
                $clean_metaboxes[] = $clean_metabox;
            }
        }
        
        update_option('ugm_metaboxes', $clean_metaboxes);
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Configuration sauvegardée avec succès !', 'up-gutenberg-metabox') . '</p></div>';
    }
}

// Récupérer la configuration actuelle
$metaboxes = get_option('ugm_metaboxes', array());
$post_types = get_post_types(array('public' => true), 'objects');
?>

<div class="wrap">
    <h1><?php _e('Up Gutenberg Metabox', 'up-gutenberg-metabox'); ?></h1>
    <p><?php _e('Configurez vos metaboxes personnalisées pour les différents post types.', 'up-gutenberg-metabox'); ?></p>
    
    <form method="post" id="ugm-config-form">
        <?php wp_nonce_field('ugm_save_config', 'ugm_nonce'); ?>
        <input type="hidden" name="action" value="save_metabox_config">
        
        <div id="ugm-metaboxes-container">
            <?php if (!empty($metaboxes)): ?>
                <?php foreach ($metaboxes as $index => $metabox): ?>
                    <div class="ugm-metabox-config" data-index="<?php echo $index; ?>">
                        <?php render_metabox_config($metabox, $index, $post_types); ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="ugm-actions">
            <button type="button" id="add-metabox" class="button button-secondary">
                <?php _e('Ajouter une Metabox', 'up-gutenberg-metabox'); ?>
            </button>
            <button type="submit" class="button button-primary">
                <?php _e('Sauvegarder la Configuration', 'up-gutenberg-metabox'); ?>
            </button>
        </div>
    </form>
</div>

<!-- Template pour nouvelle metabox -->
<script type="text/template" id="ugm-metabox-template">
    <div class="ugm-metabox-config" data-index="{{INDEX}}">
        <div class="ugm-metabox-header">
            <h3><?php _e('Metabox', 'up-gutenberg-metabox'); ?> #{{INDEX_DISPLAY}}</h3>
            <button type="button" class="button button-link-delete remove-metabox"><?php _e('Supprimer', 'up-gutenberg-metabox'); ?></button>
        </div>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="metabox_title_{{INDEX}}"><?php _e('Titre de la Metabox', 'up-gutenberg-metabox'); ?></label>
                </th>
                <td>
                    <input type="text" id="metabox_title_{{INDEX}}" name="metaboxes[{{INDEX}}][title]" class="regular-text" required>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Post Types', 'up-gutenberg-metabox'); ?></th>
                <td>
                    <?php foreach ($post_types as $post_type): ?>
                        <label>
                            <input type="checkbox" name="metaboxes[{{INDEX}}][post_types][]" value="<?php echo esc_attr($post_type->name); ?>">
                            <?php echo esc_html($post_type->label); ?>
                        </label><br>
                    <?php endforeach; ?>
                </td>
            </tr>
        </table>
        
        <div class="ugm-fields-section">
            <h4><?php _e('Champs Personnalisés', 'up-gutenberg-metabox'); ?></h4>
            <div class="ugm-fields-container" data-metabox-index="{{INDEX}}">
                <!-- Les champs seront ajoutés ici -->
            </div>
            <button type="button" class="button button-secondary add-field" data-metabox-index="{{INDEX}}">
                <?php _e('Ajouter un Champ', 'up-gutenberg-metabox'); ?>
            </button>
        </div>
    </div>
</script>

<!-- Template pour nouveau champ -->
<script type="text/template" id="ugm-field-template">
    <div class="ugm-field-config" data-field-index="{{FIELD_INDEX}}">
        <div class="ugm-field-header">
            <h5><?php _e('Champ', 'up-gutenberg-metabox'); ?> #{{FIELD_INDEX_DISPLAY}}</h5>
            <button type="button" class="button button-link-delete remove-field"><?php _e('Supprimer', 'up-gutenberg-metabox'); ?></button>
        </div>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="field_name_{{METABOX_INDEX}}_{{FIELD_INDEX}}"><?php _e('Nom du Champ', 'up-gutenberg-metabox'); ?></label>
                </th>
                <td>
                    <input type="text" id="field_name_{{METABOX_INDEX}}_{{FIELD_INDEX}}" name="metaboxes[{{METABOX_INDEX}}][fields][{{FIELD_INDEX}}][name]" class="regular-text" required>
                    <p class="description"><?php _e('Nom technique du champ (sans espaces, caractères spéciaux)', 'up-gutenberg-metabox'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="field_label_{{METABOX_INDEX}}_{{FIELD_INDEX}}"><?php _e('Libellé', 'up-gutenberg-metabox'); ?></label>
                </th>
                <td>
                    <input type="text" id="field_label_{{METABOX_INDEX}}_{{FIELD_INDEX}}" name="metaboxes[{{METABOX_INDEX}}][fields][{{FIELD_INDEX}}][label]" class="regular-text" required>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="field_type_{{METABOX_INDEX}}_{{FIELD_INDEX}}"><?php _e('Type de Champ', 'up-gutenberg-metabox'); ?></label>
                </th>
                <td>
                    <select id="field_type_{{METABOX_INDEX}}_{{FIELD_INDEX}}" name="metaboxes[{{METABOX_INDEX}}][fields][{{FIELD_INDEX}}][type]" class="field-type-select">
                        <option value="text"><?php _e('Texte', 'up-gutenberg-metabox'); ?></option>
                        <option value="textarea"><?php _e('Zone de Texte', 'up-gutenberg-metabox'); ?></option>
                        <option value="select"><?php _e('Liste Déroulante', 'up-gutenberg-metabox'); ?></option>
                        <option value="checkbox"><?php _e('Case à Cocher', 'up-gutenberg-metabox'); ?></option>
                        <option value="number"><?php _e('Nombre', 'up-gutenberg-metabox'); ?></option>
                        <option value="email"><?php _e('Email', 'up-gutenberg-metabox'); ?></option>
                        <option value="url"><?php _e('URL', 'up-gutenberg-metabox'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="field_description_{{METABOX_INDEX}}_{{FIELD_INDEX}}"><?php _e('Description', 'up-gutenberg-metabox'); ?></label>
                </th>
                <td>
                    <input type="text" id="field_description_{{METABOX_INDEX}}_{{FIELD_INDEX}}" name="metaboxes[{{METABOX_INDEX}}][fields][{{FIELD_INDEX}}][description]" class="regular-text">
                    <p class="description"><?php _e('Description optionnelle qui apparaîtra sous le champ', 'up-gutenberg-metabox'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Binding Gutenberg', 'up-gutenberg-metabox'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" class="ugm-binding-checkbox" name="metaboxes[{{METABOX_INDEX}}][fields][{{FIELD_INDEX}}][binding_enabled]" value="1">
                        <?php _e('Rendre ce champ accessible au binding des blocs', 'up-gutenberg-metabox'); ?>
                    </label>
                    <div class="ugm-binding-options" style="display:none; margin-top:8px;">
                        <label>
                            <?php _e('Type de donnée (REST)', 'up-gutenberg-metabox'); ?>
                            <select name="metaboxes[{{METABOX_INDEX}}][fields][{{FIELD_INDEX}}][binding_type]">
                                <option value="">
                                    <?php _e('Automatique (selon le type du champ)', 'up-gutenberg-metabox'); ?>
                                </option>
                                <option value="string">string</option>
                                <option value="boolean">boolean</option>
                                <option value="number">number</option>
                                <option value="integer">integer</option>
                            </select>
                        </label>
                        <p class="description"><?php _e('Active show_in_rest et propose ce champ au Block Binding. Choisissez un type si nécessaire.', 'up-gutenberg-metabox'); ?></p>
                    </div>
                </td>
            </tr>
        </table>
        
        <div class="ugm-select-options" style="display: none;">
            <h6><?php _e('Options de la Liste Déroulante', 'up-gutenberg-metabox'); ?></h6>
            <div class="ugm-options-container">
                <!-- Les options seront ajoutées ici -->
            </div>
            <button type="button" class="button button-small add-option"><?php _e('Ajouter une Option', 'up-gutenberg-metabox'); ?></button>
        </div>
    </div>
</script>

<!-- Template pour option de select -->
<script type="text/template" id="ugm-option-template">
    <div class="ugm-option-config">
        <input type="text" name="metaboxes[{{METABOX_INDEX}}][fields][{{FIELD_INDEX}}][options][{{OPTION_INDEX}}][value]" placeholder="<?php _e('Valeur', 'up-gutenberg-metabox'); ?>" class="small-text">
        <input type="text" name="metaboxes[{{METABOX_INDEX}}][fields][{{FIELD_INDEX}}][options][{{OPTION_INDEX}}][label]" placeholder="<?php _e('Libellé', 'up-gutenberg-metabox'); ?>" class="regular-text">
        <button type="button" class="button button-small remove-option"><?php _e('Supprimer', 'up-gutenberg-metabox'); ?></button>
    </div>
</script>

<?php
/**
 * Fonction pour afficher la configuration d'une metabox existante
 */
function render_metabox_config($metabox, $index, $post_types) {
    ?>
    <div class="ugm-metabox-header">
        <h3><?php _e('Metabox', 'up-gutenberg-metabox'); ?> #<?php echo ($index + 1); ?></h3>
        <button type="button" class="button button-link-delete remove-metabox"><?php _e('Supprimer', 'up-gutenberg-metabox'); ?></button>
    </div>
    
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="metabox_title_<?php echo $index; ?>"><?php _e('Titre de la Metabox', 'up-gutenberg-metabox'); ?></label>
            </th>
            <td>
                <input type="text" id="metabox_title_<?php echo $index; ?>" name="metaboxes[<?php echo $index; ?>][title]" value="<?php echo esc_attr($metabox['title']); ?>" class="regular-text" required>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php _e('Post Types', 'up-gutenberg-metabox'); ?></th>
            <td>
                <?php foreach ($post_types as $post_type): ?>
                    <label>
                        <input type="checkbox" name="metaboxes[<?php echo $index; ?>][post_types][]" value="<?php echo esc_attr($post_type->name); ?>" <?php checked(in_array($post_type->name, $metabox['post_types'])); ?>>
                        <?php echo esc_html($post_type->label); ?>
                    </label><br>
                <?php endforeach; ?>
            </td>
        </tr>
    </table>
    
    <div class="ugm-fields-section">
        <h4><?php _e('Champs Personnalisés', 'up-gutenberg-metabox'); ?></h4>
        <div class="ugm-fields-container" data-metabox-index="<?php echo $index; ?>">
            <?php if (!empty($metabox['fields'])): ?>
                <?php foreach ($metabox['fields'] as $field_index => $field): ?>
                    <div class="ugm-field-config" data-field-index="<?php echo $field_index; ?>">
                        <?php render_field_config($field, $index, $field_index); ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <button type="button" class="button button-secondary add-field" data-metabox-index="<?php echo $index; ?>">
            <?php _e('Ajouter un Champ', 'up-gutenberg-metabox'); ?>
        </button>
    </div>
    <?php
}

/**
 * Fonction pour afficher la configuration d'un champ existant
 */
function render_field_config($field, $metabox_index, $field_index) {
    ?>
    <div class="ugm-field-header">
        <h5><?php _e('Champ', 'up-gutenberg-metabox'); ?> #<?php echo ($field_index + 1); ?></h5>
        <button type="button" class="button button-link-delete remove-field"><?php _e('Supprimer', 'up-gutenberg-metabox'); ?></button>
    </div>
    
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="field_name_<?php echo $metabox_index; ?>_<?php echo $field_index; ?>"><?php _e('Nom du Champ', 'up-gutenberg-metabox'); ?></label>
            </th>
            <td>
                <input type="text" id="field_name_<?php echo $metabox_index; ?>_<?php echo $field_index; ?>" name="metaboxes[<?php echo $metabox_index; ?>][fields][<?php echo $field_index; ?>][name]" value="<?php echo esc_attr($field['name']); ?>" class="regular-text" required>
                <p class="description"><?php _e('Nom technique du champ (sans espaces, caractères spéciaux)', 'up-gutenberg-metabox'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="field_label_<?php echo $metabox_index; ?>_<?php echo $field_index; ?>"><?php _e('Libellé', 'up-gutenberg-metabox'); ?></label>
            </th>
            <td>
                <input type="text" id="field_label_<?php echo $metabox_index; ?>_<?php echo $field_index; ?>" name="metaboxes[<?php echo $metabox_index; ?>][fields][<?php echo $field_index; ?>][label]" value="<?php echo esc_attr($field['label']); ?>" class="regular-text" required>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="field_type_<?php echo $metabox_index; ?>_<?php echo $field_index; ?>"><?php _e('Type de Champ', 'up-gutenberg-metabox'); ?></label>
            </th>
            <td>
                <select id="field_type_<?php echo $metabox_index; ?>_<?php echo $field_index; ?>" name="metaboxes[<?php echo $metabox_index; ?>][fields][<?php echo $field_index; ?>][type]" class="field-type-select">
                    <option value="text" <?php selected($field['type'], 'text'); ?>><?php _e('Texte', 'up-gutenberg-metabox'); ?></option>
                    <option value="textarea" <?php selected($field['type'], 'textarea'); ?>><?php _e('Zone de Texte', 'up-gutenberg-metabox'); ?></option>
                    <option value="select" <?php selected($field['type'], 'select'); ?>><?php _e('Liste Déroulante', 'up-gutenberg-metabox'); ?></option>
                    <option value="checkbox" <?php selected($field['type'], 'checkbox'); ?>><?php _e('Case à Cocher', 'up-gutenberg-metabox'); ?></option>
                    <option value="number" <?php selected($field['type'], 'number'); ?>><?php _e('Nombre', 'up-gutenberg-metabox'); ?></option>
                    <option value="email" <?php selected($field['type'], 'email'); ?>><?php _e('Email', 'up-gutenberg-metabox'); ?></option>
                    <option value="url" <?php selected($field['type'], 'url'); ?>><?php _e('URL', 'up-gutenberg-metabox'); ?></option>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="field_description_<?php echo $metabox_index; ?>_<?php echo $field_index; ?>"><?php _e('Description', 'up-gutenberg-metabox'); ?></label>
            </th>
            <td>
                <input type="text" id="field_description_<?php echo $metabox_index; ?>_<?php echo $field_index; ?>" name="metaboxes[<?php echo $metabox_index; ?>][fields][<?php echo $field_index; ?>][description]" value="<?php echo esc_attr($field['description']); ?>" class="regular-text">
                <p class="description"><?php _e('Description optionnelle qui apparaîtra sous le champ', 'up-gutenberg-metabox'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php _e('Binding Gutenberg', 'up-gutenberg-metabox'); ?></th>
            <td>
                <label>
                    <input type="checkbox" class="ugm-binding-checkbox" name="metaboxes[<?php echo $metabox_index; ?>][fields][<?php echo $field_index; ?>][binding_enabled]" value="1" <?php checked(!empty($field['binding_enabled'])); ?>>
                    <?php _e('Rendre ce champ accessible au binding des blocs', 'up-gutenberg-metabox'); ?>
                </label>
                <div class="ugm-binding-options" style="<?php echo empty($field['binding_enabled']) ? 'display:none;' : ''; ?> margin-top:8px;">
                    <label>
                        <?php _e('Type de donnée (REST)', 'up-gutenberg-metabox'); ?>
                        <select name="metaboxes[<?php echo $metabox_index; ?>][fields][<?php echo $field_index; ?>][binding_type]">
                            <option value="" <?php selected(empty($field['binding_type'])); ?>><?php _e('Automatique (selon le type du champ)', 'up-gutenberg-metabox'); ?></option>
                            <?php
                            $types = array('string','boolean','number','integer');
                            foreach ($types as $t) {
                                printf('<option value="%1$s" %2$s>%1$s</option>', esc_attr($t), selected(isset($field['binding_type']) && $field['binding_type'] === $t, true, false));
                            }
                            ?>
                        </select>
                    </label>
                    <p class="description"><?php _e('Active show_in_rest et propose ce champ au Block Binding. Choisissez un type si nécessaire.', 'up-gutenberg-metabox'); ?></p>
                </div>
            </td>
        </tr>
    </table>
    
    <div class="ugm-select-options" <?php echo ($field['type'] !== 'select') ? 'style="display: none;"' : ''; ?>>
        <h6><?php _e('Options de la Liste Déroulante', 'up-gutenberg-metabox'); ?></h6>
        <div class="ugm-options-container">
            <?php if (!empty($field['options'])): ?>
                <?php foreach ($field['options'] as $option_value => $option_label): ?>
                    <div class="ugm-option-config">
                        <input type="text" name="metaboxes[<?php echo $metabox_index; ?>][fields][<?php echo $field_index; ?>][options][<?php echo esc_attr($option_value); ?>][value]" value="<?php echo esc_attr($option_value); ?>" placeholder="<?php _e('Valeur', 'up-gutenberg-metabox'); ?>" class="small-text">
                        <input type="text" name="metaboxes[<?php echo $metabox_index; ?>][fields][<?php echo $field_index; ?>][options][<?php echo esc_attr($option_value); ?>][label]" value="<?php echo esc_attr($option_label); ?>" placeholder="<?php _e('Libellé', 'up-gutenberg-metabox'); ?>" class="regular-text">
                        <button type="button" class="button button-small remove-option"><?php _e('Supprimer', 'up-gutenberg-metabox'); ?></button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <button type="button" class="button button-small add-option"><?php _e('Ajouter une Option', 'up-gutenberg-metabox'); ?></button>
    </div>
    <?php
}
?>
