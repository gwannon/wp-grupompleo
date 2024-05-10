<?php

//Administrador --------------------- 
add_action( 'admin_menu', 'wp_grupompleo_plugin_menu' );
function wp_grupompleo_plugin_menu() {
  add_options_page( __('Configuración ofertas de trabajo', 'wp-grupompleo'), __('Ofertas trabajo', 'wp-grupompleo'), 'manage_options', 'wp-grupompleo', 'wp_grupompleo_admin_page');
}

function wp_grupompleo_admin_page() { ?>
  <h1><?php _e("Configuración de ofertas de trabajo", 'wp-grupompleo'); ?></h1>
  <?php if(isset($_REQUEST['send']) && $_REQUEST['send'] != '') { 
    ?><p style="border: 1px solid green; color: green; text-align: center;"><?php _e("Datos guardados correctamente.", 'wp-grupompleo'); ?></p><?php
    update_option('_wp_grupompleo_endpoint_jobs', $_POST['_wp_grupompleo_endpoint_jobs']);
    update_option('_wp_grupompleo_endpoint_job', $_POST['_wp_grupompleo_endpoint_job']);
    //update_option('_wp_grupompleo_endpoint_filters', $_POST['_wp_grupompleo_endpoint_filters']);
    update_option('_wp_grupompleo_offer_page_id', $_POST['_wp_grupompleo_offer_page_id']);   
    flush_rewrite_rules(); 
  } ?>
  <form method="post">
    <b><?php _e("Endpoint Ofertas de trabajo", 'wp-grupompleo'); ?>:</b><br/>
		<input type="text" name="_wp_grupompleo_endpoint_jobs" value="<?php echo get_option("_wp_grupompleo_endpoint_jobs"); ?>" style="width: calc(100% - 20px);" /><br/>
		<b><?php _e("Endpoint de la info extendida de la oferta de trabajo", 'wp-grupompleo'); ?>:</b><br/>
		<input type="text" name="_wp_grupompleo_endpoint_job" value="<?php echo get_option("_wp_grupompleo_endpoint_job"); ?>" style="width: calc(100% - 20px);" /><br/>
    <?php /* <b><?php _e("Endpoint Filtros para ofertas de trabajo", 'wp-grupompleo'); ?>:</b><br/>
		<input type="text" name="_wp_grupompleo_endpoint_filters" value="<?php echo get_option("_wp_grupompleo_endpoint_filters"); ?>" style="width: calc(100% - 20px);" /><br/> */ ?>
    <b><?php _e("ID de la página de \"Oferta\"", 'wp-grupompleo'); ?>:</b><br/>
		<input type="number" name="_wp_grupompleo_offer_page_id" value="<?php echo get_option("_wp_grupompleo_offer_page_id"); ?>" style="width: 80px;" /><br/>
    <br/><input type="submit" name="send" class="button button-primary" min-value=" value="<?php _e('Guardar', 'wp-grupompleo'); ?>" />
	</form>
<?php }
