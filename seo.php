<?php
/* -------------- Modificaciones YOAST SEO ------------- */
//https://developer.yoast.com/customization/apis/metadata-api/
//Metemos su propio sitemap
add_filter( 'wpseo_sitemap_index', 'wp_grupompleo_add_sitemap_custom_items' );
function  wp_grupompleo_add_sitemap_custom_items( $sitemap_custom_items ) {
  $sitemap_custom_items .= '
  <sitemap>
  <loc>'.get_home_url().'/wp-content/plugins/wp-grupompleo/cache/job-offers.xml</loc>
  <lastmod>2017-05-22T23:12:27+00:00</lastmod>
  </sitemap>';
  return $sitemap_custom_items;
}

//Modificamos las metas
add_filter('wpseo_metadesc', 'wp_grupompleo_filter_wpseo_title');
add_filter('wpseo_title', 'wp_grupompleo_filter_wpseo_title');
add_filter('wpseo_opengraph_desc', 'wp_grupompleo_filter_wpseo_title');
add_filter('wpseo_opengraph_title', 'wp_grupompleo_filter_wpseo_title');

function  wp_grupompleo_filter_wpseo_title($title) {
  if(is_page(WP_GRUPOMPLEO_OFFER_PAGE_ID) ) {
    $codigo = end(explode("-", get_query_var('oferta_codigo')));
    $json = json_decode(file_get_contents(WP_GRUPOMPLEO_OFFERS_CACHE_FILE));
    foreach ($json as $offer) { 
      if($offer->Codigo == $codigo) {
        foreach ($offer as $label => $value) {
          $title = str_replace("[".$label."]", $value, $title);
        }
        break;
      }
    }
  }
  return $title;
}

add_filter('wpseo_canonical', 'wp_grupompleo_filter_wpseo_canonical');
add_filter('wpseo_opengraph_url', 'wp_grupompleo_filter_wpseo_canonical');
function wp_grupompleo_filter_wpseo_canonical ($canonical) { 
  if(is_page(WP_GRUPOMPLEO_OFFER_PAGE_ID) ) {
    $codigo = end(explode("-", get_query_var('oferta_codigo')));
    $json = json_decode(file_get_contents(WP_GRUPOMPLEO_OFFERS_CACHE_FILE));
    foreach ($json as $offer) { 
      if($offer->Codigo == $codigo) {
        $canonical = wp_grupompleo_offer_permalink($offer);
        return $canonical;
      }
    }
  }
  return $canonical;
}

function wp_grupompleo_filter_wpseo_robots($output) {
  if(is_page(WP_GRUPOMPLEO_OFFER_PAGE_ID) && get_query_var('oferta_codigo') != '') return "INDEX,FOLLOW";
  return $output;
}

add_filter( 'wpseo_robots', 'wp_grupompleo_filter_wpseo_robots');


//Schema Jobs 
function wp_grupompleo_generate_schema ($extras) { 
  
  if($extras->OFSALARIO == 0) {
    $salario = 0;
  } elseif($extras->OFSALARIO > 0 && $extras->OFSALARIO < 500) {
    $salario = number_format($extras->OFSALARIO,2,'.','');
    $tiempo = "HOUR";
  } elseif($extras->OFSALARIO >= 500 && $extras->OFSALARIO < 5000) {
    $salario = number_format($extras->OFSALARIO,0,'.','');
    $tiempo = "MONTH";
  } elseif($extras->OFSALARIO >= 5000) {
    $salario = number_format($extras->OFSALARIO,0,'.','');
    $tiempo = "YEAR";
  }; ?>
  <!-- JOB SCHEMA -->
  <script type="application/ld+json">
    {
      "@context"            : "http://schema.org/",
      "@type"               : "JobPosting",
      "identifier"          : "<?php echo $extras->Codigo; ?>",
      "url"                 : "<?php echo wp_grupompleo_offer_permalink($offer); ?>",
      "image"               : "<?php echo content_url(); ?>/webp-express/webp-images/uploads/2023/12/Logo-Grupompleo-02.png.webp",
      "title"               : "<?php echo $extras->OFPUESTOVACANTE; ?>",
      "description"         : "<?php echo str_replace(array("\r", "\n"), '', trim($extras->OFDESCRIPCION)); ?>",
      "industry"            : "Ingeniería",
      "datePosted"          : "2024-05-13T10:16:28",
      "employmentType"      : "<?php echo ($extras->OFTIPO == 'ett' ? "temporary" : "contract"); ?>",
      "hiringOrganization"  : {
        "@type"             : "Organization",
        "name"              : "GRUPOMPLEO EMPRESA DE TRABAJO TEMPORAL S.L."
      },
      "jobLocation"         : {
        "@type"             : "Place",
        "address"           : {
          "@type"           : "PostalAddress",
          "streetAddress"   : "not informed",
          "addressLocality" : "<?php echo $extras->OFUBICACION ?>",
          "addressRegion"   : "<?php echo $extras->OFPROVINCIA ?>",
          "addressCountry"  : "ES"
        }
      },
      "experienceRequirements" : {
        "@type" : "OccupationalExperienceRequirements",
        "description"        : "<?php echo (trim($extras->OFFORMACIONBASE) != '' ? "Formación: ".str_replace(array("\r", "\n"), '', trim($extras->OFFORMACIONBASE))."<br/>" : ""); ?><?php echo (trim($extras->OFIDIOMAS) != '' ? "Idiomas: ".str_replace(array("\r", "\n"), '', trim($extras->OFIDIOMAS))."<br/>" : ""); ?><?php echo (trim($extras->OFINFORMATICA) != '' ? "Conocimientos informatica: ".str_replace(array("\r", "\n"), '', trim($extras->OFINFORMATICA))."<br/>" : ""); ?><?php echo (trim($extras->OFCOMPETENCIAS) != '' ? "Competencias: ".str_replace(array("\r", "\n"), '', trim($extras->OFCOMPETENCIAS))."<br/>" : ""); ?>"
      },
      "responsibilities"   : "<?php echo str_replace(array("\r", "\n"), '', trim($extras->OFFUNCIONES)); ?>",
      <?php if($salario > 0) { ?>
      "baseSalary"         : {
        "@type"             : "MonetaryAmount",
        "currency"          : "EUR",
        "value"             : {
          "@type"           : "QuantitativeValue",
          "minValue"        : <?=$salario;?>,
          "maxValue"        : <?=$salario;?>,
          "unitText"        : "<?=$tiempo;?>"
        }
      }
      <?php } ?>
    }
  </script><?php 
}