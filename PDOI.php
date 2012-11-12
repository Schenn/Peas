<?php
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
     
     
     require_once('sqlSpinner.php');     
     
     /*
      * Name: cleanPDO
      * Description:  PDO which throws exceptions
      * takes associative array of configuration options
      *    $config = [
               'dns'=>'mysql:dbname=pdoi_tester;localhost',
               'username'=>'pdoi_tester',
               'password'=>'pdoi_pass',
               'driver_options'=>[PDO::ATTR_PERSISTENT => true]
          ];
      */
     
     
     class cleanPDO extends PDO {
          protected $hasActiveTransaction = false;
          
          function __construct($config){
               $config['dns'] = 'mysql:dbname='.$config['dbname'].';localhost';
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
                         'dns'=>'mysql:dbname=pdoi_tester;localhost',
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
               try {
                    $this->pdo = new cleanPDO($config);
                    $this->config=$config;
                    $this->pdo->query("SET wait_timeout=1200");
                    $this->debug = $debug;
               }
               catch(PDOException $e){
                    $this->ping();
               }
          }
          
          
          /* Method Name: SELECT
           * Takes: $args = [
           *             REQUIRED
           *                  'table'=>'',
           *             OPTIONAL
           *                  'columns'=>['','']
           *                       if missing or empty, select statement will build as SELECT *
           *                  'join'=>['method'=>'table', ]
           *                  'where'=>[column=>value] | [column=>[method=>value]] | [column=>[method=>[values]]]
           *                       prepares columns and values for pdo query.  Each index in where can be any of the above column options.
           *                       if method is 'like' or 'not like' | 'notlike', appends % to beginning and end of value
           *                       See sqlSpinner.php - WHERE for more information on how it is parsed and how to specify 'method'
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
               $whereValues = [];
               $where = [];
               if(isset($args['where'])){
                    //converts where into a prepared value array
                    foreach($args['where'] as $column=>$value){
                         if(!is_array($value)){
                              $c = ":where".$column;
                              $whereValues[$c]=$value;
                              $where[$column]=$value;
                         }
                         else {
                              foreach($value as $method=>$compareValue){
                                   if(!is_array($compareValue)){
                                        $c = ":where".$column;
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
                                             $c = ":where".$column.$i;
                                             $whereValues[$c]=$compareValue[$i];
                                        }
                                        $where[$column]=[$method=>$compareValue];
                                   }
                              }
                         }
                    }
               }
               
               $groupby = [];
               $having = [];
               if(isset($args['groupby'])){
                    $groupby = $args['groupby']['column'];
                    //if theres no set orderby but there is a groupby, orderby is set to NULL to increase mysql response speed
                    if(!isset($args['orderby']) || empty($args['orderby'])){
                         $args['orderby'] = 'NULL';
                    }
                    if(isset($args['groupby']['having'])){
                         $having = $args['groupby']['having'];
                         unset($args['groupby']['having']);
                    }
               }
               
               $orderby= [];
               if(isset($args['orderby'])){
                    $orderby = $args['orderby'];
               }
               
               $limit = null;
               if(isset($args['limit'])){
                    $limit = $args['limit'];
               }
               $sql = instantiate(new sqlSpinner())->SELECT($args)->WHERE($where)->GROUPBY($groupby)->HAVING($having)->ORDERBY($orderby)->LIMIT($limit)->getSQL();
               if($this->debug){
                    print_r($sql);
                    echo("<br />\n");
                    print_r($whereValues);
               }
               $this->ping();
               try {
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute($whereValues);
                    if(is_object($obj)){
                         $stmt->setFetchMode(PDO::FETCH_INTO, $obj);
                         $chunk = [];
                         while($o = $stmt->fetch()){
                              array_push($chunk, clone $o);
                         }
                    }
                    else {
                         $chunk = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    }               
                    if(count($chunk) > 0){
                         if(count($chunk) === 1){
                              return($chunk[0]);
                         }
                         else { 
                              return($chunk);
                         }
                    }
                    else {
                         return(false);
                    }
               }
               catch (PDOException $e){
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
               $sql = instantiate(new sqlSpinner())->INSERT($args)->getSQL();
               if($this->debug){
                    print_r($sql);
                    echo("<br />\n");
                    print_r($args);
               }
               try {
                    $this->ping();
                    $this->pdo->beginTransaction();
                    $stmt = $this->pdo->prepare($sql);
                    
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
                                   if($this->debug) print_r($$column.":".$$column);
                              }
                              $stmt->execute();
                              
                              $varCount = count($cols);
                              for($z=0;$z < $varCount; $z++){
                                   $$cols[$z] = null; //destroy temporary placeholder without disturbing index count
                              }
                         }
                    }
                    else {
                         $values = [];
                         foreach($args['values'] as $column=>$value){
                              $prepCol = ":$column";
                              $values[$prepCol] = $value;
                         }
                         if($this->debug){
                              echo("Values: ");
                              print_r($values);
                         }
                         if(!($stmt->execute($values))){
                              throw new Exception("Insert Failed");
                         }
                    }
                    
                    return($this->pdo->commit());
               
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
               $whereValues = [];
               $setValues = [];
               try {
                    if(isset($args['set'])){
                         foreach($args['set'] as $column=>$value){
                              $prepCol = ":set".$column;
                              $setValues[$prepCol] = $value;
                         }
                    }
                    
                    $where = [];
                    if(isset($args['where'])){
                         $where = [];
                         foreach($args['where'] as $column=>$value){
                              if(!is_array($value)){
                                   $c = ":where".$column;
                                   $whereValues[$c]=$value;
                                   $where[$column]=$value;
                              }
                              else {
                                   foreach($value as $method=>$compareValue){
                                        if(!is_array($compareValue)){
                                             $c = ":where".$column;
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
                                                  $c = ":where".$column.$i;
                                                  $whereValues[$c]=$compareValue[$i];
                                             }
                                             $where[$column]=[$method=>$compareValue];
                                        }
                                   }
                              }
                         }
                    }

                    $orderby = [];
                    if(isset($args['orderby'])){
                         $orderby = $args['orderby'];
                    }
                    $limit;
                    if(isset($args['limit'])){
                         $limit = $args['limit'];
                    }
                                       
                    $sql = instantiate(new sqlSpinner())->UPDATE($args)->WHERE($where)->ORDERBY($orderby)->LIMIT($limit)->getSQL();
                    
                    if($this->debug){
                         print_r($sql);
                         echo("<br />\n");
                         print_r($setValues);
                         echo("<br />\n");
                         print_r($whereValues);
                         echo("<br />\n");
                    }
                    
                    $this->ping();
                    $this->pdo->beginTransaction();
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute(array_merge($setValues, $whereValues));
                    return($this->pdo->commit());
               }
               catch(PDOException $pe){
                    $this->pdo->rollBack();
                    echo "Delete Failed: ".$pe->getMessage();
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
               $whereValues = [];
               if(isset($args['where'])){
                    foreach($args['where'] as $column=>$value){
                         if(!is_array($value)){
                              $c = ":where".$column;
                              $whereValues[$c]=$value;
                              $where[$column]=$value;
                         }
                         else {
                              foreach($value as $method=>$compareValue){
                                   if(!is_array($compareValue)){
                                        $c = ":".$column;
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
                                             $c = ":where".$column.$i;
                                             $whereValues[$c]=$compareValue[$i];
                                        }
                                        $where[$column]=[$method=>$compareValue];
                                   }
                              }
                         }
                    }
               }
               $order = [];
               if(isset($args['orderby'])){
                    $order = $args['orderby'];
               }
               $limit = null;
               if(isset($args['limit'])){
                    $limit = $args['limit'];
               }
               $sql = instantiate(new sqlSpinner())->DELETE($args)->WHERE($where)->ORDERBY($order)->LIMIT($limit)->getSQL();
               
               try {
                    $this->ping();
                    $this->pdo->beginTransaction();
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute($whereValues);
                    return($this->pdo->commit());
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
          
          function describe($table){
               $sql = instantiate(new sqlSpinner())->DESCRIBE($table)->getSQL();
               $this->ping();
               $stmt = $this->pdo->prepare($sql);
               $stmt->execute();
               $chunk = $stmt->fetchAll(PDO::FETCH_ASSOC);
               return($chunk);
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
           *        values = []  associative array of placeholder=>values
           * Description:  Runs a custom sql statement and executes it with the placeholder array, returning the result.  Doesn't work with select statements.
           */
          
          function run($sql="", $values=[]){
               if($sql !==""){
                    try{
                         $this->pdo->beginTransaction();
                         $this->pdo->prepare($sql);
                         $this->pdo->execute($values);
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