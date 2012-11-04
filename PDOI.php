<?php
     
     require_once('sqlSpinner.php');

     class cleanPDO extends PDO {
          protected $hasActiveTransaction = false;
          
          static function exception_handler($exception){
               die("Uncaught exception: ".$exception->getMessage());
          }
          
          function __construct($config){
               
               set_exception_handler(array(__CLASS__, "exception_handler"));
               parent::__construct($config['dns'], $config['username'], $config['password'], $config['driver_options']);
               restore_exception_handler();
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


     class PDOI
     {
          protected $pdo;
          protected $config;
          protected $debug;
     
          function __construct($config, $debug = false){
               $this->pdo = new cleanPDO($config);
               $this->config=$config;
               $this->pdo->query("SET wait_timeout=1200");
               $this->debug = $debug;
          }
          
          
          function SELECT($args){
               //$args = ['table'=>'', ('columns'=>['',''], 'where' = 'x'=>'1'], 'orderby' = ['key'=>'method'])]
               $whereValues = [];
               $where = [];
               if(isset($args['where'])){
                    foreach($args['where'] as $column=>$value){
                         if(gettype($value)!=='array'){
                              $c = ":".$column;
                              $whereValues[$c]=$value;
                              $where[$column]=$value;
                         }
                         else {
                              foreach($value as $method=>$compareValue){
                                   if(gettype($compareValue)!=='array'){
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
                                             $c = ":".$column.$i;
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
               $limit;
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
               $stmt = $this->pdo->prepare($sql);
               $stmt->execute($whereValues);
               $chunk = $stmt->fetchAll(PDO::FETCH_ASSOC);
               
               if(count($chunk) > 0){ return($chunk); }
               else { return(false);}
          }
          
          function INSERT($args){
               /*
                * $args = [table=>'',columns=>[], values = ["column"=>"value"] || [["column"=>"value","column"=>"value"]]]
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
                    
                    if(isset($args['values'][0])){
                         $colCount = count($columns);
                         $cols = [];
                         for($i=0;$i<$colCount;$i++){
                              $stmt->bindParam(":$columns[$i]",$$columns[$i]);
                              array_push($cols, $$columns[$i]);
                         }
                         $valCount = count($args['values']);
                         for($i = 0; $i < $valCount; $i++){
                              //for each grouping of values in a multi-entity insert
                              foreach($args['values'][$i] as $column=>$value){
                                   $$column = $value;
                                   print_r($$column.":".$$column);
                              }
                              $stmt->execute();
                              
                              $varCount = count($cols);
                              for($z=0;$z < $varCount; $z++){
                                   $$cols[$z] = null;
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
                              throw new Exception("Insert Failed: ");
                         }
                    }
                    
                    return($this->pdo->commit());
               } catch (Exception $e){
                    $this->pdo->rollBack();
                    echo "Insert Failed: ".$e->getMessage();
                    return(false);
               }
               catch(PDOException $pe){
                    $this->pdo->rollBack();
                    echo "Delete Failed: ".$pe->getMessage();
                    return(false);
               }

          }
          
          function UPDATE($args){
               $whereValues = [];
               $setValues = [];
               //update table set(columns=values)  where (columns=values) order by ... limit ...
               try {
<<<<<<< HEAD
=======
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
                              if(gettype($value)!=='array'){
                                   $c = ":where".$column;
                                   $whereValues[$c]=$value;
                                   $where[$column]=$value;
                              }
                              else {
                                   foreach($value as $method=>$compareValue){
                                        if(gettype($compareValue)!=='array'){
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
                    
>>>>>>> indev
                    $this->ping();
                    $this->pdo->beginTransaction();
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute(array_merge($setValues, $whereValues));
                    return($this->pdo->commit());
               }
               catch(Exception $e){
                    $this->pdo->rollBack();
                    echo "Update Failed: ".$e->getMessage();
                    return(false);
               }
               catch(PDOException $pe){
                    $this->pdo->rollBack();
                    echo "Delete Failed: ".$pe->getMessage();
                    return(false);
               }
          }
          
          function DELETE($args){
               $whereValues = [];
               if(isset($args['where'])){
<<<<<<< HEAD
                    $whereValues = $this->prepValues($args['where']);
                    $where=$args['where'];
=======
                    foreach($args['where'] as $column=>$value){
                         if(gettype($value)!=='array'){
                              $c = ":where".$column;
                              $whereValues[$c]=$value;
                              $where[$column]=$value;
                         }
                         else {
                              foreach($value as $method=>$compareValue){
                                   if(gettype($compareValue)!=='array'){
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
>>>>>>> indev
               }
               $order = [];
               if(isset($args['orderby'])){
                    $order = $args['orderby'];
               }
               $limit = null;
               if(isset($args['limit'])){
                    $limit = $args['limit'];
               }
<<<<<<< HEAD
               $sql = instantiate(new sqlSpinner())->DELETE($args)->WHERE($where)->ORDERBY($order)->LIMIT($limit);
=======
               $sql = instantiate(new sqlSpinner())->DELETE($args)->WHERE($where)->ORDERBY($order)->LIMIT($limit)->getSQL();
>>>>>>> indev
               
               try {
                    $this->ping();
                    $this->pdo->beginTransaction();
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute($whereValues);
                    return($this->pdo->commit());
               }
               catch(Exception $e){
                    $this->pdo->rollBack();
                    echo "Delete Failed: ".$e->getMessage();
                    return(false);
               }
               catch(PDOException $pe){
                    $this->pdo->rollBack();
                    echo "Delete Failed: ".$pe->getMessage();
                    return(false);
               }
          }
          
          function getColumns($table){
               $sql = instantiate(new sqlSpinner())->DESCRIBE($table)->getSQL();
               $this->ping();
               $stmt = $this->pdo->prepare($sql);
               $stmt->execute();
               $chunk = $stmt->fetchAll(PDO::FETCH_ASSOC);
               $columns = [];
               foreach($chunk as $row){
                    if($row['Key'] !== "PRI"){
                         if($row['Null'] ==='YES'){
                              $columns[$row['Field']]=null;
                         }
                         else {
                              $columns[$row['Field']]="";
                         }
                    }
               }
               return($columns);
          }
          
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
          
          function ping(){
               try {
                    $this->pdo->query("SELECT 1"); a:
                    return(true);
               }
               catch (PDOException $pe){
                    $this->pdo = new cleanPDO($this->config);
                    goto a;
               }
          }
          
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