<?php

//Administrador --------------------- 
add_action( 'admin_menu', 'wp_grupompleo_plugin_menu' );
function wp_grupompleo_plugin_menu() {
  add_options_page( __('Configuraciṕon ofertas de trabajo', 'wp-grupompleo'), __('ofertas trabajo', 'wp-grupompleo'), 'manage_options', 'wp-grupompleo', 'wp_grupompleo_admin_page');
}

function wp_grupompleo_admin_page() { ?>
  <h1><?php _e("Configuración de cuestionario de Autodiagnóstico en competencias emprendedoras", 'wp-grupompleo'); ?></h1>
  <?php if(isset($_REQUEST['send']) && $_REQUEST['send'] != '') { 
    ?><p style="border: 1px solid green; color: green; text-align: center;"><?php _e("Datos guardados correctamente.", 'wp-grupompleo'); ?></p><?php
    update_option('_wp_grupompleo_endpoint_jobs', $_POST['_wp_grupompleo_endpoint_jobs']);
    update_option('_wp_grupompleo_endpoint_search_jobs', $_POST['_wp_grupompleo_endpoint_search_jobs']);
    update_option('_wp_grupompleo_endpoint_filters', $_POST['_wp_grupompleo_endpoint_filters']);
    
  } ?>
  <form method="post">
    <b><?php _e("Endpoint Ofertas de trabajo", 'wp-grupompleo'); ?>:</b><br/>
		<input type="text" name="_wp_grupompleo_endpoint_jobs" value="<?php echo get_option("_wp_grupompleo_endpoint_jobs"); ?>" style="width: calc(100% - 20px);" /><br/>
		<b><?php _e("Endpoint Buscador de ofertas de trabajo", 'wp-grupompleo'); ?>:</b><br/>
		<input type="text" name="_wp_grupompleo_endpoint_search_jobs" value="<?php echo get_option("_wp_grupompleo_endpoint_search_jobs"); ?>" style="width: calc(100% - 20px);" /><br/>
    <b><?php _e("Endpoint Filtros para ofertas de trabajo", 'wp-grupompleo'); ?>:</b><br/>
		<input type="text" name="_wp_grupompleo_endpoint_filters" value="<?php echo get_option("_wp_grupompleo_endpoint_filters"); ?>" style="width: calc(100% - 20px);" /><br/>
		<br/><input type="submit" name="send" class="button button-primary" min-value=" value="<?php _e('Guardar', 'wp-grupompleo'); ?>" />
	</form>
<?php }
