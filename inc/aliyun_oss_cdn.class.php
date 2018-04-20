<?php
class aliyun_oss_cdn{
    public static function instance() {
        new self();
    }
    public function __construct() {
//        ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
        # add admin page and rewrite defaults
        if(is_admin()) {
            add_action('admin_menu',
                [
                    __CLASS__,
                    'admin_menu',
                ]
            );
            add_action('admin_init',
                [
                    __CLASS__,
                    'register_settings',
                ]
            );
        }

        add_action(
            'template_redirect',
            [
                __CLASS__,
                'redirect_hook',
            ]
        );
    }
    public  static function get_options() {
        return wp_parse_args(
            get_option('aliyun_oss_cdn'),
            [
                'cdn_url'            => get_option('home'),
                'dirs'           => 'wp-content,wp-includes',
                'includes'=>      '.css,.js,.gif,.png,.jpg,.ico,.ttf,.otf,.woff,.less,.woff2,.mp4',
                'mount_directory'=>false,
                'last_modify'=>false,
                'key'=>false,
                'secret'=>false,
                'endpoint'=>false,
                'bucket'=>false,
            ]

        );
    }
    public  static function get_options_formated() {
        $options=self::get_options();
        $options['includes']=array_map('trim', explode(',', $options['includes']));
        $options['dirs']=array_map('trim', explode(',', $options['dirs']));
        $options['cdn_url']='//'.str_replace('http://','',str_replace('https://','',trim($options['cdn_url'])));
        return $options;
    }



    public static function  handle_uninstall_hook() {
        delete_option('aliyun_oss_cdn');
    }

    public static function   create_plugin_database_table()     {
        $installed_ver = get_option( "aliyun_oss_cdn_version" );
        if ( $installed_ver == ALIYUN_OSS_CDN_VERSION ) {
            $table_name = $wpdb->prefix . 'aliyun_oss_cdn';
            $sql = "CREATE TABLE $table_name (
                id INT(9) NOT NULL AUTO_INCREMENT,
                upload datetime DEFAULT '0000-00-00 00:00:00' NOT NULL, 
                failed INT(2) DEFAULT 0,
                path varchar(500) DEFAULT '' NOT NULL,
                PRIMARY KEY  (id)
            );";

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
            update_option( "aliyun_oss_cdn_version", ALIYUN_OSS_CDN_VERSION );
        }



    }

    public static function  handle_install_hook() {

        $options=self::get_options();
        $options['last_modify']=time();
        add_option(
            'aliyun_oss_cdn',
            $options
        );
        self::create_plugin_database_table();
    }






    public static function register_settings(){
        register_setting(
            'aliyun_oss_cdn',
            'aliyun_oss_cdn'
        );
    }

    public static function redirect_hook(){
        $url_rewrite=new aliyun_oss_cdn_url_rewrite(self::get_options_formated());
        ob_start(array(&$url_rewrite,'cdn_rewrite'));
    }











    public static function admin_menu()
    {
        add_options_page(
            'Aliyun OSS CDN Settings',
            'Aliyun OSS CDN',
            'manage_options',
            'aliyun-oss-cdn',
            [
                __CLASS__,
                'settings_page',
            ]
        );

    }

    public static function settings_page(){
        $options = aliyun_oss_cdn::get_options();
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        $b=new aliyun_oss_cdn_upload(aliyun_oss_cdn::get_options_formated());
        if(isset($_POST['path'])&& $_POST['path']){
            $path=trim(trim($_POST['path']),'/');
            $b->purge(ABSPATH.$path);
        }

        if(isset($_POST['upload-immediately'])&&$_POST['upload-immediately']){
            $b->upload();
        }

        $uploads_lists=$b->get_files_since_last_modify();



        ?>
        <div class="wrap">
            <h1>Aliyun OSS CDN</h1>
            <h2>Status</h2>
            <p class=" "><?php
                if(!$options['key']||!$options['secret']||!$options['endpoint']){
                    echo 'Setup the Aliyun certificate to make this work.';
                }else{
                    try {
                        $ossClient = new \OSS\OssClient($options['key'], $options['secret'], $options['endpoint']);
                    } catch (OssException $e) {
//                    print $e->getMessage();
                        echo 'Setup the Aliyun certificate to make this work.';
                    }
                }

                ?></p>
            <p>Files list needs to be uploaded on next cronjob:<?php echo count($uploads_lists); ?></p>
            <form method="post"  >
                <button value="show-uploads" name="show-uploads" >Show uploads list</button>
                <button value="upload-immediately" name="upload-immediately" >Upload immediately</button>
            </form>
            <ul>
            <?php
            if(isset($_POST['show-uploads'])&& $_POST['show-uploads']){
                foreach ($uploads_lists as $file=>$time):
                    echo '<li>'.str_replace(ABSPATH,'',$file).'</li>';
                endforeach;
            }
            ?>
            </ul>
            <h2>Purge</h2>
            <p>Manually purge a resource if it's not successful upload to OSS.</p>
            <form method="post"  >
                <table class="form-table">
                    <tr valign="top">
                        <th>The file path to purge:</th>
                        <td>
                            <input type="text" name="path" />
                            <p>For example: /wp-content/test.css</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th></th>
                        <td>
                            <button value="Submit" name="Purge" >Purge</button>
                        </td>
                    </tr>
                </table>
            </form>

            <h2>Configure</h2>
            <form method="post" action="options.php">
                <?php settings_fields( 'aliyun_oss_cdn' ); ?>
                <?php do_settings_sections( 'aliyun_oss_cdn' ); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th>CDN domain name:</th>
                        <td>
                            <input type="text" name="aliyun_oss_cdn[cdn_url]" value="<?php echo $options['cdn_url']; ?>"/>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th>Files types to upload:</th>
                        <td>
                            <input type="text" name="aliyun_oss_cdn[dirs]" value="<?php echo $options['dirs']; ?>"/>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th>Directories to be upload:</th>
                        <td>
                            <input type="text" name="aliyun_oss_cdn[includes]" value="<?php echo $options['includes']; ?>"/>
                        </td>
                    </tr>

                    <?php /**
                    <tr valign="top">
                    <th>Mount Directory:</th>
                    <td>
                    <input type="text" name="aliyun_oss_cdn[mount_directory]" value="<?php echo $options['mount_directory']; ?>"/>
                    </td>
                    </tr>
                     **/?>

                    <h2>Aliyun OSS</h2>
                    <tr valign="top">
                        <th>Key:</th>
                        <td>
                            <input type="text" name="aliyun_oss_cdn[key]" value="<?php echo $options['key']; ?>"/>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th>Secret:</th>
                        <td>
                            <input type="password" name="aliyun_oss_cdn[secret]" value="<?php echo $options['secret']; ?>"/>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th>Endpoint:</th>
                        <td>
                            <input type="text" name="aliyun_oss_cdn[endpoint]" value="<?php echo $options['endpoint']; ?>"/>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th>Bucket:</th>
                        <td>
                            <input type="text" name="aliyun_oss_cdn[bucket]" value="<?php echo $options['bucket']; ?>"/>
                        </td>
                    </tr>



                    <tr valign="top">
                        <th>Upload the files since:</th>
                        <td>
                            <input type="text" name="aliyun_oss_cdn[last_modify]" value="<?php echo $options['last_modify']; ?>"/>
                            <p>The timestamp now is <?php echo time(); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>




        </div>
        <?php
    }


}