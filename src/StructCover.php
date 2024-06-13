<?php
namespace Arkhee\WebBookScraper;
use Arkhee\WebBookScraper\StructToc;
class StructCover
{
    public $title = "";
    public $author = "";

    /**
     * @var StructToc[] $toc
     */
    public $toc = array();
    public $url="";
    public $illustration = "";
    public function addToc($title, $url)
    {
        $this->toc[] = new StructToc($title, $url);
    }
}