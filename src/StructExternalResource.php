<?php
namespace WebBookScraper;

class StructExternalResource
{
    public $url;
    public $filename;
    public $extension = "";
    //public  $resourceFolder="";
    public function __construct($url /* ,$resourceFolder */)
    {
        $arrUrl = parse_url($url);
        $url = $arrUrl["scheme"]."://".$arrUrl["host"].$arrUrl["path"];
        $this->url = $url;
        $resource = pathinfo($arrUrl["path"]);
        $this->extension = $resource['extension'];
        $this->filename = "img_".md5($url).".".$this->extension;
    }

    /*
    public function getRessourceFileName():string
    {
        return $this->resourceFolder."/".$this->filename;
    }
    */
    public function getResourceName():string
    {
        return $this->filename;
    }

    public function getResourceURL():string
    {
        return $this->url;
    }
}
