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

/**
 * TODO
 * - Preguntar sistema de filtros OR o AND
 * - Metas ofertas
 * - Integrar diseño
 */

define('WP_GRUPOMPLEO_ENDPOINT_JOBS', get_option("_wp_grupompleo_endpoint_jobs"));
define('WP_GRUPOMPLEO_ENDPOINT_JOB', get_option("_wp_grupompleo_endpoint_job"));
define('WP_GRUPOMPLEO_OFFERS_CACHE_FILE', plugin_dir_path(__FILE__).'cache/joboffers.json');
define('WP_GRUPOMPLEO_FILTERS_CACHE_FILE', plugin_dir_path(__FILE__).'cache/filters.json');
define('WP_GRUPOMPLEO_SITEMAP_CACHE_FILE', plugin_dir_path(__FILE__).'cache/job-offers.xml');
define('WP_GRUPOMPLEO_ENDPOINT_OFFER_PAGE_ID', get_option("_wp_grupompleo_offer_page_id"));


//Cargamos el multi-idioma
function wp_grupompleo_plugins_loaded() {
  load_plugin_textdomain('wp-gruprompleo', false, dirname( plugin_basename( __FILE__ ) ) . '/langs/' );
}
add_action('plugins_loaded', 'wp_grupompleo_plugins_loaded', 0 );

/* ----------- Includes ------------ */
include_once(plugin_dir_path(__FILE__).'admin.php');
include_once(plugin_dir_path(__FILE__).'seo.php');

/* ----------- Cron job ------------ */
//wp-admin/admin-ajax.php?action=grupompleo_ofertas
add_action( 'wp_ajax_grupompleo_ofertas', 'wp_grupompleo_ofertas_cache' );
add_action( 'wp_ajax_nopriv_grupompleo_ofertas', 'wp_grupompleo_ofertas_cache' );
function wp_grupompleo_ofertas_cache() {
  if(!file_exists(WP_GRUPOMPLEO_OFFERS_CACHE_FILE) || (time() - filemtime(WP_GRUPOMPLEO_OFFERS_CACHE_FILE)) > /*(60*4)*/ 5) {
    $json = file_get_contents(WP_GRUPOMPLEO_ENDPOINT_JOBS);
    file_put_contents(WP_GRUPOMPLEO_OFFERS_CACHE_FILE, $json);
    $filters = [
      'Sede' => [],
      'Ubicacion' => [],
      'Tipo' => [],
    ];
    foreach (json_decode($json, true) as $job) {
      foreach($filters as $label => $filter) {
        if($label == 'Ubicacion') {
          if(!isset($filters['Ubicacion'][$job['provincia']])) $filters['Ubicacion'][$job['provincia']] = [];
          if (!in_array($job['Ubicacion'], $filters['Ubicacion'][$job['provincia']])) $filters['Ubicacion'][$job['provincia']][] = $job['Ubicacion'];
        }
        else if (!in_array($job[$label], $filters[$label])) $filters[$label][] = $job[$label];
      }
    }
    
    foreach($filters as $label => $filter) {
      if($label == 'Ubicacion') {
        uksort($filter, [new Collator('es_ES'), 'compare']);
        foreach ($filter as $sublabel => $cities) {
          asort($cities);
          $filters['Ubicacion'][$sublabel] = array_values($cities);
        }
      } else {
        asort($filter);
        $filters[$label] = array_values($filter);
      }
    }
    file_put_contents(WP_GRUPOMPLEO_FILTERS_CACHE_FILE, json_encode($filters));
  } else {
    $json = file_get_contents(WP_GRUPOMPLEO_OFFERS_CACHE_FILE);
  }
  wp_grupompleo_generate_sitemap($json);
  echo $json;
}

function wp_grupompleo_offer_permalink($offer) {
  if(is_array($offer)) $offer = json_decode(json_encode($offer), true);
  return get_the_permalink(WP_GRUPOMPLEO_ENDPOINT_OFFER_PAGE_ID).$offer->Codigo."-".sanitize_title($offer->Puesto."-".$offer->provincia."-".$offer->Ubicacion)."/";
}

function wp_grupompleo_generate_sitemap($json) {
  $sitemap = '<?xml version="1.0" encoding="UTF-8"?><?xml-stylesheet type="text/xsl" href="'.get_home_url().'/wp-content/plugins/wordpress-seo/css/main-sitemap.xsl"?>
  <urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd http://www.google.com/schemas/sitemap-image/1.1 http://www.google.com/schemas/sitemap-image/1.1/sitemap-image.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
  foreach (json_decode($json) as $offer) {
    $sitemap .= "\t\t".'<url><loc>'.wp_grupompleo_offer_permalink($offer).'</loc></url>'."\n";
  }  
  $sitemap .='</urlset>';
  file_put_contents(WP_GRUPOMPLEO_SITEMAP_CACHE_FILE, $sitemap);
}


