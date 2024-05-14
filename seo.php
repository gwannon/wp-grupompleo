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
  if(is_page(WP_GRUPOMPLEO_ENDPOINT_OFFER_PAGE_ID) ) {
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
  if(is_page(WP_GRUPOMPLEO_ENDPOINT_OFFER_PAGE_ID) ) {
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
  if(is_page(WP_GRUPOMPLEO_ENDPOINT_OFFER_PAGE_ID) && get_query_var('oferta_codigo') != '') return "INDEX,FOLLOW";
  return $output;
}

add_filter( 'wpseo_robots', 'wp_grupompleo_filter_wpseo_robots');


//Schema Jobs 
function wp_grupompleo_generate_schema ($offer, $extras) { ?>
  <!-- JOB SCHEMA -->
<script type="application/ld+json">
	{
		"@context"            : "http://schema.org/",
		"@type"               : "JobPosting",
		"identifier"          : "2775470",
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
        "postalCode"      : "08167",
        "streetAddress"   : "not informed",
        "addressLocality" : "Polinyà",
        "addressRegion"   : "Barcelona",
        "addressCountry"  : "ES"
      }
		},
		"experienceRequirements" : {
			"@type" : "OccupationalExperienceRequirements",
			"monthsOfExperience" : "12",
			"description"        : " Formación: Grado\n\n Idiomas: Inglés\n: C1\n Conocimientos: -	Nivel avanzado de Excel\n Experiencia: 1 año\n"
		}
		,"responsibilities" : "- Recogida de datos y fichas técnicas de los productos actuales para cumplimentar plantillas técnicas.
- Creación y actualización de documentación técnica y de certificados de componentes mecánicas, eléctricos e hidráulicos.
- Análisis y control de los datos mediante Excel.
- Gestión y contacto directo con fábricas, proveedores y representadas."		,"jobBenefits" : "- Incorporación en una de las empresas clave del sector.
- Incorporación inmediata.
- Posibilidad de continuidad.
- Salario competitivo."				,"baseSalary"         : {
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