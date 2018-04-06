<?php
namespace Pizza\Model;

class Orderday {
  public $id;
  public $time;
  private $organizer;
  public $orderCount;
  
  public static function readAll($startDate, $endDate=null)
  {
    $stmt = Db::prepare("SELECT orderday.id,UNIX_TIMESTAMP(time) AS time,COUNT(ordering.id) AS orderCount ".
      "FROM orderday ".
      "LEFT JOIN ordering ON ordering.day=orderday.id ".
      "WHERE time>=FROM_UNIXTIME(:startdate) ".
     ($endDate ? "AND day<=FROM_UNIXTIME(:enddate)" : ""));
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
  
  public function getMyOrdes()
  {
    return Order::getMineForDay($this->id);
  }
  
  public function getOrganizer()
  {
    return User::read($this->organizer);
  }
}
