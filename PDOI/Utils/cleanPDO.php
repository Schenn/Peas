<?php
namespace PDOI\Utils;
use PDO;
/**
 * @author Steven Chennault schenn@gmail.com
 * @link: https://github.com/Schenn/EmitterDatabaseHandler Repository
 */

/**
 * Class cleanPDO
 *
 * Constructs a PDO ready to handle errors using a dictionary of configuration options.
 *
 * @package EmitterDatabaseHandler
 * @internal
 */

class cleanPDO extends PDO {
    /** @var bool $hasActiveTransaction Is the PDO currently in the middle of a transaction */
    protected $hasActiveTransaction = false;

    /**
     * Create a new cleanPDO
     *
     * @param array $config  The dictionary of configuration options
     *  'dbname'=>'pdoi_tester',
     *  'host'=>'127.0.0.1',
     *  'username'=>'pdoi_tester',
     *  'password'=>'pdoi_pass',
     *  'driver_options'=>[PDO::ATTR_PERSISTENT => true]
     *
     */
    function __construct($config){
        if(!isset($config["dbname"]) || !isset($config['username']) || !isset($config['password'])) {
            throw new \PDOException("Invalid config arguments");
        }
        $dsn = 'mysql:dbname='.$config['dbname'].';';
        if(isset($config['host'])){
            $dsn .= $config['host'];
        } else {
            $dsn .= '127.0.0.1';
        }
        if(!isset($config['driver_options'])){
            $config['driver_options'] = [PDO::ATTR_PERSISTENT => true];
        }
        $this->hasActiveTransaction = false;
        parent::__construct($dsn, $config['username'], $config['password'], $config['driver_options']);
        parent::setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Start working
     *
     * If the pdo is already working, fail, otherwise begin a transaction
     * @return bool
     */
    function beginTransaction() {
        if($this->hasActiveTransaction){
            return(false);
        }
        else {
            $this->hasActiveTransaction = parent::beginTransaction();
            return($this->hasActiveTransaction);
        }
    }

    /**
     * Commit the work
     *
     * Commit the work the pdo has done and mark as not working
     *
     * @return bool
     */
    function commit() {
        $this->hasActiveTransaction = false;
        return(parent::commit());
    }

    /**
     * Rollback the work
     *
     * Rollback the work the pdo has done and mark as not working
     *
     * @return bool
     */
    function rollback() {
        $this->hasActiveTransaction = false;
        return(parent::rollBack());
    }

    /**
     * Return the last insert id
     * @return string|int
     */
    function lastInsertId(){
        if($this->hasActiveTransaction) {
            return(parent::lastInsertId());
        } else {
            return 0;
        }
    }
}
