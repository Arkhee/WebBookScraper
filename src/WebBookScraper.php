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
    private $cacheDir = "";
    private $cacheActive = false;
    /**
     * @param $url
     * @return void
     */
    public function __construct($url,$debug=false)
    {
        $this->url = $url;
        $this->debug = $debug;
        $this->cacheDir = sys_get_temp_dir()."/webbookscraper_cache";
        if($this->debug)
        {
            $this->logfile = sys_get_temp_dir().'/'.uniqid("webscaper_").".log";
            file_put_contents($this->logfile,"");
        }
    }

    public function clearCache()
    {
        if(file_exists($this->cacheDir))
        {
            $this->RmDir($this->cacheDir);
            mkdir($this->cacheDir);
        }
    }

    private function Rmdir($dir,$recurse=true)
    {
        $dir=trim($dir);
        if(empty($dir) || $dir=="." || $dir==".." || $dir=="/" || $dir=="//") return false;
        if (is_dir($dir))
        {
            if($recurse)
            {
                $objects = scandir($dir);
                foreach ($objects as $object)
                {
                    if ($object != "." && $object != "..")
                    {
                        if (is_dir($dir. DIRECTORY_SEPARATOR .$object) && !is_link($dir."/".$object))
                        {
                            $this->Rmdir($dir. DIRECTORY_SEPARATOR .$object,$recurse);
                        }
                        else
                        {
                            unlink($dir. DIRECTORY_SEPARATOR .$object);
                        }
                    }
                }
            }
            rmdir($dir);
        }
        return true;
    }

    public function setLogFile($logfile)
    {
        $this->logfile = $logfile;
    }

    public function setCacheDir($cachedir)
    {
        $this->cacheDir = $cachedir;
    }

    public function useCache($use=false)
    {
        $this->cacheActive = $use;
    }
    public function getLog()
    {
        return $this->log;
    }


    /**
     * @param $comment
     * @param $url
     * @param $duration
     * @return void
     */
    private function addLog($comment, $url="",$duration=0)
    {
        $curlog = array(
            "comment" => $comment,
            "url" => $url,
            "duration" => $duration
        );
        $this->log[]= $curlog;
        if($this->debug)
        {
            file_put_contents($this->logfile,implode("\t",$curlog).PHP_EOL,FILE_APPEND);
        }
    }

    private function getContent($type,$url):StructCover|StructChapter
    {
        if($this->cacheActive && !empty($this->cacheDir))
        {
            $content = Scraper::$type($url,$this->cacheDir);
        }
        else
        {
            $content = Scraper::$type($url);
        }
        return $content;
    }

    public function getBook()
    {
        $this->addLog("Beginning scraping book");
        $begin = microtime(true);
        try {
            $this->cover = $this->getContent('Toc',$this->url);
        }
        catch(\Exception $e) {
            $this->addLog("Error on main page : " . $e->getMessage(), $this->url);
        }
        $end = microtime(true);
        $this->addLog("Main page",$this->url,$end-$begin);
        foreach($this->cover->toc as $index => $toc)
        {
            $begin = microtime(true);
            try
            {
                $this->chapters[] = $this->getContent('Chapter',$toc->url); //Scraper::Chapter($toc->url);
            }
            catch(\Exception $e) {
                $this->addLog("Error on chapter " . ($index + 1) . " : " . $e->getMessage(), $toc->url);
            }
            $end = microtime(true);
            $this->addLog("Chapter ".($index+1)." on ".count($this->cover->toc),$toc->url,$end-$begin);
        }
    }
}
