<?php
namespace Pizza\Library;

abstract class Scraper
{
  public static function create($url) 
  {
    /*if (stripos($url, "lieferando")) {
      return new LieferandoScraper($url);
    }*/
    return null;
  }
  
  abstract public function scrape();
}
