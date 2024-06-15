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
                toggleLoading();
                toggleForm();
                return true;
            }
            function setMessage(message)
            {
                document.getElementById("submitMessages").innerHTML = message;
            }
            function toggleLoading()
            {
                if(document.getElementById("loading").style.display == "block")
                    document.getElementById("loading").style.display = "none";
                else
                    document.getElementById("loading").style.display = "block";
            }
            function toggleForm()
            {
                if(document.getElementById("formSubmitEpub").style.display == "none")
                    document.getElementById("formSubmitEpub").style.display = "block";
                else
                    document.getElementById("formSubmitEpub").style.display = "none";
            }
            function stopProcess()
            {
                document.getElementById("loading").style.display = "none";
                document.getElementById("formSubmitEpub").style.display = "block";
                document.getElementById("submitMessages").innerHTML = "";
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
    <?php
    $books = WebBookScraper::getAllBooksInfo(__DIR__.'/cache');
    if(count($books))
    {
        echo "<h2>Already requested books</h2>";
        echo "<ul>";
        foreach($books as $book)
        {
            echo "<li>
                <a href='index.php?url=".$book["url"]."'>".$book["title"]." (get ePub)</a><br />
                <a href='".$book["url"]."' target='_blank'>".$book["url"]."</a> 
            </li>";
        }
        echo "</ul>";
    }
    ?>
    </body>
    </html>
