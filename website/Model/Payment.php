<?php
namespace Pizza\Model;

class Payment 
{
  public $id;
  public $time;
  public $fromId;
  public $fromName;
  public $toId;
  public $toName;
  public $amount;
  
  public static function readAll($startDate, $endDate, $user)
  {
    $stmt = Db::prepare("SELECT payment.id,UNIX_TIMESTAMP(time) AS time,amount,".
        "fromuser AS fromId,touser AS toId,fuser.name AS fromName,tuser.name AS toName ".
      "FROM payment ".
      "JOIN user AS fuser ON fuser.id=payment.fromuser ".
      "JOIN user AS tuser ON tuser.id=payment.touser ".
      "WHERE time>=FROM_UNIXTIME(:start) AND time<=FROM_UNIXTIME(:end) ".
        ($user ? "AND (payment.fromuser=:user OR payment.touser=:user2)" : ""));
    $params = [":start" => $startDate, ":end" => $endDate];
    if ($user)
      $params[":user"] = $params[":user2"] = $user;
    $stmt->execute($params);
    return $stmt->fetchAll(\PDO::FETCH_CLASS, get_class());
  }
  
  public static function received($fromUser, $amount)
  {
    $stmt = Db::prepare("INSERT INTO payment (fromuser,touser,amount) VALUES (:from,:to,:amount)");
    if ($stmt->execute([':from' => $fromUser, ':to' => $_SESSION['user']->id, ':amount' => $amount])) {
      Log::info(sprintf("got payed %.2f € from user %d", $amount, $fromUser));
      return true;
    }
    return false;
  }
  
  public static function getPayedPerUser()
  {
    $stmt = Db::prepare("SELECT user.id AS user,user.name AS name,SUM(amount) AS amount ".
      "FROM payment ".
      "JOIN user ON user.id=payment.fromuser ".
      "WHERE touser=:user ".
      "GROUP BY fromuser");
    $stmt->execute([":user" => $_SESSION['user']->id]);
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }
  
  public static function getPayedToUser()
  {
    $stmt = Db::prepare("SELECT user.id AS user,user.name AS name,SUM(amount) AS amount ".
      "FROM payment ".
      "JOIN user ON user.id=payment.touser ".
      "WHERE fromuser=:user ".
      "GROUP BY touser");
    $stmt->execute([":user" => $_SESSION['user']->id]);
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }
}
