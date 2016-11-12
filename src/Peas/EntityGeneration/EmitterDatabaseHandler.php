<?php
namespace Peas\EntityGeneration;

use Exception;
use Peas\Database\SqlBuilder as SqlBuilder;
use Peas\Database\cleanPDO as cleanPDO;
use PDO;
use PDOException;
use PDOStatement;

/**
 * @author Steven Chennault schenn@mash.is
 * @link: https://github.com/Schenn/EmitterDatabaseHandler Repository
 */


/**
 * Class EmitterDatabaseHandler - PDO - Improved
 *
 * Provides a safe pdo environment with automatic rollbacks on errors, reconnects on timeouts, and provides
 * convenience methods for the most frequently used types of queries.
 *
 * Handles binding values to a prepared statement and executing them. If you use the convenience method, the query
 * will be built for you with placeholder names and run against your provided arguments without you having to do anything
 * to them. .
 *
 * EmitterDatabaseHandler is only known to be compatible with MySql.
 *
 * @uses Utils\SqlBuilder
 *
 * @package EmitterDatabaseHandler
 */
class EmitterDatabaseHandler
{
  /** @var  cleanPDO $pdo The pdo we will be interacting with */
  protected $pdo;
  /** @var  array $config The configuration dictionary */
  protected $config;
  /** @var  bool $debug Whether or not we are running in debug mode */
  protected $debug;

  /**
   * Create a new EmitterDatabaseHandler
   *
   * Using the provided configuration data, create a new pdo and ensure the connection works.
   * The config is stored in case the server goes away and the pdo needs to be recreated.
   *
   * @param array $config Dictionary of pdo configuration options.
   *     'dbname'=>'pdoi_database',
   *     'username'=>'pdoi_tester',
   *     'password'=>'pdoi_pass',
   *     'driver_options'=>[PDO::ATTR_PERSISTENT => true]
   *
   * @param bool $debug Prints valuable information to the screen so that developers can see how things work.
   * @throws Exception If unable to connect or create a pdo
   * @api
   */
  function __construct($config, $debug = false)
  {
    $limit = 3;
    $counter = 0;
    // Attempt to connect to the database $limit times or until we have success, whichever happens first
    while (true) {
      try {
        $this->pdo = new cleanPDO($config);
        $this->config = $config;
        $this->pdo->query("SET wait_timeout=1200");
        $this->debug = $debug;
        // End the while loop, we have connection
        break;
      } catch (Exception $e) {
        $this->pdo = null;
        $counter++;
        if ($debug) {
          echo "Attempt:" . $counter;
        }
        if ($counter == $limit) {
          // Throw the reason we've failed to connect to the database
          throw $e;
        }
      }
    }
  }

  /**
   * echo special values in a readable format
   * @param string $sql The sql string
   * @param array $args The dictionary
   * @internal
   */
  protected function debug($sql, $args)
  {
    if ($this->debug) {
      echo "<pre>";
      print_r($sql);
      echo("<br />\n");
      print_r($args);
      echo "</pre>";
    }
  }

  /**
   * Prepares the where arguments.
   *
   * Creates the placeholder names to give the Where query and stores the value in a dictionary that holds that relationship
   *
   * @param array $args The original 'where' arguments guiding the query construction
   * @param array $where The dictionary of columns and placeholder names
   * @param array $whereValues The dictionary of placeholder names and column values
   * @internal
   *
   * @todo Decide how we want to handle the like special chars
   */
  protected function prepWhere($args, &$where = [], &$whereValues = [])
  {
    foreach ($args as $column => $value) {
      if (!is_array($value)) {
        $placeholder = ":where" . str_replace(".", "", $column);
        $whereValues[$placeholder] = $value;
        $where[$column] = $value;
      } else {
        foreach ($value as $method => $compareValue) {
          if (!is_array($compareValue)) {
            $placeholder = ":where" . str_replace(".", "", $column);
            $cleanMethod = str_replace(" ", "", $method);
            if ($cleanMethod === "like" || $cleanMethod === "notlike") {
              // Escape additional % and _ values as these are not escaped by bind_value
              if (is_string($compareValue)) {
                $compareValue = "%" . addcslashes($compareValue, "%_") . "%";
              } else {
                $compareValue = "%" . $compareValue . "%";
              }
            }
            $whereValues[$placeholder] = $compareValue;
            $where[$column] = [$method => $compareValue];
          } else {
            $compareCount = count($compareValue);
            for ($i = 0; $i < $compareCount; $i++) {
              $placeholder = ":where" . str_replace(".", "", $column) . $i;
              $whereValues[$placeholder] = $compareValue[$i];
            }
            $where[$column] = [$method => $compareValue];
          }
        }
      }
    }
  }

