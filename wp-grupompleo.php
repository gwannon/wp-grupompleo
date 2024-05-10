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
 * - Filtro por ciudades
 * - Preguntar sistema de filtros OR o AND
 * - Metas ofertas
 * - Integrar diseño
 */

define('WP_GRUPOMPLEO_ENDPOINT_JOBS', get_option("_wp_grupompleo_endpoint_jobs"));
define('WP_GRUPOMPLEO_ENDPOINT_SEARCH_JOBS', get_option("_wp_grupompleo_endpoint_search_jobs"));
define('WP_GRUPOMPLEO_ENDPOINT_FILTERS', get_option("_wp_grupompleo_endpoint_filters"));
define('WP_GRUPOMPLEO_OFFERS_CACHE_FILE', plugin_dir_path(__FILE__).'cache/joboffers.json');
define('WP_GRUPOMPLEO_FILTERS_CACHE_FILE', plugin_dir_path(__FILE__).'cache/filters.json');
define('WP_GRUPOMPLEO_ENDPOINT_OFFER_PAGE_ID', get_option("_wp_grupompleo_offer_page_id"));

//Cargamos el multi-idioma
function wp_grupompleo_plugins_loaded() {
  load_plugin_textdomain('wp-gruprompleo', false, dirname( plugin_basename( __FILE__ ) ) . '/langs/' );
}
add_action('plugins_loaded', 'wp_grupompleo_plugins_loaded', 0 );

/* ----------- Includes ------------ */
include_once(plugin_dir_path(__FILE__).'admin.php');

