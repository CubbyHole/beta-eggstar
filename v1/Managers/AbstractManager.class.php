<?php
/**
 * Created by Notepad++.
 * User: Alban Truc
 * Date: 30/01/14
 * Time: 14:51
 */

abstract class AbstractManager
{

    const DBHOST = 'localhost';
    //const DBUSER = '';
    //const DBPWD = '';
    const DBPORT = 27017;
    const DBNAME = 'cubbyhole';
 
    private static $instance;
    protected $connection;
    protected $database;

    /**
     * Constructeur: génère la connexion à la base de données Mongo.
     * @author Alban Truc
     * @since 30/01/14
     */

    public function __construct() 
    {
        $connection_string = sprintf('mongodb://%s:%d/%s', AbstractManager::DBHOST, AbstractManager::DBPORT, AbstractManager::DBNAME);
        try 
        {
            $this->connection = new Mongo($connection_string);
            $this->database = $this->connection->selectDB(AbstractManager::DBNAME);
        } 
        catch (MongoConnectionException $e) 
        {
            throw $e;
        }
    }

    /**
     * @author Alban Truc
     * @since 30/01/14
     * @return mixed
     */

    static public function instantiate()
    {
        if(!isset(self::$instance))
        {
            $class = __CLASS__;
            self::$instance = new $class;
        }
        return self::$instance;
    }

    /**
     * @author Alban Truc
     * @param $name
     * @since 30/01/14
     * @return mixed Renvoie la collection voulue
     */

    public function getCollection($name)
    {
        return $this->database->selectCollection($name);
    }
   
}