<?php
namespace WebBookScraper;

use WebBookScraper\WebBookScraper;
use WebBookScraper\StructChapter;
use WebBookScraper\StructCover;

class Scraper
{
    public static function contentToc($url, $cacheDir = ""):StructCover
    {
        $html = self::readURLContent($url, $cacheDir);
        $description = self::extractDescriptionInformation($html);
        $xpath = self::cleanTocHTML($html, $cacheDir);
        $toc = self::extractTocInformations($xpath, $url);
        $toc->description = $description;
        return $toc;
    }

    public static function contentChapter($url, $cacheDir = ""):StructChapter
    {
        $html = self::readURLContent($url, $cacheDir);
        $chapter = self::cleanContentHTML($html, $url, $cacheDir);
        return $chapter;
    }

    public static function removeCache($url, $cacheDir)
    {
        $hash = md5($url);
        $filename = $cacheDir."/".$hash;
        if (file_exists($filename)) {
            unlink($filename);
        }
    }


    public static function storeScrape($url, $content, $cacheDir = "")
    {
        if (!empty($cacheDir)) {
            $hash = md5($url);
            if (!file_exists($cacheDir)) {
                mkdir($cacheDir, 0777, true);
            }
            $filename = $cacheDir."/".$hash;
            file_put_contents($filename, $content);
        }
    }



    public static function cleanContentHTML($html, $url, $cacheDir = ""):StructChapter
    {
        $locationChapter = WebBookScraper::getScrapePathChapterMain();
        $locationHeader = WebBookScraper::getScrapePathChapterHeader();
        $locationContent = WebBookScraper::getScrapePathChapterContent();
        $chapter = new StructChapter();
        // Charger le HTML dans DOMDocument
        $dom = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        // Récupérer le contenu de la balise "article"
        $articles = $dom->getElementsByTagName($locationChapter);
        if ($articles->length === 0) {
            self::removeCache($url, $cacheDir);
            throw new \Exception("No tag ".$locationChapter." found.");
        }
        $article = $articles->item(0);

        // Extraire le contenu de la balise "header" contenue dans l'article
        $headers = $article->getElementsByTagName($locationHeader);
        $chapitre = '';
        if ($headers->length > 0) {
            $chapitre = trim($headers->item(0)->nodeValue);
        }

        // Supprimer les balises div qui ont la classe wp-dark-mode-switch
        $xpath = new \DOMXPath($dom);



        /*
         * Cleaning forbidden attributes
         */
        // Définissez les noms des attributs que vous souhaitez supprimer
        $attributesToRemove = [
            "href", "data-recalc-dims", "fetchpriority",
            "decoding", "srcset", "sizes", "aria-level", "loading",
            "data-cfemail", "sandbox", "security", "data-secret"];
        // Tags to scan
        $tagsToScan = [ "a", "img", "li" ];

        // Sélectionnez toutes les balises que vous souhaitez modifier
        // Par exemple, ici on sélectionne toutes les balises div
        foreach ($tagsToScan as $curElement) {
            $elements = $xpath->query('//'.$curElement);

            foreach ($elements as $element) {
                foreach ($attributesToRemove as $attribute) {
                    if ($element->hasAttribute($attribute)) {
                        $element->removeAttribute($attribute);
                    }
                }
            }
        }

        $elementsToRemove = ["figure","iframe", "script"];
        foreach ($elementsToRemove as $curElement) {
            // Sélectionnez tous les éléments <figure>
            $figures = $xpath->query('//'.$curElement);

            foreach ($figures as $figure) {
                // Supprimez chaque élément <figure> de son parent
                $figure->parentNode->removeChild($figure);
            }
        }





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
        $entry_content_divs = $xpath->query("//".$locationChapter."//div[contains(@class, '".$locationContent."')]");
        $contenu = '';
        if ($entry_content_divs->length > 0) {
            $entry_content_div = $entry_content_divs->item(0);

            // Supprimer toutes les balises qu'il contient sauf p, strong, i, em, br
            $allowed_tags = ['p', 'strong', 'i', 'em', 'br', 'hr', 'center',
                            'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li' ];
            $children = $entry_content_div->childNodes;
            foreach ($children as $child) {
                if ($child instanceof \DOMElement && !in_array($child->tagName, $allowed_tags)) {
                    $entry_content_div->removeChild($child);
                }
            }





            $contenu = $dom->saveHTML($entry_content_div);
            $chapter->content = $contenu;

            /*
             * Replacing all remaining img src to a local value then returning the array
             */
            if (WebBookScraper::getScrapeImgConvert()) {
                // Sélectionnez tous les éléments <img>
                $dom->loadHTML(mb_convert_encoding($contenu, 'HTML-ENTITIES', 'UTF-8'));
                $xpath = new \DOMXPath($dom);
                $images = $xpath->query('//img');

                foreach ($images as $img) {
                    // Récupérez l'URL actuelle du tag src
                    $currentSrc = $img->getAttribute('src');
                    $newResourceName = $chapter->addExternalResource($currentSrc);
                    $img->setAttribute('src', "../image/".$newResourceName);
                }
                if (count($images)) {
                    $entry_content_div = $xpath->query("//div[contains(@class, '".$locationContent."')]");
                    $contenu = $dom->saveHTML($entry_content_div[0]);
                }
            }
        }
        $chapter->content = $contenu;
        $chapter->title = $chapitre;
        $chapter->url = $url;
        //$chapter->externalRessources = $externalRessources;
        return $chapter;
    }

