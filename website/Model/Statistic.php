<?php
namespace Pizza\Model;

class Statistic
{
  /**
   * @brief Gibt die Bestellungen des Benutzers in absteigender Häufigkeit zurück
   * 
   * @return array(product=>count)
   */
  public static function favouriteOrders()
  {
    $stmt = Db::prepare("SELECT product,COUNT(*) FROM ordering ".
      "WHERE user=:user ".
      "GROUP BY product ORDER BY COUNT(*) DESC LIMIT 5");
    $stmt->execute([":user" => $_SESSION['user']->id]);
    return $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
  }
  
  /**
   * @brief Gibt Statistiken über verwendete Lieferdienste zurück
   * 
   * Statistiken über alle Benutzer
   * @return array(deliveryservice,count,price)
   */
  public static function favouriteDeliveryServices()
  {
    $stmt = Db::query("SELECT deliveryservice AS name,COUNT(DISTINCT orderday.id) AS count,SUM(ordering.price) as price
      FROM orderday
      JOIN ordering ON ordering.day=orderday.id
      GROUP BY deliveryservice");
    return $stmt->fetchAll(\PDO::FETCH_CLASS);
  }
}
