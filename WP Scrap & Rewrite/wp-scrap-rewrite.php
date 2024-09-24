<?php 

/*
Plugin Name: WP Scrap & Rewrite
Plugin URI: https://kevin-benabdelhak.fr/plugins/wp-scrap-rewrite/
Description: WP Scrap & Rewrite est un plugin qui permet de réécrire le contenu d'une page à partir de l'URL facilement
Version: 1.1
Author: Kevin Benabdelhak
Author URI: https://kevin-benabdelhak.fr/
Contributors: kevinbenabdelhak
*/

if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'includes/page-option.php';
require_once plugin_dir_path(__FILE__) . 'includes/editeur.php';
require_once plugin_dir_path(__FILE__) . 'includes/requete.php';
