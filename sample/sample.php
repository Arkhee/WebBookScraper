<?php
/*
 * Note : this sample requires the Simplepubgen to work completely and produce an epub file
 * If you are looking for a simple example for WebBookScraper just stop before the Simplepubgen part

 */
require_once("./vendor/autoload.php");
use WebBookScraper\WebBookScraper;
use Simplepubgen\Simplepubgen;
if(isset($_POST["url"]))
{
    $url = $_POST["url"];
    $book = new WebBookScraper($url,true);
    $book->setLogFile(__DIR__.'/log.txt');
    $book->setCacheDir(__DIR__.'/cache');
    $book->useCache(true);
    $book->getBook();
    $epub = new Simplepubgen($book->cover->title);
    $epub->setCover($book->cover->illustration);
    foreach($book->chapters as $chapter)
    {
        $epub->addChapter($chapter->title,$chapter->content);
        foreach($chapter->getExternalResources() as $resource)
        {
            $epub->addResource($resource->getResourceName(),$resource->getResourceURL());
        }
    }
    $epub->generateEpub() ;
}
?>
<html lang="en-EN">
<head>
    <title>WebBookScraper</title>

</head>
<body>
<form method="post">
    <label for="url">Type URL to scrape :</label>
    <input type="text" id="url" name="url" placeholder="Book URL">
    <input type="submit" value="Let's go get the book">
</form>
</body>
</html>
