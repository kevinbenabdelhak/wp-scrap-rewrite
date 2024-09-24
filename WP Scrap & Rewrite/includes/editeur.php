<?php 



if (!defined('ABSPATH')) {
    exit; 
}


// Jquery + Script nécessaire pour générer les boutons, envoyer la requête et afficher la rep dans l'éditeur


function enqueue_rewrite_url_script() {
    $screen = get_current_screen();
    if ($screen->base === 'post') {
        wp_enqueue_script('jquery');
        ?>




        <script type="text/javascript">
jQuery(document).ready(function($) {
    if ($('#wp-rewriter-controls').length === 0) {
        var controlsDiv = $('<div>', { id: 'wp-rewriter-controls', style:'display: contents;' });
        
        var urlInput = $('<input>', {
            type: 'url',
            placeholder: 'Entrez l\'URL ici',
            class: 'wp-rewriter-url-input',
            style: 'margin: 0 0; width: 170px; font-size:12px; margin-left:5px;'
        });

        var promptInput = $('<input>', {
            type: 'text',
            placeholder: 'Entrez votre prompt',
            class: 'wp-rewriter-prompt-input',
            style: 'margin: 0 0; width: 300px; font-size:12px; margin-left:5px;'
        });

        var replaceContentCheckbox = $('<input>', {
            type: 'checkbox',
            class: 'wp-rewriter-replace-checkbox',
            id: 'wp-rewriter-replace-checkbox',
            style: 'margin-left:5px;'
        });

        var replaceContentLabel = $('<label>', {
            text: 'Remplacer tout le contenu',
            for: 'wp-rewriter-replace-checkbox'
        });

        var generateButton = $('<button>', {
            text: 'Réécrire le contenu',
            class: 'button button-primary wp-rewriter-generate-button',
        }).css({
            margin: '0 0',
            marginLeft: '10px'
        });

        var loader = $('<span>', {
            text: 'Génération en cours...',
            class: 'wp-rewriter-loader',
            css: { display: 'none', marginLeft: '10px' }
        });

        controlsDiv.append(urlInput)
            .append(promptInput)
            .append(replaceContentCheckbox)
            .append(replaceContentLabel)
            .append(generateButton)
            .append(loader);
        
        $('#wp-content-media-buttons').after(controlsDiv);  // Ajoute les contrôles après les boutons média

        generateButton.on('click', function(e) {
            e.preventDefault(); // Empêche le rechargement de la page

            // initialisé TinyMCE 
            tinyMCE.EditorManager.execCommand('mceAddEditor', true, 'content');
            tinyMCE.execCommand('mceRemoveEditor', false, 'content');
            tinyMCE.execCommand('mceAddEditor', false, 'content');

            setTimeout(function() {
                if (tinymce.get('content').initialized) {
                    tinymce.get('content').focus();
                } else {
                    tinymce.EditorManager.once('AddEditor', function() {
                        tinymce.get('content').focus();
                    });
                }
            }, 300);

            var url = urlInput.val().trim();
            var prompt = promptInput.val().trim();
            var replaceContent = replaceContentCheckbox.is(':checked'); // vérifie si la case est cochée pour remplacer ou non le contenu

            // Désactive le bouton et affiche le loader
            generateButton.prop('disabled', true);
            loader.show();

            // appel ajax (la fonction est dans requete.php)
            $.ajax({
                url: "<?php echo admin_url('admin-ajax.php'); ?>",
                method: 'POST',
                data: {
                    action: 'rewrite_url_content',
                    url: url,
                    prompt: prompt,
                    replace: replaceContent, // Ajouter la valeur de la case à cocher
                    security: '<?php echo wp_create_nonce('rewrite_url_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        var contentToInsert = response.data.rewritten_content;
                        // Si la case à cocher est cochée, remplacer le contenu
                        if (replaceContent) {
                            tinymce.get('content').setContent(contentToInsert);
                        } else {
                            // Sinon, ajouter le contenu à la fin de l'éditeur
                            tinymce.get('content').setContent(tinymce.get('content').getContent() + contentToInsert);
                        }
                    } else {
                        alert('Erreur: ' + JSON.stringify(response.data));
                    }
                },
                error: function(response) {
                    console.error('Erreur de connexion:', response);
                    alert('Erreur de connexion avec l\'API. Détails: ' + JSON.stringify(response));
                },
                complete: function() {
                    generateButton.prop('disabled', false);
                    loader.hide();
                }
            });
        });
    }
});
        </script>
        <?php
    }
}
add_action('admin_footer', 'enqueue_rewrite_url_script');
