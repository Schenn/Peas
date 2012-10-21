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
          protected $debug;
     
          function __construct($config, $debug = false){
               $this->pdo = new cleanPDO($config);
               $this->debug = $debug;
          }
          
          protected function prepValues($values = []){
               if(!empty($values)){
                    $prepValues = [];
                    foreach($values as $column=>$value){
                         $c = ":".$column;
                         
                         if(gettype($value)!=='array'){
                              $prepValues[$c]=$value;
                         }
                         else {
                              foreach($value as $method=>$secondValue){
                                   if(gettype($secondValue)!=="array"){
                                        $prepValues[$c]=$secondValue;
                                   }
                                   else {
                                        $vCount = count($secondValue);
                                        for($vc=0;$vc<$vCount;$vc++){
                                             $newC = $c.$vc;
                                             $prepValues[$newC] = $secondValue[$vc];
                                        }
                                   }
                              }
                         }
                    }
                    return($prepValues);
               }
          }
          
          function SELECT($args){
               //$args = ['table'=>'', ('columns'=>['',''], 'where' = 'x'=>'1'], 'orderby' = ['key'=>'method'])]
               $whereValues = [];
               if(isset($args['where'])){
                    $whereValues = $this->prepValues($args['where']);
               }
               
               $groupby = [];
               $having = [];
               if(isset($args['groupby'])){
                    $groupby = $args['groupby'];
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
               
               $sql = instantiate(new sqlSpinner())->SELECT($args)->WHERE($where)->GROUPBY($groupby)->HAVING($having)->ORDERBY($orderby)->getSQL();
               if($this->debug){
                    print_r($sql);
                    print_r($whereValues);
               }
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
               }
               try {
                    
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
                              print_r($values);
                         }
                         $stmt->execute($values);
                    }
                    
                    return($this->pdo->commit());
               }
               catch (Exception $e){
                    $this->pdo->rollBack();
                    echo "Insert Failed: ".$e->getMessage();
                    return(false);
               }

          }
          
          function UPDATE($args){
               $whereValues = [];
               $setValues = [];
               //update table set(columns=values)  where (columns=values) order by ... limit ...
               $setValues = $this->prepValues($args['set']);
               $where = [];
               if(isset($args['where'])){
                    $whereValues = $this->prepValues($args['where']);
                    $where = $args['where'];
               }
               $orderby = [];
               if(isset($args['orderby'])){
                    $orderby = $args['orderby'];
               }
               $limit = null;
               if(isset($args['limit'])){
                    $limit = $args['limit'];
               }
               $sql = instantiate(new sqlSpinner())->UPDATE($args)->WHERE($where)->ORDERBY($orderby)->LIMIT($limit);
               
               try {
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
          }
          
          function DELETE($args){
               $whereValues = [];
               if(isset($args['where'])){
                    $whereValues = $this->prepValues($args['where']);
                    $where=$args['where'];
               }
               $order = [];
               if(isset($args['orderby'])){
                    $order = $args['orderby'];
               }
               $limit = null;
               if(isset($args['limit'])){
                    $limit = $args['limit'];
               }
               $sql = instantiate(new sqlSpinner())->DELETE($args)->WHERE($where)->ORDERBY($order)->LIMIT($limit);
               
               try {
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
          }
          
          function getColumns($table){
               $sql = instantiate(new sqlSpinner())->DESCRIBE($table)->getSQL();
               $stmt = $this->pdo->prepare($sql);
               $stmt->execute();
               $chunk = $stmt->fetchAll(PDO::FETCH_ASSOC);
               $columns = [];
               foreach($chunk as $row){
                    if($row['Null']){
                         $columns[$row['field']]=null;
                    }
                    else {
                         $columns[$row['field']]="";
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