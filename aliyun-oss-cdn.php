<?php
/**
 * Plugin Name: Aliyun OSS CDN
 * Plugin URI: https://dengruo.com
 * Description: W3TC is a great plugin, but in your site hosted in China, you have to use the Aliyun OSS+CDN to replace AWS S3+cloudfront, this plugin force to make solution work.
 * Author: Ruo <mail@dengruo.com>
 * Version: 0.0.1
 * Text Domain: cdn_oss_cdn
 */







define('ALIYUN_OSS_CDN_DIR', dirname(__FILE__));
define('ALIYUN_OSS_CDN_VERSION','0.0.1');

/* autoload init */
function aliyun_oss_cdn_autoload($class) {
    require_once(ALIYUN_OSS_CDN_DIR.'/inc/aliyun_oss_cdn.class.php');
    require_once(ALIYUN_OSS_CDN_DIR.'/inc/aliyun_oss_cdn_url_rewrite.class.php');
    require_once(ALIYUN_OSS_CDN_DIR.'/inc/aliyun_oss_cdn_upload.class.php');
    require_once(ALIYUN_OSS_CDN_DIR.'/lib/aliyun-oss-php-sdk-2.3.0.phar');

}
spl_autoload_register('aliyun_oss_cdn_autoload');
/* loader */
add_action(
    'plugins_loaded',
    [
        'aliyun_oss_cdn',
        'instance',
    ]
);

/* uninstall */
register_uninstall_hook(
    __FILE__,
    ['aliyun_oss_cdn','handle_uninstall_hook']
);

/* activation */
register_activation_hook(
    __FILE__,
    ['aliyun_oss_cdn','handle_install_hook']
);


/**
 * Cron jobs
 */
// create a scheduled event (if it does not exist already)
function aliyun_oss_cdn_activation() {
    if( !wp_next_scheduled( 'aliyun_oss_cdn_cronjob' ) ) {
        wp_schedule_event( time(), 'hourly', 'aliyun_oss_cdn_cronjob' );
    }
}
// and make sure it's called whenever WordPress loads
add_action('wp', 'aliyun_oss_cdn_activation');
// unschedule event upon plugin deactivation
function aliyun_oss_cdn_deactivate() {
    // find out when the last event was scheduled
    $timestamp = wp_next_scheduled ('aliyun_oss_cdn_cronjob');
    // unschedule previous event if any
    wp_unschedule_event ($timestamp, 'aliyun_oss_cdn_cronjob');
}
register_deactivation_hook (__FILE__, 'aliyun_oss_cdn_deactivate');

// here's the function we'd like to call with our cron job
function aliyun_oss_cdn_cronjob_function() {
    $b=new aliyun_oss_cdn_upload(aliyun_oss_cdn::get_options_formated());
    $b->upload();
}

// hook that function onto our scheduled event:
add_action ('aliyun_oss_cdn_cronjob', 'aliyun_oss_cdn_cronjob_function');