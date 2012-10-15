<?php
     
     class PDOIConfig
     {
	  protected static $dbtype = "mysql";
	  protected static $dbhost = "localhost";
	  protected static $dbuser = "db_user";
	  protected static $dbpass = "db_user_password";
	  protected static $dbname = "db_name";
	  
	  public static function setType($dbtype)
	  {
	       self::$dbtype = $dbtype;
	  }
	  
	  public static function setHost($dbhost)
	  {
	       self::$dbhost = $dbhost;
	  }
	  
	  public static function setUser($dbuser)
	  {
	       self::$dbuser = $dbuser;
	  }
	  
	  public static function setPass($dbpass)
	  {
	       self::$dbpass = $dbpass;
	  }
	  
	  public static function setDB($db)
	  {
	       self::$dbname=$db;
	  }
	  
	  public static function getType()
	  {
	       return self::$dbtype;
	  }
	  
	  public static function getHost()
	  {
	       return self::$dbhost;
	  }
	  
	  public static function getUser()
	  {
	       return self::$dbuser;
	  }
	  
	  public static function getPass()
	  {
	       return self::$dbpass;
	  }
	  
	  public static function getDB()
	  {
	       return self::$dbname;
	  }
	  
     }

?>