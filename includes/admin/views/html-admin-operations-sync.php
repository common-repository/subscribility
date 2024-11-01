<script type="text/javascript">
    jQuery( document ).ready( function($){
      function event_add_log (el, message , is_error, is_ending, is_raw) {

          if( message && message.length > 0 ){

            if( is_error === true) {
              var msg = $( '<span style="opacity:0;color:red;">' + message + '<br /></span>' );
            }else if( is_ending === true) {
              var msg = $( '<span style="opacity:0;font-weight:bold;">' + message + '<br /></span>' );
            } else if( is_raw === true) {
              var msg = $( message );
            } else {
              var msg = $( '<span style="opacity:0;">' + message + '<br /></span>' );
            }
              el.append( msg );

              el.scrollTop( el[0].scrollHeight );

              $( msg ).fadeTo( 250, 1 );

          }

      }
      
      
      var buttons = $( '.button' );
      /* Event Listeners */
    $( '.background_process_button' ).on( 'click', function(){ 
        
        /* We must immediately blank out all other options */
        buttons.attr('disabled', true);
        
        var ResultsWrapper, VisibilityWrapper;
        
        if($( this ).data( 'troly_method' ) == 'import') {
          ResultsWrapper = document.getElementById('sse_results_wrapper_import_id');
        } else {
          ResultsWrapper = document.getElementById('sse_results_wrapper_export_id');
        }
        
        var LogWrapper = $( ResultsWrapper ).find( '.sse_log_wrapper' );
        var ResultsBox = $( '<div style="height:200px; overflow:auto;" class="results_box"></div>' );

        var ProgressWrapper = $( ResultsWrapper ).find( '.sse_progress_wrapper' );
        var ProgressBar = $( '<div style="height:100%; background:#CCC; width:0;" class="progress_bar"></div>' )


        $( LogWrapper ).empty().append( ResultsBox );
        $( ProgressWrapper ).empty().append( ProgressBar );

        //ResultsWrapper.show();
        ResultsWrapper.style.display = "block";

        source = new EventSource( $( this ).data( 'event_source') );
        
        
        /* We've started talking to the server */
        source.onopen = function(){
          event_add_log ($( ResultsBox ),'Connection opened - awaiting response', false, false );
        }
        
        /* If we have an issue with the server, let's abort! */
        source.onerror = function(){
          event_add_log ($( ResultsBox ),'An error was encountered communicating with the server.', true, true );
          buttons.attr('disabled', false);
        }
        
        var start_count = 0;
        source.addEventListener('start', function(e){
          
          var result = JSON.parse(e.data);
          $( ProgressBar ).css({
              width : '0%'
          });
          if(start_count > 4) {
            event_add_log($( ResultsBox ), result.message, false, false );
            event_add_log ($( ResultsBox ),'Failed to proceed with importing after 5 attempts.', true, false );
            event_add_log ($( ResultsBox ),'Review the log for more information.', false, true );
            source.close();
            start_count = 0;
            return;
          } else {
            start_count++;
            event_add_log($( ResultsBox ), result.message, false, false);
          }
        });
        
        
        source.addEventListener('message' , function(e) {
          var result = JSON.parse( e.data );
          event_add_log($( ResultsBox ), result.message, false, false );
          $( ProgressBar ).css({
              width : result.progress + '%'
          });
          
        });
        
        source.addEventListener('close' , function(e) {
          var result = JSON.parse( e.data );
          
          event_add_log ($( ResultsBox ), result.message, false, true );
          $( ProgressBar ).css({
              width : result.progress + '%'
          });
          source.close();
          buttons.attr('disabled', false);
        });
        
        source.addEventListener('validation_error' , function(e) {
          var result = JSON.parse( e.data );
          event_add_log ($( ResultsBox ), result.message, true, false );
          $( ProgressBar ).css({
              width : result.progress + '%'
          });
        });
        
        source.addEventListener('validation_error_code' , function(e) {
          var result = JSON.parse( e.data );
          event_add_log ($( ResultsBox ), result.message, false, false, true );
          $( ProgressBar ).css({
              width : result.progress + '%'
          });
        });

        source.addEventListener('fatal' , function(e) {
          var result = JSON.parse( e.data );
          event_add_log($( ResultsBox ), result.message , true);
          
          /* Close the connection */
          source.close();
          event_add_log($( ResultsBox ), "Process halted.", false, true);
          buttons.attr('disabled', false);
        });
      });
    });

    function toggle_visibility(idx, idy) {
        var e = document.getElementById(idx);

        if(e.style.display == 'none') {
            e.style.display = 'block';
        }
        else {
            e.style.display = 'none';
        }

        var a = document.getElementById(idy);

        if(a.innerText == 'hide') {
            a.innerText = 'show';
        }
        else {
            a.innerText = 'hide';
        }
    }

