<?php
namespace Pizza\Model;

class Orderday {
  public $id;
  public $time;
  private $organizer;
  public $deliveryservice;
  public $orderCount;
  
  public static function read($id)
  {
    $stmt = Db::prepare("SELECT orderday.id,UNIX_TIMESTAMP(time) AS time,organizer,deliveryservice ".
      "FROM orderday ".
      "WHERE id=:id");
    $stmt->execute([":id" => $id]);
    $stmt->setFetchMode(\PDO::FETCH_CLASS, get_class());
    return $stmt->fetch(\PDO::FETCH_CLASS);
  }
  
  public static function readAll($startDate, $endDate=null)
  {
    $stmt = Db::prepare("SELECT orderday.id,UNIX_TIMESTAMP(time) AS time,organizer,deliveryservice,COUNT(ordering.id) AS orderCount ".
      "FROM orderday ".
      "LEFT JOIN ordering ON ordering.day=orderday.id ".
      "WHERE time>=FROM_UNIXTIME(:startdate) ".
     ($endDate ? "AND time<=FROM_UNIXTIME(:enddate)" : ""). 
     "GROUP BY orderday.id");
    $params = [":startdate" => $startDate];
    if ($endDate)
      $params[":enddate"] = $endDate;
    $stmt->execute($params);
    return $stmt->fetchAll(\PDO::FETCH_CLASS, get_class());
  }

  public function getOrders()
  {
    return Order::getAllForDay($this->id);
  }
  
  public function getMyOrder()
  {
    return Order::getMineForDay($this->id);
  }
  
  public function getOrganizer()
  {
    return User::read($this->organizer);
  }
}
