jQuery(document).ready(function($) {
    'use strict';
    
    let metaboxIndex = $('#ugm-metaboxes-container .ugm-metabox-config').length;
    let fieldIndexes = {};
    
    // Initialiser les index des champs existants
    $('#ugm-metaboxes-container .ugm-metabox-config').each(function() {
        const index = $(this).data('index');
        fieldIndexes[index] = $(this).find('.ugm-field-config').length;
    });
    
    /**
     * Ajouter une nouvelle metabox
     */
    $('#add-metabox').on('click', function() {
        const template = $('#ugm-metabox-template').html();
        const html = template
            .replace(/\{\{INDEX\}\}/g, metaboxIndex)
            .replace(/\{\{INDEX_DISPLAY\}\}/g, metaboxIndex + 1);
        
        $('#ugm-metaboxes-container').append(html);
        fieldIndexes[metaboxIndex] = 0;
        metaboxIndex++;
        
        // Faire défiler vers la nouvelle metabox
        $('html, body').animate({
            scrollTop: $('#ugm-metaboxes-container .ugm-metabox-config:last').offset().top - 50
        }, 500);

        // Initialiser le tri sur le nouveau conteneur de champs
        const $newContainer = $('#ugm-metaboxes-container .ugm-metabox-config:last .ugm-fields-container');
        initSortableOnContainer($newContainer);
    });
    
    /**
     * Supprimer une metabox
     */
    $(document).on('click', '.remove-metabox', function() {
        const $metabox = $(this).closest('.ugm-metabox-config');
        
        if (confirm('Êtes-vous sûr de vouloir supprimer cette metabox ?')) {
            $metabox.addClass('removing');
            setTimeout(function() {
                $metabox.remove();
                updateMetaboxIndexes();
            }, 300);
        }
    });
    
    /**
     * Ajouter un nouveau champ
     */
    $(document).on('click', '.add-field', function() {
        const metaboxIdx = $(this).data('metabox-index');
        const fieldIdx = fieldIndexes[metaboxIdx] || 0;
        const template = $('#ugm-field-template').html();
        
        const html = template
            .replace(/\{\{METABOX_INDEX\}\}/g, metaboxIdx)
            .replace(/\{\{FIELD_INDEX\}\}/g, fieldIdx)
            .replace(/\{\{FIELD_INDEX_DISPLAY\}\}/g, fieldIdx + 1);
        
        $(this).siblings('.ugm-fields-container').append(html);
        fieldIndexes[metaboxIdx] = fieldIdx + 1;
        
        // Faire défiler vers le nouveau champ
        const $newField = $(this).siblings('.ugm-fields-container').find('.ugm-field-config:last');
        $('html, body').animate({
            scrollTop: $newField.offset().top - 50
        }, 500);

        // Initialiser l'état des options de binding et dérivées pour le nouveau champ
        initBindingUIForField($newField);
        initDerivedUIForField($newField);
    });
    
    /**
     * Supprimer un champ
     */
    $(document).on('click', '.remove-field', function() {
        const $field = $(this).closest('.ugm-field-config');
        
        if (confirm('Êtes-vous sûr de vouloir supprimer ce champ ?')) {
            $field.addClass('removing');
            setTimeout(function() {
                $field.remove();
                updateFieldIndexes();
            }, 300);
        }
    });
    
    /**
     * Gérer le changement de type de champ
     */
    $(document).on('change', '.field-type-select', function() {
        const $field = $(this).closest('.ugm-field-config');
        const $selectOptions = $field.find('.ugm-select-options');
        
        if ($(this).val() === 'select') {
            $selectOptions.slideDown();
        } else {
            $selectOptions.slideUp();
        }
    });
    
    /**
     * Ajouter une option de select
     */
    $(document).on('click', '.add-option', function() {
        const $field = $(this).closest('.ugm-field-config');
        const metaboxIdx = $field.closest('.ugm-fields-container').data('metabox-index');
        const fieldIdx = $field.data('field-index');
        const optionIdx = $field.find('.ugm-option-config').length;
        
        const template = $('#ugm-option-template').html();
        const html = template
            .replace(/\{\{METABOX_INDEX\}\}/g, metaboxIdx)
            .replace(/\{\{FIELD_INDEX\}\}/g, fieldIdx)
            .replace(/\{\{OPTION_INDEX\}\}/g, optionIdx);
        
        $(this).siblings('.ugm-options-container').append(html);
    });
    
    /**
     * Supprimer une option de select
     */
    $(document).on('click', '.remove-option', function() {
        if (confirm('Supprimer cette option ?')) {
            $(this).closest('.ugm-option-config').remove();
        }
    });
    
    /**
     * Validation du formulaire
     */
    $('#ugm-config-form').on('submit', function(e) {
        let isValid = true;
        const errors = [];
        
        // Vérifier que chaque metabox a un titre
        $('.ugm-metabox-config').each(function() {
            const title = $(this).find('input[name*="[title]"]').val().trim();
            if (!title) {
                errors.push('Toutes les metaboxes doivent avoir un titre.');
                isValid = false;
                return false;
            }
            
            // Vérifier qu'au moins un post type est sélectionné
            const hasPostType = $(this).find('input[name*="[post_types]"]:checked').length > 0;
            if (!hasPostType) {
                errors.push('Chaque metabox doit être assignée à au moins un post type.');
                isValid = false;
                return false;
            }
            
            // Vérifier les champs
            $(this).find('.ugm-field-config').each(function() {
                const fieldName = $(this).find('input[name*="[name]"]').val().trim();
                const fieldLabel = $(this).find('input[name*="[label]"]').val().trim();
                
                if (!fieldName || !fieldLabel) {
                    errors.push('Tous les champs doivent avoir un nom et un libellé.');
                    isValid = false;
                    return false;
                }
                
                // Vérifier que le nom du champ ne contient que des caractères valides
                if (!/^[a-zA-Z0-9_]+$/.test(fieldName)) {
                    errors.push('Le nom du champ "' + fieldName + '" contient des caractères non autorisés. Utilisez uniquement des lettres, chiffres et underscores.');
                    isValid = false;
                    return false;
                }
                
                // Vérifier les options pour les champs select
                const fieldType = $(this).find('select[name*="[type]"]').val();
                if (fieldType === 'select') {
                    const hasOptions = $(this).find('.ugm-option-config').length > 0;
                    if (!hasOptions) {
                        errors.push('Les champs de type "Liste déroulante" doivent avoir au moins une option.');
                        isValid = false;
                        return false;
                    }
                }
            });
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Erreurs de validation :\n\n' + errors.join('\n'));
            return false;
        }
        
        // Ajouter un indicateur de chargement
        $(this).addClass('ugm-loading');
        $(this).find('input[type="submit"]').prop('disabled', true).val('Sauvegarde en cours...');
    });
    
    /**
     * Mettre à jour les index des metaboxes après suppression
     */
    function updateMetaboxIndexes() {
        $('#ugm-metaboxes-container .ugm-metabox-config').each(function(index) {
            $(this).data('index', index);
            // Rafraîchir le titre affiché à partir de l'input
            const titleVal = $(this).find('input[name*="[title]"]').val();
            $(this).find('.ugm-metabox-title').text(titleVal ? titleVal : 'Metabox sans titre');
            
            // Mettre à jour tous les attributs name et id
            $(this).find('input, select, textarea').each(function() {
                const name = $(this).attr('name');
                const id = $(this).attr('id');
                
                if (name) {
                    $(this).attr('name', name.replace(/metaboxes\[\d+\]/, 'metaboxes[' + index + ']'));
                }
                if (id) {
                    $(this).attr('id', id.replace(/_\d+_/, '_' + index + '_'));
                }
            });
            
            // Mettre à jour les labels
            $(this).find('label').each(function() {
                const forAttr = $(this).attr('for');
                if (forAttr) {
                    $(this).attr('for', forAttr.replace(/_\d+_/, '_' + index + '_'));
                }
            });
        });
        
        metaboxIndex = $('#ugm-metaboxes-container .ugm-metabox-config').length;
    }
    
    /**
     * Mettre à jour les index des champs après suppression
     */
    function updateFieldIndexes() {
        $('.ugm-fields-container').each(function() {
            const metaboxIdx = $(this).data('metabox-index');
            
            $(this).find('.ugm-field-config').each(function(index) {
                $(this).data('field-index', index);
                // Rafraîchir le titre du champ à partir du nom
                const fieldNameVal = $(this).find('input[name*="[name]"]').val();
                $(this).find('.ugm-field-title').text(fieldNameVal ? fieldNameVal : 'Sans nom');
                
                // Mettre à jour tous les attributs name et id
                $(this).find('input, select, textarea').each(function() {
                    const name = $(this).attr('name');
                    const id = $(this).attr('id');
                    
                    if (name) {
                        $(this).attr('name', name.replace(/\[fields\]\[\d+\]/, '[fields][' + index + ']'));
                    }
                    if (id) {
                        $(this).attr('id', id.replace(/_\d+_\d+/, '_' + metaboxIdx + '_' + index));
                    }
                });
                
                // Mettre à jour les labels
                $(this).find('label').each(function() {
                    const forAttr = $(this).attr('for');
                    if (forAttr) {
                        $(this).attr('for', forAttr.replace(/_\d+_\d+/, '_' + metaboxIdx + '_' + index));
                    }
                });
            });
            
            fieldIndexes[metaboxIdx] = $(this).find('.ugm-field-config').length;
        });
    }

    /**
     * Initialiser jQuery UI Sortable sur un conteneur spécifique
     */
    function initSortableOnContainer($container) {
        if (!$container || !$container.length) return;
        if ($container.data('sortable-initialized')) return;
        $container.sortable({
            items: '> .ugm-field-config',
            handle: '.ugm-field-header',
            placeholder: 'ugm-sort-placeholder',
            forcePlaceholderSize: true,
            tolerance: 'pointer',
            update: function() {
                updateFieldIndexes();
            }
        });
        $container.data('sortable-initialized', true);
    }

    /**
     * Initialiser Sortable sur tous les conteneurs existants
     */
    function initSortable() {
        $('.ugm-fields-container').each(function() {
            initSortableOnContainer($(this));
        });
    }

    // Initialiser au chargement de la page
    initSortable();

    // Initialiser les titres présents (metaboxes et champs) à partir des valeurs existantes
    $('.ugm-metabox-config').each(function() {
        const titleVal = $(this).find('input[name*="[title]"]').val();
        $(this).find('.ugm-metabox-title').text(titleVal ? titleVal : 'Metabox sans titre');
    });
    $('.ugm-field-config').each(function() {
        const fieldNameVal = $(this).find('input[name*="[name]"]').val();
        $(this).find('.ugm-field-title').text(fieldNameVal ? fieldNameVal : 'Sans nom');
    });

    /**
     * Gestion de l'UI de Binding Gutenberg
     */
    function initBindingUIForField($field) {
        const $checkbox = $field.find('.ugm-binding-checkbox');
        const $opts = $field.find('.ugm-binding-options');
        if ($checkbox.is(':checked')) {
            $opts.show();
        } else {
            $opts.hide();
        }
    }

    // Toggle au changement
    $(document).on('change', '.ugm-binding-checkbox', function() {
        const $field = $(this).closest('.ugm-field-config');
        const $opts = $field.find('.ugm-binding-options');
        if ($(this).is(':checked')) {
            $opts.slideDown(150);
        } else {
            $opts.slideUp(150);
        }
    });

    /**
     * Gestion de l'UI de Donnée Dérivée
     */
    function initDerivedUIForField($field) {
        const $checkbox = $field.find('.ugm-derived-checkbox');
        const $opts = $field.find('.ugm-derived-options');
        if ($checkbox.is(':checked')) {
            $opts.show();
        } else {
            $opts.hide();
        }
    }

    $(document).on('change', '.ugm-derived-checkbox', function() {
        const $field = $(this).closest('.ugm-field-config');
        const $opts = $field.find('.ugm-derived-options');
        if ($(this).is(':checked')) {
            $opts.slideDown(150);
        } else {
            $opts.slideUp(150);
        }
    });

    // Initialiser l'état pour les champs existants
    $('.ugm-field-config').each(function() {
        const $field = $(this);
        initBindingUIForField($field);
        initDerivedUIForField($field);
    });

    /**
     * Toggle détails de metabox
     */
    $(document).on('click', '.ugm-toggle-metabox', function() {
        const $btn = $(this);
        const $config = $btn.closest('.ugm-metabox-config');
        const $details = $config.children('.ugm-metabox-details');
        const expanded = $btn.attr('aria-expanded') === 'true';
        if (expanded) {
            $details.slideUp(150);
            $btn.attr('aria-expanded', 'false').text('Afficher les détails');
        } else {
            $details.slideDown(150);
            $btn.attr('aria-expanded', 'true').text('Masquer les détails');
        }
    });

    /**
     * Toggle options d'un champ
     */
    $(document).on('click', '.ugm-toggle-field', function() {
        const $btn = $(this);
        const $config = $btn.closest('.ugm-field-config');
        const $body = $config.children('.ugm-field-body');
        const expanded = $btn.attr('aria-expanded') === 'true';
        if (expanded) {
            $body.slideUp(150);
            $btn.attr('aria-expanded', 'false').text('Afficher les options');
        } else {
            $body.slideDown(150);
            $btn.attr('aria-expanded', 'true').text('Masquer les options');
        }
    });

    /**
     * Mise à jour en direct des en-têtes
     */
    $(document).on('input', 'input[name*="[title]"]', function() {
        const $wrap = $(this).closest('.ugm-metabox-config');
        const val = $(this).val().trim();
        $wrap.find('.ugm-metabox-title').text(val || 'Metabox sans titre');
    });
    $(document).on('input', 'input[name*="[name]"]', function() {
        const $wrap = $(this).closest('.ugm-field-config');
        const val = $(this).val().trim();
        $wrap.find('.ugm-field-title').text(val || 'Sans nom');
    });
    
    /**
     * Auto-générer le nom du champ basé sur le libellé
     */
    $(document).on('blur', 'input[name*="[label]"]', function() {
        const $nameField = $(this).closest('tr').prev().find('input[name*="[name]"]');
        
        if (!$nameField.val().trim()) {
            let name = $(this).val().trim()
                .toLowerCase()
                .replace(/[^a-z0-9\s]/g, '')
                .replace(/\s+/g, '_')
                .substring(0, 50);
            
            $nameField.val(name);
        }
    });
    
    /**
     * Initialiser les champs select existants
     */
    $('.field-type-select').each(function() {
        const $field = $(this).closest('.ugm-field-config');
        const $selectOptions = $field.find('.ugm-select-options');
        
        if ($(this).val() === 'select') {
            $selectOptions.show();
        }
    });
    
    /**
     * Confirmation avant de quitter la page si des modifications non sauvegardées
     */
    let formChanged = false;
    
    $('#ugm-config-form input, #ugm-config-form select, #ugm-config-form textarea').on('change', function() {
        formChanged = true;
    });
    
    $(window).on('beforeunload', function() {
        if (formChanged) {
            return 'Vous avez des modifications non sauvegardées. Êtes-vous sûr de vouloir quitter cette page ?';
        }
    });
    
    $('#ugm-config-form').on('submit', function() {
        formChanged = false;
    });
    
    /**
     * Raccourcis clavier
     */
    $(document).on('keydown', function(e) {
        // Ctrl/Cmd + S pour sauvegarder
        if ((e.ctrlKey || e.metaKey) && e.which === 83) {
            e.preventDefault();
            $('#ugm-config-form').submit();
        }
        
        // Ctrl/Cmd + N pour nouvelle metabox
        if ((e.ctrlKey || e.metaKey) && e.which === 78) {
            e.preventDefault();
            $('#add-metabox').click();
        }
    });
});
