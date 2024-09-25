<?php

if (!defined('ABSPATH')) {
    exit; 
}

// Ajouter des actions groupées
function add_bulk_rewrite_actions() {
    ?>
  <script type="text/javascript">
    jQuery(document).ready(function ($) {
        if ($('select[name="action"] option[value="scrapper_rewrite_content"]').length === 0) {
            $('select[name="action"], select[name="action2"]').append('<option value="scrapper_rewrite_content">Scrapper et Réécrire</option>');
        }

        var nonceVal = '<?php echo wp_create_nonce('rewrite_content_nonce'); ?>';

        const urlPromptDiv = `
            <div id="url-prompt-div" style="display: inline-flex;">
                <textarea id="bulk-url-textarea" name="bulk-url-textarea" style="font-size:14px;width: 350px; height: 30px; resize: auto;" placeholder="Entrez les URLs(une par ligne)"></textarea>
            </div>
        `;

        $('.tablenav.top .bulkactions').append(urlPromptDiv);
        $('.tablenav.bottom .bulkactions').append(urlPromptDiv);

        $(document).on('click', '#doaction, #doaction2', function (e) {
            const action = $('select[name="action"]').val() !== '-1' ? $('select[name="action"]').val() : $('select[name="action2"]').val();
            if (action !== 'scrapper_rewrite_content') return;
            e.preventDefault();

            const urls = $('#bulk-url-textarea').val().split('\n').map(url => url.trim()).filter(url => url.length > 0);
            if (!urls.length) {
                alert('Veuillez entrer des URLs');
                return;
            }

            $('#bulk-action-loader').remove();
            $('#doaction, #doaction2').after("<div id='bulk-action-loader' style='max-width: 160px; display: flex; flex-direction: row; align-content: center; align-items: center; float: inherit;'><span class='spinner is-active' style='margin-left: 10px;'></span> <span id='generation-progress'>0 / " + urls.length + " terminés</span></div>");

            let completedCount = 0;
            let failedCount = 0;

            function processNext(index) {
                if (index >= urls.length) {
                    $('#bulk-action-loader').remove();
                    if (completedCount > 0) {
                        const message = completedCount + " post(s) traité(s) avec succès.";
                        $("<div class='notice notice-success is-dismissible'><p>" + message + "</p></div>").insertAfter(".wp-header-end");
                    }

                    if (failedCount > 0) {
                        const message = failedCount + " échec(s).";
                        $("<div class='notice notice-error is-dismissible'><p>" + message + "</p></div>").insertAfter(".wp-header-end");
                    }

                    return;
                }

                console.debug("Envoi de la requête pour l'URL:", urls[index]);

                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'rewrite_content_bulk_action',
                        urls: [urls[index]],
                        security: nonceVal
                    },
                    success: function (response) {
                        console.debug("Réponse reçue pour l'URL:", urls[index], response);

                        if (response.success) {
                            completedCount++;
                            const post = response.data.post;

                            // Créer une ligne de tableau en utilisant le format WordPress
                            const newRow = `
                                <tr id="post-${post.ID}" class="iedit author-self level-0 post-${post.ID} type-post status-draft format-standard hentry category-uncategorized">
                                    <th scope="row" class="check-column">
                                        <input type="checkbox" name="post[]" value="${post.ID}">
                                    </th>
                                    <td class="title column-title has-row-actions column-primary page-title">
                                        <strong><a class="row-title" href="post.php?post=${post.ID}&action=edit">${post.post_title}</a></strong>
                                        <div class="row-actions">
                                            <span class="edit"><a href="post.php?post=${post.ID}&action=edit" aria-label="Modifier « ${post.post_title} »">Modifier</a> | </span>
                                            <span class="trash"><a href="post.php?post=${post.ID}&action=trash" class="submitdelete" aria-label="Déplacer « ${post.post_title} » vers la Corbeille">Mettre à la corbeille</a></span>
                                        </div>
                                    </td>
                                    <td class="date column-date" data-colname="Date">
                                        Brouillon<br>
                                        <abbr title="${post.post_date_formatted}">${post.post_date_relative}</abbr>
                                    </td>
                                </tr>
                            `;
                            $('#the-list').prepend(newRow);
                        } else {
                            failedCount++;
                        }
                        $('#generation-progress').text(completedCount + " / " + urls.length + " terminés");
                        processNext(index + 1);
                    },
                    error: function (xhr, status, error) {
                        console.error("Erreur reçue pour l'URL:", urls[index], status, error);

                        failedCount++;
                        $('#generation-progress').text(completedCount + " / " + urls.length + " terminés");
                        processNext(index + 1);
                    }
                });
            }

            processNext(0);
        });
    });
</script>
    <?php
}
add_action('admin_footer-edit.php', 'add_bulk_rewrite_actions');