</script>

<style type="text/css">

    .sse_results_wrapper{
        display:none;
        width:440px;
        margin-top: 10px;
        border: 1px solid rgb(204, 204, 204);
    }

    .sse_progress_wrapper{
        width:100%;
        height:20px;
        border-top:1px solid #CCC;
    }

    .results_box span{
        padding:3px 10px;
        display:block;
    }

    .sync_title {
        float: left;
        margin-right: 6px;
        font-size: large;
        font-weight: 600;
    }

    .sync_title_more {
        position:relative;
        top: 2px;
        font-size: smaller;
        font-weight: 700;
        color: gray;
    }

    .sync_image {
        position: relative;
        left: -4px;
        margin-top: 30px;
    }

    .sync_button_container {
        float: left;
        margin-top:25px;
        margin-right: 10px;
    }

    a.button.sync_button:hover {
        background: #458de3;
        border-color: #006799;
        color: #fff;
    }

    .sync_clear {
        clear: both;
    }

    .sse_results_visibility {
        position: relative;
        width:440px;
        display: none;
    }

    .a_visibility {
        float: right;
        outline: none;
        border-color: inherit;
        -webkit-box-shadow: none;
        box-shadow: none;
        text-decoration: none;
    }

    .a_visibility:active {
        outline: none;
        border-color: inherit;
        -webkit-box-shadow: none;
        box-shadow: none;
        text-decoration: none;
    }

    .a_visibility:focus {
        outline: none;
        border-color: inherit;
        -webkit-box-shadow: none;
        box-shadow: none;
        text-decoration: none;
    }

</style>


