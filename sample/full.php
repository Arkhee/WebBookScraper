<?php
require_once("./vendor/autoload.php");
use WebBookScraper\WebBookScraper;
use Simplepubgen\Simplepubgen;
if(isset($_GET["url"]))
{
    // Get URL parameter
    $url = $_GET["url"];

    // Start Scraping
    $book = new WebBookScraper($url,true);
    $book->setLogFile(__DIR__.'/log.txt');
    $book->setCacheDir(__DIR__.'/cache');
    $book->useCache(true);
    $book->setBatchSize(10);
    $book->setBatchSizeActive(true);
    $book->setBatchCallback("parent.setMessage");
    $book->setNoSpamTimeInterval(100);
    $book->getBook();

    // Scraping finished, start generating EPUB
    $epub = new Simplepubgen($book->cover->title);
    $epub->setCover($book->cover->illustration);
    $epub->setDescription($book->cover->description);
    foreach($book->chapters as $chapter)
    {
        $epub->addChapter($chapter->title,$chapter->content);
        foreach($chapter->getExternalResources() as $resource)
        {
            $epub->addResource($resource->getResourceName(),$resource->getResourceURL());
        }
    }
    // No parameters :
    // * epub named from provided title
    // * file downloaded directly, not stored on server
    $epub->generateEpub();
}
?>
    <html lang="en-EN">
    <head>
        <title>WebBookScraper</title>
        <script>
            function submitForm()
            {
                document.getElementById("submitMessages").innerHTML = "Getting book chapter per chapter ...";
                hideForm();
                return true;
            }
            function setMessage(message)
            {
                document.getElementById("submitMessages").innerHTML = message;
            }
            function hideForm()
            {
                document.getElementById("formSubmitEpub").style.display = "none";
            }
            function stopProcess()
            {
                document.getElementById("loading").style.display = "none";
                document.getElementById("formSubmitEpub").style.display = "block";
                document.getElementById("submitMessages").innerHTML = "Process stopped.";
                document.getElementById("frameEpub").location="";
            }
        </script>
    </head>
    <body>
    <form method="get" target="frameEpub" id="formSubmitEpub" onsubmit="submitForm();">
        <label for="url">Type URL to scrape :</label>
        <input type="text" id="url" name="url" placeholder="Book URL">
        <input type="submit" value="Get the book !">
    </form>
    <div id="loading" style="display:none;">
        <p>Getting book chapter per chapter ...</p>
        <button onclick="stopProcess()">Stop process</button>
    </div>
    <div id="submitMessages"></div>
    <iframe name="frameEpub" id="frameEpub" style="display:none;"></iframe>
    </body>
    </html>
