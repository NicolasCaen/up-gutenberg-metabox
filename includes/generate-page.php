<?php
// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Récupérer les metaboxes configurées
$metaboxes = get_option('ugm_metaboxes', array());

// Traitement de la génération
if (isset($_POST['generate_metabox']) && isset($_POST['metabox_id'])) {
    if (wp_verify_nonce($_POST['ugm_generate_nonce'], 'ugm_generate_action')) {
        $generator = new UGM_Code_Generator();
        $result = $generator->generate_single_metabox($_POST['metabox_id']);
        
        if ($result['success']) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
        }
    }
}

// Traitement de la génération du fichier root
if (isset($_POST['generate_root'])) {
    if (wp_verify_nonce($_POST['ugm_generate_nonce'], 'ugm_generate_action')) {
        $selected = isset($_POST['selected_metaboxes']) ? $_POST['selected_metaboxes'] : array();
        $generator = new UGM_Code_Generator();
        $result = $generator->generate_root_file($selected);
        
        if ($result['success']) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
        }
    }
}

// Traitement de l'import
if (isset($_POST['import_metaboxes'])) {
    if (wp_verify_nonce($_POST['ugm_import_nonce'], 'ugm_import_action')) {
        $importer = new UGM_Code_Importer();
        $result = $importer->import_from_theme();
        
        if ($result['success']) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
            // Recharger les metaboxes après import
            $metaboxes = get_option('ugm_metaboxes', array());
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
        }
    }
}

?>

<div class="wrap">
    <h1><?php _e('Générer le Code des Metaboxes', 'up-gutenberg-metabox'); ?></h1>
    
    <div class="ugm-generate-section">
        <h2><?php _e('Metaboxes Disponibles', 'up-gutenberg-metabox'); ?></h2>
        
        <?php if (empty($metaboxes)): ?>
            <p><?php _e('Aucune metabox configurée. Veuillez d\'abord créer des metaboxes.', 'up-gutenberg-metabox'); ?></p>
        <?php else: ?>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" class="check-column">
                            <input type="checkbox" id="select-all-metaboxes" />
                        </th>
                        <th scope="col"><?php _e('Titre', 'up-gutenberg-metabox'); ?></th>
                        <th scope="col"><?php _e('Post Types', 'up-gutenberg-metabox'); ?></th>
                        <th scope="col"><?php _e('Nombre de champs', 'up-gutenberg-metabox'); ?></th>
                        <th scope="col"><?php _e('Statut', 'up-gutenberg-metabox'); ?></th>
                        <th scope="col"><?php _e('Actions', 'up-gutenberg-metabox'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($metaboxes as $index => $metabox): 
                        $file_exists = file_exists(get_stylesheet_directory() . '/functions/metabox/inc/' . sanitize_file_name($metabox['id']) . '.php');
                    ?>
                        <tr>
                            <td>
                                <input type="checkbox" class="metabox-checkbox" name="selected_metaboxes[]" value="<?php echo esc_attr($metabox['id']); ?>" />
                            </td>
                            <td>
                                <strong><?php echo esc_html($metabox['title']); ?></strong>
                            </td>
                            <td>
                                <?php echo esc_html(implode(', ', $metabox['post_types'])); ?>
                            </td>
                            <td>
                                <?php echo count($metabox['fields']); ?>
                            </td>
                            <td>
                                <?php if ($file_exists): ?>
                                    <span class="dashicons dashicons-yes-alt" style="color: green;"></span> <?php _e('Généré', 'up-gutenberg-metabox'); ?>
                                <?php else: ?>
                                    <span class="dashicons dashicons-minus" style="color: orange;"></span> <?php _e('Non généré', 'up-gutenberg-metabox'); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="post" style="display: inline;">
                                    <?php wp_nonce_field('ugm_generate_action', 'ugm_generate_nonce'); ?>
                                    <input type="hidden" name="metabox_id" value="<?php echo esc_attr($metabox['id']); ?>" />
                                    <button type="submit" name="generate_metabox" class="button button-small">
                                        <?php _e('Générer', 'up-gutenberg-metabox'); ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="ugm-bulk-actions">
                <h3><?php _e('Actions groupées', 'up-gutenberg-metabox'); ?></h3>
                
                <form method="post" id="generate-root-form">
                    <?php wp_nonce_field('ugm_generate_action', 'ugm_generate_nonce'); ?>
                    <p><?php _e('Générer le fichier root.php avec les metaboxes sélectionnées:', 'up-gutenberg-metabox'); ?></p>
                    <div id="selected-metaboxes-container"></div>
                    <button type="submit" name="generate_root" class="button button-primary">
                        <?php _e('Générer root.php', 'up-gutenberg-metabox'); ?>
                    </button>
                </form>
            </div>
            
        <?php endif; ?>
    </div>
    
    <div class="ugm-import-section" style="margin-top: 40px;">
        <h2><?php _e('Importer depuis le thème', 'up-gutenberg-metabox'); ?></h2>
        <p><?php _e('Cette action va scanner le dossier functions/metabox/inc/ du thème et importer les metaboxes trouvées.', 'up-gutenberg-metabox'); ?></p>
        
        <form method="post">
            <?php wp_nonce_field('ugm_import_action', 'ugm_import_nonce'); ?>
            <button type="submit" name="import_metaboxes" class="button button-secondary">
                <?php _e('Importer les metaboxes', 'up-gutenberg-metabox'); ?>
            </button>
        </form>
    </div>
    
    <div class="ugm-info-section" style="margin-top: 40px; padding: 20px; background: #f0f0f1; border-left: 4px solid #2271b1;">
        <h3><?php _e('Information', 'up-gutenberg-metabox'); ?></h3>
        <p><?php _e('Les fichiers générés seront créés dans:', 'up-gutenberg-metabox'); ?></p>
        <ul>
            <li><code><?php echo esc_html(get_stylesheet_directory()); ?>/functions/metabox/inc/</code> - <?php _e('Fichiers individuels des metaboxes', 'up-gutenberg-metabox'); ?></li>
            <li><code><?php echo esc_html(get_stylesheet_directory()); ?>/functions/metabox/root.php</code> - <?php _e('Fichier principal d\'inclusion', 'up-gutenberg-metabox'); ?></li>
        </ul>
        <p><?php _e('Une fois les fichiers générés, vous pouvez désactiver ce plugin. Les metaboxes continueront à fonctionner via le code du thème.', 'up-gutenberg-metabox'); ?></p>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Sélectionner/désélectionner tout
    $('#select-all-metaboxes').on('change', function() {
        $('.metabox-checkbox').prop('checked', $(this).prop('checked'));
        updateSelectedMetaboxes();
    });
    
    // Mettre à jour la liste des metaboxes sélectionnées
    $('.metabox-checkbox').on('change', function() {
        updateSelectedMetaboxes();
    });
    
    function updateSelectedMetaboxes() {
        var container = $('#selected-metaboxes-container');
        container.empty();
        
        $('.metabox-checkbox:checked').each(function() {
            container.append('<input type="hidden" name="selected_metaboxes[]" value="' + $(this).val() + '" />');
        });
    }
});
</script>