  /**
   * Prepare the Join Arguments
   *
   * Prepares the arguments the SqlBuilder uses to create the JOIN part of the sql clause
   *
   * @param array $args The arguments being used to guide the construction of the query.
   * @param array $join The dictionary of relationships guiding the joining of tables
   * @param array $joinCondition The condition to use for joining the table.
   */
  protected function prepJoin(&$args, &$join = [], &$joinCondition = [])
  {
    $cols = [];
    $tables = [];
    // Separate the tables and columns in the
    foreach ($args['table'] as $table) {
      foreach ($table as $tableName => $columnList) {
        array_push($tables, $tableName);
        $columnCount = count($columnList);
        for ($i = 0; $i < $columnCount; $i++) {
          if (isset($columnList[$i])) {
            // Attach the table name to the column name
            array_push($cols, $tableName . "." . $columnList[$i]);
          }
        }
      }
    }
    $args['table'] = $tables[0];
    $args['columns'] = $cols;

    if (array_key_exists("where", $args)) {
      foreach ($args['where'] as $table => $columnInfo) {
        foreach ($columnInfo as $columnName => $columnRules) {
          $fullName = $table . "." . $columnName;
          $args['where'][$fullName] = $columnRules;
        }
        unset($args['where'][$table]);
      }
    }

    if (array_key_exists("set", $args)) {
      foreach ($args['set'] as $table => $columnInfo) {
        foreach ($columnInfo as $columnName => $columnRules) {
          $fullName = $table . "." . $columnName;
          $args['set'][$fullName] = $columnRules;
        }
        unset($args['set'][$table]);
      }
    }

    $join = $args['join'];
    if (array_key_exists("on", $args)) {
      $joinCondition["on"] = $args['on'];
    } else if (array_key_exists("using", $args)) {
      $joinCondition["using"] = $args['using'];
    }

  }

