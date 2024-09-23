<?php 

if (!defined('ABSPATH')) {
    exit; 
}
add_action('wp_ajax_rewrite_url_content', 'rewrite_url_content');

function rewrite_url_content() {
    check_ajax_referer('rewrite_url_nonce', 'security');

    $url = esc_url_raw($_POST['url']);
    $prompt = sanitize_text_field($_POST['prompt']);
    $replace = isset($_POST['replace']) ? filter_var($_POST['replace'], FILTER_VALIDATE_BOOLEAN) : false;

    // Récupérer le style d'écriture et la clé API depuis les options
    $style_of_writing = get_option('wp_scrap_and_rewrite_style_of_writing', '');
    $api_key = get_option('wp_scrap_and_rewrite_openai_api_key', ''); // Nouvelle ligne pour récupérer la clé API

    if (empty($api_key)) {
        wp_send_json_error(['data' => 'Clé API OpenAI non configurée.']);
    }

    // Si aucune URL n'est fournie, mais que le prompt est utilisé, on envoie uniquement le prompt à OpenAI
    if (empty($url) && empty($prompt)) {
        wp_send_json_error(['data' => 'Veuillez entrer une URL valide ou un prompt.']);
        return;
    }

    if (!empty($url)) {
        $html_content = @file_get_contents($url);
        if ($html_content === FALSE) {
            wp_send_json_error(['data' => 'Erreur lors de la récupération du contenu.']);
            return;
        }
        $html_content = wp_strip_all_tags($html_content);
    } else {
        $html_content = '';
    }

    $full_prompt = [
        [
            'role' => 'system',
            'content' => trim($style_of_writing . ' ' . $html_content)
        ],
        [
            'role' => 'user',
            'content' => [
                ['type' => 'text', 'text' => 'Réécris cet article et donne la réponse dans une variable "reponse" au format HTML balisé standard au format JSON. Commence par la balise <h1>. Voici les balises acceptées : h1, h2, h3, h4, p, span, strong, ul, ol, li, dfn, a, table, tr, th, td. Donne un JSON avec la variable "reponse" et le contenu réécris.']
            ]
        ]
    ];

    if ($prompt) {
        $full_prompt[] = [
            'role' => 'user',
            'content' => [
                ['type' => 'text', 'text' => $prompt]
            ]
        ];
    }

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'timeout' => 100,
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        ],
        'body' => json_encode([
            'model' => 'gpt-4o-mini',
            'messages' => $full_prompt,
            'temperature' => 1,
            'max_tokens' => 10000,
            'top_p' => 1,
            'frequency_penalty' => 0,
            'presence_penalty' => 0,
            'response_format' => [
                'type' => 'json_object'
            ]
        ]),
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error(['data' => 'Erreur lors de la communication avec l\'API. Détails: ' . $response->get_error_message()]);
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (isset($data['choices'][0]['message']['content'])) {
        $json_response = json_decode($data['choices'][0]['message']['content'], true);

        if (isset($json_response['reponse'])) {
            $rewritten_content = str_replace(["&nbsp;", "\n"], "", trim($json_response['reponse']));
            wp_send_json_success(['rewritten_content' => $rewritten_content]);
        } else {
            wp_send_json_error(['data' => 'Aucune clé "reponse" trouvée dans la réponse JSON.']);
        }
    } else {
        wp_send_json_error(['data' => 'Aucune réponse valide reçue de l\'API.']);
    }
}