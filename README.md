# WebBookScraper : Extract online webnovels !  
This simple PHP project can be used to extract groups of page organized with 
a summary and individual pages per chapter , like web novels websites

## How it works
The script will download the page located at the provided url and extract
its content. It will then search for a list of links located in a specific 
container and download them too. 
In case the destination pages contain images, the script will list them
and store them in memory for each chapter. 

The default behaviour is to store the content in memory for further use, 
but there is an option to cache files as well. This is useful in case of 
a large table of content.

## How to use
The script is a simple PHP class that can be used in any PHP project.
The main class is `WebBookScraper` and it requires a few parameters to work:
- The URL of the main page (string)
- The option to debug, this will create a log file (boolean)

The default behaviour of the parser is to located content in the following 
parts of the pagen, by default :
* The "title" of the page is read from the "article head" selector
* The "content" of the page is read from the "article div.entry-content" selector
These can be modified at runtime by calling the `setSelectors[...]` static methods.

## How to install
The project is available on packagist and can be installed using composer:
```bash
composer require "arkhee/webbookscraper""
```


## Example
There is a sample provided to see how it works, have a look at the sample folder
To use it as-is you must use the Simplepubgen and the WebBookScraper packages.
Created a new folder on your server and copy the sample file at it's root
Install both packages with composer and run the sample file.

```bash
composer require "arkhee/simplepubgen"
composer require "arkhee/webbookscraper"
```
