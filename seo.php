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
    $codigo = explode("-", get_query_var('oferta_codigo'))[0];
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
    $codigo = explode("-", get_query_var('oferta_codigo'))[0];
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
function wp_grupompleo_generate_schema ($extras) { ?>
  <!-- JOB SCHEMA -->
<script type="application/ld+json">
	{
		"@context"            : "http://schema.org/",
		"@type"               : "JobPosting",
		"identifier"          : "<?php echo $extras->Codigo; ?>",
		"url"                 : "<?php echo wp_grupompleo_offer_permalink($offer); ?>",
		"image"               : "https://dghqs88jwcgws.cloudfront.net/wp-content/plugins/rand-job-search/public/img/logo-randstad-stacked-diap-medium.png?x68837",
		"title"               : "<?php echo $extras->OFPUESTOVACANTE; ?>",
		"description"         : "<?php echo $extras->OFDESCRIPCION; ?>",
		"industry"            : "Ingeniería",
		"datePosted"          : "2024-05-13T10:16:28",
		"validThrough"        : "2024-11-13T10:16:28",
		"employmentType"      : "TEMPORARY",
		"occupationalCategory": "Ingeniería, Ingenieria y Oficina técnica",
		"workHours"           : "4 hours",
		"directApply"     : true,
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
			"monthsOfExperience" : "12",
			"description"        : " Formación: <?=$extras->OFFORMACIONBASE;?>\n\n Idiomas: <?=$extras->OFIDIOMAS;?>\n\n Conocimientos informatica: <?=$extras->OFINFORMATICA;?>\n Competencias: <?=$extras->OFCOMPETENCIAS;?>\n"
		},
    "responsibilities"   : "<?php echo $extras->OFFUNCIONES; ?>",
    "baseSalary"         : {
			"@type"             : "MonetaryAmount",
			"currency"          : "EUR",
			"value"             : {
				"@type"           : "QuantitativeValue",
				"minValue"        : 25000,
				"maxValue"        : 35000,
				"unitText"        : "YEAR"
			}
		}
			}
</script><?php 
}