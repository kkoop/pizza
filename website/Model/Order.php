<?php
namespace Pizza\Model;

class Order 
{
  public $id;
  public $day;
  private $user;
  private $userObj;
  public $product;
  public $comment;
  public $price;
  
  public static function read($id)
  {
    $stmt = Db::prepare("SELECT * FROM ordering WHERE id=:id");
    $stmt->execute([":id" => $id]);
    $stmt->setFetchMode(\PDO::FETCH_CLASS, get_class());
    return $stmt->fetch(\PDO::FETCH_CLASS);
  }
  
  public static function getAllForDay($dayId)
  {
    $stmt = Db::prepare("SELECT * FROM ordering WHERE day=:day");
    $stmt->execute([":day" => $dayId]);
    return $stmt->fetchAll(\PDO::FETCH_CLASS, get_class());
  }
  
  public static function getMineForDay($dayId)
  {
    $stmt = Db::prepare("SELECT * FROM ordering WHERE day=:day AND user=:user");
    $stmt->execute([":day" => $dayId, ":user" => $_SESSION['user']->id]);
    $stmt->setFetchMode(\PDO::FETCH_CLASS, get_class());
    return $stmt->fetch(\PDO::FETCH_CLASS);
  }
  
  public static function create($dayId, $product, $comment, $price)
  {
    $stmt = Db::prepare("INSERT INTO ordering (day,user,product,comment,price) VALUES (:day,:user,:product,:comment,:price)");
    $stmt->execute([":day"     => $dayId, 
                    ":user"    => $_SESSION['user']->id,
                    ":product" => $product,
                    ":comment" => $comment,
                    ":price"   => $price]);
    Log::info(sprintf("created order %s (%.2f €) for day %d", $product, $price, $dayId));
  }
  
  public function update()
  {
    $stmt = Db::prepare("UPDATE ordering SET product=:product,comment=:comment,price=:price WHERE id=:id");
    $stmt->execute([":product" => $this->product,
                    ":comment" => $this->comment,
                    ":price"   => $this->price,
                    ":id"      => $this->id]);
    Log::info(sprintf("changed order %d: %s (%.2f €) for day %d", $this->id, $this->product, $this->price, $this->day));
  }
  
  public function getUser()
  {
    if ($this->userObj == null)
      $this->userObj = User::read($this->user);
    return $this->userObj;
  }
  
  public function getDay()
  {
    return Orderday::read($this->day);
  }
  
  public static function getOwedPerUser()
  {
    $stmt = Db::prepare("SELECT user.id AS user,user.name AS name,SUM(price) AS amount ".
      "FROM ordering ".
      "JOIN user ON user.id=ordering.user ".
      "JOIN orderday ON orderday.id=ordering.day ".
      "WHERE orderday.organizer=:user AND orderday.time<NOW() AND ordering.user!=:user2 ".
      "GROUP BY ordering.user");
    $stmt->execute([":user" => $_SESSION['user']->id, ":user2" => $_SESSION['user']->id]);
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }

  public static function getOwingToUser()
  {
    $stmt = Db::prepare("SELECT user.id AS user,user.name AS name,SUM(price) AS amount ".
      "FROM ordering ".
      "JOIN orderday ON orderday.id=ordering.day ".
      "JOIN user ON user.id=orderday.organizer ".
      "WHERE orderday.organizer!=:user AND orderday.time<NOW() AND ordering.user=:user2 ".
      "GROUP BY orderday.organizer");
    $stmt->execute([":user" => $_SESSION['user']->id, ":user2" => $_SESSION['user']->id]);
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }
}
