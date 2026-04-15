jQuery(document).ready(function($) {
    'use strict';

    function copyToClipboard(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text);
        }
        return new Promise(function(resolve, reject) {
            try {
                const $tmp = $('<textarea readonly></textarea>').val(text).appendTo('body');
                $tmp[0].select();
                const ok = document.execCommand('copy');
                $tmp.remove();
                if (ok) resolve();
                else reject(new Error('copy_failed'));
            } catch (e) {
                reject(e);
            }
        });
    }

    function buildBindingSnippet(metaKey) {
        return "<!-- wp:paragraph {\"metadata\":{\"bindings\":{\"content\":{\"source\":\"core/post-meta\",\"args\":{\"key\":\"" + metaKey + "\"}}}}} -->\n\n<!-- /wp:paragraph -->";
    }

    function buildTermBindingSnippet(metaKey) {
        return "<!-- wp:paragraph {\"metadata\":{\"bindings\":{\"content\":{\"source\":\"ugm/term-meta\",\"args\":{\"key\":\"" + metaKey + "\"}}}}} -->\n\n<!-- /wp:paragraph -->";
    }

    function isUGMMetabox($postbox) {
        const hasPluginNonce = $postbox.find('input[type="hidden"][name^="ugm_metabox_nonce_"]').length > 0;
        const hasThemeNonce = $postbox.find('input[type="hidden"][name^="metabox_nonce_"]').length > 0;
        return hasPluginNonce || hasThemeNonce;
    }

    function injectButtonsIntoMetabox($postbox) {
        const $tables = $postbox.find('table.form-table');
        if (!$tables.length) return;

        $tables.each(function() {
            const $table = $(this);
            $table.find('tr').each(function() {
                const $tr = $(this);
                const $label = $tr.find('th label[for]').first();
                const metaKey = ($label.attr('for') || '').trim();

                if (!metaKey) return;

                const $td = $tr.find('td').first();
                if (!$td.length) return;

                if ($td.find('.ugm-binding-copy').length) return;

                const $btn = $(
                    '<button type="button" class="button button-secondary ugm-binding-copy" aria-label="Copier le snippet Block Binding">' +
                        '<span class="dashicons dashicons-clipboard"></span>' +
                    '</button>'
                );

                $btn.on('click', function() {
                    const snippet = buildBindingSnippet(metaKey);
                    const original = $btn.html();

                    copyToClipboard(snippet)
                        .then(function() {
                            $btn.text('Copié');
                            setTimeout(function() {
                                $btn.html(original);
                            }, 800);
                        })
                        .catch(function() {
                            alert('Impossible de copier dans le presse-papier.');
                        });
                });

                let $btnFormatted = null;
                if (!metaKey.endsWith('_formatted')) {
                    const formattedKey = metaKey + '_formatted';
                    $btnFormatted = $(
                        '<button type="button" class="button button-secondary ugm-binding-copy ugm-binding-copy-formatted" aria-label="Copier le snippet Block Binding (_formatted)">' +
                            '<span class="dashicons dashicons-clipboard"></span>' +
                            '<span class="ugm-binding-copy-suffix">F</span>' +
                        '</button>'
                    );

                    $btnFormatted.on('click', function() {
                        const snippet = buildBindingSnippet(formattedKey);
                        const original = $btnFormatted.html();

                        copyToClipboard(snippet)
                            .then(function() {
                                $btnFormatted.text('Copié');
                                setTimeout(function() {
                                    $btnFormatted.html(original);
                                }, 800);
                            })
                            .catch(function() {
                                alert('Impossible de copier dans le presse-papier.');
                            });
                    });
                }

                const $firstInput = $td.find('input, textarea, select').first();
                if ($firstInput.length) {
                    $firstInput.after($btn);
                    if ($btnFormatted) {
                        $btn.after($btnFormatted);
                    }
                } else {
                    $td.append($btn);
                    if ($btnFormatted) {
                        $td.append($btnFormatted);
                    }
                }
            });
        });
    }

    $('.postbox').each(function() {
        const $postbox = $(this);
        if (!isUGMMetabox($postbox)) return;
        injectButtonsIntoMetabox($postbox);
    });

    // Taxonomy term screens: inject buttons on fields with data-ugm-binding="1"
    $('[data-ugm-binding="1"]').each(function() {
        const $container = $(this);
        // Find the meta key from the label[for] or from the first input/textarea/select name
        const $label = $container.find('label[for]').first();
        let metaKey = ($label.attr('for') || '').trim();
        if (!metaKey) {
            const $input = $container.find('input, textarea, select').first();
            metaKey = ($input.attr('name') || '').trim();
        }
        if (!metaKey) return;

        // Find the element to append the button after
        const $input = $container.find('input, textarea, select').first();
        if (!$input.length) return;

        // Avoid duplicates
        if ($container.find('.ugm-binding-copy').length) return;

        const $btn = $(
            '<button type="button" class="button button-secondary ugm-binding-copy" aria-label="Copier le snippet Block Binding">' +
                '<span class="dashicons dashicons-clipboard"></span>' +
            '</button>'
        );

        $btn.on('click', function() {
            const snippet = buildTermBindingSnippet(metaKey);
            const original = $btn.html();
            copyToClipboard(snippet)
                .then(function() {
                    $btn.text('Copié');
                    setTimeout(function() { $btn.html(original); }, 800);
                })
                .catch(function() {
                    alert('Impossible de copier dans le presse-papier.');
                });
        });

        $input.after($btn);

        // Add _formatted button if derived is enabled
        if ($container.data('ugm-derived')) {
            const formattedKey = metaKey + '_formatted';
            const $btnF = $(
                '<button type="button" class="button button-secondary ugm-binding-copy ugm-binding-copy-formatted" aria-label="Copier le snippet Block Binding (_formatted)">' +
                    '<span class="dashicons dashicons-clipboard"></span>' +
                    '<span class="ugm-binding-copy-suffix">F</span>' +
                '</button>'
            );

            $btnF.on('click', function() {
                const snippet = buildTermBindingSnippet(formattedKey);
                const original = $btnF.html();
                copyToClipboard(snippet)
                    .then(function() {
                        $btnF.text('Copié');
                        setTimeout(function() { $btnF.html(original); }, 800);
                    })
                    .catch(function() {
                        alert('Impossible de copier dans le presse-papier.');
                    });
            });

            $btn.after($btnF);
        }
    });
});
