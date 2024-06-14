<?php
namespace WebBookScraper;
use WebBookScraper\StructExternalResource;
class StructChapter
{
    public $title = "";
    public $content = "";
    public $url="";
    /**
     * @var StructExternalResource[] $externalResources
     */
    public $externalResources = array();
    public function getExternalResources():array
    {
        return $this->externalResources;
    }
    public function addExternalResource($url):string
    {
        $resource = new StructExternalResource($url);
        $this->externalResources[] = $resource;
        return $resource->getResourceName();
    }
}