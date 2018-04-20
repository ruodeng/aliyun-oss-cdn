<?php

/**
 * Class aliyun_oss_cdn_url_rewrite
 * Rewrite the URL to CDN url
 */
class aliyun_oss_cdn_url_rewrite {
    var $site_url=null;
    var $cdn_url =null;
    var $dirs=null;
    var $includes=null;
    var $enable=true;
    function __construct($options)
    {
        $this->site_url=site_url();
        $this->cdn_url =$options['cdn_url'];
        $this->dirs    =$options['dirs'];
        $this->includes=$options['includes'];
        if(!$this->cdn_url){
            $this->enable=false;
        }
    }
    protected function include_asset(&$asset){
        foreach ($this->includes as $include) {
            if (!!$include && stristr($asset, $include) != false) {
                return true;
            }
        }
        return false;
    }
    function cdn_rewrite_url(&$asset){
        if (!$this->include_asset($asset[0])) {
            return $asset[0];
        }
        return str_replace($this->site_url,$this->cdn_url,$asset[0]);
    }
    public function cdn_rewrite($html){
        if(!$this->enable){
            return $html;
        }
        $site_url=site_url();
        $dirs=$this->dirs == '' || count($this->dirs) < 1 ?'wp\-content|wp\-includes':implode('|', array_map('quotemeta', $this->dirs));
        $regex_rule = '#(?<=[(\"\'])'.str_replace('/','\/',quotemeta($site_url));
        $regex_rule .= '\/(?:((?:'.$dirs.')[^\"\')]+))(?=[\"\')])#';
        $cdn_html = preg_replace_callback($regex_rule, "self::cdn_rewrite_url" , $html);
        return $cdn_html;
    }
}