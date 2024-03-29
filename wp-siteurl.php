<?php

/**
 Plugin Name: Site URL
 Plugin URI: https://github.com/benignware-labs/wp-siteurl
 Description: Fix Site URL Conflicts
 Version: 0.0.7
 Author: Rafael Nowrotek, Benignware
 Author URI: http://benignware.com
 License: MIT
*/

require_once 'lib/settings.php';


function wp_siteurl_env() {
  return in_array($_SERVER['SERVER_NAME'], array(
    'localhost',
    '127.0.0.1',
    '10.37.129.2',
    '10.0.2.2'
  )) ? 'development' : 'production';
}

function wp_siteurl_get_options() {
  return apply_filters('siteurl_options', array_merge(
    array(
      'environment' => array(
        'production' => 1,
        'development' => 1
      ),
      'uploads' => 'local'
    ), get_option('siteurl_options') ?: array()
  ));
}

function wp_siteurl_is_enabled() {
  $env = wp_siteurl_env();
  $options = wp_siteurl_get_options();

  $is_enabled = ($env === 'development' && $options['environment']['development'])
    || ($env === 'production' && $options['environment']['production']);

  return $is_enabled;
}

function wp_siteurl_get_baseurl() {
  $abs_path = rtrim(ABSPATH ? ABSPATH : get_home_path(), '/');

  if (file_exists(dirname($abs_path) . DIRECTORY_SEPARATOR . 'wp-config.php')) {
    $config_file = dirname($abs_path) . DIRECTORY_SEPARATOR . 'wp-config.php';
  } else {
    $config_file = $abs_path . DIRECTORY_SEPARATOR . 'wp-config.php';
  }

  $document_root = stripslashes($_SERVER['DOCUMENT_ROOT']);

  $protocol = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' || $_SERVER['SERVER_PORT'] == 443
    ? 'https'
    : 'http';
  $hostname = $_SERVER['HTTP_HOST'];
  $pathname = rtrim(dirname(str_replace($document_root, '', $config_file)), './\\');

  $base_url = $protocol . '://' . $hostname . ($pathname ? '/' : '') . $pathname;

  return $base_url;
}

function wp_siteurl_get_option($name = 'siteurl') {
  global $wpdb;

  $opt = $wpdb->get_row("SELECT * FROM $wpdb->options WHERE option_name = '$name'");

  if ($opt) {
    return $opt->option_value;
  }

  return null;
}

function wp_siteurl_is_valid() {
  $siteurl = wp_siteurl_get_option('siteurl');
  $baseurl = wp_siteurl_get_baseurl();

  return $siteurl === $baseurl;
}

function wp_siteurl_url_replace($url, $search_url = '', $replace_url = '') {
  if (!$search_url) {
    return $url;
  }

  $protocol_pattern = "https?\:\/\/";
  $search_uri = preg_replace("~^$protocol_pattern~", "", $search_url);
  $siteurl_pattern = "~^($protocol_pattern)?" . preg_quote($search_uri) . "~";

  if (preg_match($siteurl_pattern, $url)) {
    $replace_uri = preg_replace("~^$protocol_pattern~", "", $replace_url);
    $replace = preg_match("~^$protocol_pattern~", $url) ? $replace_url : $replace_uri;
    $new_url = preg_replace($siteurl_pattern, $replace, $url);
  } else {
    $new_url = $url;
  }

  return $new_url;
}

function wp_siteurl_get_site_url($url) {
  if (!wp_siteurl_is_enabled()) {
    return $url;
  }

  $siteurl = wp_siteurl_get_option('siteurl');
  $baseurl = wp_siteurl_get_baseurl();

  $siteurl_canonical = rtrim($siteurl, '/');
  $baseurl_canonical = rtrim($baseurl, '/');

  $result = wp_siteurl_url_replace($url, $siteurl_canonical, $baseurl_canonical);

  if (in_array($result, [$siteurl_canonical, $baseurl_canonical]) && preg_match('~\/$~', $url)) {
    $result = rtrim($result, '/') . '/';
  }

  return $result;
}

