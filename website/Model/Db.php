<?php
namespace Pizza\Model;

/**
 * @brief Datenbankschnittstelle
 * 
 */
class Db {
  private function __construct($connect)
  {
    if ($connect) {
      try {
        $this->pdo = new \PDO('mysql:dbname=pizza;host=localhost', 'pizza', '932MFjxdCiSjaLjE');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
      } catch (PDOException $e) {
        die("Error connecting to DB ({$e->getMessage()})");
      }
    }
  }

  private static function getInstance($connect=true)
  {
    if (self::$instance === null) 
      self::$instance = new self($connect);
    return self::$instance;
  }

/**
 * @brief Ersetzt die PDO-Instanz durch die übergebene
 *
 * Wird für Unit-Tests benutzt, um die Verbindung zur Testdatenbank zu übergeben 
 * @return void
 */
  public static function setPdoForTests($pdo) 
  {
    self::getInstance(false)->pdo = $pdo;
  }

  public static function pdo()
  {
    return self::getInstance()->pdo;
  }

  public static function prepare($str)
  {
    return self::getInstance()->pdo->prepare($str);
  }

  public static function query()
  {
    return call_user_func_array(array(self::getInstance()->pdo, "query"), func_get_args());
  }

  public static function lastInsertId()
  {
    return self::getInstance()->pdo->lastInsertId();
  }
  
  public static function beginTransaction()
  {
    return self::getInstance()->pdo->beginTransaction();
  }
  
  public static function commit()
  {
    return self::getInstance()->pdo->commit();
  }
  
  public static function rollBack()
  {
    return self::getInstance()->pdo->rollBack();
  }

  private $pdo;
  private static $instance;
}
