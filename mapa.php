<?php

function wp_grupompleo_map($params = array(), $content = null) {
	ob_start();
	$data = [];
	/*$csv = explode("\n", "aragonmpleo | Zaragoza,\"Calle Felipe Sanclemente, 8, Escalera segunda 1ºA, 50001\",876 015 890,41.6495782,-0.8820205
	catalunyampleo | Granollers,\"plaza de la Corona, 6 2ª planta, 08401\",93 176 76 18,41.6057009,2.2880057
	catalunyampleo | Sant Joan Despí,\"c/ Fructuós Gelabert, nº 2-4, 2º 4ª. edificio Conata 1, 08970\",93 654 12 06,41.3731373,2.0671082
	catalunyampleo | Martorell,\"Carretera de Piera 18, 08760\",93 882 82 92,41.4781099,1.9207528
	euskadimpleo | Bilbao,\"c/ Ledesma, 10 Bis, 7ª planta, 48001\",94 498 30 35,43.26233329999999,-2.9281689
	euskadimpleo | Donostia,\"Plaza Pinares nº 1-3º, oficina 2, 20001\",943 01 49 90,43.3222664,-1.9748045
	iruñampleo | Pamplona,\"c/ Navas de Tolosa, 27 - 1ºdcha, 31002\",948 21 26 74,42.815419,-1.6508554
	madridmpleo | Getafe,\"c/ Madrid 4, 1ºB, 28901\",91 020 07 75,40.305555,-3.731624
	riojampleo | Logroño,\"avda Club Deportivo, nº 50 - 1ºA, 26007\",941 22 93 92,42.4575748,-2.4575868");*/
	$csv = explode("\n", trim($content) );
	foreach ($csv as $line) {
		$data[] = str_getcsv($line);
	} ?>


	<div id="grupompleo_map_wrap_responsive">
		<select id="grupompleo_map_wrap_responsive_select">
			<option><?php _e("Elige tu oficina", "grupompleo"); ?></option>
			<?php foreach ($data as $key => $item) { ?>
				<option value="<?=$key?>"><?=$item[0]?></option>
			<?php } ?>
		</select>
	</div>
	<script>
		jQuery("#grupompleo_map_wrap_responsive select").on('change', function() {
			var key = jQuery("#grupompleo_map_wrap_responsive select").val();
			map.setZoom(16);
			map.setCenter({ lat: markers[key].getPosition().lat(), lng: markers[key].getPosition().lng() });
			jQuery("#grupompleo_map_wrap #grupompleo_list ul li").not("#grupompleo_map_wrap #grupompleo_list ul li[data-key="+key+"]").removeClass("show");

			jQuery("#grupompleo_map_wrap #grupompleo_list ul li[data-key="+key+"]").addClass("show");

		});
	</script>
	<div id="grupompleo_map_wrap">
		<div id="grupompleo_list">
			<ul>
				<?php foreach ($data as $key => $item) { ?>
					<li data-long="<?=$item[4]?>" data-lat="<?=$item[3]?>" data-key="<?=$key?>">
						<h3><?=$item[0]?></h3>
						<?=$item[1]?><br/>
						<a href="tel:<?=$item[2]?>"><?=$item[2]?></a> <a href="mailto:<?=$item[5]?>"><?=$item[5]?></a><br/>
						<a href="https://www.google.com/maps/dir/?api=1&destination=<?=$item[3]?>,<?=$item[4]?>" target="_blank"><?php _e("Cómo llegar", "grupompleo"); ?></a>
					</li>
				<?php } ?>
			</ul>
		</div>
		<div id="grupompleo_map"></div>
	</div>
	<style>
		#grupompleo_map_wrap {
			display: flex;
			justify-content: space-between;
			align-items: stretch;
			flex-wrap: wrap;
		}

		#grupompleo_map_wrap #grupompleo_map {
			min-height: 300px;
		}

		#grupompleo_map_wrap #grupompleo_map,
		#grupompleo_map_wrap #grupompleo_list {
			width: 100%;
		}

		#grupompleo_map_wrap #grupompleo_list ul {
			padding: 0;
			margin: 0;
		}

		#grupompleo_map_wrap #grupompleo_list ul li {
			cursor: pointer;
			transition: all 0.3s;
			font-size: 12px;
			display: none;
			list-style-type : none;
			padding: 10px;
			margin: 0;
		}

		#grupompleo_map_wrap #grupompleo_list ul li h3 {
			font-weight: 700;
			line-height: 120%;
			font-size: 19px !important;
			padding: 0;
   		margin: 0;
			color: #fcc501;
		}

		#grupompleo_map_wrap #grupompleo_list ul li a:first-of-type {
			display: inline-block;
			padding: 7px 7px 7px 20px;
			border: 1px solid #f7da7f;
			position: relative;
			background-color: #f7da7f;
			border-radius: 6px;
			margin: 0 5px 5px 0;
		}

		#grupompleo_map_wrap #grupompleo_list ul li a:first-of-type:before {
			content: "";
			display: block;
			position: absolute;
			background: transparent url(<?=plugin_dir_url(__FILE__).'images/telefono.svg';?>) center left no-repeat;
			width: 10px;
			height: 10px;
			top: 11px;
			left: 6px;
		}

		#grupompleo_map_wrap #grupompleo_list ul li a:first-of-type:hover {
			color: #000;
		}

		#grupompleo_map_wrap #grupompleo_list ul li a:nth-of-type(2) {
			display: inline-block;
			padding: 7px 7px 7px 20px;
			border: 1px solid #f7da7f;
			position: relative;
			background-color: #fff;
			border-radius: 6px;
			margin: 0 5px 5px 0;
			word-break: break-word;
		}

		#grupompleo_map_wrap #grupompleo_list ul li a:nth-of-type(2):before {
			content: "";
			display: block;
			position: absolute;
			background: transparent url(<?=plugin_dir_url(__FILE__).'images/mail.svg';?>) center left no-repeat;
			width: 10px;
			height: 10px;
			top: 13px;
			left: 6px;
		}

		#grupompleo_map_wrap #grupompleo_list ul li a:nth-of-type(3) {
			display: inline-block;
			padding: 7px 7px 7px 25px;
			border: none;
			position: relative;
			border-radius: 6px;
			margin: 0 5px 0 0;
		}

		#grupompleo_map_wrap #grupompleo_list ul li a:nth-of-type(3):before {
			content: "";
			display: block;
			position: absolute;
			background: transparent url(<?=plugin_dir_url(__FILE__).'images/ubicacion.svg';?>) center left no-repeat;
			width: 14px;
			height: 15px;
			top: 10px;
			left: 6px;
			background-size: contain;
		}

		#grupompleo_map_wrap #grupompleo_list ul li.show {
			display: block;
		}

		#grupompleo_map_wrap #grupompleo_list ul li:hover,
		#grupompleo_map_wrap #grupompleo_list ul li.show {
			background-color: #f4f4f4;
		}

		#grupompleo_map_wrap_responsive select {
			width: 100%;
			box-sizing: border-box;
			margin-bottom: 20px;
		}

		@media (min-width: 680px) {
			#grupompleo_map_wrap #grupompleo_list ul li {
				display: block;
			}

			#grupompleo_map_wrap #grupompleo_map {
				width: 65%;
			} 

			#grupompleo_map_wrap #grupompleo_list {
				width: 35%;
			} 

			#grupompleo_map_wrap_responsive select {
				display: none;
			}
		}
	</style>
	<script type="text/javascript" src="https://grupompleo.enuttisworking.com/wp-includes/js/jquery/jquery.min.js?ver=3.7.1" id="jquery-core-js"></script>
	<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=<?=$params['apikey']?>"></script>
	<script type="text/javascript">
		var default_zoom = 2;
		var default_lat = 41.6495782;
		var default_lng = -0.8820205;
		var map;
		var markers = [];
		var infowindow = new google.maps.InfoWindow();
		var latlngbounds = new google.maps.LatLngBounds();
		var defaultLatLng = new google.maps.LatLng(default_lat, default_lng);
		let json = <?=json_encode($data)?>;
		function initialize() {
			var mapProp = {
				center: defaultLatLng,
				zoom: default_zoom,
				mapTypeId: google.maps.MapTypeId.ROADMAP
			};
			map = new google.maps.Map(document.getElementById("grupompleo_map"), mapProp);
			getAllMarkers(json);
		}
		initialize();
		function getAllMarkers(json) {
			jQuery.each(json, function(key, data) {
				const icon = {
					url: "/wp-content/uploads/2024/02/ubicacionGrupompleo.png", // url
					scaledSize: new google.maps.Size(50, 50), // scaled size
					origin: new google.maps.Point(0,0), // origin
					anchor: new google.maps.Point(25,50) // anchor
				};
				var latLng = new google.maps.LatLng(data[3], data[4]);
				marker = new google.maps.Marker({
					position: latLng,
					map: map,
					icon: icon,
					title: data[0]
				});
				latlngbounds.extend(latLng);
				markers[key] = marker;
				//var details = '<b>'+data[0]+'</b><br/>'+data[1]+'<br/>'+data[2];
				bindInfoWindow(marker, map, infowindow/*, details*/, key);
			});
			map.fitBounds(latlngbounds);
		}

		function bindInfoWindow(marker, map, infowindow/* , details*/, key) {
			google.maps.event.addListener(marker, 'click', function() {
				/*infowindow.setContent(details);
				infowindow.open(map, marker);*/
				map.setZoom(16);
				map.setCenter(marker.position);
				jQuery("#grupompleo_map_wrap #grupompleo_list ul li").removeClass("show");
				jQuery("#grupompleo_map_wrap #grupompleo_list ul li[data-key="+key+"]").addClass("show");
				jQuery("#grupompleo_map_wrap_responsive select").val(key);
			});
		}

		jQuery("#grupompleo_map_wrap #grupompleo_list ul li").on("mouseover", function() {
			const icon = {
				url: "/wp-content/plugins/wp-grupompleo/images/ubicacionGrupompleoActive.webp", // url
				scaledSize: new google.maps.Size(70, 70), // scaled size
				origin: new google.maps.Point(0,0), // origin
				anchor: new google.maps.Point(35,70) // anchor
			};
			var key = jQuery(this).data("key");
			markers[key].setIcon(icon);
		});

		jQuery("#grupompleo_map_wrap #grupompleo_list ul li").on("mouseout", function() {
			const icon = {
				url: "/wp-content/plugins/wp-grupompleo/images/ubicacionGrupompleo.webp", // url
				scaledSize: new google.maps.Size(50, 50), // scaled size
				origin: new google.maps.Point(0,0), // origin
				anchor: new google.maps.Point(25,50) // anchor
			};
			var key = jQuery(this).data("key");
			markers[key].setIcon(icon);
		});

		jQuery("#grupompleo_map_wrap #grupompleo_list ul li").click(function() {
			jQuery("#grupompleo_map_wrap #grupompleo_list ul li").removeClass("show");
			jQuery(this).addClass("show");
			jQuery("#grupompleo_map_wrap_responsive select").val(jQuery(this).data("key"));
			map.setZoom(16);
			map.setCenter({ lat: jQuery(this).data("lat"), lng: jQuery(this).data("long") });
		});
	</script>
	<?php return ob_get_clean();
}
add_shortcode('mapa-oficinas', 'wp_grupompleo_map');
