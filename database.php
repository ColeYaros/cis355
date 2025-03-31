<?php
class Database
{
    private static $dbHost = 'localhost';
    private static $dbName = 'cis355';  // Your database name
    private static $dbUsername = 'root';  // Default XAMPP username
    private static $dbPassword = 'fake';  // Default XAMPP password (empty)

    private static $connection = null;

    // Prevent instance of this class
    private function __construct() {}

    public static function connect()
    {
        if (self::$connection === null) {
            try {
                self::$connection = new PDO(
                    "mysql:host=" . self::$dbHost . ";dbname=" . self::$dbName,
                    self::$dbUsername,
                    self::$dbPassword
                );
            } catch (PDOException $e) {
                die("Connection failed: " . $e->getMessage());
            }
        }
        return self::$connection;
    }

    public static function disconnect() 
    {
        //echo "in method";
        if (self::$connection !== null){
            self::$connection = null;
        }
        return true;
    }
}
?>

