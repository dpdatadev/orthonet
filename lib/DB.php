<?php /** @noinspection ALL */

/** @noinspection PhpUndefinedClassInspection */

namespace PDOSingleton;
//Basic wrapper/proxy - Singleton PDO
//Documentation: https://phpdelusions.net/pdo/pdo_wrapper
use Exception;
use PDO;

class PostgresImpl
{
    protected static $instance = null;

    protected function __construct()
    {
    }

    protected function __clone()
    {
    }

    /**
     * @throws Exception
     */
    public static function instance()
    {
        if (self::$instance === null) {
            $params = parse_ini_file('database-pg.ini');

            if ($params === false) {
                throw new Exception("Error reading database configuration file");
            }

            $opt = array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => FALSE,
            );

            $dsn = sprintf("pgsql:host=%s;port=%d;dbname=%s;",
                $params['host'],
                $params['port'],
                $params['database']);

            self::$instance = new PDO($dsn, $params['user'], $params['password'], $opt);
        }
        return self::$instance;
    }

    // Proxy to native PDO methods
    public static function __callStatic($method, $args)
    {
        return call_user_func_array(array(self::instance(), $method), $args);
    }

    //Helper function to run prepared statements smoothly
    public static function run($sql, $args = [])
    {
        if (!$args) {
            return self::instance()->query($sql);
        }
        $stmt = self::instance()->prepare($sql);
        $stmt->execute($args);
        return $stmt;
    }
}