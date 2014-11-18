<?php
     namespace PDOI;
     require_once("Utils/sqlSpinner.php");
     use PDOI\Utils\sqlSpinner as sqlSpinner;
     use PDO;
     use PDOException;
     /*
      *   Author: Steven Chennault
      *   Email: schenn@gmail.com
      *   Github: https://github.com/Schenn/PDOI
      *   Name: PDOI.php
      *   Description:  PDOI is a class which can be used to greatly simplify the dynamic needs of a webmaster.
      *             It handles the basic database demands with error catching and dynamic sql generation from an array of parameters.
      *             Currently it is only known to be compatable with mysql, however that setting can be overwritten in the __construct of the cleanPDO
      *
      */

     /*
      * Name: cleanPDO
      * Description:  PDO which throws exceptions
      * takes associative array of configuration options
      *    $config = [
               'dns'=>'mysql:dbname=pdoi_tester;',
               'host'=>'127.0.0.1',
               'username'=>'pdoi_tester',
               'password'=>'pdoi_pass',
               'driver_options'=>[PDO::ATTR_PERSISTENT => true]
          ];
      */


     class cleanPDO extends PDO {
          protected $hasActiveTransaction = false;

          function __construct($config){
               $config['dns'] = 'mysql:dbname='.$config['dbname'].';';
               if(isset($config['host'])){
                    $config['dns'] .= $config['host'];
               } else {
                    $config['dns'] .= '127.0.0.1';
               }
               if(!isset($config['driver_options'])){
                    $config['driver_options'] = [PDO::ATTR_PERSISTENT => true];
               }
               $this->hasActiveTransaction = false;
               parent::__construct($config['dns'], $config['username'], $config['password'], $config['driver_options']);
               parent::setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
          }

          function beginTransaction() {
               if($this->hasActiveTransaction){
                    return(false);
               }
               else {
                    $this->hasActiveTransaction = parent::beginTransaction();
                    return($this->hasActiveTransaction);
               }
          }

          function commit() {
               $this->hasActiveTransaction = false;
               return(parent::commit());
          }

          function rollback() {
               $this->hasActiveTransaction = false;
               return(parent::rollBack());
          }
     }

     /*
      * Class Name: PDOI
      * Description: Takes an associative array of parameters which is used to
      * construct (via sqlSpinner), prepare and execute an sql statement with
      * error catching, transactions, rollback and prepared values.
      */


     class PDOI
     {
          protected $pdo;
          protected $config;
          protected $debug;


          /* Method Name: __construct
           * Takes:  $config = [
                         'dbname'=>'pdoi_database',
                         'username'=>'pdoi_tester',
                         'password'=>'pdoi_pass',
                         'driver_options'=>[PDO::ATTR_PERSISTENT => true]
                    ];
                    debug = false
           * Description: Creates a new PDO object using the config information sent,
           * sets the pdoi config (in order to recreate the pdo should the mysql server 'go away')
           * Sets the timeout to 1200 ms to keep the connection alive
           * Sets the pdoi debug property to the true/false setting sent across
           */

          function __construct($config, $debug = false){
               $limit = 3;
               $counter = 0;
               while(true){
                    try {
                         $this->pdo = new cleanPDO($config);
                         $this->config=$config;
                         $this->pdo->query("SET wait_timeout=1200");
                         $this->debug = $debug;
                         break;
                    }
                    catch(Exception $e){
                         $this->pdo = null;
                         $counter++;
                         if($debug){
                              echo "Attempt:".$counter;
                         }
                         if($counter == $limit){
                              throw $e;
                         }
                    }
               }
          }

          protected function prepWhere($args, &$where =[], &$whereValues = []){
               foreach($args as $column=>$value){
                    if(!is_array($value)){
                         $c = ":where".str_replace(".","",$column);
                         $whereValues[$c]=$value;
                         $where[$column]=$value;
                    }
                    else {
                         foreach($value as $method=>$compareValue){
                              if(!is_array($compareValue)){
                                   $c = ":where".str_replace(".","",$column);
                                   $m = str_replace(" ","",$method);
                                   if($m === "like" || $m === "notlike"){
                                        $compareValue = "%".$compareValue."%";
                                   }
                                   $whereValues[$c] = $compareValue;
                                   $where[$column] = [$method=>$compareValue];
                              }
                              else {
                                   $compCount = count($compareValue);
                                   for($i=0; $i<$compCount; $i++){
                                        $c = ":where".str_replace(".","",$column).$i;
                                        $whereValues[$c]=$compareValue[$i];
                                   }
                                   $where[$column]=[$method=>$compareValue];
                              }
                         }
                    }
               }


          }

          protected function prepJoin(&$args, &$join=[], &$jCond=[]){
               $cols = [];
               $tables = [];
               foreach($args['table'] as $table){
                   foreach($table as $tableName=>$columnList){
                        array_push($tables, $tableName);
                        $c = count($columnList);
                        for($i=0;$i<$c;$i++){
                            if(isset($columnList[$i])){
                                array_push($cols, $tableName.".".$columnList[$i]);
                            }
                        }
                   }
               }
               $args['table'] = $tables[0];
               $args['columns']=$cols;

               if(array_key_exists("where", $args)){
                    foreach($args['where'] as $table=>$columnInfo){
                         foreach($columnInfo as $columnName=>$columnRules){
                              $ky = $table.".".$columnName;
                              $args['where'][$ky]=$columnRules;
                         }
                         unset($args['where'][$table]);
                    }
               }

               if(array_key_exists("set", $args)){
                    foreach($args['set'] as $table=>$columnInfo){
                         foreach($columnInfo as $columnName=>$columnRules){
                              $ky = $table.".".$columnName;
                              $args['set'][$ky]=$columnRules;
                         }
                         unset($args['set'][$table]);
                    }
               }

               $join = $args['join'];
               if(array_key_exists("on", $args)){
                    $jCond["on"] = $args['on'];
               }
               else if(array_key_exists("using", $args)){
                    $jCond["using"] = $args['using'];
               }

          }


          /* Method Name: SELECT
           * Takes: $args = [
           *             REQUIRED
           *                  'table'=>['','']
           *                       IF JOINING!!!! (if array_key_exists("join", $args))  //put into delete and update!
           *                       'table'=>['tableName'=>['columnName','columnName'], "tableName"=>['columnName','columnName']]
           *             OPTIONAL
           *                  'columns'=>['','']
           *                       if missing or empty, select statement will build as SELECT *
           *                  'where'=>[column=>value] | [column=>[method=>value]] | [column=>[method=>[values]]]
           *                       prepares columns and values for pdo query.  Each index in where can be any of the above column options.
           *                       if method is 'like' or 'not like' | 'notlike', appends % to beginning and end of value
           *                       See sqlSpinner.php - WHERE for more information on how it is parsed and how to specify 'method'
           *
           *                       IF JOINING!!!
           *                       'where'=>['tableName'=>[column=>value] | [column=>[method=>value]] | [column=>[method=>[values]]], 'tableName'=>[column=>value] | [column=>[method=>value]] | [column=>[method=>[values]]]]
           *                  'join'=> ['method'=>'tableName']
           *                  'on'=>[['table'=>'column', 'table'=>'column'], ['table'=>'column', 'table'=>'column']]
           *                  || 'using' =>['columnName','columnName']
           *                  'groupby'=>['column'=>[""], "having"=>['aggmethod'=>"", 'columns'=>['',''], 'comparison'=>['method'=>'','value'=>'']]
           *                       Sets the order by to NULL if not already set
           *                       See sqlSpinner.php - HAVING for more information on how having is passed and how to set it up
           *                  'orderby'=>['column'=>'ASC' || 'DESC']
           *                  'limit'=> #
           *                       Sets the LIMIT value in the Select statemen
           *             Very Optional
           *                  'distinct'=> ALL | DISTINCT | DISTINCTROW
           *                       (doesn't have to be uppercase, ALL is ignored as it's mysql's default)
           *                  'result'=> big | small
           *                       (Adds [SQL_SMALL_RESULT] [SQL_BIG_RESULT] to the select statement )
           *                  'priority'=>true
           *                       (Adds HIGH_PRIORITY to select statement)
           *                  'buffer'=>true
           *                       (Adds SQL_BUFFER_RESULT to select statement)
           *                  'cache'=> true | false
           *                       (Adds SQL_CACHE | SQL_NO_CACHE to select statement)
           *                  ];
           *        $obj = Object with properties capable of holding returned rows
           * Description:  Selects rows from the database
           *        Converts where values into unique placeholders and values for pdo->prepare(sql) and pdo->execute(values)
           *        Sends values through the sqlSpinner to generate an sql query string which is ready to be used in pdo->prepare.
           *        if debug is set to true, prints the sql and the prepared value array
           *        if query succeeds, returns result as an array of associative array, else returns false
           */

          function SELECT($args, $obj = null){
               $join = [];
               $jCond = [];
               if(array_key_exists("join", $args)){ // if select query involves a join
                    $this->prepJoin($args, $join, $jCond);
               }

               $where = [];
               $whereValues = [];
               if(isset($args['where'])){ //if where options
                    //converts where into a prepared value array
                    $this->prepWhere($args['where'], $where, $whereValues);
               }

               $groupby = [];
               $having = [];
               if(isset($args['groupby'])){//if groupby options
                    $groupby = $args['groupby']['column'];
                    //if theres no set orderby but there is a groupby, orderby is set to NULL to increase mysql response speed
                    if(!isset($args['orderby']) || empty($args['orderby'])){
                         $args['orderby'] = 'NULL';
                    }
                    if(isset($args['groupby']['having'])){  //if having options
                         $having = $args['groupby']['having'];
                         unset($args['groupby']['having']);
                    }
               }

               $orderby= [];
               if(isset($args['orderby'])){ //if orderby options
                    $orderby = $args['orderby'];
               }

               $limit = null;
               if(isset($args['limit'])){ //if limit
                    $limit = $args['limit'];
               }

               //spin sql statement from options
               $sql = (new Utils\sqlSpinner())->SELECT($args)->JOIN($join, $jCond)->WHERE($where)->GROUPBY($groupby)->HAVING($having)->ORDERBY($orderby)->LIMIT($limit)->getSQL();
               if($this->debug){ //if in debug mode
                   echo "<pre>";
                    print_r($sql);
                    echo("<br />\n");
                    print_r($whereValues);
                    echo "</pre>";
               }
               $this->ping(); // before running sql query, ensure the db is still 'there'
               try {
                    $stmt = $this->pdo->prepare($sql);  //prepare sql statement through pdo
                    $stmt->execute($whereValues); //execute statement with prepared value array
                    if(is_object($obj)){ //if plugging results into an object
                         $stmt->setFetchMode(PDO::FETCH_INTO, $obj);
                         $chunk = [];
                         while($o = $stmt->fetch()){ //for each result, put representitive object into an array
                              array_push($chunk, clone $o);
                         }
                    }
                    else {
                         $chunk = $stmt->fetchAll(PDO::FETCH_ASSOC); //returns an associative array of result(s)
                    }
                    if(count($chunk) > 0){ //if result
                         if(count($chunk) === 1){ //if only 1 result
                              return($chunk[0]); //return 1 result as the result entry
                         }
                         else {
                              return($chunk); //return the whole result array if result size > 1
                         }
                    }
                    else {
                         return(false); //no results
                    }
               }
               catch (PDOException $e){ //pdo failure
                    echo "SELECT failed: ".$e->getMessage();
                    return(false);
               }
          }

          /* Method Name: INSERT
           * Takes: $args = [
           *             REQUIRED
           *                  'table'=>'',
           *                  'columns'=>['','']
           *                  'values' => ['column'=>'value'] | [["column"=>"value","column"=>"value"],["column"=>"value","column"=>"value"] ]
           *                  ];
           * Description: Inserts rows into the database
           *   Converts Values to placeholder values for sql statement.
           *   Values can either be a single insert row or an array of rows to be inserted
           */

          function INSERT($args){
               /*
                * $args = [table=>'',columns=>[], values = ["column"=>"value"] || [["column"=>"value","column"=>"value"], ["column"=>"value","column"=>"value"]]]
                */
               $sql = (new Utils\sqlSpinner())->INSERT($args)->getSQL(); //spin sql statement from arguments
               if($this->debug){ //if in debug mode
                    echo "<pre>";
                    print_r($sql);
                    echo("<br />\n");
                    print_r($args);
                    echo "</pre>";
               }
               try {
                    $this->ping(); //verify db access
                    $this->pdo->beginTransaction(); //begin a transaction session with the database
                    $stmt = $this->pdo->prepare($sql);  //prepare the statement

                    //if array of value arrays to process multiple inserts in one table, uses bindParam over executing with value array
                    if(isset($args['values'][0])){ //if index 0 isset, then values is [["column"=>"value","column"=>"value"], ["column"=>"value","column"=>"value"]]
                         $colCount = count($columns);
                         $cols = [];
                         for($i=0;$i<$colCount;$i++){
                              $stmt->bindParam(":$columns[$i]",$$columns[$i]); //bind insert placeholders to variable parameters
                              array_push($cols, $$columns[$i]);
                         }
                         $valCount = count($args['values']);
                         for($i = 0; $i < $valCount; $i++){
                              //for each grouping of values in a multi-entity insert
                              foreach($args['values'][$i] as $column=>$value){
                                   //set variable placeholders to current row values
                                   $$column = $value;
                                   if($this->debug) print_r($$column.":".$$column); //if debugging
                              }
                              $stmt->execute(); //execute statement with bound parameters for each row in array of value arrays

                              $varCount = count($cols);
                              for($z=0;$z < $varCount; $z++){
                                   $$cols[$z] = null; //destroy temporary placeholder without disturbing index count
                              }
                         }
                    }
                    else { //one row of values to insert
                         $values = [];
                         foreach($args['values'] as $column=>$value){
                              if(isset($value)){
                                   $prepCol = ":$column";
                                   $values[$prepCol] = $value;
                              }
                         }
                         if($this->debug){ //if debugging
                              echo("Values: ");
                              print_r($values);
                         }
                         if(!($stmt->execute($values))){ //executes with parameter array, if fails throws exception
                              throw new Exception("Insert Failed");
                         }
                    }

                    return($this->pdo->commit()); //returns result of committing changes to db

               }catch(PDOException $pe){
                    $this->pdo->rollBack();
                    echo "Insert Failed: ".$pe->getMessage();
               }
               catch (Exception $e){
                    $this->pdo->rollBack();
                    echo "Insert Failed: ".$e->getMessage();
               }


          }

          /* Method Name: UPDATE
           * Takes: $args = [
           *             REQUIRED
           *                  'table'=>'',
           *                  'set'=>['column'=>value, column=>value],
           *                  'where'=>[column=>value] | [column=>[method=>value]] | [column=>[method=>[values]]]
           *                       prepares columns and values for pdo query.  Each index in where can be any of the above column options.
           *                       if method is 'like' or 'not like' | 'notlike', appends % to beginning and end of value
           *                       See sqlSpinner.php - WHERE for more information on how it is parsed and how to specify 'method'
           *             OPTIONAL
           *                  'orderby'=>['column'=>'ASC' || 'DESC']
           *                  'limit'=> #
           *                       Sets the LIMIT value in the Select statemen
           * Description: Updates entries in the database
           *        Converts set and where values into unique placeholders and values for pdo->prepare(sql) and pdo->execute(values)
           *        Sends values through the sqlSpinner to generate an sql query string which is ready to be used in pdo->prepare.
           *        if debug is set to true, prints the sql and the prepared value array
           *        if query succeeds, returns true, else returns false
           */

          function UPDATE($args){

               $setValues = [];
               try {
                    $join = [];
                    $jCond = [];
                    if(array_key_exists("join", $args)){ // if select query involves a join
                         $this->prepJoin($args, $join, $jCond);
                    }
                    if(isset($args['set'])){  //set values for update (UPDATE table SET setColumn = setValue, etc)
                         foreach($args['set'] as $column=>$value){
                              $prepCol = ":set".str_replace(".","",$column);
                              $setValues[$prepCol] = $value;
                         }
                    }
                    else { //no set values assigned, throw an error
                         throw new Exception("Set values missing for update command!",10);
                    }

                    $where = [];
                    $whereValues = [];
                    if(isset($args['where'])){ //where
                         $this->prepWhere($args['where'], $where, $whereValues);
                    }
                    else {
                         throw new Exception("Missing WHERE values for update command!", 11);
                    }

                    $orderby = [];  //orderby
                    if(isset($args['orderby'])){
                         $orderby = $args['orderby'];
                    }
                    $limit = null; //limit
                    if(isset($args['limit']) && empty($join)){
                         $limit = $args['limit'];
                    }

                    //Spin sql from options
                    $sql = (new Utils\sqlSpinner())->UPDATE($args)->JOIN($join, $jCond)->SET($args)->WHERE($where)->ORDERBY($orderby)->LIMIT($limit)->getSQL();

                    if($this->debug){ //if debugging
                        echo "<pre>";
                         print_r($sql);
                         echo("<br />\n");
                         print_r($setValues);
                         echo("<br />\n");
                         print_r($whereValues);
                         echo("<br />\n");
                         echo "</pre>";
                    }

                    $this->ping(); //determine db access
                    $this->pdo->beginTransaction(); //begin new transaction in the db
                    $stmt = $this->pdo->prepare($sql); //prepares sql statement
                    $stmt->execute(array_merge($setValues, $whereValues)); //executes with the value arrays
                    return($this->pdo->commit()); //returns commit status
               }
               catch(PDOException $pe){
                    $this->pdo->rollBack();
                    echo "Update Failed: ".$pe->getMessage();
                    return(false);
               }
               catch(Exception $e){
                    $this->pdo->rollBack();
                    echo "Update Failed: ".$e->getMessage();
                    return(false);
               }

          }


          /* Method Name: DELETE
           * Takes: $args = [
           *             REQUIRED
           *                  'table'=>'',
           *                  'where'=>[column=>value] | [column=>[method=>value]] | [column=>[method=>[values]]]
           *                       prepares columns and values for pdo query.  Each index in where can be any of the above column options.
           *                       if method is 'like' or 'not like' | 'notlike', appends % to beginning and end of value
           *                       See sqlSpinner.php - WHERE for more information on how it is parsed and how to specify 'method'
           *             OPTIONAL
           *                  'orderby'=>['column'=>'ASC' | 'DESC']
           *                  'limit'=> #
           *                       Sets the LIMIT value in the Select statemen
           * Description:  Deletes entries in the database
           *        Converts where values into unique placeholders and values for pdo->prepare(sql) and pdo->execute(values)
           *        Sends values through the sqlSpinner to generate an sql query string which is ready to be used in pdo->prepare.
           *        if debug is set to true, prints the sql and the prepared value array
           *        if query succeeds, returns true, else returns false
           */
          function DELETE($args){
               $join = [];
               $jCond = [];
               if(array_key_exists("join", $args)){ // if select query involves a join
                    $this->prepJoin($args, $join, $jCond);
               }
               $where = [];
               $whereValues = [];
               if(isset($args['where'])){ //where
                    $this->prepWhere($args['where'], $where, $whereValues);
               }
               else {
                    throw new Exception("Where values needed to delete from table.", 12);
               }
               $order = [];
               if(isset($args['orderby'])){ //orderby
                    $order = $args['orderby'];
               }
               $limit = null;
               if(isset($args['limit'])){ //limit
                    $limit = $args['limit'];
               }
               //spin sql from arguments
               $sql = (new Utils\sqlSpinner())->DELETE($args)->JOIN($join, $jCond)->WHERE($where)->ORDERBY($order)->LIMIT($limit)->getSQL();
               if($this->debug){ //if debugging
                         echo "<pre>";
                         print_r($sql);
                         echo("<br />\n");
                         print_r($whereValues);
                         echo("<br />\n");
                         echo "</pre>";
                    }
               try {
                    $this->ping(); //ensure db access
                    $this->pdo->beginTransaction(); //begin transaction
                    $stmt = $this->pdo->prepare($sql); //prepare statement
                    $stmt->execute($whereValues); //exewcute with value array
                    return($this->pdo->commit()); //return commit status
               }
               catch(PDOException $pe){
                    $this->pdo->rollBack();
                    echo "Delete Failed: ".$pe->getMessage();
                    return(false);
               }
               catch(Exception $e){
                    $this->pdo->rollBack();
                    echo "Delete Failed: ".$e->getMessage();
                    return(false);
               }

          }

          /* CREATE
           * Description: Creates a table
           * Takes: table name, properties
           */
          
          function CREATE($table, $props){
              
              try {
                  
                  $this->pdo->beginTransaction();
                    if($exists){
                        $sql = (new Utils\sqlSpinner())->CREATE($table,$props)->getSQL();
                        if($this->debug){
                            echo "<pre>";
                            print_r($sql);
                            echo "<br />";
                            print_r($table);
                            echo "<br />";
                            print_r(json_encode($props));
                        }
                        try {
                            $this->ping();
                            $this->pdo->beginTransaction();
                            $stmt = $this->pdo->prepare($sql);
                            $stmt->execute();
                            return($this->pdo->commit());
                        }
                        catch(PDOException $pe){
                              $this->pdo->rollBack();
                              echo "Create Failed: ".$pe->getMessage();
                              return(false);
                        }
                        catch(Exception $e){
                              $this->pdo->rollBack();
                              echo "Create Failed: ".$e->getMessage();
                              return(false);
                        }
                    }
              } catch (PDOException $e) {
                    $sql = (new Utils\sqlSpinner())->CREATE($table,$props)->getSQL();
                    if($this->debug){
                        echo "<pre>";
                        print_r($sql);
                        echo "<br />";
                        print_r($table);
                        echo "<br />";
                        print_r(json_encode($props));
                    }
                    try {
                        $this->ping();
                        $this->pdo->beginTransaction();
                        $stmt = $this->pdo->prepare($sql);
                        $stmt->execute();
                        return($this->pdo->commit());
                    }
                    catch(PDOException $pe){
                          $this->pdo->rollBack();
                          echo "Create Failed: ".$pe->getMessage();
                          return(false);
                    }
                    catch(Exception $e){
                          $this->pdo->rollBack();
                          echo "Create Failed: ".$e->getMessage();
                          return(false);
                    }
              }
          }
          
          function DROP($table){
              $sql = (new Utils\sqlSpinner())->DROP($table)->getSQL();
              try {
                  $this->ping();
                  $this->pdo->beginTransaction();
                  $stmt = $this->pdo->prepare($sql);
                  $stmt->execute();
                  return($this->pdo->commit());
              } catch(PDOException $pe){
                    $this->pdo->rollBack();
                    echo "Drop Failed: ".$pe->getMessage();
                    return(false);
              }
              catch(Exception $e){
                    $this->pdo->rollBack();
                    echo "Drop Failed: ".$e->getMessage();
                    return(false);
              }
          }
          
          /* Name: describe
           * Description:  Returns table schema information about a table
           * Takes: table name
           */

          function describe($table){
               try {
                    
               
               $sql = (new Utils\sqlSpinner())->DESCRIBE($table)->getSQL(); //instantiate sql
               $this->ping(); //ensure db access
               $stmt = $this->pdo->prepare($sql); //prepare statement
               $stmt->execute(); //execute statement
               $chunk = $stmt->fetchAll(PDO::FETCH_ASSOC); //return assocative array of table schema
               return($chunk);
               } catch(PDOException $p){
                    echo "Describe Failed: ".$pe->getMessage();
                    return(false);
               }
          }

          /* Name: queue
           * Takes: instructions = [PDOIMETHOD=>PDOIMETHODARGUMENTS]
           * Description:  Takes a set of instructions and processes through them.  For example, if you wanted to do an insert update then select
           */

          function queue($instructions = []){
               try {
                    $this->pdo->beginTransaction();
                    foreach($instruction as $method->$args){
                         $M = strtoupper($method);
                         $this->$M[$args];
                    }
                    return($this->pdo->commit());
               }
               catch (Exception $e){
                    $this->pdo->rollBack();
                    echo "Failed: ".$e->getMessage();
                    return(false);
               }
          }


          /* Name: ping
           * Description: Runs a quick check to determine if the pdo is still active, if not it creates a new one and continues on.
           */

          function ping(){
               try {
                    $this->pdo->query("SELECT 1"); a:
                    return(true);
               }
               catch (PDOException $pe){
                    $this->pdo = new cleanPDO($this->config);
                    $this->pdo->query("SET wait_timeout=1200");
                    goto a;
               }
          }

          /* Name: run
           * Takes: sql = '' Sql statement already containing placeholders
           *        values = []  associative array of placeholder=>values  (Values must be escaped!)
           * Description:  Runs a custom sql statement and executes it with the placeholder array, returning the result. 
           */

          function run($sql="", $values=[]){
               if($sql !==""){
                    try{
                         $this->pdo->beginTransaction();
                         $stmt = $this->pdo->prepare($sql);
                         $stmt->execute($values);
                         return($this->pdo->commit());
                    }
                    catch (Exception $e){
                         $this->pdo->rollBack();
                         echo("Failed: ").$e->getMessage();
                         return(false);
                    }
               }
          }
     }
?>