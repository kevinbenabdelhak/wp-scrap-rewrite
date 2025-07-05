<?php 

/*
Plugin Name: WP Scrap & Rewrite
Plugin URI: https://kevin-benabdelhak.fr/plugins/wp-scrap-rewrite/
Description: WP Scrap & Rewrite est un plugin qui permet de réécrire le contenu d'une page à partir de l'URL facilement
Version: 1.3
Author: Kevin Benabdelhak
Author URI: https://kevin-benabdelhak.fr/
Contributors: kevinbenabdelhak
*/

if (!defined('ABSPATH')) {
    exit;
}




if ( !class_exists( 'YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory' ) ) {
    require_once __DIR__ . '/plugin-update-checker/plugin-update-checker.php';
}
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$monUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/kevinbenabdelhak/wp-scrap-rewrite/', 
    __FILE__,
    'wp-scrap-rewrite' 
);
$monUpdateChecker->setBranch('main');






require_once plugin_dir_path(__FILE__) . 'includes/page-option.php';
require_once plugin_dir_path(__FILE__) . 'includes/editeur.php';
require_once plugin_dir_path(__FILE__) . 'includes/requete.php';

require_once plugin_dir_path(__FILE__) . 'bulk/ajax-bulk.php'; 
require_once plugin_dir_path(__FILE__) . 'bulk/actions-groupee.php';