add_filter( 'option_siteurl', 'wp_siteurl_get_site_url', 100 );
add_filter( 'site_url', 'wp_siteurl_get_site_url', 100);
add_filter( 'admin_url', 'wp_siteurl_get_site_url', 100);
add_filter( 'content_url', 'wp_siteurl_get_site_url', 100);
add_filter( 'plugins_url', 'wp_siteurl_get_site_url', 100);
add_filter( 'script_loader_src', 'wp_siteurl_get_site_url', 100 );
add_filter( 'style_loader_src', 'wp_siteurl_get_site_url', 100 );

function wp_siteurl_get_home_url($url) {
  if (!wp_siteurl_is_enabled()) {
    return $url;
  }

  $homeurl = wp_siteurl_get_option('home');
  $baseurl = wp_siteurl_get_baseurl();

  $homeurl_canonical = rtrim($homeurl, '/');
  $baseurl_canonical = rtrim($baseurl, '/');

  $result = wp_siteurl_url_replace($url, $homeurl_canonical, $baseurl_canonical);

  if (in_array($result, [$homeurl_canonical, $baseurl_canonical]) && preg_match('~\/$~', $url)) {
    $result = rtrim($result, '/') . '/';
  }

  return $result;
}

add_filter( 'option_home', 'wp_siteurl_get_home_url', 1 );
add_filter( 'home_url', 'wp_siteurl_get_home_url', 1);

add_filter( 'upload_dir', function($paths) {
  if (!wp_siteurl_is_enabled()) {
    return $paths;
  }

  $options = wp_siteurl_get_options();

  if ($options['uploads'] === 'local') {
    $paths = array_merge($paths); // Copy of array really needed?
    $paths['url'] = wp_siteurl_get_site_url($paths['url']);
    $paths['baseurl'] = wp_siteurl_get_site_url($paths['baseurl']);
  }

  return $paths;
}, 1, 2);


add_filter( 'wp_resource_hints', function($urls) {
  if (!wp_siteurl_is_enabled()) {
    return $urls;
  }

  $urls = array_map('wp_siteurl_get_site_url', $urls);

  return $urls;
}, 1);


function wp_siteurl_sanitize_content($content) {
  if (!wp_siteurl_is_enabled()) {
    return $content;
  }

  if (is_array(($content))) {
    $content = array_map(function($value) {
      return wp_siteurl_sanitize_content($value);
    }, $content);

    return $content;
  }

  $siteurl = wp_siteurl_get_option('siteurl');
  $baseurl = wp_siteurl_get_baseurl();
  $siteurl_canonical = rtrim($siteurl, '/');
  $baseurl_canonical = rtrim($baseurl, '/');
  $site_url_canonical_without_prototcol = preg_replace('/^https?/', '', $siteurl_canonical);

  $pattern = sprintf('/https?%s/', preg_quote($site_url_canonical_without_prototcol, '/'));
  $content = preg_replace($pattern, $baseurl_canonical, $content);

  return $content;
}

add_filter( 'the_content', 'wp_siteurl_sanitize_content', 5 );
add_filter( 'content_edit_pre', 'wp_siteurl_sanitize_content', 5 );
add_filter( 'get_avatar_url', 'wp_siteurl_sanitize_content', 5 );

// add_filter( 'wp_get_attachment_image', 'wp_siteurl_sanitize_content', PHP_INT_MAX );


add_filter('wp_nav_menu_objects', function($items) {
  foreach($items as $item) {
    $item->url = wp_siteurl_sanitize_content($item->url);
  }
  return $items;
} , 10);