  /**
   * Retrieves data from the database.
   *
   * Using a dictionary of arguments, pdoi guides the construction of a select sql query.
   * If the query succeeds, it will return the result. Either as a single object or an array of objects if $obj is provided
   * if no $obj is provided, than it will return a single associative array or an array of arrays.
   *
   * @param array $args Dictionary of arguments which guide the creation of the SELECT query.
   *     REQUIRED
   *          'table'=>['','']
   *               IF JOINING!!!! (if array_key_exists("join", $args))  //put into delete and update!
   *               'table'=>['tableName'=>['columnName','columnName'], "tableName"=>['columnName','columnName']]
   *     OPTIONAL
   *          'columns'=>['','']
   *               if missing or empty, select statement will build as SELECT *.
   *                 If Joining, specify the columns that belong to each table under the table argument
   *          'where'=>[column=>value] | [column=>[method=>value]] | [column=>[method=>[values]]]
   *               prepares columns and values for pdo query.  Each index in where can be any of the above column options.
   *               if method is 'like' or 'not like' | 'notlike', appends % to beginning and end of value
   *               See SqlBuilder.php - WHERE for more information on how it is parsed and how to specify 'method'
   *          'limit'=> #
   *                Sets the LIMIT value in the Select statement
   *          'groupby'=>['column'=>[""], "having"=>['aggmethod'=>"", 'columns'=>['',''], 'comparison'=>['method'=>'','value'=>'']]
   *                Sets the order by to NULL if not already set
   *          'orderby'=>['column'=>'ASC' || 'DESC']
   *     IF JOINING!!!
   *          'where'=>['tableName'=>[column=>value] | [column=>[method=>value]] | [column=>[method=>[values]]], 'tableName'=>[column=>value] | [column=>[method=>value]] | [column=>[method=>[values]]]]
   *          'join'=> ['method'=>'tableName']
   *          'on'=>[['table'=>'column', 'table'=>'column'], ['table'=>'column', 'table'=>'column']]
   *       || 'using' =>['columnName','columnName']
   *     Very Optional
   *          'distinct'=> ALL | DISTINCT | DISTINCTROW
   *                 (doesn't have to be uppercase, ALL is ignored as it's mysql's default)
   *          'result'=> big | small
   *                 (Adds [SQL_SMALL_RESULT] [SQL_BIG_RESULT] to the select statement )
   *          'priority'=>true
   *                 (Adds HIGH_PRIORITY to select statement)
   *          'buffer'=>true
   *                 (Adds SQL_BUFFER_RESULT to select statement)
   *          'cache'=> true | false
   *                 (Adds SQL_CACHE | SQL_NO_CACHE to select statement)
   *
   * @param object|null $obj The object to load the data from the database into
   *
   * @return array|object|null|bool The result or false on failure
   *
   * @uses Utils\SqlBuilder::SELECT
   * @uses Utils\SqlBuilder::JOIN
   * @uses Utils\SqlBuilder::WHERE
   * @uses Utils\SqlBuilder::GROUPBY
   * @uses Utils\SqlBuilder::HAVING
   * @uses Utils\SqlBuilder::ORDERBY
   * @uses Utils\SqlBuilder::LIMIT
   * @api
   * @todo fetch_obj instead of fetch_assoc for when an object isn't provided. Then we'll always return an object or array of objects. Only the object will be a very limited anonymous object.
   * @todo should we return null on failure or false?
   */
  function SELECT($args, $obj = null)
  {
    // Prepare Join arguments first
    // because Where arguments need to be given table names if there's a join.
    $join = [];
    $joinCondition = [];
    if (array_key_exists("join", $args)) {
      $this->prepJoin($args, $join, $joinCondition);
    }

    // Prepare Where arguments
    $where = [];
    $whereValues = [];
    if (isset($args['where'])) {
      // Extracts the values and replaces them with placeholders for the prepare method
      $this->prepWhere($args['where'], $where, $whereValues);
    }

    // Prepare the Groupby and having arguments
    $groupby = [];
    $having = [];
    if (isset($args['groupby'])) {
      $groupby = $args['groupby']['column'];
      //if there's no set orderby but there is a groupby, orderby is set to NULL to increase mysql response speed
      if (!isset($args['orderby']) || empty($args['orderby'])) {
        $args['orderby'] = 'NULL';
      }
      //if there's options for having
      if (isset($args['groupby']['having'])) {
        $having = $args['groupby']['having'];
        unset($args['groupby']['having']);
      }
    }

    // Prepare the orderby arguments
    $orderby = [];
    if (isset($args['orderby'])) {
      $orderby = $args['orderby'];
    }

    // Prepare the limit argument
    $limit = null;
    if (isset($args['limit'])) {
      $limit = $args['limit'];
    }

    //spin sql statement from options
    $sql = (new SqlBuilder())->SELECT($args)->JOIN($join, $joinCondition)->WHERE($where)->GROUPBY($groupby)->HAVING($having)->ORDERBY($orderby)->LIMIT($limit)->getSQL();
    // If we're debugging, display the sql and where values
    $stmt = $this->run($sql, $whereValues);
    //if plugging results into an object
    if (is_object($obj)) {

      $stmt->setFetchMode(PDO::FETCH_INTO, $obj);
      $chunk = [];
      //for each result, put representative object into an array
      while ($object = $stmt->fetch()) {

        array_push($chunk, clone $object);
      }
    } else {
      //returns an array of associative arrays of result(s)
      $chunk = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    //if result
    if (count($chunk) > 0) {
      //if only 1 result
      if (count($chunk) === 1) {
        //return that 1 result instead of the array containing one result
        return ($chunk[0]);
      } else {
        //return the whole result array if result size > 1
        return ($chunk);
      }
    } else {
      //no results
      return (NULL);
    }
  }

  /**
   * Inserts data into the database
   *
   * Converts values into placeholder values, prepares the statement and executes it against the provided values.
   * If Values are an array of data-sets, INSERT will store all the values before committing the transaction.
   *
   * Provides convenience in executing an insert over an array of data-sets
   *
   * @param array $args Dictionary of arguments used to guide the construction of the query
   *      REQUIRED
   *         'table'=>'',
   *         'columns'=>['','']
   *         'values' => ['column'=>'value'] | [["column"=>"value","column"=>"value"],["column"=>"value","column"=>"value"] ]
   *
   * @uses Utils\SqlBuilder::INSERT
   * @return bool Success
   * @throws Exception if statement->execute fails with bound variables
   * @api
   *
   */
  function INSERT($args)
  {
    // spin sql statement from arguments with placeholders
    $sql = (new SqlBuilder())->INSERT($args)->getSQL();
    //if in debug mode
    $this->debug($sql, $args);
    try {
      //verify db exists
      $this->ping();
      //begin a transaction session with the database
      $this->pdo->beginTransaction();
      //prepare the statement
      $stmt = $this->pdo->prepare($sql);

      // if Values are array of data-sets. Process multiple inserts in one table using bindParam
      if (isset($args['values'][0])) {
        $id = [];
        $columns = $args['columns'];
        $colCount = count($columns);
        $cols = [];
        for ($i = 0; $i < $colCount; $i++) {
          //bind insert placeholders to variable parameters
          $stmt->bindParam(":$columns[$i]", $$columns[$i]);
          array_push($cols, $$columns[$i]);
        }
        $valCount = count($args['values']);
        for ($i = 0; $i < $valCount; $i++) {
          //for each grouping of values in a multi-entity insert
          foreach ($args['values'][$i] as $column => $value) {
            //set variable placeholders to current row values
            $$column = $value;
            //if debugging
            if ($this->debug) print_r($$column . ":" . $$column);
          }
          //execute statement with bound parameters for each row in array of value arrays
          $stmt->execute();
          array_push($id, $this->pdo->lastInsertId());

          //destroy temporary placeholder without disturbing index count
          $varCount = count($cols);
          for ($z = 0; $z < $varCount; $z++) {
            $$cols[$z] = null;
          }
        }
      } //one data-set to insert
      else {
        $values = [];
        foreach ($args['values'] as $column => $value) {
          if (isset($value)) {
            $prepCol = ":$column";
            $values[$prepCol] = $value;
          }
        }
        $this->debug("Values: ", $values);
        //executes with parameter array, if fails throws exception
        if (!($stmt->execute($values))) {
          throw new Exception("Insert Failed");
        }
        $id = $this->pdo->lastInsertId();
      }
      //returns result of committing changes to db
      if ($this->pdo->commit()) {
        return $id;
      } else {
        return false;
      }

    } catch (PDOException $pe) {
      $this->pdo->rollBack();
      echo "Insert Failed: " . $pe->getMessage();
      return false;
    } catch (Exception $e) {
      $this->pdo->rollBack();
      echo "Insert Failed: " . $e->getMessage();
      return false;
    }
  }

  /**
   * Updates data in the database
   *
   * Generates arguments for the SqlBuilder to use to create the sql query. Also prepares placeholders and executes
   * the prepared statement against the provided arguments.
   *
   * @param array $args Dictionary of arguments used to guide the construction of the update query.
   *        REQUIRED
   *           'table'=>'',
   *           'set'=>['column'=>value, column=>value],
   *           'where'=>[column=>value] | [column=>[method=>value]] | [column=>[method=>[values]]]
   *                  prepares columns and values for pdo query.  Each index in where can be any of the above column options.
   *                  if method is 'like' or 'not like' | 'notlike', appends % to beginning and end of value
   *                  See SqlBuilder.php - WHERE for more information on how it is parsed and how to specify 'method'
   *        OPTIONAL
   *           'orderby'=>['column'=>'ASC' || 'DESC']
   *           'limit'=> #
   *                  Sets the LIMIT value in the Update statement
   *
   * @uses Utils\SqlBuilder::UPDATE
   * @uses Utils\SqlBuilder::JOIN
   * @uses Utils\SqlBuilder::SET
   * @uses Utils\SqlBuilder::WHERE
   * @uses Utils\SqlBuilder::ORDERBY
   * @uses Utils\SqlBuilder::LIMIT
   * @return bool success
   * @throws Exception if no 'set' or 'where' arguments provided
   * @api
   */
  function UPDATE($args)
  {

    $setValues = [];
    // Generate join arguments
    $join = [];
    $joinCondition = [];
    if (array_key_exists("join", $args)) { // if select query involves a join
      $this->prepJoin($args, $join, $joinCondition);
    }

    // Generate set arguments
    if (isset($args['set'])) {  //set values for update (UPDATE table SET setColumn = setValue, etc)
      foreach ($args['set'] as $column => $value) {
        $prepCol = ":set" . str_replace(".", "", $column);
        $setValues[$prepCol] = $value;
      }
    } // No set values assigned, throw an error
    else {
      throw new Exception("Set values missing for update command!", 10);
    }

    // Prepare where arguments
    $where = [];
    $whereValues = [];
    if (isset($args['where'])) { //where
      $this->prepWhere($args['where'], $where, $whereValues);
    } else {
      throw new Exception("Missing WHERE values for update command!", 11);
    }

    // Prepare order by and limit arguments
    $orderby = [];
    if (isset($args['orderby'])) {
      $orderby = $args['orderby'];
    }
    $limit = null; //limit
    if (isset($args['limit']) && empty($join)) {
      $limit = $args['limit'];
    }

    //Spin sql from options
    $sql = (new SqlBuilder())->UPDATE($args)->JOIN($join, $joinCondition)->SET($args)->WHERE($where)->ORDERBY($orderby)->LIMIT($limit)->getSQL();

    return ($this->run($sql, array_merge($setValues, $whereValues)));

  }

  /**
   * Delete data from the database
   *
   * Uses the provided arguments to guide the construction of a DELETE query. Prepares and executes the statement
   * with bound values
   *
   * @param array $args Dictionary of arguments used to guide the sql construction.
   *        REQUIRED
   *             'table'=>'',
   *             'where'=>[column=>value] | [column=>[method=>value]] | [column=>[method=>[values]]]
   *                  prepares columns and values for pdo query.  Each index in where can be any of the above column options.
   *                  if method is 'like' or 'not like' | 'notlike', appends % to beginning and end of value
   *                  See SqlBuilder.php - WHERE for more information on how it is parsed and how to specify 'method'
   *        OPTIONAL
   *             'orderby'=>['column'=>'ASC' | 'DESC']
   *             'limit'=> #
   *                  Sets the LIMIT value in the Select statement
   *
   * @uses Utils\SqlBuilder::DELETE
   * @uses Utils\SqlBuilder::JOIN
   * @uses Utils\SqlBuilder::WHERE
   * @uses Utils\SqlBuilder::ORDERBY
   * @uses Utils\SqlBuilder::LIMIT
   *
   * @return bool success
   * @throws Exception
   * @api
   */
  function DELETE($args)
  {
    // prepare join arguments
    $join = [];
    $joinCondition = [];
    if (array_key_exists("join", $args)) { // if select query involves a join
      $this->prepJoin($args, $join, $joinCondition);
    }

    // prepare where arguments
    $where = [];
    $whereValues = [];
    if (isset($args['where'])) {
      $this->prepWhere($args['where'], $where, $whereValues);
    } else {
      throw new Exception("Where values needed to delete from table.", 12);
    }

    // Prepare order by and limit
    $order = [];
    if (isset($args['orderby'])) { //orderby
      $order = $args['orderby'];
    }
    $limit = null;
    if (isset($args['limit'])) { //limit
      $limit = $args['limit'];
    }

    //spin sql from arguments
    $sql = (new SqlBuilder())->DELETE($args)->JOIN($join, $joinCondition)->WHERE($where)->ORDERBY($order)->LIMIT($limit)->getSQL();
    return ($this->run($sql, $whereValues));
  }


  /**
   * Build a Create Table query
   *
   * This method guides the construction and execution of a CREATE TABLE query
   *
   * @param string $table The table name
   * @param array $props The dictionary of fields=>[mysql column properties]
   *
   * @uses Utils\SqlBuilder::CREATE
   * @return bool success
   * @throws string If the table already exists
   * @api
   */
  function CREATE($table, $props)
  {
    $this->pdo->beginTransaction();
    if (!$this->tableExists($table)) {
      $sql = (new SqlBuilder())->CREATE($table, $props)->getSQL();
      return ($this->run($sql));
    } else {
      // Table already exists
      throw new Exception("Table already exists: " . $table);
    }
  }

  /**
   * Drop a table from the database
   *
   * Constructs and executes a drop sql statement
   *
   * @param string $table Name of the table to drop
   * @api
   * @return bool Success
   *
   */
  function DROP($table)
  {
    $sql = (new SqlBuilder())->DROP($table)->getSQL();
    return ($this->run($sql));
  }

  /**
   * Describes a table
   *
   * Retrieves the table Schema from the database as an associative array
   *
   * @param string $table table name
   * @return array result
   * @api
   */
  function describe($table)
  {
    $sql = (new SqlBuilder())->DESCRIBE($table)->getSQL(); // Generate sql
    $stmt = $this->run($sql);
    $chunk = $stmt->fetchAll(PDO::FETCH_ASSOC); //return associative array of table Schema
    return ($chunk);
  }

  /**
   * Ensure that we are still connected to the database
   *
   * Otherwise, it attempts to recreate the pdo
   *
   * @internal
   */
  function ping()
  {
    try {
      $this->pdo->query("SELECT 1");
      a:
      return (true);
    } catch (PDOException $pe) {
      $this->pdo = new cleanPDO($this->config);
      $this->pdo->query("SET wait_timeout=1200");
      goto a;
    }
  }

  /**
   * Attempt to run provided sql against provided values
   *
   * @param string $sql Must have placeholder values
   * @param array $values Dictionary of placeholder names => values
   * @return bool|PDOStatement Success|Result set
   *
   * @throws Exception If no sql is provided to run
   *
   * @todo This wont work for some types of sql methods. We should try to do a check to guide the flow of execution and what is returned
   */
  function run($sql, $values = [])
  {
    if ($sql !== "") {
      $method = substr($sql, 0, strpos($sql, " "));
      $this->debug($sql, $values);
      try {
        $this->ping();
        if ($method !== "SELECT" && $method !== "DESC") {
          $this->pdo->beginTransaction();
        }
        $stmt = $this->pdo->prepare($sql);
        if (!empty($values)) {
          $stmt->execute($values);
        } else {
          $stmt->execute();
        }
        if ($method !== "SELECT" && $method !== "DESC") {
          return ($this->pdo->commit());
        } else {
          // Selects can be fetched into a multitude of ways. Return the statement so that the caller can decide
          // Desc is a kind of select that returns the table | column Schema as a data set
          return ($stmt);
        }
      } catch (PDOException $p) {
        if ($method !== "SELECT" && $method !== "DESC") {
          $this->pdo->rollBack();
        }
        echo (ucfirst(strtolower($method)) . " Failed: ") . $p->getMessage();
        return (false);
      } catch (Exception $e) {
        if ($method !== "SELECT" && $method !== "DESC") {
          $this->pdo->rollBack();
        }
        echo (ucfirst(strtolower($method)) . " Failed: ") . $e->getMessage();
        return (false);
      }
    } else {
      throw new Exception("No sql provided to run method");
    }
  }

  /**
   * Determine if a table exists
   *
   * @param string $table The name of the table to check
   * @return bool
   */
  function tableExists($table)
  {
    try {
      $result = $this->pdo->query("Select 1 from {$table}");
      return !!$result;
    } catch (PDOException $pe) {
      return false;
    }
  }
}