    public static function cleanTocHTML(string $html):\DOMXPath
    {
        $locationToc = WebBookScraper::getScrapePathTocMain();
        $locationHeader = WebBookScraper::getScrapePathTocHeader();
        $locationContent = WebBookScraper::getScrapePathTocContent();

        // Chargement du contenu HTML dans un DOMDocument
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true); // Désactivation des erreurs de libxml
        $dom->loadHTML($html);
        libxml_clear_errors();

        // Initialisation d'un XPath pour naviguer dans le DOM
        $xpath = new \DOMXPath($dom);

        // Récupération du contenu de la balise "article"
        $articleNode = $xpath->query('//'.$locationToc)->item(0);
        $articleContent = $dom->saveHTML($articleNode);

        // Extraction du contenu de la balise "header" contenue dans l'article
        $headerNode = $xpath->query('//'.$locationToc.'//'.$locationHeader)->item(0);
        $chapitre = $headerNode ? $dom->saveHTML($headerNode) : '';

        // Suppression des balises div avec la classe "wp-dark-mode-switch"
        foreach ($xpath->query('//div[contains(@class, "wp-dark-mode-switch")]') as $node) {
            $node->parentNode->removeChild($node);
        }

        // Récupération du contenu du div avec la classe "entry-content"
        $entryContentNode = $xpath->query('//div[contains(@class, "'.$locationContent.'")]')->item(0);
        $entryContent = $dom->saveHTML($entryContentNode);
        return $xpath;
    }

    public static function extractDescriptionInformation(string $html) : string
    {
        $locationToc = WebBookScraper::getScrapePathTocMain();
        $locationHeader = WebBookScraper::getScrapePathTocHeader();
        $locationContent = WebBookScraper::getScrapePathTocContent();


        $doc = new \DOMDocument();
        @$doc->loadHTML($html);
        $xpath = new \DOMXPath($doc);

        $description = "";
        /*
        Do the equivalent of the following jQuery lines but with xpath :
        jQuery(".entry-content script,.entry-content > div, .entry-content br").remove();
        jQuery(".entry-content a").closest("p").remove()
        */
        // Sélectionner et supprimer les éléments script, div, br à l'intérieur de .entry-content
        $entries = $xpath->query(
            "//".$locationToc."//div[contains(@class, '".$locationContent."')]//script | ".
            "//".$locationToc."//div[contains(@class, '".$locationContent."')]/div | ".
            "//".$locationToc."//div[contains(@class, '".$locationContent."')]//br"
        );
        foreach ($entries as $entry) {
            $entry->parentNode->removeChild($entry);
        }
        // Sélectionner et supprimer les paragraphes contenant des liens à l'intérieur de .entry-content
        $entries = $xpath->query("//".$locationToc."//div[contains(@class, '".$locationContent."')]//a//parent::p");
        foreach ($entries as $entry) {
            $entry->parentNode->removeChild($entry);
        }

        $entry_content_div = $xpath->query("//".$locationToc."//div[contains(@class, '".$locationContent."')]");
        $description = $doc->saveHTML($entry_content_div[0]);
        return $description;
    }

    public static function extractTocInformations(\DOMXPath $xpath, $url):StructCover
    {
        $locationToc = WebBookScraper::getScrapePathTocMain();
        $locationHeader = WebBookScraper::getScrapePathTocHeader();
        $locationContent = WebBookScraper::getScrapePathTocContent();

        $toc = new StructCover();
        // Extraction du contenu de la balise "header" contenue dans l'article
        $headerNode = $xpath->query('//'.$locationToc.'//'.$locationHeader)->item(0);
        $toc->title = trim($headerNode->nodeValue) ;

        // Récupération de tous les liens dans le contenu "entry-content"
        $links = $xpath->query('//div[contains(@class, "'.$locationContent.'")]//a');
        $illustration = '';
        foreach ($links as $link) {
            $lien = $link->getAttribute('href');
            $libelle = $link->nodeValue;
            $parse = parse_url($lien);
            //echo "Vérification ".$url." vs ".$arrLien["dirname"]."<br />";
            $infoPathLien = pathinfo($lien);
            // Check if link is an image and if it is the case store it in a separate variable
            if (isset($infoPathLien['extension'])
                && in_array(strtolower($infoPathLien['extension']), array('jpg', 'jpeg', 'png'))) {
                $illustration=$lien;
                continue;
            }
            if (isset($parse['host']) && strpos($url, $parse['host'])!==false && strpos($lien, "?share=")===false) {
                $toc->addToc($libelle, $lien);
            }
        }
        $toc->illustration = $illustration;

        return $toc;
    }

    public static function tidyHTML(string $html):string
    {
        $dom = new \DOMDocument();

        // Charger le code HTML. Utilisez @ pour supprimer les avertissements sur le HTML mal formé.
        // Utilisez les options LIBXML_HTML_NOIMPLIED et LIBXML_HTML_NODEFDTD
        // pour éviter d'ajouter des balises HTML et BODY automatiquement.
        @$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        // Enregistrer et retourner le HTML corrigé
        $retour = $dom->saveHTML();
        if ($retour === false) {
            $retour = $html;
        }
        return $retour;
        /*
        $config = array(
            'indent' => true,
            'output-xhtml' => true,
            'wrap' => 200
        );
        $tidy = new \tidy;
        $tidy->parseString($html, $config, 'utf8');
        $tidy->cleanRepair();
        return tidy_get_output($tidy);
        */
    }

    public static function readURLContent($url, $cacheDir = ""):string
    {
        if (!empty($cacheDir)) {
            $content = self::getCache($url, $cacheDir);
            if (!empty($content)) {
                return $content;
            }
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $content = curl_exec($ch);
        curl_close($ch);
        $content = self::tidyHTML($content);
        if (!empty($cacheDir)) {
            self::storeScrape($url, $content, $cacheDir);
        }
        return $content;
    }

    public static function getCache($url, $cacheDir)
    {
        $hash = md5($url);
        $filename = $cacheDir."/".$hash;
        if (file_exists($filename)) {
            return file_get_contents($filename);
        } else {
            return "";
        }
    }
}
