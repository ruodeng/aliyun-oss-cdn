<?php

/**
 * Class aliyun_oss_cdn_upload
 * Upload the files to aliyun OSS
 */
class aliyun_oss_cdn_upload{
//find files need to be uploaded
//find
    var $last_modify=false;
    var $dir=null;
    var $includes=null;
    var $files=[];
    var $mount_directory=null;
    var $key=null;
    var $secret=null;
    var $endpoint=null;
    var $bucket=null;
    var $enable=true;
    function __construct($options)
    {
        $this->last_modify=$options['last_modify'];
        $this->dirs    =$options['dirs'];
        $this->includes=$options['includes'];
        $this->mount_directory=$options['mount_directory'];
        $this->key=$options['key'];
        $this->secret=$options['secret'];
        $this->endpoint=$options['endpoint'];
        $this->bucket=$options['bucket'];
        if(!$this->key || !$this->secret||!$this->endpoint||!$this->bucket){
            $this->enable=false;
        }
    }

    public function search_files($dir){
        $files = scandir($dir);
        $ignored = array(
            '.',
            '..',
            '.svn',
            '.htaccess',
            '.git',
            '.DS_Store',
            'CVS',
            'Thumbs.db',
            'desktop.ini');
        foreach($files as $key => $value){
            $path = realpath($dir.DIRECTORY_SEPARATOR.$value);
            if(!is_dir($path)) {
                if (in_array($path, $ignored)) continue;
                $pathinfo=pathinfo($path);
                if(isset($pathinfo['extension'])  && filemtime($path) >= $this->last_modify && in_array('.'.$pathinfo['extension'],$this->includes)){
                    $this->files[$path] = filemtime($path);
                }
            } else if($value != "." && $value != "..") {
                $this->search_files($path);
            }
        }
    }
    public function get_files_since_last_modify(){
        foreach($this->dirs as $dir){
            $this->search_files(ABSPATH.'/'.$dir);
        }
        asort($this->files);
        return $this->files;
    }

    function purge($file){
        if(!$this->enable) return false;
        $this->files=[$file=>time()];
        $this->upload_via_aliyun_php_sdk(true);
        //On the aliyun OSS can enable purge CDN when OSS files update option
    }
    function upload(){
        if(!$this->enable) return false;
        $this->get_files_since_last_modify();
        $this->upload_via_aliyun_php_sdk();
    }
    function upload_via_aliyun_php_sdk($purge=false){
        try {
            $ossClient = new \OSS\OssClient($this->key, $this->secret, $this->endpoint);
        } catch (OssException $e) {
            print $e->getMessage();
        }
        $count=0;
        foreach($this->files as $file=>$time){
            $count++;
            if ($count>200){
                return true;
            }
            $target= str_replace(ABSPATH,'',$file);
            $ossClient->uploadFile($this->bucket,$target,$file);


            if(!$purge &&  $time>$this->last_modify   && $time){
                $this->last_modify=$time;
                $options=get_option('aliyun_oss_cdn');
                $options['last_modify']=$this->last_modify;
                update_option('aliyun_oss_cdn',$options);
            }
        }
    }

    /**
     * can't work at this moment
     */
    function upload_via_mount(){
//        var_dump($this->files);
        echo $this->mount_directory;
        $file='/home/uutom/public_html/wp-includes/js/crop/cropper.js';
        $target=$this->mount_directory.'/'.str_replace(ABSPATH,'',$file);
        $path=pathinfo($target);//
        var_dump($path['dirname']);

//        echo copy($file,$target);
        var_dump($path['dirname']);
        var_dump(file_exists($path['dirname']));
        mkdir('/oss/uutom/wp-includes/js',0777,true);
        if (!file_exists($path['dirname'])) {
            mkdir($path['dirname'], 0777, true);
        }
//        if (!copy($file,$target)) {
//            echo "copy failed \n";
//        }
    }



}