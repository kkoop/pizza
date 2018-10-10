<?php
namespace Pizza\Model;

class Menu
{
  public $id;
  public $url;
  
  public static function read($url)
  {
    if ($url == "")
      return null;
    $stmt = Db::prepare("SELECT id,url FROM menu WHERE url=:url");
    $stmt->execute([":url" => $url]);
    return $stmt->fetchObject(get_class());
  }
  
  public function getItems()
  {
    return Menuitem::readAll($this->id);
  }
  
  public function delete()
  {
    $stmt = Db::prepare("DELETE FROM menu WHERE id=:id");
    $stmt->execute([":id" => $this->id]);
  }
}
