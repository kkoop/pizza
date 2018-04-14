<?php
namespace Pizza\Model;

class Order {
  public $id;
  public $day;
  private $user;
  private $userObj;
  public $product;
  public $comment;
  public $price;
  
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
  }
  
  public function update()
  {
    $stmt = Db::prepare("UPDATE ordering SET product=:product,comment=:comment,price=:price WHERE id=:id");
    $stmt->execute([":product" => $this->product,
                    ":comment" => $this->comment,
                    ":price"   => $this->price,
                    ":id"      => $this->id]);
  }
  
  public function getUser()
  {
    if ($this->userObj == null)
      $this->userObj = User::read($this->user);
    return $this->userObj;
  }
}