/* Widget support is not stable yet */
// https://philipnewcomer.net/2014/06/filter-output-wordpress-widget/
function wp_siteurl_dynamic_sidebar_params() {
  global $wp_registered_widgets;
  $original_callback_params = func_get_args();
  $widget_id = $original_callback_params[0]['widget_id'];
  // echo "PHP" . phpversion();
  // $original_callback = $wp_registered_widgets[ $widget_id ]['original_callback'];
  // $wp_registered_widgets[ $widget_id ]['callback'] = $original_callback;

  // $widget_id_base = $wp_registered_widgets[ $widget_id ]['callback'][0]->id_base;
  $GLOBALS['WP_SITEURL_WIDGET_FILTER_ORIGINAL_CALLBACK'] = $wp_registered_widgets[ $widget_id ]['callback'];
  $wp_registered_widgets[ $widget_id ]['callback'] = 'wp_bootstrap_widget_callback_function';
}
// add_filter( 'dynamic_sidebar_params', 'wp_siteurl_dynamic_sidebar_params' );

function wp_siteurl_widget_callback() {
  global $wp_registered_widgets;
  $original_callback_params = func_get_args();
  $widget_id = $original_callback_params[0]['widget_id'];
  $original_callback = $GLOBALS['WP_SITEURL_WIDGET_FILTER_ORIGINAL_CALLBACK'];
  $wp_registered_widgets[ $widget_id ]['callback'] = $original_callback;
  $widget_id_base = $wp_registered_widgets[ $widget_id ]['callback'][0]->id_base;
  if ( is_callable( $original_callback ) ) {
    ob_start();
    call_user_func_array( $original_callback, $original_callback_params );
    $widget_output = ob_get_clean();
    $widget_output = wp_siteurl_sanitize_content($widget_output);
    echo $widget_output;
  }
}


add_filter('wp_get_attachment_url', function($url, $post_id = null) {
  if (!wp_siteurl_is_enabled()) {
    return;
  }

  $siteurl = wp_siteurl_get_option('siteurl');
  $baseurl = wp_siteurl_get_baseurl();

  if ($siteurl !== $baseurl) {
    $home_path = rtrim(ABSPATH ? ABSPATH : get_home_path(), '/');
    $upload_dir = wp_get_upload_dir();
    $url_info = parse_url($url);
    $url_path = $url_info['path'];

    $upload_dir_path = str_replace($home_path, '', $upload_dir['basedir']);
    $attachment_src_path = str_replace($upload_dir_path, '', $url_path);
    $attachment_src = $home_path . $upload_dir_path . $attachment_src_path;

    if (!file_exists($attachment_src)) {
      $url = rtrim($siteurl, '/') . $url_path;
    }
  }

  return $url;
});


/* Third Party Support */
add_filter( 'wpml_home_url', 'wp_siteurl_get_home_url', 1 );
add_filter( 'wpml_url_converter_get_abs_home', 'wp_siteurl_get_home_url', 1 );


/* Admin */
add_action( 'admin_notices', function() {
  if (!wp_siteurl_is_enabled()) {
    return;
  }

  $id = uniqid();
  $siteurl = wp_siteurl_get_option('siteurl');
  $baseurl = wp_siteurl_get_baseurl();
  $message = "Site URL <code>$siteurl</code> conflicts with Base URL <code>$baseurl</code>";

  if ($siteurl !== $baseurl) : ?>
    <div id="siteurl-notice-<?= $id; ?>" class="notice notice-warning is-dismissible">
      <h3 class="notice-title">Site URL Conflict</h3>
      <p class="notice-message">
        <?php _e( $message, 'siteurl' ); ?>
      </p>

      <?php if (current_user_can('manage_options')) : ?>
        <div class="notice-actions" style="margin-bottom: 10px">
          <button class="notice-action button" style="text-align: center">
            Resolve
          </button>
          <span class="spinner" style="vertical-align: middle; float: none; padding: 4px 0; background-position: center; margin: 0 4px"></span>
        </div>
        <script>
          (function($) {

            var
              $notice = $('#siteurl-notice-<?= $id; ?>'),
              $button = $notice.find('.notice-action'),
              $spinner = $notice.find('.spinner'),
              $message = $notice.find('.notice-message');

            $button.click(function(e) {
              if ($notice.hasClass('notice-warning') || $notice.hasClass('notice-error')) {
                $button.attr('disabled', 'disabled');
                $button.addClass('is-pending');
                $spinner.addClass('is-active');
                $.post(
                  ajaxurl,
                  {
                    'action': 'siteurl_replace'
                  }
                ).done(function(response) {
                  $message.html(response.message);
                  $notice.addClass('notice-success');
                  $button.html("Done");
                }).fail(function(xhr, status, error) {
                    // Handle error
                    $message.html(xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message || error);
                    $notice.addClass('notice-error');
                    $button.html("Try again");
                }).always(function() {
                  // Set button states
                  $button.removeAttr('disabled');
                  $button.removeClass('is-pending');
                  $spinner.removeClass('is-active');
                  $notice.removeClass('notice-warning');

                })
              } else {
                $notice.find('.notice-dismiss').trigger('click');
              }
            });
          })(jQuery);
        </script>
      <?php endif; ?>
    </div>
  <?php endif;
});


