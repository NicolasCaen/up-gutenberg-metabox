<?php
/**
 * Filtres personnalisés: Transformer un textarea multi-lignes en <ul><li>
 */

if (!defined('ABSPATH')) {
    exit;
}

// Lors du hook public, enregistrer le filtre dérivé
add_action('add-gutenberg-metabox-filter', function () {
    if (!class_exists('UpGutenbergMetabox')) {
        return;
    }

    UpGutenbergMetabox::register_derived_filter(
        'textarea_ul_li',
        __('Textarea en liste <ul><li>', 'up-gutenberg-metabox'),
        function ($v) {
            if ($v === null || $v === '') {
                return '';
            }
            $text = (string) $v;
            // Normaliser les fins de ligne
            $text = str_replace(["\r\n", "\r"], "\n", $text);
            $lines = explode("\n", $text);
            // Nettoyer et filtrer les lignes vides
            $lines = array_values(array_filter(array_map('trim', $lines), function ($l) {
                return $l !== '';
            }));

            if (empty($lines)) {
                return '';
            }

            // Construire la liste en échappant chaque item
            $items = array_map(function ($line) {
                return '<li>' . esc_html($line) . '</li>';
            }, $lines);

            return '<ul>' . implode('', $items) . '</ul>';
        }
    );
});
