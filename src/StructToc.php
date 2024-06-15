<?php
namespace WebBookScraper;
class StructToc
{
    public $title = "";
    public $url="";

    public function __construct($title, $url)
    {
        $this->title = $title;
        $this->url = $url;
    }
}