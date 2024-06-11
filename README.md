# WP Grupompleo

Plugin de WordPress para conectar WP con intranet de Grupompleo

## Pagina de ajustes
WP-ADMIN > Ajustes > Ofertas trabajo

* Endpoint Ofertas de trabajo: Endpoint del que sacar el json con todas las 
* Endpoint Buscador de ofertas de trabajo ???
* Endpoint Filtros para ofertas de trabajo ???
* ID de la página de "Oferta": ID de la página donde meteremos el código corto [oferta] para mostrar los datos de la oferta 

## Crons
* Cada 5 minutos a la URL https://dominio.com/wp-admin/admin-ajax.php?action=grupompleo_ofertas

## Códigos cortos
* [ofertas-filtradas] Muestra todas las ofertas con un sistema de búsqueda y filtrado.
* [ofertas-portadas] Muestra las 10 últimas ofertas del sistema
* [oferta] Shortcode que se mete en la página de "Oferta" para que salga los datos de la oferta que se pasa por URL.
* [mapa-oficinas] Muestra un mapa de Google Maps con todas las oficinas.

## Librerías usadas
* [Isotope](https://isotope.metafizzy.co/) Filter & sort magical layouts

## Por hacer
* Nuevo Pie (Ainara)
* Imágenes genéricas en la ficha de oferta (Esperar)
* Cookies + Tag Manager (Aritz)
* Mapas diseño (Ainara)
* Arreglos varios de diseño