/* ----------- Cron job ------------ */
//wp-admin/admin-ajax.php?action=grupompleo_ofertas
add_action( 'wp_ajax_grupompleo_ofertas', 'wp_grupompleo_ofertas_cache' );
add_action( 'wp_ajax_nopriv_grupompleo_ofertas', 'wp_grupompleo_ofertas_cache' );
function wp_grupompleo_ofertas_cache() {
  if(!file_exists(WP_GRUPOMPLEO_OFFERS_CACHE_FILE) || (time() - filemtime(WP_GRUPOMPLEO_OFFERS_CACHE_FILE)) > /*(60*4)*/ 5) {
    $json = file_get_contents(WP_GRUPOMPLEO_ENDPOINT_JOBS);
    file_put_contents(WP_GRUPOMPLEO_OFFERS_CACHE_FILE, $json);
    $filters = [
      'Delegacion' => [],
      'Tipo' => [],
      'Ubicacion' => [],
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
  echo $json;
}

/* ----------- Rewrite Rules ------- */
add_action( 'init', 'wp_grupompleo_rewrite_rules' );
function wp_grupompleo_rewrite_rules(){
  add_rewrite_rule('^oferta/([^/]*)/?','index.php?page_id='.WP_GRUPOMPLEO_ENDPOINT_OFFER_PAGE_ID.'&oferta_codigo=$matches[1]','top');
  add_rewrite_tag('%oferta_codigo%','([^&]+)');
}

/* ----------- Filters ------------- */
function wp_grupompleo_oferta_title( $title, $id = null ) {
  if ( is_page(WP_GRUPOMPLEO_ENDPOINT_OFFER_PAGE_ID) ) {
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
  $json = json_decode(file_get_contents(WP_GRUPOMPLEO_OFFERS_CACHE_FILE));
  foreach ($json as $offer) { 
    if($offer->Codigo == $codigo) {
      foreach($offer as $label => $data) {
        echo "<p><b>".$label."</b>: ".$data."</p>";
      }
      break;
    }
  }
  return ob_get_clean();
}
add_shortcode('oferta', 'wp_grupompleo_oferta_shortcode');


function wp_grupompleo_ofertas_portadas_shortcode($params = array(), $content = null) {
  ob_start(); 
  //TODO
  return ob_get_clean();
}
add_shortcode('ofertas-portadas', 'wp_grupompleo_ofertas_portadas_shortcode');

function wp_grupompleo_ofertas_con_filtro_shortcode($params = array(), $content = null) {
  ob_start(); ?>
  <script src="https://unpkg.com/isotope-layout@3/dist/isotope.pkgd.min.js"></script>
  <p><input type="text" class="quicksearch" placeholder="Buscar" /></p>
  <div class="filters-button-group">
    <?php $json = json_decode(file_get_contents(WP_GRUPOMPLEO_FILTERS_CACHE_FILE));
      foreach ($json as $title => $group) { ?>
      <h3><?=$title?></h3>
      <div class="button-group">
        <label><input type="radio" name="<?=sanitize_title($title)?>" value="" checked="checked" /> Todas</label>
        <?php if($title != 'Ubicacion') { 
          foreach ($group as $button) { ?>
          <label><input type="radio" name="<?=sanitize_title($title)?>" value="<?=sanitize_title($title); ?>-<?=sanitize_title($button); ?>" /> <?=$button?></label>
        <?php } } else { ?>
          <select name="<?=sanitize_title($title)?>">
          <?php foreach ($group as $label => $cities) { ?>
            <option value="provincia-<?=sanitize_title($label); ?>"><?=$label?></option>
            <?php foreach ($cities as $city) { ?>
              <option value="ubicacion-<?=sanitize_title($city); ?>"> ∟ <?=$city?></option>
          <?php } } ?>
            </select>
        <?php } ?>
      </div>
    <?php } ?>
  </div>
  <div class="jobs-grid">
    <?php $json = json_decode(file_get_contents(WP_GRUPOMPLEO_OFFERS_CACHE_FILE));
    foreach ($json as $offer) { ?>
      <div class="jobs-item delegacion-<?=sanitize_title($offer->Delegacion)?> tipo-<?=sanitize_title($offer->Tipo)?> provincia-<?=sanitize_title($offer->provincia); ?> ubicacion-<?=sanitize_title($offer->Ubicacion); ?>" data-category="<?=sanitize_title($offer->Tipo)?>">
        <p class="name"><?=$offer->Puesto?></p>
        <p class="place"><?=$offer->provincia?> - <?=$offer->Ubicacion?></p>
        <p class="type"><?=$offer->Tipo?> - <?=$offer->Delegacion?></p>
        <a href="<?php echo get_the_permalink(690).$offer->Codigo."-".sanitize_title($offer->Puesto."-".$offer->provincia."-".$offer->Ubicacion)."/"; ?>">Ver oferta</a>
      </div>
    <?php } ?>
  </div>
  <style>
    .jobs-item { 
      width: 25%;
      box-sizing: border-box;
      padding: 10px;
      border: 1px solid black;
      background-color: white;
      border-radius: 10px;
    }
  </style>
  <script>
    var qsRegex;
    var selectedRadios = [];
    var iso = jQuery('.jobs-grid').isotope({
      // options
      itemSelector: '.jobs-item',
      layoutMode: 'fitRows',
      filter: function() {
        if(qsRegex) {
          if(jQuery(this).text().match( qsRegex )) {
            if(selectedRadios.length === 0) return true;
            else {
              var control = 0;  
              selectedRadios.forEach((element) => {
                if (jQuery(this).hasClass(element)) control++;
              });
              if(control == selectedRadios.length) return true;
              else return false;
            }
          } else {
            return false;
          }
        } else {
          if(selectedRadios.length === 0) return true;
            else {
              var control = 0;  
              selectedRadios.forEach((element) => {
                if (jQuery(this).hasClass(element)) control++;
              });
              if(control == selectedRadios.length) return true;
              else return false;
            }
          return true;
        } 
      },
    });

    // use value of search field to filter
    var quicksearch = document.querySelector('.quicksearch');
    quicksearch.addEventListener( 'keyup', debounce( function() {
      qsRegex = new RegExp( quicksearch.value, 'gi' );
      iso.isotope();
    }, 200 ) );

    // debounce so filtering doesn't happen every millisecond
    function debounce( fn, threshold ) {
      var timeout;
      threshold = threshold || 100;
      return function debounced() {
        clearTimeout( timeout );
        var args = arguments;
        var _this = this;
        function delayed() {
          fn.apply( _this, args );
        }
        timeout = setTimeout( delayed, threshold );
      };
    }

    document.querySelectorAll("input[type='radio'],select").forEach((element) => {
      element.addEventListener('change',function(){
        selectedRadios = [];
        document.querySelectorAll("input[type='radio']:checked,select").forEach((element) => {
          if(element.value!= '') selectedRadios.push(element.value);
        });
        iso.isotope();
      });
    });
  </script>
  <?php return ob_get_clean();
}
add_shortcode('ofertas-filtradas', 'wp_grupompleo_ofertas_con_filtro_shortcode');