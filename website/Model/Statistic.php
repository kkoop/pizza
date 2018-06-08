<?php
namespace Pizza\Model;

class Statistic
{
  public static function favouriteOrders()
  {
    $stmt = Db::prepare("SELECT product,COUNT(*) FROM ordering ".
      "WHERE user=:user ".
      "GROUP BY product ORDER BY COUNT(*) DESC LIMIT 5");
    $stmt->execute([":user" => $_SESSION['user']->id]);
    return $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
  }
}
