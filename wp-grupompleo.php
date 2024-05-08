<?php

/**
 * Plugin Name: WP Grupompleo
 * Plugin URI:  https://github.com/gwannon/wp-grupompleo
 * Description: Plugin de WordPress para conectar WP con intranet de Grupompleo
 * Version:     1.0
 * Author:      Gwannon
 * Author URI:  https://github.com/gwannon/
 * License:     GNU General Public License v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-gruprompleo
 *
 * PHP 8.1
 * WordPress 6.5.3
 */

define('WP_GRUPOMPLEO_ENDPOINT_JOBS', get_option("_wp_grupompleo_endpoint_jobs"));
define('WP_GRUPOMPLEO_ENDPOINT_SEARCH_JOBS', get_option("_wp_grupompleo_endpoint_search_jobs"));
define('WP_GRUPOMPLEO_ENDPOINT_FILTERS', get_option("_wp_grupompleo_endpoint_filters"));

 //Cargamos el multi-idioma
function wp_grupompleo_plugins_loaded() {
  load_plugin_textdomain('wp-gruprompleo', false, dirname( plugin_basename( __FILE__ ) ) . '/langs/' );
}
add_action('plugins_loaded', 'wp_grupompleo_plugins_loaded', 0 );

/* ----------- Includes ------------ */
include_once(plugin_dir_path(__FILE__).'admin.php');


/* ----------- Códigos cortos ------ */
function wp_grupompleo_ofertas_portadas_shortcode($params = array(), $content = null) {
  ob_start(); 


  return ob_get_clean();
}
add_shortcode('ofertas-portadas', 'wp_grupompleo_ofertas_portadas_shortcode');

function wp_grupompleo_ofertas_con_filtro_shortcode($params = array(), $content = null) {
  ob_start(); 


  return ob_get_clean();
}
add_shortcode('ofertas-filtradas', 'wp_grupompleo_ofertas_con_filtro_shortcode');