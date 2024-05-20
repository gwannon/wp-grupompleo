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
define('WP_GRUPOMPLEO_OFFER_PAGE_ID', get_option("_wp_grupompleo_offer_page_id"));
define('WP_GRUPOMPLEO_SEARCH_OFFERS_PAGE_ID', get_option("_wp_grupompleo_search_offers_page_id"));

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
        //print_r ($filter);
        uksort($filter, [new Collator('es_ES'), 'compare']);
        $filters['Ubicacion'] = $filter;
        foreach ($filter as $sublabel => $cities) {
          asort($cities);
          $filters['Ubicacion'][$sublabel] = array_values($cities);
        }
        //print_r ($filter);
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
  return get_the_permalink(WP_GRUPOMPLEO_OFFER_PAGE_ID).$offer->Codigo."-".sanitize_title($offer->Puesto."-".$offer->provincia."-".$offer->Ubicacion)."/";
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
  add_rewrite_rule('^oferta-de-empleo/([^/]*)/?','index.php?page_id='.WP_GRUPOMPLEO_OFFER_PAGE_ID.'&oferta_codigo=$matches[1]','top');
  add_rewrite_tag('%oferta_codigo%','([^&]+)');
}

/* ----------- Filters ------------- */
function wp_grupompleo_oferta_title( $title, $id = null ) {
  if ( is_page(WP_GRUPOMPLEO_OFFER_PAGE_ID) && in_the_loop()) {
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
  if($codigo != '') {
    $extras = json_decode(file_get_contents(WP_GRUPOMPLEO_ENDPOINT_JOB."?cod=" . $codigo));
    //print_r($extras[0]);
    $extras[0]->{"Codigo"} = $codigo;
    $extras[0]->{"Puesto"} = $extras[0]->OFPUESTOVACANTE;
    $extras[0]->{"provincia"} = $extras[0]->OFPROVINCIA;
    $extras[0]->{"Ubicacion"} = $extras[0]->OFUBICACION; ?>
    <div class="ofheader">
      <h1><?php printf(__('%s en %s', "wp-gruprompleo"), $extras[0]->OFPUESTOVACANTE, $extras[0]->OFUBICACION);?></h1>
      <div><a href="<?php echo get_home_url(); ?>">home</a> - <a href="<?php echo get_the_permalink(WP_GRUPOMPLEO_SEARCH_OFFERS_PAGE_ID); ?>">ofertas de empleo</a> - <?php echo $extras[0]->OFPUESTOVACANTE; ?></a></div>
      <?php if($extras[0]->OFSALARIO == 0) {
          $salario = __('según valía', "");
        } elseif($extras[0]->OFSALARIO > 0 && $extras[0]->OFSALARIO < 500) {
          $salario = sprintf(__('%s €/hora', "wp-gruprompleo"), number_format($extras[0]->OFSALARIO,2,',','.'));
        } elseif($extras[0]->OFSALARIO >= 500 && $extras[0]->OFSALARIO < 5000) {
          $salario = sprintf(__('%s €/mes', "wp-gruprompleo"), number_format($extras[0]->OFSALARIO,0,',','.'));
        } elseif($extras[0]->OFSALARIO >= 5000) {
          $salario = sprintf(__('%s €/anuales', "wp-gruprompleo"), number_format($extras[0]->OFSALARIO,0,',','.'));
        }; ?>
      <h2><i class="fa-euro-sign fas"></i> <?php echo $salario ?> <i class="fa-calendar-alt fas"></i> <?php echo date("d/m/Y", strtotime($extras[0]->OFFECHA)); ?></h2>  

      <div class="shareoffer">
        <span><a href="https://www.facebook.com/sharer.php?u=https%3A%2F%2Fwordpress.grupompleo.com%2Fofertas-de-empleo%2Foperario-a-de-fresadora-cnc%2F&amp;t=OPERARIO%2FA%20DE%20FRESADORA%20CNC." target="_blank" rel="noreferrer" title="" aria-label="Facebook" data-placement="top" data-toggle="tooltip" data-title="Facebook" data-="" data-original-title="Facebook"><i class="fusion-social-network-icon fusion-tooltip fusion-facebook awb-icon-facebook" style="color:#ffffff;" aria-hidden="true"></i></a></span>
        <span><a href="https://twitter.com/share?text=OPERARIO%2FA%20DE%20FRESADORA%20CNC.&amp;url=https%3A%2F%2Fwordpress.grupompleo.com%2Fofertas-de-empleo%2Foperario-a-de-fresadora-cnc%2F" target="_blank" rel="noopener noreferrer" title="" aria-label="X" data-placement="top" data-toggle="tooltip" data-title="X" data-="" data-original-title="X"><i class="fusion-social-network-icon fusion-tooltip fusion-twitter awb-icon-twitter" style="color:#ffffff;" aria-hidden="true"></i></a></span>
        <span><a href="https://www.linkedin.com/shareArticle?mini=true&amp;url=https%3A%2F%2Fwordpress.grupompleo.com%2Fofertas-de-empleo%2Foperario-a-de-fresadora-cnc%2F&amp;title=OPERARIO%2FA%20DE%20FRESADORA%20CNC.&amp;summary=precisamos%20un%2Fa%20operario%2Fa%20con%20experiencia%20en%20manejo%20de%20fresadora%20de%20cnc%20para%20importante%20empresa%20en%20cercanias%20de%20basauri." target="_blank" rel="noopener noreferrer" title="" aria-label="LinkedIn" data-placement="top" data-toggle="tooltip" data-title="LinkedIn" data-="" data-original-title="LinkedIn"><i class="fusion-social-network-icon fusion-tooltip fusion-linkedin awb-icon-linkedin" style="color:#ffffff;" aria-hidden="true"></i></a></span>
        <span><a href="https://api.whatsapp.com/send?text=https%3A%2F%2Fwordpress.grupompleo.com%2Fofertas-de-empleo%2Foperario-a-de-fresadora-cnc%2F" target="_blank" rel="noopener noreferrer" title="" aria-label="WhatsApp" data-placement="top" data-toggle="tooltip" data-title="WhatsApp" data-="" data-original-title="WhatsApp"><i class="fusion-social-network-icon fusion-tooltip fusion-whatsapp awb-icon-whatsapp" style="color:#ffffff;" aria-hidden="true"></i></a></span>
        <span><a href="mailto:?subject=OPERARIO%2FA%20DE%20FRESADORA%20CNC.&amp;body=https%3A%2F%2Fwordpress.grupompleo.com%2Fofertas-de-empleo%2Foperario-a-de-fresadora-cnc%2F" target="_self" title="" aria-label="Correo electrónico" data-placement="top" data-toggle="tooltip" data-title="Correo electrónico" data-original-title="Correo electrónico"><i class="fusion-social-network-icon fusion-tooltip fusion-mail awb-icon-mail" style="color:#ffffff;" aria-hidden="true"></i></a></span>
        <?php if($extras[0]->OFVISIBLEENWEB == 1) { ?>
          <a href="<?php echo $extras[0]->OFLINKINSCRIPCION; ?>"><?php _e("inscribirme a esta oferta", 'wp-gruprompleo'); ?></a>
        <?php } ?>
      </div>
    </div>
    <div class="ofcontent">
      <div>
        <div class="oflogo"><?=str_replace("mpleo", "<span>mpleo</span>", mb_strtolower($extras[0]->OFDELEGACIONTEXTO))?></div>
        <ul>
          <?php 
            if(trim($extras[0]->OFTIPOCONTRATO) != '') echo "<li><i class='fa-file fas'></i> ".trim($extras[0]->OFTIPOCONTRATO)."</li>";
            if(trim($extras[0]->OFDESCTIPOCONTRATO) != '') echo "<li><i class='fas fa-file-alt'></i></i> ".trim($extras[0]->OFDESCTIPOCONTRATO)."</li>";
            if(trim($extras[0]->OFVARIABLE) != '') echo "<li><i class='fas fa-star'></i> ".trim($extras[0]->OFVARIABLE)."</li>";
            if(trim($extras[0]->OFJORNADA) != '') echo "<li><i class='fa-clock fas'></i> ".trim($extras[0]->OFJORNADA)."</li>";
            if(trim($extras[0]->OFUBICACION) != '') echo "<li><i class='fa-map-marker-alt fas'></i> ".trim($extras[0]->OFUBICACION)."</li>";
          ?>
        </ul>
        <div class="ofboton">
          <?php if($extras[0]->OFVISIBLEENWEB == 1) { ?>
          <a href="<?php echo $extras[0]->OFLINKINSCRIPCION; ?>"><?php _e("inscribirme a esta oferta", 'wp-gruprompleo'); ?></a>
          <?php } else { ?>
            <p><b><?php _e("Oferta caducada", 'wp-gruprompleo'); ?></b></p>
          <?php } ?>
        </div>
      </div>
      <div>
        <?php if(trim($extras[0]->OFDESCRIPCION) != '')  {
          echo "<h3>".__("descripción del puesto vacante", 'wp-gruprompleo')."</h3>"; 
          echo "<p>".trim($extras[0]->OFDESCRIPCION)."</p>";
        }

        if(trim($extras[0]->OFFUNCIONES) != '')  {
          echo "<h3>".__("funciones y responsabilidades", 'wp-gruprompleo')."</h3>"; 
          echo "<p>".trim($extras[0]->OFFUNCIONES)."</p>";
        }
        /* ----------------------------- */
        echo "<h3>".__("requisitos del puesto", 'wp-gruprompleo')."</h3>"; 

        if(trim($extras[0]->OFFORMACIONBASE) != '')  {
          echo "<div class='boxeddata'><h4>".__("formación base", 'wp-gruprompleo')."</h4>"; 
          echo "<p>".trim($extras[0]->OFFORMACIONBASE)."</p></div>";
        }

        if(trim($extras[0]->OFEXPERIENCIA) != '')  {
          echo "<div class='boxeddata'><h4>".__("experiencia", 'wp-gruprompleo')."</h4>"; 
          echo "<p>".trim($extras[0]->OFEXPERIENCIA)."</p></div>";
        }

        if(trim($extras[0]->OFREQUISITOSDESEADOS) != '')  {
          echo "<div class='boxeddata'><h4>".__("requisitos deseados", 'wp-gruprompleo')."</h4>"; 
          echo "<p>".trim($extras[0]->OFREQUISITOSDESEADOS)."</p></div>";
        }

        if(trim($extras[0]->OFINFORMATICA) != '')  {
          echo "<div class='boxeddata'><h4>".__("informática", 'wp-gruprompleo')."</h4>"; 
          echo "<p>".trim($extras[0]->OFINFORMATICA)."</p></div>";
        }

        if(trim($extras[0]->OFIDIOMAS) != '')  {
          echo "<div class='boxeddata'><h4>".__("idiomas", 'wp-gruprompleo')."</h4>"; 
          echo "<p>".trim($extras[0]->OFIDIOMAS)."</p></div>";
        }

        if(trim($extras[0]->OFCOMPETENCIAS) != '')  {
          echo "<div class='boxeddata'><h4>".__("competencias", 'wp-gruprompleo')."</h4>"; 
          echo "<p>".trim($extras[0]->OFCOMPETENCIAS)."</p></div>";
        } ?>

        <div class="ofinfo"><?php echo apply_filters("the_content", $content); ?></div>
      </div>
    </div>
    <style>
      .ofheader {
        background-color: #333333;
        margin-bottom: 90px;
        position: relative;
        padding: 40px 0px;
      }
      .ofheader:after {
        content: "";
        display: block;
        width: 100%;
        height: 45px;
        background-color: #333333;   
        clip-path: polygon(0% 0%, 100% 100%, 100% 0%);
        position: absolute;
        bottom: -44px;
        left: 0px; 
      }

      .ofheader > * {
        max-width: 1248px; 
        margin: auto;
        padding-left: 20px;
        padding-right: 20px;
        color: #ffffff;
      }

      .ofheader > h1 {
        font-size: 45px;
        color: #ffffff;
        text-transform; lowercase;
        margin-bottom: 30px;
      }

      .ofheader > div {
        margin-bottom: 50px;
      }

      .ofheader > div > a {
        color: #ffffff;
      }

      .ofheader > h2 {
        font-size: 28px;
        color: #ffffff;
        text-transform; lowercase;
      }

      .ofheader > h2 > i:nth-of-type(2) {
        margin-left: 20px;
      }

      .ofcontent {
        display: flex;
        max-width: 1248px;
        margin: auto;
        flex-wrap: wrap;
        align-content: flex-start;
        align-items: flex-start;
        justify-content: center;
      }

      .ofcontent > div:first-of-type {
        background-color: #ededed;
        border-radius: 5px;
        padding: 20px;
        width: calc(100% - 20px);
      }

      .ofcontent > div:first-of-type ul li {
        position: relative;
        list-style-type: none;
        padding-left: 10px;
        padding-bottom: 5px;
        color: #000000;
        font-weight: 16px;
      }

      .ofcontent > div:first-of-type ul li i {
        position: absolute;
        display: block;
        left: -23px;
        top: 5px;
      }

      .ofcontent > div:first-of-type ul li i.fa-star {
        left: -26px;
      }
      

      .ofcontent > div:last-of-type {
        padding: 20px;
        width: 100%;
      }

      @media (min-width: 780px) {
        .ofcontent > div:first-of-type {
          width: 33.33%;
        }

        .ofcontent > div:last-of-type {
          width: 66.66%;
        }
      }


      .ofcontent > div:last-of-type > *:first-child {
        margin-top: 0px;
        padding-top: 0px;
      }

      .ofcontent > div:last-of-type > h3 {
        font-weight: 500;
        font-size: 20px;
        color: #000000;
      }

      .ofcontent > div:last-of-type > p {
        font-weight: 400;
        font-size: 16px;
        color: #000000;
      }

      .ofcontent > div:last-of-type > div.boxeddata {
        background-color: #ededed;
        border-radius: 5px;
        padding: 15px 10px;
        margin-bottom: 10px;
      }

      .ofcontent > div:last-of-type > div.boxeddata > h4 {
        border-bottom: 2px solid #ffffff;
        margin: 0px 0px 20px 0px;
        padding: 0px 0px 5px 0px;
        color: #000000;
        font-size: 18px;
        font-weight: 500;
      }

      .ofcontent > div:last-of-type > div.boxeddata > p {
        color: #000000;
        font-size: 16px;
        font-weight: 400;
      }

      .ofcontent > div:last-of-type > div.ofinfo {
        color: #000000;
        font-size: 16px;
        font-weight: 400;
        margin-top: 40px;
        padding-top: 40px;
        position: relative;
      }

      .ofcontent > div:last-of-type > div.ofinfo:before {
        content: "";
        display: block;
        width: 50%;
        left: 25%;
        height: 3px;
        background-color: #000000;
        top: -2px;
        position: absolute;
      }

      .oflogo {
        color: #716F68;
        font-size: 20px;
        font-weight: 700;
        text-align: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid #ffffff;
      }

      .oflogo span {
        font-weight: 400;
        font-style: italic;

      }

      .ofboton {
        text-align: center;
        margin-top: 30px;
        padding: 30px 10px 10px;
        border-top: 1px solid #ffffff;
      }

      .ofboton a {
        font-weight: 700;
        background-color: #fcc501;
        width: 100%;
        font-size: 19px;
        border-radius: 5px;
        border: 2px solid #fcc501;
        padding: 10px 20px;
        transition: all 0.3s;
        color: #000000;
      }

      .ofboton a:hover {
        background-color: #ffffff;
      }
    </style>
    <?php wp_grupompleo_generate_schema ($extras[0]);
  }
  return ob_get_clean();
}
add_shortcode('oferta', 'wp_grupompleo_oferta_shortcode');

function wp_grupompleo_ofertas_portadas_shortcode($params = array(), $content = null) {
  ob_start(); ?>
  <script src="https://unpkg.com/isotope-layout@3/dist/isotope.pkgd.min.js"></script>
  <div class="jobs-grid">
    <?php $json = json_decode(file_get_contents(WP_GRUPOMPLEO_OFFERS_CACHE_FILE));
      $sedes = [];
      $newjson = [];
      foreach ($json as $key => $offer) { 
        if(!in_array($offer->Sede, $sedes)) { 
          $sedes[] = $offer->Sede;
          $newjson[] = $offer;
          $json[$key]->Sede = "";
        }
      }
      shuffle($newjson);
      foreach ($json as $key => $offer) { 
        if($offer->Sede == 'Irunampleo') { 
          $newjson[] = $offer;
          break;
        }
      }
      foreach ($newjson as $offer) { ?>
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
    var iso = jQuery('.jobs-grid').isotope({
      // options
      itemSelector: '.jobs-item',
      layoutMode: 'fitRows',
    });
    jQuery( document ).ready(function() {
      var minheight = 0;
      jQuery('.jobs-item').not('.jobs-item:hidden').each(function() {
        if(jQuery(this).outerHeight() > minheight) {
          minheight = jQuery(this).outerHeight();
        }
      });
      jQuery('.jobs-item').css("min-height", minheight + "px");
      iso.isotope();
    });
  </script>
  <?php return ob_get_clean();
}
add_shortcode('ofertas-portada', 'wp_grupompleo_ofertas_portadas_shortcode');

function wp_grupompleo_ofertas_con_filtro_shortcode($params = array(), $content = null) {
  ob_start(); ?>
  <script src="https://unpkg.com/isotope-layout@3/dist/isotope.pkgd.min.js"></script>
  <div class="filters-button-group">
    <div class="button-group"><h3>Buscador</h3><input type="text" class="quicksearch" placeholder="Buscar" /></div>
    <?php $json = json_decode(file_get_contents(WP_GRUPOMPLEO_FILTERS_CACHE_FILE));
      foreach ($json as $title => $group) { ?>
      <div class="button-group">
        <h3><?=($title == 'Ubicacion' ? "Localidad" : $title)?></h3>
        <?php if($title != 'Ubicacion') { ?>
          <select name="<?=sanitize_title($title)?>">
            <option value="">Todas</option>
            <?php foreach ($group as $button) { $sanitize_title = sanitize_title($title); ?>
              <option value="<?=$sanitize_title; ?>-<?=sanitize_title($button); ?>"<?=(isset($_GET[$sanitize_title]) && $_GET[$sanitize_title] == sanitize_title($button) ? " selected='selected'" : "")?>><?=$button?></option>
           <?php } ?>
          </select>
        <?php } else { ?>
          <select name="<?=sanitize_title($title)?>" id="select-<?=sanitize_title($title)?>">
            <option value="">Todas</option>
            <?php foreach ($group as $label => $cities) { ?>
              <option value="provincia-<?=sanitize_title($label); ?>"<?=(isset($_GET['provincia']) && $_GET['provincia'] == $label ? " selected='selected'" : "")?>><?=$label?></option>
            <?php } ?>          
          </select>
        </div>
        <div class="button-group" style=" width: 100%; order: 5;">
          <div id="city-group-<?=sanitize_title($title)?>">
            <?php foreach ($group as $label => $cities) { ?>
              <?php foreach ($cities as $city) { ?>
                <label class="city hidden" data-provincia="provincia-<?=sanitize_title($label);?>"><input type="checkbox" name="<?=sanitize_title($title)?>-localidad" value="ubicacion-<?=sanitize_title($city); ?>"<?=(isset($_GET[$sanitize_title]) && $_GET[$sanitize_title] == sanitize_title($button) ? " checked='checked'" : "")?>/> <?=$city?></label>
              <?php } ?>
            <?php } ?>
          </div>
          <script>
            jQuery("#select-<?=sanitize_title($title)?>").on('change', function() {
              jQuery("#city-group-<?=sanitize_title($title)?> input[type='checkbox']").prop( "checked", false );
              var currentState = this.value;
              //console.log("Seleccionamos: "+currentState);
              jQuery("label.city").each(function() {
                
                if(jQuery(this).data("provincia") == currentState) {
                  jQuery(this).removeClass("hidden");
                  jQuery(this).addClass("showed");
                } else {
                  jQuery(this).addClass("hidden");
                  jQuery(this).removeClass("showed");
                }
              });
            });
            jQuery("#city-group-<?=sanitize_title($title)?>").not("#city-group-<?=sanitize_title($title)?> *").click(function() {
              jQuery(this).toggleClass("opened");
            });

          </script>
          <style>
            /*#city-group-<?=sanitize_title($title)?> {
              width: 300px;
              height: 50px;
              border-style: solid;
              border-width: var(--form_border_width-top) var(--form_border_width-right) var(--form_border_width-bottom) var(--form_border_width-left);
              border-color: var(--form_border_color);
              border-radius: var(--form_border_radius);
              background-color: #ffffff;
              position: relative;
              overflow:hidden;
            }

            #city-group-<?=sanitize_title($title)?>.opened {
              overflow: auto;
              height: 300px;
            }

            #city-group-<?=sanitize_title($title)?>:after {
              content:"";
              display: block;
              width: 10px;
              height: 5px;
              position: absolute;
              top: 18px;
              right: 8px;
              background-color: grey;
              clip-path: polygon(40% 100%, 0% 20%, 20% 0%, 50% 60%, 80% 0%, 100% 20%, 60% 100%);
            }

            #city-group-<?=sanitize_title($title)?>:before {
              content: "Selecciona una o más ciudades";
              display: block;
              padding: 10px;
            }*/

            #city-group-<?=sanitize_title($title)?> {
              display: flex;
              flex-wrap: wrap;
              align-content: center;
              flex-direction: row;
              border: 1px solid #cecece;
              background-color: #cecece;
              border-radius: 10px;
              overflow: hidden;
              transition:  0.3s; 
            }

            #city-group-<?=sanitize_title($title)?> label {
              width: 25%;
              display: block;
              background-color: white;
              position: relative;
              z-index: 1;
              padding: 5px;
              font-size: 12px;
            }

            #city-group-<?=sanitize_title($title)?> label.hidden {
              display: none;
            }
          </style>
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
  <div id="noresults">NO HAY RESULTADOS PARA ESTA BÚSQUEDA</div>
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
      <li><a href="<?php echo wp_grupompleo_offer_permalink($offer); ?>"><?php printf(__("%s en %s (%s)", "wp-gruprompleo"), $offer->Puesto, $offer->provincia, $offer->Ubicacion); ?></a></li>
    <?php } ?>
  </ul>
  <?php return ob_get_clean();
}
add_shortcode('ofertas-mapa', 'wp_grupompleo_ofertas_mapa_shortcode');