<div class="wrap">
  <p>Use this page to manually update information in WooCommerce and WordPress from Troly.</p>
    <div class="sync_title"><?php _e( 'Import', 'wp99234' ); ?></div><div class="sync_title_more">(<?php _e( 'Pull from Troly: this will create new or update existing records.', 'wp99234' ); ?>)</div>

    <div class="sync_image"><img src="<?php echo WP99234()->plugin_url() ?>/includes/admin/assets/images/subs_to_wp.png"></div>

    <div id="trigger_membership_import" class="sync_button_container">

        <?php $url = add_query_arg( array(
            'do_wp99234_import_membership_types' => 1
        ), admin_url( 'admin.php?page=wp99234' ) ); ?>

        <?php $import_memberships_url = add_query_arg( array(
            'action' => 'subs_import_memberships',
            'nonce'  => wp_create_nonce( 'subs_import_memberships' )
        ), admin_url( 'admin-ajax.php' ) ); ?>

        <a class="button sync_button background_process_button" data-troly_method="import" data-event_source="<?php echo esc_url_raw( $import_memberships_url ); ?>" href="javascript:void(0)"><?php _e( 'Import memberships / clubs', 'wp99234' ); ?></a>

    </div>

    <?php
    $can_see_product_import = false;

    if( get_option( 'wp99234_product_import_has_run' ) == true ){
        if( current_user_can( 'manage_wp99234_products' ) ){
            $can_see_product_import = true;
        }
    } else {
        $can_see_product_import = true;
    }

    ?>

    <?php if( $can_see_product_import ): ?>
        <div id="run_product_import" class="sync_button_container">

            <?php $import_products_url = add_query_arg( array(
                'action' => 'subs_import_products',
                'nonce'  => wp_create_nonce( 'subs_import_products' )
            ), admin_url( 'admin-ajax.php' ) ); ?>

            <a id="run_product_import_button" class="button sync_button background_process_button" data-troly_method="import" href="javascript:void(0)" data-event_source="<?php echo esc_url_raw( $import_products_url ); ?>"><?php _e( 'Import products', 'wp99234' ); ?></a>

        </div>
    <?php endif; ?>

    <?php

    //Users can only see the import button if they ran the initial import or if no import has been run.
    $can_see_user_import = false;

    if( get_option( 'wp99234_user_import_has_run' ) === true ) {
        if ( current_user_can( 'manage_wp99234_users' ) ) {
            $can_see_user_import = true;
        }
    } else {
        $can_see_user_import = true;
    }
    ?>

    <?php if( $can_see_user_import ): ?>
        <div id="run_user_import" class="sync_button_container">

            <?php $import_users_url = add_query_arg( array(
                'action' => 'subs_import_users',
                'nonce'  => wp_create_nonce( 'subs_import_users' )
            ), admin_url( 'admin-ajax.php' ) ); ?>

            <a id="run_user_import_button" class="button sync_button background_process_button" data-troly_method="import" data-event_source="<?php echo esc_url_raw( $import_users_url ); ?>" href="javascript:void(0)"><?php _e( 'Import customers', 'wp99234' ); ?></a>

        </div>
    <?php endif; ?>

    <div class="sync_clear">&nbsp;</div>

    <div id="sse_results_wrapper_import_id" class="sse_results_wrapper">
        <div id="sse_log_wrapper_import_id" class="sse_log_wrapper" style="width:100%;"></div>
        <div class="sse_progress_wrapper"></div>
    </div>

    <br />
    <br />
    <br />

    <div class="sync_title"><?php _e( 'Export', 'wp99234' ); ?></div><div class="sync_title_more">(<?php _e( 'Push to Troly: this will create new or update existing records.', 'wp99234' ); ?>)</div>

    <div class="sync_image"><img src="<?php echo WP99234()->plugin_url() ?>/includes/admin/assets/images/wp_to_subs.png"></div>

    <?php

    //Users can only see the import button if they ran the initial import or if no import has been run.
    $can_see_user_export = false;

    if( get_option( 'wp99234_user_export_has_run' ) === true ) {
        if ( current_user_can( 'manage_wp99234_users' ) ) {
            $can_see_user_export = true;
        }
    } else {
        $can_see_user_export = true;
    }
    ?>

    <?php if( $can_see_user_export ): ?>

        <div id="run_user_export" class="sync_button_container">

            <?php $export_users_url = add_query_arg( array(
                'action' => 'subs_export_users',
                'nonce'  => wp_create_nonce( 'subs_export_users' )
            ), admin_url( 'admin-ajax.php' ) ); ?>

            <a id="run_user_export_button" class="button sync_button background_process_button" data-troly_method="export" data-event_source="<?php echo esc_url_raw( $export_users_url ); ?>" href="javascript:void(0)"><?php _e( 'Export customers', 'wp99234' ); ?></a>

        </div>

    <?php endif; ?>

    <?php

    //Users can only see the import button if they ran the initial import or if no import has been run.
    $can_see_product_export = false;

    if( get_option( 'wp99234_product_export_has_run' ) === true ) {
        if ( current_user_can( 'manage_wp99234_users' ) ) {
            $can_see_product_export = true;
        }
    } else {
        $can_see_product_export = true;
    }
    ?>

    <?php if( $can_see_user_export ): ?>

        <div id="run_product_export" class="sync_button_container">

            <?php $export_products_url = add_query_arg( array(
                'action' => 'subs_export_products',
                'nonce'  => wp_create_nonce( 'subs_export_products' )
            ), admin_url( 'admin-ajax.php' ) ); ?>

            <a id="run_product_export_button" class="button sync_button background_process_button" data-troly_method="export" data-event_source="<?php echo esc_url_raw( $export_products_url ); ?>" href="javascript:void(0)"><?php _e( 'Export products', 'wp99234' ); ?></a>

        </div>

    <?php endif; ?>

    <div class="sync_clear">&nbsp;</div>

    <div id="sse_results_wrapper_export_id" class="sse_results_wrapper">
        <div id="sse_log_wrapper_export_id" class="sse_log_wrapper" style="width:100%;"></div>
        <div class="sse_progress_wrapper"></div>
    </div>
    <div id="sse_results_visibility_export_id" class="sse_results_visibility">
        <a class="a_visibility" id="sse_visibility_export_a_id" onclick="toggle_visibility('sse_log_wrapper_export_id', 'sse_visibility_export_a_id');" href="javascript:void(0)">hide</a>
    </div>

</div>