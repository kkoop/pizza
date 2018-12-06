<?php
namespace Pizza\Model;

class File
{
  public $id;
  public $user;
  public $userName;
  public $title;
  public $created;
  public $expiry;
  
  public static function getAll()
  {
    $stmt = Db::prepare("SELECT upload.id,upload.user,upload.title,UNIX_TIMESTAMP(upload.created) AS created,
        UNIX_TIMESTAMP(upload.expiry) AS expiry,user.name AS userName
      FROM upload
      JOIN user ON user.id=upload.user 
      WHERE upload.expiry IS NULL OR DATE(upload.expiry)>=DATE(NOW())
      ORDER BY created");
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_CLASS, get_class());
  }
  
  public static function read($id)
  {
    $stmt = Db::prepare("SELECT upload.id,upload.user,upload.title,UNIX_TIMESTAMP(upload.created) AS created,
        UNIX_TIMESTAMP(upload.expiry) AS expiry,user.name AS userName
      FROM upload
      JOIN user ON user.id=upload.user 
      WHERE upload.id=:id");
    $stmt->execute([":id" => $id]);
    return $stmt->fetchObject(get_class());
  }
  
  public static function create($title, $tmp_file, $mime, $expiry)
  {
    // bei dieser Gelegenheit aufräumen
    self::deleteOld();

    if ($expiry == '')
      $expiry = NULL;
    $stmt = Db::prepare("INSERT INTO upload (user,title,mime,content,expiry) 
      VALUES (:user,:title,:mime,:file,:expiry)");
    if ($stmt->execute([":user"   => $_SESSION['user']->id,
                        ":title"  => $title,
                        ":mime"   => $mime,
                        ":file"   => file_get_contents($tmp_file),
                        ":expiry" => $expiry]))
      return Db::lastInsertId();
    return null;
  }
  
  public static function deleteOld()
  {
    Db::query("DELETE FROM upload WHERE DATE(upload.expiry)<DATE(NOW())");
  }
  
  public function getBlob(&$mime)
  { 
    $stmt = Db::prepare("SELECT mime,content FROM upload WHERE id=:id");
    $stmt->execute([":id" => $this->id]);
    if ($row = $stmt->fetch()) {
      $mime = $row['mime'];
      return $row['content'];
    }
    return null;
  }
  
  public function delete()
  {
    $stmt = Db::prepare("DELETE FROM upload WHERE id=:id");
    return $stmt->execute([":id" => $this->id]);
  }
}
