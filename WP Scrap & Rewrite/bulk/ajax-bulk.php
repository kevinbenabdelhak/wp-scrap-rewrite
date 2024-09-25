<?php

if (!defined('ABSPATH')) {
    exit;
}


// recuperer option

$publication = get_option('wp_scrap_and_rewrite_publication', 'brouillon');


function rewrite_content_bulk_action() {
    check_ajax_referer('rewrite_content_nonce', 'security');

    $urls = array_map('esc_url_raw', $_POST['urls']);
    $api_key = get_option('wp_scrap_and_rewrite_openai_api_key', '');
    $openai_model = get_option('wp_scrap_and_rewrite_openai_model', 'gpt-4o-mini');
 $style_of_writing = get_option('wp_scrap_and_rewrite_style_of_writing', '');
    if (empty($api_key)) {
        wp_send_json_error(['data' => 'Clé API OpenAI non configurée.']);
    }

    $content = '';
    foreach ($urls as $url) {
        $html_content = wp_strip_all_tags(@file_get_contents($url));
        $content .= 'Voici le contenu de la page à réécrire : '.$html_content . "\n Voici les consignes de rédaction globale :". $style_of_writing;
    }

    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("Contenu récupéré: " . $content);
    }

    $full_prompt = [
        [
            'role' => 'system',
            'content' => trim($content)
        ],
        [
            'role' => 'user',
            'content' => [
                ['type' => 'text', 'text' => 'Paraphrase cette page et donne le titre dans une variable "titre" ainsi que le contenu dans une variable "contenu", le tout au format JSON.(même longueur de contenu avec les mêmes types de balises html que le contenu initial mais en paraphrasé et sans attribut). Je veux intégrer ça dans un article, donc ne commence pas par le header mais par le début du contenu. Je veux le même nombre de mots. Ne met pas le header et footer, juste le contenu du sujet de la page. Ne copie pas de formulaire de contact.']
            ]
        ]
    ];

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'timeout' => 100,
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        ],
        'body' => json_encode([
            'model' => $openai_model,
            'messages' => $full_prompt,
            'temperature' => 1,
            'max_tokens' => 4000,
            'top_p' => 1,
            'frequency_penalty' => 0,
            'presence_penalty' => 0,
            'response_format' => [
                'type' => 'json_object'
            ]
        ]),
    ]);

    if (is_wp_error($response)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Erreur communication API: " . $response->get_error_message());
        }
        wp_send_json_error(['data' => 'Erreur lors de la communication avec l\'API. Détails: ' . $response->get_error_message()]);
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("Réponse API OpenAI: " . print_r($data, true));
    }

    if (isset($data['choices'][0]['message']['content'])) {
        $json_response = json_decode($data['choices'][0]['message']['content'], true);

        if (isset($json_response['titre']) && isset($json_response['contenu'])) {
           
  
    $post_status = ($publication = 'brouillon') ? 'draft' : 'publish';

    $new_post = array(
        'post_title'   => wp_strip_all_tags($json_response['titre']),
        'post_content' => wp_kses_post($json_response['contenu']),
        'post_status'  => $post_status,
        'post_author'  => get_current_user_id(),
    );
      
   $post_id = wp_insert_post($new_post);

            if ($post_id == 0) {
                wp_send_json_error(['data' => 'Erreur lors de la création du post.']);
            } else {
                $post = get_post($post_id);
                wp_send_json_success([
                    'data' => 'Post créé avec succès.', 
                    'post' => [
                        'ID' => $post->ID, 
                        'post_title' => $post->post_title, 
                        'post_date_formatted' => get_the_date('Y-m-d H:i:s', $post),
                        'post_date_relative' => human_time_diff(strtotime($post->post_date), current_time('timestamp')) . ' ago'
                    ]
                ]);
            }
        } else {
            wp_send_json_error(['data' => 'Aucune clé "titre" ou "contenu" trouvée dans la réponse JSON.']);
        }
    } else {
        wp_send_json_error(['data' => 'Aucune réponse valide reçue de l\'API.']);
    }
}

add_action('wp_ajax_rewrite_content_bulk_action', 'rewrite_content_bulk_action');