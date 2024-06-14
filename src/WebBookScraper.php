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

    private $log = array();
    private $debug = false;
    private $logfile = __DIR__.'/log.txt';

    /**
     * @param $url
     * @return void
     */
    public function __construct($url,$debug=false)
    {
        $this->url = $url;
        $this->debug = $debug;
        if($this->debug)
        {
            file_put_contents($this->logfile,"");
        }
    }

    private function addLog($comment, $url="",$duration=0)
    {
        $this->log[]= array(
            "comment" => $comment,
            "url" => $url,
            "duration" => $duration
        );
        if($this->debug)
        {
            file_put_contents($this->logfile,implode("\t",$this->log).PHP_EOL,FILE_APPEND);
        }
    }

    public function getBook()
    {
        $this->addLog("Beginning scraping book");
        $begin = microtime(true);
        $this->cover = Scraper::Toc($this->url);
        $end = microtime(true);
        $this->addLog("Main page",$this->url,$end-$begin);
        foreach($this->cover->toc as $index => $toc)
        {
            $begin = microtime(true);
            $this->chapters[] = Scraper::Chapter($toc->url);
            $end = microtime(true);
            $this->addLog("Chapter ".($index+1)." on ".count($this->cover->toc),$toc->url,$end-$begin);
        }
    }
}
