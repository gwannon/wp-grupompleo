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
include_once(plugin_dir_path(__FILE__).'mapa.php');

/* ----------- Cron job ------------ */
//wp-admin/admin-ajax.php?action=grupompleo_ofertas
add_action( 'wp_ajax_grupompleo_ofertas', 'wp_grupompleo_ofertas_cache' );
add_action( 'wp_ajax_nopriv_grupompleo_ofertas', 'wp_grupompleo_ofertas_cache' );
function wp_grupompleo_ofertas_cache() {
  if(!file_exists(WP_GRUPOMPLEO_OFFERS_CACHE_FILE) || (time() - filemtime(WP_GRUPOMPLEO_OFFERS_CACHE_FILE)) > /*(60*4)*/ 5) {
    $json = file_get_contents(WP_GRUPOMPLEO_ENDPOINT_JOBS);
    file_put_contents(WP_GRUPOMPLEO_OFFERS_CACHE_FILE, $json);
    $filters = [
      //'Sede' => [],
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
  return get_the_permalink(WP_GRUPOMPLEO_OFFER_PAGE_ID).sanitize_title($offer->Puesto."-".$offer->provincia."-".$offer->Ubicacion)."-".$offer->Codigo."/";
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
    $codigo = end(explode("-", get_query_var('oferta_codigo')));
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
  $codigo = end(explode("-", get_query_var('oferta_codigo')));
  if($codigo != '') {
    $extras = json_decode(file_get_contents(WP_GRUPOMPLEO_ENDPOINT_JOB."?cod=" . $codigo));
    //print_r($extras[0]);
    $extras[0]->{"Codigo"} = $codigo;
    $extras[0]->{"Puesto"} = $extras[0]->OFPUESTOVACANTE;
    $extras[0]->{"provincia"} = $extras[0]->OFPROVINCIA;
    $extras[0]->{"Ubicacion"} = $extras[0]->OFUBICACION; ?>
    <div class="ofheader">
      <div>
        <div><a href="<?php echo get_home_url(); ?>">home</a> - <a href="<?php echo get_the_permalink(WP_GRUPOMPLEO_SEARCH_OFFERS_PAGE_ID); ?>">ofertas de empleo</a> - <?php echo $extras[0]->OFPUESTOVACANTE; ?></a></div>
        <h1><?php printf(__('%s en %s', "wp-gruprompleo"), $extras[0]->OFPUESTOVACANTE, $extras[0]->OFUBICACION);?></h1>
        <?php
          if (!is_numeric($extras[0]->OFSALARIO)) $extras[0]->OFSALARIO = intval($extras[0]->OFSALARIO);
          if($extras[0]->OFSALARIO == 0) {
            $salario = __('según valía', "wp-grupompleo");
          } elseif($extras[0]->OFSALARIO > 0 && $extras[0]->OFSALARIO < 500) {
            $salario = sprintf(__('%s €/hora', "wp-gruprompleo"), number_format($extras[0]->OFSALARIO,2,',','.'));
          } elseif($extras[0]->OFSALARIO >= 500 && $extras[0]->OFSALARIO < 5000) {
            $salario = sprintf(__('%s €/mes', "wp-gruprompleo"), number_format($extras[0]->OFSALARIO,0,',','.'));
          } elseif($extras[0]->OFSALARIO >= 5000) {
            $salario = sprintf(__('%s €/anuales', "wp-gruprompleo"), number_format($extras[0]->OFSALARIO,0,',','.'));
          };
        ?>
        <h2><i class="fa-euro-sign fas"></i> <?php echo $salario ?> <i class="fa-calendar-alt fas"></i> <?php echo date("d/m/Y", strtotime($extras[0]->OFFECHA)); ?></h2>  

        <div class="shareoffer">
          <span><a href="https://www.facebook.com/sharer.php?u=<?php echo urlencode(wp_grupompleo_offer_permalink($extras[0])); ?>&amp;quote=<?php echo urlencode($extras[0]->OFPUESTOVACANTE); ?>" target="_blank" rel="noreferrer" title="" aria-label="Facebook" data-placement="top" data-toggle="tooltip" data-title="Facebook" data-="" data-original-title="Facebook"><i class="fusion-social-network-icon fusion-tooltip fusion-facebook awb-icon-facebook" style="color:#ffffff;" aria-hidden="true"></i></a></span>
          <span><a href="https://twitter.com/share?text=<?php echo urlencode($extras[0]->OFPUESTOVACANTE); ?>&amp;url=<?php echo urlencode(wp_grupompleo_offer_permalink($extras[0])); ?>" target="_blank" rel="noopener noreferrer" title="" aria-label="X" data-placement="top" data-toggle="tooltip" data-title="X" data-="" data-original-title="X"><i class="fusion-social-network-icon fusion-tooltip fusion-twitter awb-icon-twitter" style="color:#ffffff;" aria-hidden="true"></i></a></span>
          <span><a href="https://www.linkedin.com/shareArticle?url=<?php echo urlencode(wp_grupompleo_offer_permalink($extras[0])); ?>&amp;title=<?php echo urlencode($extras[0]->OFPUESTOVACANTE); ?>&amp;summary=<?php echo urlencode(trim($extras[0]->OFDESCRIPCION)); ?>" target="_blank" rel="noopener noreferrer" title="" aria-label="LinkedIn" data-placement="top" data-toggle="tooltip" data-title="LinkedIn" data-="" data-original-title="LinkedIn"><i class="fusion-social-network-icon fusion-tooltip fusion-linkedin awb-icon-linkedin" style="color:#ffffff;" aria-hidden="true"></i></a></span>
          <span><a href="https://api.whatsapp.com/send?text=<?php echo urlencode(wp_grupompleo_offer_permalink($extras[0])); ?>" target="_blank" rel="noopener noreferrer" title="" aria-label="WhatsApp" data-placement="top" data-toggle="tooltip" data-title="WhatsApp" data-="" data-original-title="WhatsApp"><i class="fusion-social-network-icon fusion-tooltip fusion-whatsapp awb-icon-whatsapp" style="color:#ffffff;" aria-hidden="true"></i></a></span>
          <span><a href="mailto:?subject=<?php echo urlencode($extras[0]->OFPUESTOVACANTE); ?>&amp;body=<?php echo urlencode(wp_grupompleo_offer_permalink($extras[0])); ?>" target="_self" title="" aria-label="Correo electrónico" data-placement="top" data-toggle="tooltip" data-title="Correo electrónico" data-original-title="Correo electrónico"><i class="fusion-social-network-icon fusion-tooltip fusion-mail awb-icon-mail" style="color:#ffffff;" aria-hidden="true"></i></a></span>
          <?php if($extras[0]->OFVISIBLEENWEB == 1) { ?>
            <br/><a href="<?php echo $extras[0]->OFLINKINSCRIPCION; ?>"><?php _e("inscribirme a esta oferta", 'wp-gruprompleo'); ?></a>
          <?php } ?>
        </div>
      </div>
    </div>
    <div class="ofcontent">
      <div id="scrollable">
        <div class="oflogo"><?=str_replace("mpleo", "<span>mpleo</span>", mb_strtolower($extras[0]->OFDELEGACIONTEXTO))?></div>
        <ul>
          <?php 
            if(trim($extras[0]->OFTIPOCONTRATO) != '') echo "<li><i class='fa-file fas'></i> ".trim($extras[0]->OFTIPOCONTRATO)."</li>";
            if(trim($extras[0]->OFDESCTIPOCONTRATO) != '') echo "<li><i class='fas fa-file-alt'></i></i> ".trim($extras[0]->OFDESCTIPOCONTRATO)."</li>";
            if(trim($extras[0]->OFVARIABLE) != '') echo "<li><i class='fas fa-star'></i> ".trim($extras[0]->OFVARIABLE)."</li>";
            //if(trim($extras[0]->OFJORNADA) != '') echo "<li><i class='fa-clock fas'></i> ".trim($extras[0]->OFJORNADA)."</li>";
            if(trim($extras[0]->OFHORARIO) != '')  echo "<li><i class='fa-clock fas'></i> ".trim($extras[0]->OFHORARIO)."</li>";
            if(trim($extras[0]->OFUBICACION) != '') echo "<li><i class='fa-map-marker-alt fas'></i> ".trim($extras[0]->OFUBICACION)."</li>";
          ?>
        </ul>
        <div class="ofboton">
          <?php if($extras[0]->OFVISIBLEENWEB == 1) { ?>
          <a href="<?php echo $extras[0]->OFLINKINSCRIPCION; ?>"><?php _e("inscribirme a esta oferta", 'wp-gruprompleo'); ?></a>
          <?php } else { ?>
            <p><b><?php _e("Esta oferta ya ha sido cubierta, pero muchos/as han encontrado su puesto ideal explorando nuestras vacantes, ¡encuentra el tuyo!", 'wp-gruprompleo'); ?></b></p>
            <a href="<?php echo get_the_permalink(WP_GRUPOMPLEO_SEARCH_OFFERS_PAGE_ID); ?>"><?php _e("buscar empleo", 'wp-gruprompleo'); ?></a>
          <?php } ?>
        </div>
      </div>
      <div id="notscrollable">
        <?php if(trim($extras[0]->OFDESCRIPCION) != '')  {
          echo "<h3>".__("descripción del puesto vacante", 'wp-gruprompleo')."</h3>"; 
          echo "<p>".str_replace("- ", "&#10003; ", trim($extras[0]->OFDESCRIPCION))."</p>";
        }

        if(trim($extras[0]->OFFUNCIONES) != '')  {
          echo "<h3>".__("funciones y responsabilidades", 'wp-gruprompleo')."</h3>"; 
          echo "<p>".str_replace("- ", "&#10003; ", trim($extras[0]->OFFUNCIONES))."</p>";
        }
        /* ----------------------------- */
        echo "<h3>".__("requisitos del puesto de trabajo", 'wp-gruprompleo')."</h3>"; 

        if(trim($extras[0]->OFFORMACIONBASE) != '')  {
          echo "<div class='boxeddata'><h4>".__("formación base", 'wp-gruprompleo')."</h4>"; 
          echo "<p>".str_replace("- ", "&#10003; ", trim($extras[0]->OFFORMACIONBASE))."</p></div>";
        }

        if(trim($extras[0]->OFEXPERIENCIA) != '')  {
          echo "<div class='boxeddata'><h4>".__("experiencia laboral", 'wp-gruprompleo')."</h4>"; 
          echo "<p>".str_replace("- ", "&#10003; ", trim($extras[0]->OFEXPERIENCIA))."</p></div>";
        }

        if(trim($extras[0]->OFREQUISITOSDESEADOS) != '')  {
          echo "<div class='boxeddata'><h4>".__("requisitos deseados", 'wp-gruprompleo')."</h4>"; 
          echo "<p>".str_replace("- ", "&#10003; ", trim($extras[0]->OFREQUISITOSDESEADOS))."</p></div>";
        }

        if(trim($extras[0]->OFINFORMATICA) != '')  {
          echo "<div class='boxeddata'><h4>".__("informática", 'wp-gruprompleo')."</h4>"; 
          echo "<p>".str_replace("- ", "&#10003; ", trim($extras[0]->OFINFORMATICA))."</p></div>";
        }

        if(trim($extras[0]->OFIDIOMAS) != '')  {
          echo "<div class='boxeddata'><h4>".__("idiomas", 'wp-gruprompleo')."</h4>"; 
          echo "<p>".str_replace("- ", "&#10003; ", trim($extras[0]->OFIDIOMAS))."</p></div>";
        }

        if(trim($extras[0]->OFCOMPETENCIAS) != '')  {
          echo "<div class='boxeddata'><h4>".__("competencias", 'wp-gruprompleo')."</h4>"; 
          echo "<p>".str_replace("- ", "&#10003; ", trim($extras[0]->OFCOMPETENCIAS))."</p></div>";
        } ?>
          <?php if($extras[0]->OFVISIBLEENWEB == 1) { ?>
            <div class="ofbotonresp">
              <a href="<?php echo $extras[0]->OFLINKINSCRIPCION; ?>"><?php _e("inscribirme a esta oferta", 'wp-gruprompleo'); ?></a>
            </div>
          <?php } ?>

        <div class="ofinfo"><?php echo apply_filters("the_content", $content); ?></div>
      </div>
    </div>
    <style>
      <?php echo file_get_contents(plugin_dir_path(__FILE__).'css/style.css'); ?>
    </style>
    <script>
        jQuery(window).resize(function() {
          // This will fire each time the window is resized:
          if(jQuery(window).width() >= 1024) {
            jQuery(document).on( "scroll", function() {
              var currentpos = document.documentElement.scrollTop - jQuery('#notscrollable').position().top;
              //console.log(currentpos);
              //console.log(jQuery('#notscrollable').position().top + jQuery('#notscrollable').outerHeight());
              if (currentpos > 0 && currentpos < (jQuery('#notscrollable').outerHeight() - jQuery('#scrollable').outerHeight() )) {
                jQuery('#scrollable').css("margin-top", currentpos+"px");
              } else if (currentpos <= 0 ) {
                jQuery('#scrollable').css("margin-top", "0px");
              } else if (currentpos >= (jQuery('#notscrollable').outerHeight() - jQuery('#scrollable').outerHeight() )) {
                jQuery('#scrollable').css("margin-top", (jQuery('#notscrollable').outerHeight() - jQuery('#scrollable').outerHeight())+"px");
              }
            });
          } else {
            jQuery('#scrollable').css("margin-top", "0px");
          }
        }).resize(); // This will simulate a resize to trigger the initial run.
    </script>
    <?php wp_grupompleo_generate_schema ($extras[0]);
  }
  return ob_get_clean();
}
add_shortcode('oferta', 'wp_grupompleo_oferta_shortcode');

function wp_grupompleo_ofertas_portadas_shortcode($params = array(), $content = null) {
  ob_start(); ?>
  <?php /* <script src="https://unpkg.com/isotope-layout@3/dist/isotope.pkgd.min.js"></script> */ ?>
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
          <a href="<?php echo wp_grupompleo_offer_permalink($offer); ?>"><?php _e('Ver oferta', 'wp-gruprompleo'); ?></a>
        </div>
    <?php } ?>
  </div>
  <style>
    <?php echo file_get_contents(plugin_dir_path(__FILE__).'css/style.css'); ?>
      @media (min-width: 1024px) {
        .jobs-grid {
          display: flex;
          flex-wrap: wrap;
          justify-content: center;
        }
      }
  </style>
  <?php return ob_get_clean();
}
add_shortcode('ofertas-portada', 'wp_grupompleo_ofertas_portadas_shortcode');

function wp_grupompleo_ofertas_portadas_slider_shortcode($params = array(), $content = null) {
  ob_start(); ?>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
  <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
  <div class="swiper mySwiper swiper-h">
    <div class="jobs-grid swiper-wrapper">
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
          <div class="swiper-slide jobs-item">
            <p><?=str_replace("mpleo", "<span>mpleo</span>", mb_strtolower($offer->Delegacion))?></p>
            <p class="place"><?=$offer->provincia?><br/><?=ucfirst(mb_strtolower($offer->Ubicacion))?></p>
            <p class="name"><?=mb_strtolower($offer->Puesto)?></p>
            <a href="<?php echo wp_grupompleo_offer_permalink($offer); ?>"><?php _e('Ver oferta', 'wp-gruprompleo'); ?></a>
          </div>
      <?php } ?>
    </div>
    <div class="swiper-button-next"></div>
    <div class="swiper-button-prev"></div>
  </div>
  <style>
    <?php echo file_get_contents(plugin_dir_path(__FILE__).'css/style.css'); ?>
    .swiper {
      width: 100%;
      border-radius: 10px;
    }

    .swiper-slide.jobs-item {
      margin: 0px;
     }

    .swiper.mySwiper .swiper-button-next,
    .swiper.mySwiper .swiper-button-prev {
      color: #fcc501;
    }
  </style>
  <script>
    var swiper = new Swiper(".mySwiper", {
      slidesPerView: 1,
      spaceBetween: 10,
      breakpoints: {
        640: {
          slidesPerView: 2,
          spaceBetween: 10,
        },
        768: {
          slidesPerView: 3,
          spaceBetween: 10,
        }
      },
      pagination: {
        el: ".swiper-pagination",
        type: "fraction",
      },
      navigation: {
        nextEl: ".swiper-button-next",
        prevEl: ".swiper-button-prev",
      },
    });
  </script>
  <?php return ob_get_clean();
}
add_shortcode('ofertas-portada-slider', 'wp_grupompleo_ofertas_portadas_slider_shortcode');