/* ----------- Rewrite Rules ------- */
add_action( 'init', 'wp_grupompleo_rewrite_rules' );
function wp_grupompleo_rewrite_rules(){
  add_rewrite_rule('^oferta-de-empleo/([^/]*)/?','index.php?page_id='.WP_GRUPOMPLEO_ENDPOINT_OFFER_PAGE_ID.'&oferta_codigo=$matches[1]','top');
  add_rewrite_tag('%oferta_codigo%','([^&]+)');
}

/* ----------- Filters ------------- */
function wp_grupompleo_oferta_title( $title, $id = null ) {
  if ( is_page(WP_GRUPOMPLEO_ENDPOINT_OFFER_PAGE_ID) && in_the_loop()) {
    $codigo = explode("-", get_query_var('oferta_codigo'))[0];
    $json = json_decode(file_get_contents(WP_GRUPOMPLEO_OFFERS_CACHE_FILE));
    foreach ($json as $offer) { 
      if($offer->Codigo == $codigo) {
        return 'Oferta: '.$offer->Puesto;
      }
    }
  }
  return $title;
}
add_filter( 'the_title', 'wp_grupompleo_oferta_title', 10, 2 );

/* ----------- Códigos cortos ------ */
function wp_grupompleo_oferta_shortcode($params = array(), $content = null) {
  ob_start(); 
  $codigo = explode("-", get_query_var('oferta_codigo'))[0];
  $extras = json_decode(file_get_contents(WP_GRUPOMPLEO_ENDPOINT_JOB."?cod=" . $codigo));
  $extras[0]->{"Codigo"} = $codigo;
  $extras[0]->{"Puesto"} = $extras[0]->OFPUESTOVACANTE;
  $extras[0]->{"provincia"} = $extras[0]->OFPROVINCIA;
  $extras[0]->{"Ubicacion"} = $extras[0]->OFUBICACION;
  print_r($extras[0]);

  echo "<h1>".$extras[0]->OFPUESTOVACANTE." en ".$extras[0]->OFUBICACION."</h1>";
  foreach($extras[0] as $label => $data) {
    echo "<p><b>".$label."</b>: ".$data."</p>";
  }
  wp_grupompleo_generate_schema ($extras[0]);
  return ob_get_clean();
}
add_shortcode('oferta', 'wp_grupompleo_oferta_shortcode');

function wp_grupompleo_ofertas_portadas_shortcode($params = array(), $content = null) {
  ob_start(); ?>
  <script src="https://unpkg.com/isotope-layout@3/dist/isotope.pkgd.min.js"></script>
  <div class="jobs-grid">
    <?php $json = json_decode(file_get_contents(WP_GRUPOMPLEO_OFFERS_CACHE_FILE));
      $json = array_slice($json, 0, 10); 
    foreach ($json as $offer) { ?>
      <div class="jobs-item delegacion-<?=sanitize_title($offer->Delegacion)?> tipo-<?=sanitize_title($offer->Tipo)?> provincia-<?=sanitize_title($offer->provincia); ?> ubicacion-<?=sanitize_title($offer->Ubicacion); ?>" data-category="<?=sanitize_title($offer->Tipo)?>" data-search="<?php echo str_replace("-", " ", sanitize_title($offer->Puesto." ".$offer->provincia." ".$offer->Ubicacion." ".$offer->Tipo." ".$offer->Delegacion));?>">
        <p><?=str_replace("mpleo", "<span>mpleo</span>", mb_strtolower($offer->Delegacion))?></p>
        <p class="place"><?=$offer->provincia?><br/><?=ucfirst(mb_strtolower($offer->Ubicacion))?></p>
        <p class="name"><?=mb_strtolower($offer->Puesto)?></p>
        <a href="<?php echo wp_grupompleo_offer_permalink($offer); ?>">Ver oferta</a>
      </div>
    <?php } ?>
  </div>
  <style>
    <?php echo file_get_contents(plugin_dir_path(__FILE__).'css/style.css'); ?>
  </style>
  <script>
    <?php echo file_get_contents(plugin_dir_path(__FILE__).'js/isotope.js'); ?>
  </script>
  <?php return ob_get_clean();
}
add_shortcode('ofertas-portada', 'wp_grupompleo_ofertas_portadas_shortcode');