function wp_siteurl_get_mysql_error() {
  global $wpdb;

  $message = '';

  if ($wpdb->last_error !== '') {
    $str = htmlspecialchars( $wpdb->last_result, ENT_QUOTES );
    $query = htmlspecialchars( $wpdb->last_query, ENT_QUOTES );

    return $str;
  };

  return false;
}

function wp_siteurl_ajax_replace() {
  // Handle request then generate response using WP_Ajax_Response
  if (current_user_can('manage_options')) {
    $baseurl = wp_siteurl_get_baseurl();
    $siteurl = wp_siteurl_get_option('siteurl');
    $result = wp_siteurl_sql_replace_url($siteurl, $baseurl);
    $success = wp_siteurl_get_baseurl() == wp_siteurl_get_option('siteurl');
    $error = wp_siteurl_get_mysql_error();
    if ($success) {
      wp_send_json( array(
        'message' => 'URL was successfully replaced',
        'success' => $success,
        'result' => $result,
        'error' => $error
      ) );
    } else {
      http_response_code( 500 );
      wp_send_json_error( array(
        'message' => 'URL could not be replaced',
        'success' => $success,
        'result' => $result,
        'error' => $error
      ), 500 );
    }
  } else {
    http_response_code( 500 );
    wp_send_json_error( 'You are not permitted to access this action', 403 );
  }

  wp_die();
}
add_action( 'wp_ajax_siteurl_replace', 'wp_siteurl_ajax_replace' );


// Replace

function wp_siteurl_sql_replace_url($oldurl, $newurl) {
  global $wpdb;

  // TODO: Custom Tables (WPML etc.)
  $sql[]= "UPDATE wp_posts SET guid = replace(guid, '$oldurl','$newurl')";
  $sql[]= "UPDATE wp_posts SET post_content = replace(post_content, 'src=\"$oldurl', 'src=\"$newurl')";
  $sql[]= "UPDATE wp_posts SET post_content = replace(post_content, 'href=\"$oldurl', 'href=\"$newurl')";
  $sql[]= "UPDATE wp_links SET link_url = replace(link_url, '$oldurl', '$newurl')";
  $sql[]= "UPDATE wp_links SET link_image = replace(link_image, '$oldurl', '$newurl')";
  $sql[]= "UPDATE wp_postmeta SET meta_value = replace(meta_value, '$oldurl', '$newurl')";
  $sql[]= "UPDATE wp_usermeta SET meta_value = replace(meta_value, '$oldurl', '$newurl')";
  $sql[]= "UPDATE wp_options SET option_value = replace(option_value, '$oldurl', '$newurl')";

  try {
    foreach ($sql as $query) {
      $result = $wpdb->query( $query );
    }
  } catch (Exception $e) {
    $result = $e->message;
  }
  return $result;
}


require_once 'int/acf.php';

?>
