<?php
require_once("../src/WebBookScraper.php");
require_once("../src/Scraper.php");
require_once("../src/StructChapter.php");
require_once("../src/StructCover.php");
require_once("../src/StructToc.php");
require_once("../src/WebBookScraper.php");
use Arkhee\WebBookScraper\WebBookScraper;
?>
<html lang="en-EN">
<head>
    <title>WebBookScraper</title>

</head>
<body>
<form method="post">
    <label for="url">Type URL to scrape :</label>
    <input type="text" id="url" name="url" placeholder="URL du livre">
    <input type="submit" value="Récupérer le livre">
</form>
</body>
</html>
<?php
if(isset($_POST["url"]))
{
    $url = $_POST["url"];
    $book = new WebBookScraper($url);
    $book->getBook();
    echo "<h1>".$book->cover->title."</h1>\r\n";
    echo "Liens : <br>\r\n";
    echo "<ul>\r\n";
    foreach($book->cover->toc as $toc)
    {
        echo "<li><a href='".$toc->url."'>".$toc->title."</a></li>\r\n";
    }
    echo "</ul>\r\n";
    echo "Premier chapitre : <br />\r\n";
    echo "<h2>".$book->chapters[0]->title."</h2>\r\n";
    echo "<div class='content'>".$book->chapters[0]->content."</div>\r\n";
}
