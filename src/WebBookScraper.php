<?php
namespace WebBookScraper;

use WebBookScraper\Scraper;
use WebBookScraper\Tools;

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
    private $cacheRootDir = "";
    private $cacheActive = false;
    private $batchSize = 50;
    private $batchCallback = "";
    private $batchSizeActive = false;
    private $noSpamTimeInterval=0;

    private static $scrape_path_toc_main = 'article';
    private static $scrape_path_toc_header = 'header';
    private static $scrape_path_toc_content = 'entry-content';
    private static $scrape_path_chapter_main = 'article';
    private static $scrape_path_chapter_header = 'header';
    private static $scrape_path_chapter_content = 'entry-content';
    private static $scrape_img_convert_in_content = true;
    /**
     * @param $url
     * @return void
     */
    public function __construct($url, $debug = false)
    {
        $this->url = $url;
        $this->debug = $debug;
        $this->cacheRootDir = sys_get_temp_dir()."/webbookscraper_cache/";
        $this->cacheDir = $this->cacheRootDir."/url_".md5($this->url)."/";
        if ($this->debug) {
            $this->logfile = sys_get_temp_dir().'/'.uniqid("webscaper_").".log";
            file_put_contents($this->logfile, "");
        }
    }

    /**
     * @param $convert
     * @return void
     * Set the option to convert images to local files (for epub insertion)
     */
    public static function setScrapeImgConvert($convert = true)
    {
        self::$scrape_img_convert_in_content = (bool)$convert;
    }

    /**
     * @return void
     * Get the option to convert images to local files (for epub insertion)
     */
    public static function getScrapeImgConvert():bool
    {
        return (bool)self::$scrape_img_convert_in_content;
    }

    /**
     * @param $path
     * @return void
     * Set the main TAG (article, body etc) where the content is located in the TOC page
     */
    public static function setScrapePathTocMain(string $path = "article")
    {
        self::$scrape_path_toc_main = $path;
    }

    /**
     * @param $path
     * @return void
     * Set the main TAG (header) where the TOC content is located in the TOC page
     */
    public static function setScrapePathTocHeader(string $path = "header")
    {
        self::$scrape_path_toc_header = $path;
    }

    /**
     * @param $path
     * @return void
     * Set the main CLASS (entry-content) where the TOC content is located in the TOC page
     */
    public static function setScrapePathTocContent(string $path = "entry-content")
    {
        self::$scrape_path_toc_content = $path;
    }
    /**
     * @param $path
     * @return void
     * Set the main TAG (article, body etc) where the content is located in the CHAPTER page
     */
    public static function setScrapePathChapterMain(string $path = "article")
    {
        self::$scrape_path_chapter_main = $path;
    }

    /**
     * @param $path
     * @return void
     * Set the main TAG (header) where the TOC content is located in the CHAPTER page
     */
    public static function setScrapePathChapterHeader(string $path = "header")
    {
        self::$scrape_path_chapter_header = $path;
    }

    /**
     * @param $path
     * @return void
     * Set the main CLASS (entry-content) where the TOC content is located in the CHAPTER page
     */
    public static function setScrapePathChapterContent(string $path = "entry-content")
    {
        self::$scrape_path_chapter_content = $path;
    }




    /**
     * @return void
     * GET the main TAG (article, body etc) where the content is located in the TOC page
     */
    public static function getScrapePathTocMain():string
    {
        return self::$scrape_path_toc_main;
    }

    /**
     * @return void
     * GET the main TAG (header) where the TOC content is located in the TOC page
     */
    public static function getScrapePathTocHeader():string
    {
        return self::$scrape_path_toc_header;
    }

    /**
     * @return void
     * GET the main CLASS (entry-content) where the TOC content is located in the TOC page
     */
    public static function getScrapePathTocContent():string
    {
        return self::$scrape_path_toc_content;
    }
    /**
     * @return void
     * GET the main TAG (article, body etc) where the content is located in the CHAPTER page
     */
    public static function getScrapePathChapterMain():string
    {
        return self::$scrape_path_toc_main;
    }

    /**
     * @return void
     * GET the main TAG (header) where the TOC content is located in the CHAPTER page
     */
    public static function getScrapePathChapterHeader():string
    {
        return self::$scrape_path_toc_header;
    }

    /**
     * @return void
     * GET the main CLASS (entry-content) where the TOC content is located in the CHAPTER page
     */
    public static function getScrapePathChapterContent():string
    {
        return self::$scrape_path_toc_content;
    }




    public function clearCache()
    {
        if (file_exists($this->cacheDir)) {
            Tools::rmDir($this->cacheDir);
            mkdir($this->cacheDir, 0777, true);
        }
    }

    public function setLogFile($logfile)
    {
        $this->logfile = $logfile;
    }

    public function setCacheDir($cachedir)
    {
        $this->cacheRootDir = $cachedir;
        $this->cacheDir = $cachedir;
        // add trailing slash if missing
        if (substr($this->cacheDir, -1)!=="/") {
            $this->cacheDir .= "/";
            $this->cacheRootDir .= "/";
        }
        // Add url md5 to cache dir
        $this->cacheDir .= "url_".md5($this->url)."/";
    }

    public function useCache($use = false)
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
    private function addLog($comment, $url = "", $duration = 0)
    {
        $curlog = array(
            "comment" => $comment,
            "url" => $url,
            "duration" => $duration
        );
        $this->log[]= $curlog;
        if ($this->debug) {
            file_put_contents($this->logfile, implode("\t", $curlog).PHP_EOL, FILE_APPEND);
        }
    }

    private function getContent($type, $url):StructCover|StructChapter
    {
        $method = "content".$type;
        if (!method_exists(Scraper::class, $method)) {
            throw new \Exception("Method ".$method." does not exist in Scraper class");
        }
        if ($this->cacheActive && !empty($this->cacheDir)) {
            $content = Scraper::$method($url, $this->cacheDir);
        } else {
            $content = Scraper::$method($url);
        }
        return $content;
    }

    public function setBatchCallback($script)
    {
        $this->batchCallback = $script;
    }
    public function setBatchSize(int $size = 50)
    {
        $this->batchSize = $size;
    }

    public function setBatchSizeActive(bool $active = true)
    {
        $this->batchSizeActive = $active;
    }

    private function countFilesInCache($cacheDir):int
    {
        $nbFiles = count(glob($cacheDir."*"));
        return $nbFiles;
    }

    public function getBook()
    {
        $this->addLog("Beginning scraping book");
        $begin = microtime(true);
        try {
            $this->cover = $this->getContent('Toc', $this->url);
            $this->storeBookInfo($this->cover->title, $this->cover->description);
        } catch (\Exception $e) {
            $this->addLog("Error on main page : " . $e->getMessage(), $this->url);
        }
        $end = microtime(true);
        $this->addLog("Main page", $this->url, $end-$begin);
        $countChapter = 0;
        foreach ($this->cover->toc as $index => $toc) {
            $begin = microtime(true);
            try {
                if (!$this->isFileInCache($toc->url)) {
                    $countChapter ++;
                }
                $this->chapters[] = $this->getContent('Chapter', $toc->url); //Scraper::Chapter($toc->url);
                if ($this->cacheActive && $this->batchSizeActive && $countChapter >= $this->batchSize) {
                    // If cache active only AND batch size active, redirect to the same page to continue scraping
                    $countFiles = $this->countFilesInCache($this->cacheDir);
                    $message = "Batch status : ".$countFiles." on ".count($this->cover->toc);
                    Tools::redirectToUrl(Tools::getCalledURL(), $message, $this->batchCallback);
                }
            } catch (\Exception $e) {
                $this->addLog("Error on chapter " . ($index + 1) . " : " . $e->getMessage(), $toc->url);
            }
            $end = microtime(true);
            /*
             * Wait a bit more before next chapter if there is a nospam interval
             */
            if ($this->noSpamTimeInterval>0) {
                usleep($this->noSpamTimeInterval);
            }
            $this->addLog("Chapter ".($index+1)." on ".count($this->cover->toc), $toc->url, $end-$begin);
        }
    }

    public function isFileInCache($url):bool
    {
        return file_exists($this->cacheDir.md5($url));
    }


    /**
     * @param $url
     * @return array|mixed
     * Get the book info from the cache
     */
    public function getBookInfo($url = "")
    {
        if (empty($url)) {
            $url = $this->url;
        }
        $infoFile = $this->cacheDir.md5($url).".json";
        if (file_exists($infoFile)) {
            return json_decode(file_get_contents($infoFile), true);
        }
        return array();
    }

    /**
     * @param $title
     * @param $description
     * @return void
     * Store the book info in the cache
     */
    private function storeBookInfo($title, $description)
    {
        if ($this->cacheActive) {
            $infoFile = $this->cacheDir.md5($this->url).".json";
            if (!file_exists($infoFile)) {
                $bookinfo = array(
                    "title" => $title,
                    "url" => $this->url,
                    "description" => $description
                );
                file_put_contents($infoFile, json_encode($bookinfo));
            }
            $this->storeAllBooksInfo($this->cover->title, $this->cover->description);
        }
    }

    public function setNoSpamTimeInterval(int $milliseconds)
    {
        $this->noSpamTimeInterval = $milliseconds;
    }

    public static function getAllBooksInfo($cacheDir):array
    {
        $retour = array();
        $infoFile = $cacheDir."/"."books.json";
        if (file_exists($infoFile)) {
            $retour = json_decode(file_get_contents($infoFile), true);
            if (!is_array($retour)) {
                $retour = array();
            }
        }
        return $retour;
    }

    private function storeAllBooksInfo($title, $description)
    {
        if ($this->cacheActive) {
            $infoFile = $this->cacheRootDir."books.json";
            if (file_exists($infoFile)) {
                $books = json_decode(file_get_contents($infoFile), true);
                $books = array_values($books);
                foreach ($books as $book) {
                    if ($book["url"]==$this->url) {
                        return;
                    }
                }
            }
            if (!isset($books) || !is_array($books)) {
                $books=array();
            }
            $bookinfo = array(
                "date" => date("Y-m-d H:i:s"),
                "title" => $title,
                "url" => $this->url,
                "description" => $description
            );
            $books[] = $bookinfo;
            file_put_contents($infoFile, json_encode($books));
        }
    }
}
