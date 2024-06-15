<?php
namespace WebBookScraper;

class Tools
{

    public static function rmDir($dir, $recurse = true)
    {
        $dir=trim($dir);
        if (empty($dir) || $dir=="." || $dir==".." || $dir=="/" || $dir=="//") {
            return false;
        }
        if (is_dir($dir)) {
            if ($recurse) {
                $objects = scandir($dir);
                foreach ($objects as $object) {
                    if ($object != "." && $object != "..") {
                        if (is_dir($dir. DIRECTORY_SEPARATOR .$object) && !is_link($dir."/".$object)) {
                            self::rmDir($dir. DIRECTORY_SEPARATOR .$object, $recurse);
                        } else {
                            unlink($dir. DIRECTORY_SEPARATOR .$object);
                        }
                    }
                }
            }
            rmdir($dir);
        }
        return true;
    }


    public static function getCalledURL():string
    {
        return $_SERVER['REQUEST_URI'];
    }

    public static function redirectToUrl($url, $message = "", $callback = "")
    {
        if (!headers_sent() && empty($message)) {
            header("Location:".$url);
        } else {
            if (!empty($callback)) {
                echo "<script type='text/javascript'>".$callback."('".$message."');</script>\r\n";
            } else {
                echo "<script type='text/javascript'>alert('".$message."');</script>\r\n";
            }
            echo "<script type='text/javascript'>window.location = '".$url."';</script>\r\n";
        }
        die();
    }

}