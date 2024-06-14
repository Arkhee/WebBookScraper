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
        //$url = $arrUrl["scheme"]."://".$arrUrl["host"].$arrUrl["path"];
        $this->url = $url;
        $resource = pathinfo($arrUrl["path"]);
        $extension = $resource['extension'];
        $this->filename = "img_".md5($url).".".$resource['extension'];
        /*
        $this->resourceFolder= $resourceFolder;
        if(!file_exists($resourceFolder))
        {
            mkdir($resourceFolder);
        }
        // check if last character is a slash
        if(substr($this->resourceFolder,-1)!=="/")
        {
            $this->resourceFolder .= "/";
        }
        file_put_contents($this->getRessourceFileName(),file_get_contents($url));
        */
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