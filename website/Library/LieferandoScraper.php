<?php
namespace Pizza\Library;
use Pizza\Model;

class LieferandoScraper extends Scraper
{
  private $url;

  public function __construct($url)
  {
    $this->url = rtrim($url, "/");
  }

  public function scrape()
  {
    $dom = new \DOMDocument();
    if (!$dom->loadHTMLFile($this->url, LIBXML_NOWARNING | LIBXML_NOERROR)) {
      return false;
    }
    $xpath = new \DOMXpath($dom);
    Model\Db::beginTransaction();
    $stmtDelete = Model\Db::prepare("DELETE FROM menu WHERE url=:url");
    $stmtDelete->execute([":url" => $this->url]);
    $stmtInsert = Model\Db::prepare("INSERT INTO menu (url) VALUES (:url)");
    $stmtInsert->execute([":url" => $this->url]);
    $menuId = Model\Db::lastInsertId();
    $stmtInsert = Model\Db::prepare("INSERT INTO menuitem (menu,name,price,sort) VALUES (:menu,:name,:price,:sort)");
    $menuitems = $xpath->query("//div[@class='meal']");
    foreach ($menuitems as $sort=>$menuitem) {
      $nameitems = $xpath->query("descendant::span[@itemprop='name']", $menuitem);
      $priceitems = $xpath->query("descendant::span[@itemprop='price']", $menuitem);
      if ($nameitems->length == 1 && $priceitems->length == 1) {
        $stmtInsert->execute([":menu"  => $menuId, 
                              ":name"  => trim($nameitems[0]->textContent),
                              ":price" => trim($priceitems[0]->textContent),
                              ":sort"  => $sort]);
      } else {
        // etwas stimmt nicht
        Model\Db::rollBack();
        return false;
      }
    }
    Model\Db::commit();
    return true;
  }
}
