<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    return;
}

if (isset($_POST['ugm_settings_action']) && $_POST['ugm_settings_action'] === 'save_metabox_sources') {
    if (isset($_POST['ugm_nonce']) && wp_verify_nonce($_POST['ugm_nonce'], 'ugm_save_metabox_sources')) {
        $posted_sources = isset($_POST['ugm_metabox_sources']) ? wp_unslash($_POST['ugm_metabox_sources']) : array();
        $clean_sources = array();

        if (is_array($posted_sources)) {
            foreach ($posted_sources as $metabox_id => $source) {
                $metabox_id = sanitize_text_field($metabox_id);
                $source = sanitize_text_field($source);
                if (in_array($source, array('plugin', 'theme'), true)) {
                    $clean_sources[$metabox_id] = $source;
                }
            }
        }

        update_option('ugm_metabox_sources', $clean_sources);
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Réglages enregistrés.', 'up-gutenberg-metabox') . '</p></div>';
    } else {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Nonce invalide.', 'up-gutenberg-metabox') . '</p></div>';
    }
}

$metaboxes = get_option('ugm_metaboxes', array());
$sources = get_option('ugm_metabox_sources', array());
?>

<div class="wrap">
    <h1><?php _e('Réglages', 'up-gutenberg-metabox'); ?></h1>
    <p><?php _e('Choisissez la source à utiliser pour chaque metabox. Par défaut : plugin.', 'up-gutenberg-metabox'); ?></p>

    <form method="post">
        <?php wp_nonce_field('ugm_save_metabox_sources', 'ugm_nonce'); ?>
        <input type="hidden" name="ugm_settings_action" value="save_metabox_sources" />

        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Metabox', 'up-gutenberg-metabox'); ?></th>
                    <th style="width:220px;"><?php _e('Source', 'up-gutenberg-metabox'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($metaboxes) && is_array($metaboxes)) : ?>
                    <?php foreach ($metaboxes as $metabox) : ?>
                        <?php
                        $metabox_id = isset($metabox['id']) ? (string) $metabox['id'] : '';
                        if ($metabox_id === '') {
                            continue;
                        }
                        $title = isset($metabox['title']) ? (string) $metabox['title'] : $metabox_id;
                        $current = isset($sources[$metabox_id]) ? $sources[$metabox_id] : 'plugin';
                        if (!in_array($current, array('plugin', 'theme'), true)) {
                            $current = 'plugin';
                        }
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($title); ?></strong>
                                <br />
                                <code><?php echo esc_html($metabox_id); ?></code>
                            </td>
                            <td>
                                <label style="margin-right:12px;">
                                    <input type="radio" name="ugm_metabox_sources[<?php echo esc_attr($metabox_id); ?>]" value="plugin" <?php checked($current, 'plugin'); ?> />
                                    <?php _e('Plugin', 'up-gutenberg-metabox'); ?>
                                </label>
                                <label>
                                    <input type="radio" name="ugm_metabox_sources[<?php echo esc_attr($metabox_id); ?>]" value="theme" <?php checked($current, 'theme'); ?> />
                                    <?php _e('Thème', 'up-gutenberg-metabox'); ?>
                                </label>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="2"><?php _e('Aucune metabox trouvée.', 'up-gutenberg-metabox'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php submit_button(__('Enregistrer', 'up-gutenberg-metabox')); ?>
    </form>
</div>