function wp_grupompleo_ofertas_con_filtro_shortcode($params = array(), $content = null) {
  ob_start(); $buscaroferta = json_decode(stripslashes($_COOKIE['buscaroferta']), true); /*echo "<pre>"; print_r($buscaroferta); echo "</pre>";*/ ?>
  <script src="https://unpkg.com/isotope-layout@3/dist/isotope.pkgd.min.js"></script>
  <?php /* <script src="https://cdn.jsdelivr.net/npm/js-cookie@3.0.5/dist/js.cookie.min.js"></script> */ ?>
  <div class="filters-button-group">
    <div class="button-group"><h2><?php _e("<span>Empleos en el</span> Buscador", 'wp-gruprompleo');/*_e("Buscador <span>de trabajo</span>", 'wp-gruprompleo');*/ ?></h3>
    <input type="text" placeholder="<?php _e('Buscar', 'wp-gruprompleo'); ?>"
      <?php echo(isset($_GET['search']) || isset($buscaroferta['search']) ? " ".(isset($_GET['search']) ? "value='".strip_tags($_GET['search']).'"' : (isset($buscaroferta['search']) && $buscaroferta['search'] != '' ? "value='".$buscaroferta['search']."'" : ""))."'" : ""); ?> 
        <?=((isset($_GET['search']) && $_GET['search'] != '') || (isset($buscaroferta['search']) && $buscaroferta['search'] != '') ? " class='quicksearch selected'": " class='quicksearch'")?>/></div>
    <?php $json = json_decode(file_get_contents(WP_GRUPOMPLEO_FILTERS_CACHE_FILE));
      foreach ($json as $title => $group) { ?>
      <div class="button-group">
        <h2><span><?php _e('Empleos por', 'wp-gruprompleo'); ?></span><?=($title == 'Ubicacion' ? "Provincia" : ($title == 'Tipo' ? "Contratación" : $title))?></h2>
        <?php if($title != 'Ubicacion') { ?>
          <select name="<?php $sanitize_title = sanitize_title($title); echo $sanitize_title; ?>"<?=((isset($_GET[$sanitize_title]) && $_GET[$sanitize_title] != '') || (isset($buscaroferta[$sanitize_title]) && $buscaroferta[$sanitize_title] != '') ? " class='selected'": "")?>>
            <option value="">Todas</option>
            <?php foreach ($group as $button) {  ?>
              <option value="<?=$sanitize_title; ?>-<?=sanitize_title($button); ?>"<?=(isset($_GET[$sanitize_title]) && $_GET[$sanitize_title] == sanitize_title($button) ? " selected='selected'" : ( isset($buscaroferta[$sanitize_title]) && $buscaroferta[$sanitize_title] == $sanitize_title."-".sanitize_title($button) ? " selected='selected'" : ""))?>><?=($button == 'Directa' ? "Directa a través de empresa" : ($button == 'ETT' ? "A través de ETT" : $button))?></option>
            <?php } ?>
          </select>
        <?php } else { $sanitize_title = sanitize_title($title); ?>
          <select name="<?=$sanitize_title?>" id="select-<?=$sanitize_title?>"<?=((isset($_GET[$sanitize_title]) && $_GET[$sanitize_title] != '') || (isset($buscaroferta[$sanitize_title]) && $buscaroferta[$sanitize_title] != '') ? " class='selected'": "")?>>
            <option value=""><?php _e("Todos", 'wp-gruprompleo'); ?></option>
            <?php foreach ($group as $label => $cities) { ?>
              <option value="provincia-<?=sanitize_title($label); ?>"<?=(isset($_GET['provincia']) && $_GET['provincia'] == $label ? " selected='selected'" : (isset($buscaroferta[$sanitize_title]) && $buscaroferta[$sanitize_title] == "provincia-".sanitize_title($label) ? " selected='selected'" : ""))?>><?=$label?></option>
            <?php } ?>          
          </select>
        </div>
        <div class="button-group" style=" width: 100%; order: 5;">
          <div id="city-group-<?=$sanitize_title?>">
            <?php foreach ($group as $label => $cities) { ?>
              <?php foreach ($cities as $city) { ?>
                <label class="city <?=(isset($buscaroferta[$sanitize_title]) && $buscaroferta[$sanitize_title] == "provincia-".sanitize_title($label) ? 'show' : 'hidden')?>" 
                  data-provincia="provincia-<?=sanitize_title($label);?>">
                  <input type="checkbox" 
                    name="<?=$sanitize_title?>-localidad" 
                    value="ubicacion-<?=sanitize_title($city); ?>"
                    <?=(isset($buscaroferta['localizacion']) && count($buscaroferta['localizacion']) > 0 && in_array("ubicacion-".sanitize_title($city), $buscaroferta['localizacion']) ? " checked='checked'" : "")?>/> 
                  <?=$city?>
                </label>
              <?php } ?>
            <?php } ?>
          </div>
          <script>
            jQuery("#select-<?=sanitize_title($title)?>").on('change', function() {
              jQuery("#city-group-<?=sanitize_title($title)?> input[type='checkbox']").prop( "checked", false );
              var currentState = this.value;
              //console.log("Seleccionamos: "+currentState);
              var e = document.getElementById("city-group-<?=sanitize_title($title)?>");
              e.scrollTop = 0;
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
            #city-group-<?=sanitize_title($title)?> {
              display: flex;
              flex-wrap: wrap;
              align-content: flex-start;
              flex-direction: row;
              border: 1px solid #ffffff;
              border-radius: 10px;
              overflow: hidden;
              transition:  0.3s; 
              margin-top: 10px;
            }

            #city-group-<?=sanitize_title($title)?>:has(label:not(.hidden)) {
              border: 1px solid rgb(242, 243, 245);
            }

            #city-group-<?=sanitize_title($title)?> label {
              width: 50%;
              display: block;
              background-color: white;
              position: relative;
              z-index: 1;
              padding: 5px 5px 5px 30px;
              font-size: 12px;
              position: relative;
            }

            #city-group-<?=sanitize_title($title)?> label:has(input.selected) {
              color: #000000;
              background-color: #fcc5017a;
            }

            #city-group-<?=sanitize_title($title)?> label input {
              position: absolute;
              top: 5px;
              left: 0px;
            }

            @media (max-width: 799px) {
              #city-group-<?=sanitize_title($title)?> {
                overflow: auto;
                max-height: 180px;
              }
            }

            @media (min-width: 800px) {
              #city-group-<?=sanitize_title($title)?> label {
                width: 20%;
              }
            }

            #city-group-<?=sanitize_title($title)?> label.hidden {
              display: none;
            }
          </style>
        <?php } ?>
      </div>
    <?php } ?>
    <?php $json = json_decode(file_get_contents(WP_GRUPOMPLEO_OFFERS_CACHE_FILE)); ?>
    <span id="numberresults"><?php printf(__("Hemos encontrado <b>%d</b> ofertas de empleo.", 'wp-gruprompleo'), count($json)); ?></span>
  </div>
  <div class="jobs-grid">
    <?php foreach ($json as $offer) { $puesto =  wp_grupompleo_ofertas_dividir_generos($offer->Puesto); ?>
      <div class="jobs-item sede-<?=sanitize_title($offer->Sede)?> tipo-<?=sanitize_title($offer->Tipo)?> provincia-<?=sanitize_title($offer->provincia); ?> ubicacion-<?=sanitize_title($offer->Ubicacion); ?>" data-category="<?=sanitize_title($offer->Tipo)?>" data-search="<?php echo str_replace("-", " ", sanitize_title($puesto." ".$offer->provincia." ".$offer->Ubicacion." ".$offer->Tipo." ".$offer->Sede));?>">
        <p><?=str_replace("mpleo", "<span>mpleo</span>", mb_strtolower($offer->Delegacion))?></p>
        <p class="place"><?=$offer->provincia?><br/><?=ucfirst(mb_strtolower($offer->Ubicacion))?></p>
        <p class="name"><?=mb_strtolower($offer->Puesto)?></p>
        <a href="<?php echo wp_grupompleo_offer_permalink($offer); ?>"><?php _e("Ver oferta", 'wp-gruprompleo'); ?></a>
      </div>
    <?php } ?>
  </div>
  <div id="noresults">
    <p><?php _e("Parece que no hemos encontrado lo que buscas<span></span>.", 'wp-gruprompleo'); ?></p>
    <p><?php _e("Chequea cómo lo has escrito o utiliza sinónimos.", 'wp-gruprompleo'); ?></p>
    <p><strong><?php _e("¡Vuelve a intentarlo!", 'wp-gruprompleo'); ?></strong></p>
    <img src="/wp-content/uploads/2024/04/error-busqueda.png" alt="">
  </div>
  <style>
    <?php echo file_get_contents(plugin_dir_path(__FILE__).'css/style.css'); ?>
  </style>
  <script>
    <?php echo file_get_contents(plugin_dir_path(__FILE__).'js/isotope.js'); ?>
    <?php echo(isset($_GET['search']) || isset($buscaroferta['search']) ? ' qsRegex = new RegExp( quicksearch.value.toLowerCase().normalize("NFD").replace(/\p{Diacritic}/gu, ""), \'gi\' );
  iso.isotope();' : ""); ?>
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


function wp_grupompleo_ofertas_contador_shortcode($params = array(), $content = null) {

  $json = json_decode(file_get_contents(WP_GRUPOMPLEO_OFFERS_CACHE_FILE));
  echo count($json);
}
add_shortcode('ofertas-contador', 'wp_grupompleo_ofertas_contador_shortcode');

function wp_grupompleo_ofertas_contador_shortocode($output, $tag, $attr, $m) { 
  //fusion_counter_box
  if ($tag == 'fusion_counter_box') {
    if($attr['value'] == 145) {
      return do_shortcode('[fusion_counter_box value="'.count(json_decode(file_get_contents(WP_GRUPOMPLEO_OFFERS_CACHE_FILE))).'" unit_pos="suffix" direction="up" /]'); 
    }
  }
  return $output; 
}
add_filter( "pre_do_shortcode_tag", "wp_grupompleo_ofertas_contador_shortocode", 10, 4 );
  



//Damos error 404 si la oferta no existe
add_filter( 'template_include', 'wp_grupompleo_oferta_404', 99 );
function wp_grupompleo_oferta_404( $template ) {
  if (is_page(WP_GRUPOMPLEO_OFFER_PAGE_ID)  ) {
    //Si no existe la oferta error 404
    $codigo = end(explode("-", get_query_var('oferta_codigo')));
    $json = json_decode(file_get_contents(WP_GRUPOMPLEO_OFFERS_CACHE_FILE), true);
    $offer_id = array_search($codigo, array_column($json, 'Codigo'));
    if($offer_id === '') {
      status_header( 404 );
      nocache_headers();
      include( get_query_template( '404' ) );
      die();
    }
    //Si la oferta existe pero la URL es diferente 
    $offer = json_decode(file_get_contents(WP_GRUPOMPLEO_OFFERS_CACHE_FILE))[$offer_id];
    $currentlink = (is_ssl() ? 'https://' : 'http://'). $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $link = wp_grupompleo_offer_permalink($offer);
    if($link != $currentlink) {
      wp_redirect($link, 301);
      exit;
    }

  }
  return $template;
}


//Enlace con hash en las urls a la página de búsqueda de empleo
function wp_grupompleo_change_permalinks($permalink, $post) { 
  if($post == WP_GRUPOMPLEO_SEARCH_OFFERS_PAGE_ID) return add_query_arg(['hash' => hash('md5', date("mdHis").rand(1,100000))], $permalink);
  else return $permalink; 
}; 
add_filter( 'page_link', 'wp_grupompleo_change_permalinks', 10, 3);

//Divide los opuestos en sus dos versiones de genero
function wp_grupompleo_ofertas_dividir_generos($puesto) {
  $words = explode(" ", $puesto);
  foreach ($words as $key => $word) {
    if(preg_match("/(o\/a|a\/o|as\/os|os\/as|e\/a|es\/as|a\/e|as\/es)/i", $word)) { 
      $first = substr($word, 0, strpos($word, '/'));
      $second = substr($word, (strpos($word, '/') + 1));
      $words[$key] = $first." ".substr($first, 0, (strlen($first) - strlen($second))).$second;
    } else if(preg_match("/(r\/a)/", $word)) { 
      $first = substr($word, 0, strpos($word, '/'));
      $second = substr($word, (strpos($word, '/') + 1));
      $words[$key] = $first." ".$first.$second;
    }
  }
  return implode(" ", $words);
}
