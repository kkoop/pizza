<?php
namespace Pizza\Model;

class Orderday 
{
  public $id;
  public $time;
  public $organizer;
  public $deliveryservice;
  public $url;
  public $maildue;
  public $mailready;
  public $orderCount;
  public $amount;
  
  public static function read($id)
  {
    $stmt = Db::prepare("SELECT orderday.id,UNIX_TIMESTAMP(time) AS time,organizer,deliveryservice,url,maildue,mailready ".
      "FROM orderday ".
      "WHERE id=:id");
    $stmt->execute([":id" => $id]);
    $stmt->setFetchMode(\PDO::FETCH_CLASS, get_class());
    return $stmt->fetch(\PDO::FETCH_CLASS);
  }
  
  public static function readAll($startDate, $endDate=null)
  {
    $stmt = Db::prepare("SELECT orderday.id,UNIX_TIMESTAMP(time) AS time,organizer,deliveryservice,url,maildue,mailready,".
        "COUNT(ordering.id) AS orderCount,SUM(price) AS amount ".
      "FROM orderday ".
      "LEFT JOIN ordering ON ordering.day=orderday.id ".
      "WHERE time>=FROM_UNIXTIME(:startdate) ".
     ($endDate ? "AND time<=FROM_UNIXTIME(:enddate)" : ""). 
     "GROUP BY orderday.id ".
     "ORDER BY time DESC");
    $params = [":startdate" => $startDate];
    if ($endDate)
      $params[":enddate"] = $endDate;
    $stmt->execute($params);
    return $stmt->fetchAll(\PDO::FETCH_CLASS, get_class());
  }
  
  /** 
   * @brief Returns all orderdays 
   * @return array(Orderday)
   */
  public static function readDue()
  {
    $stmt = Db::query("SELECT orderday.id,UNIX_TIMESTAMP(time) AS time,organizer,deliveryservice,url,maildue,mailready ".
      "FROM orderday ".
      "WHERE time<NOW() AND NOT maildue");
    return $stmt->fetchAll(\PDO::FETCH_CLASS, get_class());
  }
  
  public static function create($time, $service, $url=null)
  {
    $stmt = Db::prepare("INSERT INTO orderday (time,organizer,deliveryservice,url) VALUES (:time,:user,:service,:url)");
    if ($stmt->execute(['time' => $time, ':user' => $_SESSION['user']->id, ':service' => $service, ":url" => $url])) {
      $day = self::read(Db::lastInsertId());
      Log::info("created orderday {$day->id}");
      return $day;
    }
    return null;
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
  
  public function mailDueSent()
  {
    $stmt = Db::prepare("UPDATE orderday SET maildue=1 WHERE id=:id");
    $stmt->execute([":id" => $this->id]);
  }
}
