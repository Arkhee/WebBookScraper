<?php
namespace WebBookScraper;
use WebBookScraper\Scraper;
class WebBookScraper
{
    private $url = "";
    /**
     * @var StructCover $cover
     */
    public $cover = null;
    /**
     * @var StructChapter[] $chapters
     */
    public $chapters = array();

    /**
     * @param $url
     * @return void
     */
    public function __construct($url)
    {
        $this->url = $url;
    }
    public function getBook()
    {
        $this->cover = Scraper::Toc($this->url);
        foreach($this->cover->toc as $toc)
        {
            $this->chapters[] = Scraper::Chapter($toc->url);
            // DEBUG
            break;
        }
    }
}
