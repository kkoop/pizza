<?php
namespace Pizza\Model;

class Recommendation
{
  public $product;
  public $count;
  public $price;

  public static function getOwnFavourites($day)
  {
    if (empty($day->url))
      return;
    $stmt = Db::prepare("SELECT COUNT(*) AS count,product,MAX(price) AS price
      FROM ordering
      JOIN orderday ON orderday.id=ordering.day
      WHERE ordering.user=:user AND orderday.url=:url AND orderday.id!=:day
      GROUP BY product
      ORDER BY count DESC
      LIMIT 5");
    $stmt->execute([':user' => $_SESSION['user']->id,
                    ':day'  => $day->id,
                    ':url'  => $day->url]);
    return $stmt->fetchAll(\PDO::FETCH_CLASS, get_class());
  }

  public static function getAllFavourites($day)
  {
    if (empty($day->url))
      return;
    $stmt = Db::prepare("SELECT COUNT(*) AS count,product,MAX(price) AS price
      FROM ordering
      JOIN orderday ON orderday.id=ordering.day
      WHERE orderday.url=:url AND orderday.id!=:day
      GROUP BY product
      ORDER BY count DESC
      LIMIT 5");
    $stmt->execute([':day'  => $day->id,
                    ':url'  => $day->url]);
    return $stmt->fetchAll(\PDO::FETCH_CLASS, get_class());
  }
}