function wp_grupompleo_ofertas_con_filtro_shortcode($params = array(), $content = null) {
  ob_start(); ?>
  <script src="https://unpkg.com/isotope-layout@3/dist/isotope.pkgd.min.js"></script>
  <p><input type="text" class="quicksearch" placeholder="Buscar" /></p>
  <div class="filters-button-group">
    <?php $json = json_decode(file_get_contents(WP_GRUPOMPLEO_FILTERS_CACHE_FILE));
      foreach ($json as $title => $group) { ?>
      <h3><?=$title?></h3>
      <div class="button-group">
        <?php if($title != 'Ubicacion') { ?>
          <select name="<?=sanitize_title($title)?>">
            <option value="">Todas</option>
            <?php foreach ($group as $button) { $sanitize_title = sanitize_title($title); ?>
            <option value="<?=$sanitize_title; ?>-<?=sanitize_title($button); ?>"<?=(isset($_GET[$sanitize_title]) && $_GET[$sanitize_title] == sanitize_title($button) ? " selected='selected'" : "")?>><?=$button?></option>
            <?php /* <label><input type="radio" name="<?=sanitize_title($title)?>" value="" checked="checked" /> Todas</label>
            <?php foreach ($group as $button) { $sanitize_title = sanitize_title($title); ?>
            <label><input type="radio" name="<?=$sanitize_title?>" value="<?=$sanitize_title; ?>-<?=sanitize_title($button); ?>"<?=(isset($_GET[$sanitize_title]) && $_GET[$sanitize_title] == sanitize_title($button) ? " checked='checked'" : "")?>/> <?=$button?></label> */ ?>
          <?php } ?>
          </select>
        <?php  } else { ?>
          <select name="<?=sanitize_title($title)?>">
            <option value="">Todas</option>
            <?php foreach ($group as $label => $cities) { ?>
              <option value="provincia-<?=sanitize_title($label); ?>"<?=(isset($_GET['provincia']) && $_GET['provincia'] == $label ? " selected='selected'" : "")?>><?=$label?></option>
              <?php foreach ($cities as $city) { ?>
                <option value="ubicacion-<?=sanitize_title($city); ?>"<?=(isset($_GET['ubicacion']) && $_GET['ubicacion'] == strtoupper($city) ? " selected='selected'" : "")?>> ∟ <?=$city?></option>
            <?php } } ?>
          </select>
        <?php } ?>
      </div>
    <?php } ?>
  </div>
  <div class="jobs-grid">
    <?php $json = json_decode(file_get_contents(WP_GRUPOMPLEO_OFFERS_CACHE_FILE));foreach ($json as $offer) { ?>
      <div class="jobs-item sede-<?=sanitize_title($offer->Sede)?> tipo-<?=sanitize_title($offer->Tipo)?> provincia-<?=sanitize_title($offer->provincia); ?> ubicacion-<?=sanitize_title($offer->Ubicacion); ?>" data-category="<?=sanitize_title($offer->Tipo)?>" data-search="<?php echo str_replace("-", " ", sanitize_title($offer->Puesto." ".$offer->provincia." ".$offer->Ubicacion." ".$offer->Tipo." ".$offer->Sede));?>">
        <p><?=str_replace("mpleo", "<span>mpleo</span>", mb_strtolower($offer->Delegacion))?></p>
        <p class="place"><?=$offer->provincia?><br/><?=ucfirst(mb_strtolower($offer->Ubicacion))?></p>
        <p class="name"><?=mb_strtolower($offer->Puesto)?></p>
        <a href="<?php echo wp_grupompleo_offer_permalink($offer); ?>">Ver oferta</a>
      </div>
    <?php } ?>
  </div>
  <style>
    <?php echo file_get_contents(plugin_dir_path(__FILE__).'css/style.css'); ?>
  </style>
  <script>
    <?php echo file_get_contents(plugin_dir_path(__FILE__).'js/isotope.js'); ?>
  </script>
  <?php return ob_get_clean();
}
add_shortcode('ofertas-filtradas', 'wp_grupompleo_ofertas_con_filtro_shortcode');


function wp_grupompleo_ofertas_mapa_shortcode($params = array(), $content = null) {
  ob_start(); ?>
  <ul>
    <?php $json = json_decode(file_get_contents(WP_GRUPOMPLEO_OFFERS_CACHE_FILE));foreach ($json as $offer) { ?>
      <li><a href="<?php echo get_the_permalink(WP_GRUPOMPLEO_ENDPOINT_OFFER_PAGE_ID).$offer->Codigo."-".sanitize_title($offer->Puesto."-".$offer->provincia."-".$offer->Ubicacion)."/"; ?>"><?php printf(__("%s en %s (%s)", "wp-gruprompleo"), $offer->Puesto, $offer->provincia, $offer->Ubicacion); ?></a></li>
    <?php } ?>
  </ul>
  <?php return ob_get_clean();
}
add_shortcode('ofertas-mapa', 'wp_grupompleo_ofertas_mapa_shortcode');
