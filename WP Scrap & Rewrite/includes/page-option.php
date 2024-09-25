<?php  
if (!defined('ABSPATH')) {
    exit; 
}

// Ajouter le menu d'options
add_action('admin_menu', 'wp_scrap_and_rewrite_menu');

function wp_scrap_and_rewrite_menu() {
    add_options_page(
        'WP Scrap & Rewrite',
        'WP Scrap & Rewrite',
        'manage_options',
        'wp-scrap-and-rewrite',
        'wp_scrap_and_rewrite_options_page' 
    );
}

function wp_scrap_and_rewrite_options_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Enregistrez l'option si le formulaire a été soumis

    if (isset($_POST['submit'])) {
        check_admin_referer('wp_scrap_and_rewrite_save_options'); 
        $style_of_writing = sanitize_textarea_field($_POST['style_of_writing']);
        $openai_api_key = sanitize_text_field($_POST['openai_api_key']); 
        $openai_model = sanitize_text_field($_POST['openai_model']); 
		$publication =  sanitize_text_field($_POST['publication']); 
        update_option('wp_scrap_and_rewrite_style_of_writing', $style_of_writing);
        update_option('wp_scrap_and_rewrite_openai_api_key', $openai_api_key); 
        update_option('wp_scrap_and_rewrite_openai_model', $openai_model);
		update_option('wp_scrap_and_rewrite_publication', $publication); 
        echo '<div class="updated"><p>Options enregistrées.</p></div>';
    }

    // Récupération des options existantes
    $style_of_writing = get_option('wp_scrap_and_rewrite_style_of_writing', '');
    $openai_api_key = get_option('wp_scrap_and_rewrite_openai_api_key', ''); 
    $openai_model = get_option('wp_scrap_and_rewrite_openai_model', 'gpt-4o-mini'); 
	$publication =  get_option('wp_scrap_and_rewrite_publication', 'publie'); 
	
    // Vérification du plugin 'WP Ideogram API'
    $plugin_file = 'WP-Ideogram-API/wp-ideogram-api.php';
    if (is_plugin_active($plugin_file)) {
        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_file);
        if (version_compare($plugin_data['Version'], '1.4', '==')) {
            echo '<div class="error"><p>Votre plugin <strong>WP Ideogram API</strong> est à la version 1.4. Veuillez le <a href="https://kevin-benabdelhak.fr/plugins/wp-ideogram-api/" target="_blank">mettre à jour</a> à la 1.5 pour corriger le style du bouton et éviter un conflit avec WP Scrap & Rewrite.</p></div>';
        }
    }
    
    ?>
    <div class="wrap">
        <h1>WP Scrap & Rewrite</h1>
        <form method="post" action="">
            <?php wp_nonce_field('wp_scrap_and_rewrite_save_options'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Clé API OpenAI</th>
                    <td><input type="text" name="openai_api_key" value="<?php echo esc_attr($openai_api_key); ?>" class="regular-text" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Style d'écriture</th>
                    <td><textarea name="style_of_writing" rows="10" cols="50" class="large-text"><?php echo esc_textarea($style_of_writing); ?></textarea></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Modèle OpenAI</th>
                    <td>
                        <select name="openai_model">
                            <option value="gpt-4o-mini" <?php selected($openai_model, 'gpt-4o-mini'); ?>>gpt-4o-mini</option>
                            <option value="gpt-4o" <?php selected($openai_model, 'gpt-4o'); ?>>gpt-4o</option>
                            <option value="gpt-3.5-turbo" <?php selected($openai_model, 'gpt-3.5-turbo'); ?>>gpt-3.5-turbo</option>
                            
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Publication auto en bulk</th>
                    <td>
                        <select name="publication">
                            <option value="brouillon" <?php selected($publication, 'brouillon'); ?>>Brouillon</option>
                            <option value="publie" <?php selected($publication, 'publie'); ?>>Publié</option>
                           
                      
                        </select>
                    </td>
                </tr>
            </table>
            <?php submit_button('Enregistrer les options'); ?>
        </form>
    </div>
    <?php
}
?>