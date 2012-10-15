<?php

     class cleanPDO extends PDO {
          protected $hasActiveTransaction = false;
          
          static function exception_handler($exception){
               die("Uncaught exception: ",$exception->getMessage());
          }
          
          function __construct($config){
               
               set_exception_handler(array(__CLASS__, "exception_handler"));
               parent::__construct($config->dns, $config->username, $config->password, $config->driver_options);
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
               parent::commit();
               $this->hasActiveTransaction = false;
          }
          
          function rollback() {
               parent::rollBack();
               $this->hasActiveTransaction = false;
          }
     }
     
     class sqlSpinner {
          protected $method;
          protected $sql;

          
          function SELECT($args){
               $this->method = 'select';
               $this->sql = "SELECT ";
               $i=0;
               $cols = count($args['columns']);
               if($cols > 0){
                    foreach($args['columns'] as $col){
                         $i++;
                         if($i !== $cols-1){
                              $this->sql .="$col, ";
                         }
                         else {
                              $this->sql .= $col;
                         }
                    }
               }
               else {
                    $this->sql .= " * ";
               }
               
               $this->sql ,= " FROM $args['table']";
               
               return($this);
          }
          
          function INSERT($args){
               $this->method = 'select';
               $this->sql = "INSERT INTO $args['table']";
               
               $columnCount = count($args['columns']);
               
               $this->sql .="(";
               for($i = 0; $i<$columnCount; $i++){
                    $this->sql .= $args['columns'][$i];
                    if($i !== $columnCount-1)
                    {
                         $this->sql .= ", ";
                    }
               }
               $this->sql .=") VALUES (";
               for($i = 0; $i<$columnCount; $i++){
                    $this->sql .= ":".$args['columns'][$i];
                    if($i !== $columnCount-1)
                    {
                         $this->sql .= ", ";
                    }
               }
               $this->sql .=")";
               
               return($this);
               
          }

          function WHERE($columns = []){
               
               if(!empty($columns)){
                    $this->sql .=" WHERE ";
                    $colCount = count($columns);
                    for($i = 0; $i<$colCount;$i++){
                         $this->sql .= "$columns[$i] = :$columns[$i]";
                         if($i !== $colCount - 1){
                              if($this->method === "select"){
                                   $this->sql .= " AND ";
                              }
                         }
                    }
               }
               return($this);
          }
          
          function WHERELIKE($columns = []){
               
          }
          
          function WHERENOTLIKE($columns = []){
               
          }
          
          function ORDERBY($sort = []){
               //$sort = ['column'=>'method','column'=>'method'] || "column"
               
               if(!empty($sort)){
                    $this->sql .= " ORDER BY ";
                    
                    $t = gettype($sort);
                    if($t === "string"){
                         $this->sql .= $column;
                    }
                    else if($t = "array"){
                         $i = 0;
                         $orderCount = count($sort);
                         foreach($sort as $column=>$method){
                              $method = strtoupper($method);
                              $this->sql .= "$column $method"
                              $i++;
                              if($i < $orderCount){
                                   $this->sql .=", ";
                              }
                         }
                    }
               }
               return($this);
               
          }
          
          function getSQL(){
               return($this->sql);
          }
          
     }

     class PDOI
     {
          protected $pdo;
     
          function __construct($config){
               $this->pdo = new cleanPDO($config);
          }
          
          function SELECT($args){
               //$args = ['table'=>'', 'columns'=>['',''], 'where' = ['x'=>'1'], 'sort' = ['key'=>'method']
               
               $where = [];
               $whereValues = [];
               if(count($args['where'])>0){
                    foreach($args['where'] as $column=>$value){
                         $whereValues[":$column"=>$value];
                         array_push($where, $column);
                    }
               }
               
               $sql = new sqlSpinner()->SELECT($args)->WHERE($where)->ORDERBY($args['sort'])->getSQL();
               $stmt = $this->pdo->prepare($sql);
               $stmt->execute($whereValues);
               $chunk = $stmt->fetchAll(PDO::FETCH_ASSOC);
               
               if(count($chunk) > 0){ return($chunk); }
               else { return(false);}
          }
          
          
          function SELECTLIKE($args){
               /*     
                *   pattern = %bar || foo% || %ooba%
                */
                             
               $where = [];
               $whereValues = [];
               if(count($args['where'])>0){
                    foreach($args['where'] as $column=>$value){
                         $whereValues[":$column"=>$value];
                         array_push($where, $column);
                    }
               }
               $like = [];
               if(isset($args['like'])){
                    $column = ":".$args['like']['column'];
                    $like[$column] = $args['like']['pattern'];
                    $sql = new sqlSpinner()->SELECT($args)->WHERE($where)->LIKE($args)->ORDERBY($args['sort']);
               }
               elseif(isset($args['notlike'])){
                    $column = ":".$args['notlike']['column'];
                    $like[$column] = $args['notlike']['pattern'];
               }
               
               
               $stmt = $this->pdo->prepare($sql);
               $stmt->execute(array_merge$whereValues);
               $chunk = $stmt->fetchAll(PDO::FETCH_ASSOC);
               
               if(count($chunk) > 0){ return($chunk); }
               else { return(false);}
          }
          
          function INSERT($args){
               /*
                * $args = [table=>'',columns=>[], values = ["column"=>"value"] || [["column"=>"value","column"=>"value"]]]
                */
               $sql = new sqlSpinner()->INSERT($args)->getSQL();
               
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
               
          }
          
          function DELETE($args){
               
          }
          
          function queue($instructions = []){
               try {
                    $this->pdo->beginTransaction();
                    foreach($instruction as $method->$args){
                         $this->$method[$args];
                    }
                    $this->pdo->commit();
               }
               catch (Exception $e){
                    $this->pdo->rollBack();
                    echo "Failed: ".$e->getMessage();
               }
          }
     }
     
?>