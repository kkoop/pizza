<?php
namespace Pizza\Model;

class Menuitem
{
  public $menu;
  public $name;
  public $price;
  
  public static function readAll($menu)
  {
    $stmt = Db::prepare("SELECT * FROM menuitem WHERE menu=:menu");
    $stmt->execute([":menu" => $menu]);
    return $stmt->fetchAll(\PDO::FETCH_CLASS, get_class());
  }
}
