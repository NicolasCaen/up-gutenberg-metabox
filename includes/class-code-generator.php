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

        // Générer les assets pour la copie Block Binding (fonctionnel même si le plugin est désactivé)
        file_put_contents($this->theme_metabox_path . 'assets/js/metabox-binding-copy.js', $this->get_binding_copy_js_code());
        file_put_contents($this->theme_metabox_path . 'assets/css/metabox-binding-copy.css', $this->get_binding_copy_css_code());
        
        // Générer les assets pour les champs gallery (fonctionnel même si le plugin est désactivé)
        file_put_contents($this->theme_metabox_path . 'assets/js/metabox-gallery.js', $this->get_gallery_js_code());
        file_put_contents($this->theme_metabox_path . 'assets/css/metabox-gallery.css', $this->get_gallery_css_code());
        
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

        $code .= "\n";
        $code .= "add_action('admin_enqueue_scripts', function(\$hook) {\n";
        $code .= "    if (!in_array(\$hook, array('post.php','post-new.php'), true)) {\n";
        $code .= "        return;\n";
        $code .= "    }\n";
        $code .= "    if (defined('UGM_PLUGIN_VERSION')) {\n";
        $code .= "        return;\n";
        $code .= "    }\n";
        $code .= "    if (wp_script_is('up-gutenberg-metabox-binding-copy', 'enqueued') || wp_script_is('up-gutenberg-metabox-binding-copy', 'registered')) {\n";
        $code .= "        return;\n";
        $code .= "    }\n";
        $code .= "    \$base = get_stylesheet_directory_uri() . '/functions/metabox/assets/';\n";
        $code .= "    wp_enqueue_script('ugm-metabox-binding-copy', \$base . 'js/metabox-binding-copy.js', array('jquery'), '1.0.0', true);\n";
        $code .= "    wp_enqueue_style('ugm-metabox-binding-copy', \$base . 'css/metabox-binding-copy.css', array(), '1.0.0');\n";
        $code .= "    \n";
        $code .= "    // Gallery assets pour les champs de type gallery\n";
        $code .= "    wp_enqueue_media();\n";
        $code .= "    wp_enqueue_script('ugm-metabox-gallery', \$base . 'js/metabox-gallery.js', array('jquery', 'jquery-ui-sortable'), '1.0.0', true);\n";
        $code .= "    wp_enqueue_style('ugm-metabox-gallery', \$base . 'css/metabox-gallery.css', array(), '1.0.0');\n";
        $code .= "    wp_localize_script('ugm-metabox-gallery', 'ugm_gallery_i18n', array(\n";
        $code .= "        'selectImages' => __('Choisir des images', 'textdomain'),\n";
        $code .= "        'remove' => __('Retirer', 'textdomain'),\n";
        $code .= "    ));\n";
        $code .= "});\n";
        
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

        $code .= "    protected function apply_filter_surface_m2(\$value) {\n";
        $code .= "        if (\$value === '' || \$value === null) return '';\n";
        $code .= "        if (is_numeric(\$value)) {\n";
        $code .= "            return number_format((float)\$value, 0, ',', ' ') . ' M2';\n";
        $code .= "        }\n";
        $code .= "        return trim((string)\$value) . ' M2';\n";
        $code .= "    }\n\n";
        
        $code .= "    protected function apply_filter_currency_eur(\$value) {\n";
        $code .= "        if (\$value === '' || \$value === null) return '';\n";
        $code .= "        return number_format((float)\$value, 0, ',', ' ') . ' €';\n";
        $code .= "    }\n\n";

        $code .= "    protected function apply_filter_currency_eur_2dec(\$value) {\n";
        $code .= "        if (\$value === '' || \$value === null) return '';\n";
        $code .= "        return number_format((float)\$value, 2, ',', ' ') . ' €';\n";
        $code .= "    }\n\n";

        $code .= "    protected function apply_filter_yes_no(\$value) {\n";
        $code .= "        return ((string)\$value === '1') ? 'Oui' : 'Non';\n";
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

        $code .= "\n    protected function apply_filter_datetime_dmy_hi(\$value) {\n";
        $code .= "        \$ts = strtotime((string)\$value);\n";
        $code .= "        return \$ts ? date_i18n('d/m/Y H:i', \$ts) : (string)\$value;\n";
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

        if (!file_exists($this->theme_metabox_path . 'assets/js/')) {
            wp_mkdir_p($this->theme_metabox_path . 'assets/js/');
        }

        if (!file_exists($this->theme_metabox_path . 'assets/css/')) {
            wp_mkdir_p($this->theme_metabox_path . 'assets/css/');
        }
    }

    private function get_binding_copy_js_code() {
        return "jQuery(document).ready(function($) {\n" .
            "    'use strict';\n\n" .
            "    function copyToClipboard(text) {\n" .
            "        if (navigator.clipboard && navigator.clipboard.writeText) {\n" .
            "            return navigator.clipboard.writeText(text);\n" .
            "        }\n" .
            "        return new Promise(function(resolve, reject) {\n" .
            "            try {\n" .
            "                const \$tmp = $('<textarea readonly></textarea>').val(text).appendTo('body');\n" .
            "                \$tmp[0].select();\n" .
            "                const ok = document.execCommand('copy');\n" .
            "                \$tmp.remove();\n" .
            "                if (ok) resolve();\n" .
            "                else reject(new Error('copy_failed'));\n" .
            "            } catch (e) {\n" .
            "                reject(e);\n" .
            "            }\n" .
            "        });\n" .
            "    }\n\n" .
            "    function buildBindingSnippet(metaKey) {\n" .
            "        return \"<!-- wp:paragraph {\\\"metadata\\\":{\\\"bindings\\\":{\\\"content\\\":{\\\"source\\\":\\\"core/post-meta\\\",\\\"args\\\":{\\\"key\\\":\\\"\" + metaKey + \"\\\"}}}}} -->\\n\\n<!-- /wp:paragraph -->\";\n" .
            "    }\n\n" .
            "    function isUGMMetabox(\$postbox) {\n" .
            "        const hasPluginNonce = \$postbox.find('input[type=\\\"hidden\\\"][name^=\\\"ugm_metabox_nonce_\\\"]').length > 0;\n" .
            "        const hasThemeNonce = \$postbox.find('input[type=\\\"hidden\\\"][name^=\\\"metabox_nonce_\\\"]').length > 0;\n" .
            "        return hasPluginNonce || hasThemeNonce;\n" .
            "    }\n\n" .
            "    function injectButtonsIntoMetabox(\$postbox) {\n" .
            "        const \$tables = \$postbox.find('table.form-table');\n" .
            "        if (!\$tables.length) return;\n\n" .
            "        \$tables.each(function() {\n" .
            "            const \$table = $(this);\n" .
            "            \$table.find('tr').each(function() {\n" .
            "                const \$tr = $(this);\n" .
            "                const \$label = \$tr.find('th label[for]').first();\n" .
            "                const metaKey = (\$label.attr('for') || '').trim();\n\n" .
            "                if (!metaKey) return;\n\n" .
            "                const \$td = \$tr.find('td').first();\n" .
            "                if (!\$td.length) return;\n\n" .
            "                if (\$td.find('.ugm-binding-copy').length) return;\n\n" .
            "                const \$btn = $(\n" .
            "                    '<button type=\\\"button\\\" class=\\\"button button-secondary ugm-binding-copy\\\" aria-label=\\\"Copier le snippet Block Binding\\\">' +\n" .
            "                        '<span class=\\\"dashicons dashicons-clipboard\\\"></span>' +\n" .
            "                    '</button>'\n" .
            "                );\n\n" .
            "                \$btn.on('click', function() {\n" .
            "                    const snippet = buildBindingSnippet(metaKey);\n" .
            "                    const original = \$btn.html();\n\n" .
            "                    copyToClipboard(snippet)\n" .
            "                        .then(function() {\n" .
            "                            \$btn.text('Copié');\n" .
            "                            setTimeout(function() {\n" .
            "                                \$btn.html(original);\n" .
            "                            }, 800);\n" .
            "                        })\n" .
            "                        .catch(function() {\n" .
            "                            alert('Impossible de copier dans le presse-papier.');\n" .
            "                        });\n" .
            "                });\n\n" .
            "                let \$btnFormatted = null;\n" .
            "                if (!metaKey.endsWith('_formatted')) {\n" .
            "                    const formattedKey = metaKey + '_formatted';\n" .
            "                    \$btnFormatted = $(\n" .
            "                        '<button type=\\\"button\\\" class=\\\"button button-secondary ugm-binding-copy ugm-binding-copy-formatted\\\" aria-label=\\\"Copier le snippet Block Binding (_formatted)\\\">' +\n" .
            "                            '<span class=\\\"dashicons dashicons-clipboard\\\"></span>' +\n" .
            "                            '<span class=\\\"ugm-binding-copy-suffix\\\">F</span>' +\n" .
            "                        '</button>'\n" .
            "                    );\n\n" .
            "                    \$btnFormatted.on('click', function() {\n" .
            "                        const snippet = buildBindingSnippet(formattedKey);\n" .
            "                        const original = \$btnFormatted.html();\n\n" .
            "                        copyToClipboard(snippet)\n" .
            "                            .then(function() {\n" .
            "                                \$btnFormatted.text('Copié');\n" .
            "                                setTimeout(function() {\n" .
            "                                    \$btnFormatted.html(original);\n" .
            "                                }, 800);\n" .
            "                            })\n" .
            "                            .catch(function() {\n" .
            "                                alert('Impossible de copier dans le presse-papier.');\n" .
            "                            });\n" .
            "                    });\n" .
            "                }\n\n" .
            "                const \$firstInput = \$td.find('input, textarea, select').first();\n" .
            "                if (\$firstInput.length) {\n" .
            "                    \$firstInput.after(\$btn);\n" .
            "                    if (\$btnFormatted) {\n" .
            "                        \$btn.after(\$btnFormatted);\n" .
            "                    }\n" .
            "                } else {\n" .
            "                    \$td.append(\$btn);\n" .
            "                    if (\$btnFormatted) {\n" .
            "                        \$td.append(\$btnFormatted);\n" .
            "                    }\n" .
            "                }\n" .
            "            });\n" .
            "        });\n" .
            "    }\n\n" .
            "    $('.postbox').each(function() {\n" .
            "        const \$postbox = $(this);\n" .
            "        if (!isUGMMetabox(\$postbox)) return;\n" .
            "        injectButtonsIntoMetabox(\$postbox);\n" .
            "    });\n" .
            "});\n";
    }

    private function get_binding_copy_css_code() {
        return ".ugm-metabox-table .ugm-binding-copy{margin-left:8px;padding:0 6px;min-width:32px;height:30px;line-height:28px;vertical-align:middle;}" .
            ".ugm-metabox-table .ugm-binding-copy .dashicons{line-height:28px;}" .
            ".ugm-metabox-table .ugm-binding-copy-formatted{position:relative;}" .
            ".ugm-metabox-table .ugm-binding-copy-formatted .ugm-binding-copy-suffix{display:inline-block;margin-left:2px;font-size:11px;font-weight:700;line-height:1;vertical-align:middle;}";
    }

    private function get_gallery_js_code() {
        return "(function($){
  'use strict';

  function csvToIds(csv){
    if(!csv) return [];
    return String(csv)
      .split(',')
      .map(function(s){ return parseInt(s.trim(),10); })
      .filter(function(n){ return !isNaN(n) && n > 0; });
  }

  function idsToCsv(ids){
    return (ids||[]).filter(function(n){return !!n;}).join(',');
  }

  function refreshHiddenInput(\$wrap){
    var ids = [];
    \$wrap.find('.ugm-gallery-item').each(function(){
      var id = parseInt($(this).attr('data-id'),10);
      if(!isNaN(id) && id>0) ids.push(id);
    });
    var \$input = \$wrap.find('input[type=\"hidden\"]');
    \$input.val(idsToCsv(ids)).trigger('change');
  }

  function addItem(\$list, id, thumbHtml){
    var \$li = $('<li class=\"ugm-gallery-item\"/>').attr('data-id', id);
    var \$thumb = $('<span class=\"ugm-thumb\"/>').html(thumbHtml);
    var \$remove = $('<button type=\"button\" class=\"button-link ugm-remove\" aria-label=\"'+ (window.ugm_gallery_i18n ? ugm_gallery_i18n.remove : 'Remove') +'\">&times;</button>');
    \$li.append(\$thumb).append(\$remove);
    \$list.append(\$li);
  }

  function fetchThumbHtml(attachment){
    // If media frame supplies sizes, use thumbnail/url
    if(attachment && attachment.sizes && attachment.sizes.thumbnail){
      var s = attachment.sizes.thumbnail;
      return '<img src=\"'+ s.url +'\" width=\"'+ (s.width||80) +'\" height=\"'+ (s.height||80) +'\" alt=\"\" />';
    }
    if(attachment && attachment.icon){
      return '<img src=\"'+ attachment.icon +'\" width=\"80\" height=\"80\" alt=\"\" />';
    }
    // Fallback
    return '<span class=\"ugm-missing-thumb\" />';
  }

  function openMediaFrame(\$wrap){
    var title = (window.ugm_gallery_i18n ? ugm_gallery_i18n.selectImages : 'Select images');
    var frame = wp.media({
      title: title,
      library: { type: 'image' },
      multiple: true,
      button: { text: title }
    });

    frame.on('select', function(){
      var selection = frame.state().get('selection');
      var \$list = \$wrap.find('.ugm-gallery-list');
      selection.each(function(model){
        var att = model.toJSON();
        // Avoid duplicates
        var exists = false;
        \$list.find('.ugm-gallery-item').each(function(){
          if (parseInt($(this).attr('data-id'),10) === att.id) { exists = true; return false; }
        });
        if(!exists){
          addItem(\$list, att.id, fetchThumbHtml(att));
        }
      });
      refreshHiddenInput(\$wrap);
    });

    frame.open();
  }

  function initSortable(\$wrap){
    var \$list = \$wrap.find('.ugm-gallery-list');
    if(!\$list.length || \$list.data('sortable-initialized')) return;
    \$list.sortable({
      items: '> .ugm-gallery-item',
      placeholder: 'ugm-gallery-sort-placeholder',
      forcePlaceholderSize: true,
      tolerance: 'pointer',
      update: function(){ refreshHiddenInput(\$wrap); }
    });
    \$list.data('sortable-initialized', true);
  }

  $(document).on('click', '.ugm-gallery-select', function(e){
    e.preventDefault();
    var \$wrap = $(this).closest('.ugm-gallery-field');
    openMediaFrame(\$wrap);
  });

  $(document).on('click', '.ugm-gallery-field .ugm-remove', function(e){
    e.preventDefault();
    var \$wrap = $(this).closest('.ugm-gallery-field');
    $(this).closest('.ugm-gallery-item').remove();
    refreshHiddenInput(\$wrap);
  });

  // Initialize existing fields on load
  $(function(){
    $('.ugm-gallery-field').each(function(){
      initSortable($(this));
    });
  });

})(jQuery);";
    }

    private function get_gallery_css_code() {
        return "/* UGM Gallery field styles */
.ugm-gallery-field { margin-top: 6px; }
.ugm-gallery-field .ugm-gallery-select { margin-bottom: 8px; }

.ugm-gallery-list { 
  list-style: none; 
  padding: 0; 
  margin: 0; 
  display: flex; 
  flex-wrap: wrap; 
  gap: 8px; 
}

.ugm-gallery-item { 
  position: relative; 
  width: 80px; 
  height: 80px; 
  border: 1px solid #ddd; 
  border-radius: 3px; 
  overflow: hidden; 
  background: #fff;
}

.ugm-gallery-item .ugm-thumb img { 
  width: 100%; 
  height: 100%; 
  object-fit: cover; 
  display: block; 
}

.ugm-gallery-item .ugm-remove { 
  position: absolute; 
  top: 0; 
  right: 2px; 
  color: #a00; 
  font-size: 18px; 
  line-height: 1; 
}

.ugm-gallery-sort-placeholder { 
  width: 80px; 
  height: 80px; 
  border: 2px dashed #b3b3b3; 
  background: #f5f5f5; 
  border-radius: 3px; 
}";
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
        
        // Initialiser la classe (éviter les doublons plugin + thème)
        $code .= "// Initialiser\n";
        $code .= "\$ugm_should_load = true;\n";
        $code .= "if (class_exists('UpGutenbergMetabox')) {\n";
        $code .= "    \$ugm_sources = get_option('ugm_metabox_sources', array());\n";
        $code .= "    \$ugm_source = isset(\$ugm_sources['{$metabox['id']}']) ? \$ugm_sources['{$metabox['id']}'] : 'plugin';\n";
        $code .= "    \$ugm_should_load = (\$ugm_source === 'theme');\n";
        $code .= "}\n";
        $code .= "if (\$ugm_should_load) {\n";
        $code .= "    {$class_name}::get_instance();\n";
        $code .= "}\n";
        
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
                
            case 'gallery':
                $code .= "        \$ids_{$var_name} = array_filter(array_map('absint', array_filter(array_map('trim', explode(',', (string)\$value_{$var_name})))));\n";
                $code .= "        echo '<div class=\"ugm-gallery-field\" data-input=\"#{$field['name']}\">';\n";
                $code .= "        echo '<button type=\"button\" class=\"button ugm-gallery-select\">' . esc_html__('Choisir des images', 'textdomain') . '</button>';\n";
                $code .= "        echo '<ul class=\"ugm-gallery-list\" data-name=\"{$field['name']}\">';\n";
                $code .= "        if (!empty(\$ids_{$var_name})) {\n";
                $code .= "            foreach (\$ids_{$var_name} as \$att_id) {\n";
                $code .= "                \$thumb = wp_get_attachment_image(\$att_id, array(80,80), true);\n";
                $code .= "                if (\$thumb) {\n";
                $code .= "                    echo '<li class=\"ugm-gallery-item\" data-id=\"' . esc_attr(\$att_id) . '\">';\n";
                $code .= "                    echo '<span class=\"ugm-thumb\">' . \$thumb . '</span>';\n";
                $code .= "                    echo '<button type=\"button\" class=\"button-link ugm-remove\" aria-label=\"' . esc_attr__('Retirer', 'textdomain') . '\">&times;</button>';\n";
                $code .= "                    echo '</li>';\n";
                $code .= "                }\n";
                $code .= "            }\n";
                $code .= "        }\n";
                $code .= "        echo '</ul>';\n";
                $code .= "        echo '<input type=\"hidden\" id=\"{$field['name']}\" name=\"{$field['name']}\" value=\"' . esc_attr(implode(',', \$ids_{$var_name})) . '\" />';\n";
                $code .= "        echo '</div>';\n";
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
        } elseif ($field['type'] === 'gallery') {
            $code .= "        if (isset(\$_POST['{$field['name']}'])) {\n";
            $code .= "            \$ids = array_filter(array_map('absint', array_filter(array_map('trim', explode(',', (string)wp_unslash(\$_POST['{$field['name']}']))))));\n";
            $code .= "            \$value_{$var_name} = implode(',', \$ids);\n";
            $code .= "        } else {\n";
            $code .= "            \$value_{$var_name} = '';\n";
            $code .= "        }\n";
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
