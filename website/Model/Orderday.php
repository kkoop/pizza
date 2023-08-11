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
  
  public function __construct()
  {
    $t = new \DateTime();
    $t->setTimestamp($this->time);
    $this->time = $t;
  }
  
  public static function read($id)
  {
    $stmt = Db::prepare("SELECT orderday.id,UNIX_TIMESTAMP(time) AS time,organizer,deliveryservice,url,maildue,mailready ".
      "FROM orderday ".
      "WHERE id=:id");
    $stmt->execute([":id" => $id]);
    $stmt->setFetchMode(\PDO::FETCH_CLASS, get_class());
    return $stmt->fetch(\PDO::FETCH_CLASS);
  }
  
  /** 
   * @brief Returns all orderdays after a given date
   * @param $startDate DateTime
   * @param $endDate DateTime|null
   * @return array(Orderday)
   */
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
    $params = [":startdate" => $startDate->getTimestamp()];
    if ($endDate)
      $params[":enddate"] = $endDate->getTimestamp();
    $stmt->execute($params);
    return $stmt->fetchAll(\PDO::FETCH_CLASS, get_class());
  }
  
  /** 
   * @brief Returns all orderdays where order time lies in the past and no due mail has been sent 
   * @return array(Orderday)
   */
  public static function readDue()
  {
    $stmt = Db::query("SELECT orderday.id,UNIX_TIMESTAMP(time) AS time,organizer,deliveryservice,url,maildue,mailready ".
      "FROM orderday ".
      "WHERE time<NOW() AND NOT maildue");
    return $stmt->fetchAll(\PDO::FETCH_CLASS, get_class());
  }
  
  /**
   * @brief create a new orderday record
   * @param $time DateTime
   * @param $service string Delivery service
   * @param $url string|null URL of order website
   * @return Orderday|null Newly created record
   */
  public static function create($time, $service, $url=null)
  {
    $stmt = Db::prepare("INSERT INTO orderday (time,organizer,deliveryservice,url) VALUES (FROM_UNIXTIME(:time),:user,:service,:url)");
    if ($stmt->execute(['time'      => $time->getTimestamp(),
                        ':user'     => $_SESSION['user']->id, 
                        ':service'  => $service, 
                        ":url"      => $url])) {
      $day = self::read(Db::lastInsertId());
      Log::info("created orderday {$day->id}");
      return $day;
    }
    return null;
  }
  
  public function write()
  {
    $stmt = Db::prepare("UPDATE orderday SET time=FROM_UNIXTIME(:time),organizer=:user,deliveryservice=:service,url=:url 
      WHERE id=:id");
    if ($stmt->execute(['time'     => $this->time->getTimestamp(),
                        ':user'    => $this->organizer,
                        ':service' => $this->deliveryservice,
                        ':url'     => $this->url,
                        ':id'      => $this->id]))
    {
      Log::info("edited orderday {$this->id}");
      return true;
    }
    return false;
  }

  public function delete()
  {
    Log::info("deleted orderday {$this->id}");
    $stmt = Db::prepare("DELETE FROM orderday WHERE id=:id");
    return $stmt->execute([':id'=>$this->id]) && $stmt->rowCount() > 0;
  }

  public function getOrders()
  {
    return Order::getAllForDay($this->id);
  }
  
  public function getMyOrders()
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
  
  public function mailReadySent()
  {
    $stmt = Db::prepare("UPDATE orderday SET mailready=1 WHERE id=:id");
    $stmt->execute([":id" => $this->id]);
  }
}
