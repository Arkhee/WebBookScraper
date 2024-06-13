<?php
namespace WebBookScraper;
use WebBookScraper\StructChapter;
use WebBookScraper\StructCover;
class Scraper
{
    public static function Toc($url):StructCover
    {
        $html = self::ReadURLContent($url);
        $xpath = self::CleanTocHTML($html);
        $toc = self::ExtractTocInformations($xpath,$url);
        return $toc;
    }

    public static function Chapter($url):StructChapter
    {
        $html = self::ReadURLContent($url);
        $chapter = self::CleanContentHTML($html,$url);
        return $chapter;
    }


    public static function CleanContentHTML($html,$url):StructChapter
    {
        $chapter = new StructChapter();
        // Charger le HTML dans DOMDocument
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        // Récupérer le contenu de la balise "article"
        $articles = $dom->getElementsByTagName('article');
        if ($articles->length === 0) {
            throw new \Exception("Aucune balise 'article' trouvée.");
        }
        $article = $articles->item(0);

        // Extraire le contenu de la balise "header" contenue dans l'article
        $headers = $article->getElementsByTagName('header');
        $chapitre = '';
        if ($headers->length > 0) {
            $chapitre = trim($headers->item(0)->nodeValue);
        }

        // Supprimer les balises div qui ont la classe wp-dark-mode-switch
        $xpath = new \DOMXPath($dom);
        $divs = $xpath->query("//div[contains(@class, 'wp-dark-mode-switch')]");
        foreach ($divs as $div) {
            $div->parentNode->removeChild($div);
        }
        $divs = $xpath->query("//div[contains(@class, 'cb_p6_patreon_button')]");
        foreach ($divs as $div) {
            $div->parentNode->removeChild($div);
        }
        $divs = $xpath->query("//div[contains(@class, 'sharedaddy')]");
        foreach ($divs as $div) {
            $div->parentNode->removeChild($div);
        }

        // Supprimer les balises p parente des liens dont le libellé est "Previous Chapter" ou "Next Chapter"
        $links = $xpath->query("//a[text()='Previous Chapter' or text()='Next Chapter']");
        foreach ($links as $link) {
            $parent = $link->parentNode;
            if ($parent->nodeName === 'p') {
                $parent->parentNode->removeChild($parent);
                break;
            }
        }

        // Récupérer le contenu du div ayant la classe "entry-content" dans la balise article
        $entry_content_divs = $xpath->query("//article//div[contains(@class, 'entry-content')]");
        $contenu = '';
        if ($entry_content_divs->length > 0) {
            $entry_content_div = $entry_content_divs->item(0);

            // Supprimer toutes les balises qu'il contient sauf p, strong, i, em, br
            $allowed_tags = ['p', 'strong', 'i', 'em', 'br', 'hr', 'center', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li' ];
            $children = $entry_content_div->childNodes;
            foreach ($children as $child) {
                if ($child instanceof \DOMElement && !in_array($child->tagName, $allowed_tags)) {
                    $entry_content_div->removeChild($child);
                }
            }
            $contenu = $dom->saveHTML($entry_content_div);
        }
        $chapter->content = $contenu;
        $chapter->title = $chapitre;
        $chapter->url = $url;
        return $chapter;
    }

    public static function CleanTocHTML(string $html):\DOMXPath
    {
        // Chargement du contenu HTML dans un DOMDocument
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true); // Désactivation des erreurs de libxml
        $dom->loadHTML($html);
        libxml_clear_errors();

        // Initialisation d'un XPath pour naviguer dans le DOM
        $xpath = new \DOMXPath($dom);

        // Récupération du contenu de la balise "article"
        $articleNode = $xpath->query('//article')->item(0);
        $articleContent = $dom->saveHTML($articleNode);

        // Extraction du contenu de la balise "header" contenue dans l'article
        $headerNode = $xpath->query('//article//header')->item(0);
        $chapitre = $headerNode ? $dom->saveHTML($headerNode) : '';

        // Suppression des balises div avec la classe "wp-dark-mode-switch"
        foreach ($xpath->query('//div[contains(@class, "wp-dark-mode-switch")]') as $node) {
            $node->parentNode->removeChild($node);
        }

        // Récupération du contenu du div avec la classe "entry-content"
        $entryContentNode = $xpath->query('//div[contains(@class, "entry-content")]')->item(0);
        $entryContent = $dom->saveHTML($entryContentNode);
        return $xpath;
    }

    public static function ExtractTocInformations(\DOMXPath $xpath,$url):StructCover
    {
        $toc = new StructCover();
        // Extraction du contenu de la balise "header" contenue dans l'article
        $headerNode = $xpath->query('//article//header')->item(0);
        $toc->title = trim($headerNode->nodeValue) ;

        // Récupération de tous les liens dans le contenu "entry-content"
        $links = $xpath->query('//div[contains(@class, "entry-content")]//a');
        $illustration = '';
        foreach ($links as $link) {
            $lien = $link->getAttribute('href');
            $libelle = $link->nodeValue;
            $parse = parse_url($lien);
            //echo "Vérification ".$url." vs ".$arrLien["dirname"]."<br />";
            $infoPathLien = pathinfo($lien);
            // Check if link is an image and if it is the case store it in a separate variable
            if(isset($infoPathLien['extension']) && in_array(strtolower($infoPathLien['extension']), array('jpg', 'jpeg', 'png'))) {
                $illustration=$lien;
                continue;
            }
            if(isset($parse['host']) && strpos($url,$parse['host'])!==false && strpos($lien,"?share=")===false)
            {
                $toc->addToc($libelle,$lien);
            }
        }
        $toc->illustration = $illustration;

        return $toc;
    }

    public static function TidyHTML(string $html):string
    {
        $config = array(
            'indent' => true,
            'output-xhtml' => true,
            'wrap' => 200
        );
        $tidy = new \tidy;
        $tidy->parseString($html, $config, 'utf8');
        $tidy->cleanRepair();
        return tidy_get_output($tidy);
    }

    public static function ReadURLContent($url):string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $content = curl_exec($ch);
        curl_close($ch);
        $content = self::TidyHTML($content);
        return $content;
    }
}
