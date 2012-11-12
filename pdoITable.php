<?php
     /*
      *   Author: Steven Chennault
      *   Email: schenn@gmail.com
      *   Github: https://github.com/Schenn/PDOI
      *   Name: pdoITable.php
      *   Description:  pdoITable is a front end for the PDOI system which acts a front end for the tables themselves.
      *   They have the ability to perform more complex actions and maintain table data itself.  You can setCol the various
      *   columns and run pdoITable->insert() or update() without needing to construct most of the arguments. In addition,
      *   the results from select queries are stored in an object which outputs as json by default.
      *
      */
     require_once('PDOI.php');
     require_once("dynamo.php");

     class pdoITable extends PDOI {
          protected $tableName;
          protected $columns=[];
          protected $columnMeta=[];
          protected $args = [];
          protected $entity;
          
          function __construct($config, $table, $debug=false){
               parent::__construct($config, $debug);
               $this->setTable($table);
          }
          
          function setTable($table){
               $this->tableName = $table;
               $this->args['table'] = $this->tableName;
               $this->setColumns();
          }
          
          function setColumns(){
               $description = parent::describe($this->tableName);
               foreach($description as $row){
                    $field = $row['Field'];
                    unset($row['Field']);
                    
                    $sansType = preg_split("/int|decimal|double|float|double|real|bit|bool|serial|date|time|year|char|text|binary|blob|enum|set|geometrycollection|multipolygon|multilinestring|multipoint|polygon|linestring|point|geometry/",strtolower($row['Type']));
                    if(isset($sansType[1])){
                         $sansParens = preg_split("/\(|\)/",$sansType[1]);
                         if(isset($sansParens[1])){
                              $this->columnMeta[$field]['length'] = intval($sansParens[1]);
                         }
                    }
                    $type = preg_filter("/\(|\d+|\)/","",strtolower($row['Type']));
                    
                    $this->columnMeta[$field]['type'] = $type;
                    $this->columnMeta[$field]['default'] = $row['Default'];
                    
                    if($row['Key'] === "PRI"){
                         $this->columnMeta[$field]['primaryKey'] = true;
                         if($row['Extra'] === "auto_increment"){
                              $this->columnMeta[$field]['auto'] = true;
                         }
                    }
                    
                    switch($type){
                         case "int":
                         case "decimal":
                         case "double":
                         case "float":
                         case "real":
                         case "bit":
                         case "serial":
                              $this->columns[$field] = (empty($row['Default'])) ? 0 : $row['Default'];
                              break;
                         case "bool":
                              $this->columns[$field] = (empty($row['Default'])) ? false : $row['Default'];
                              break;
                         case "date":
                         case "time":
                         case "year":
                              $this->columns[$field]= (empty($row['Default'])) ? date("Y-m-d H:i:s") : strtotime($row['Default']);
                              $this->columnMeta[$field]['format'] = "Y-m-d H:i:s";
                              break;
                         default:
                              $this->columns[$field]= (empty($row['Default'])) ? "" : $row['Default'];
                              break;
                    }
               }
               $this->args['columns']=[];
               foreach($this->columns as $column=>$n){
                    array_push($this->args['columns'], $column);
               }
               $this->entity = new dynamo($this->columns);
               $this->entity->setValidationRules($this->columnMeta);
               
          }
          
          function select($options, $entity = null){
               $entity = ($entity !== null ? $entity : $this->entity);
               $a = $this->args;
               foreach($options as $option=>$setting){
                    $a[$option]=$setting;
               }
               
               return(parent::SELECT($a, $entity));
          }
          
          function insert($options){
               $a = $this->args;
               foreach($a['columns'] as $index=>$key){
                    if(array_key_exists("auto",$this->columnMeta[$key])){
                         $removeIndex = $index;
                    }
               }
               unset($a['columns'][$removeIndex]);
               $a['columns'] = array_values($a['columns']);
               if(!isset($options['values'])){
                    $a['values']=[];
                    foreach($this->columns as $column=>$value){
                         if(!array_key_exists("auto",$this->columnMeta[$column])){
                              $a['values'][$column]=$value;
                         }
                    }
               }
               foreach($options as $option=>$setting){
                    $a[$option]=$setting;
               }
               
               return(parent::INSERT($a));
          }
          
          function setCol($col,$val){
               //Validate?
               $this->columns[$col]=$val;
          }
          
          function getCol($col){
               return($this->columns[$col]);
          }
          
          function update($options){
               $a = $this->args;
               foreach($a['columns'] as $index=>$key){
                    if(array_key_exists("primaryKey",$this->columnMeta[$key])){
                         $removeIndex = $index;
                    }
               }
               unset($a['columns'][$removeIndex]);
               $a['columns'] = array_values($a['columns']);
               foreach($options as $option=>$setting){
                    $a[$option]=$setting;
               }
               return(parent::UPDATE($a));
          }
          
          function delete($options){
               $a= $this->args;
               foreach($options as $option=>$setting){
                    $a[$option]=$setting;
               }
               return(parent::DELETE($a));
          }
          
          function addMethodToEntity($name, $method){
               if(is_callable($method)){
                    $this->entity->$name = $method;
               }
          }        
          
          function reset(){
               foreach($this->columns as $key=>$value){
                    $this->columns[$key] = $this->columnMeta[$key]['default'];
               }
          }
               
          function display(){
               $this->outputOffshoot();
          }
          
          function Offshoot(){
               $e = $this->entity;
               $t = $this;
               
               $e->insert = function() use($t){
                    $args = [];
                    $args['values'] = [];
                    
                    foreach($this as $key=>$value){
                         $validation = $this->getRule($key);
                         if(!array_key_exists('fixed',$validation)){
                              if($value !== $validation['default'] && $value !== null){
                                   $args['values'][$key]=$value;
                              }
                         }
                    }
                    $t->insert($args);
                    
               };
               
               $e->update = function() use ($t){
                    $args = [];
                    foreach($this->properties as $key=>$value){
                         if(!array_key_exists('fixed',$this->getRule($key))){
                              $setKey = 'set'.$key;
                              $args['set'][$setKey]=$value;
                         }
                         else {
                              $whereKey = 'where'.$key;
                              $args['where'][$whereKey] = $value;
                         }
                    }
                    $args['limit']=1;
                    $t->update($args);
               };
               
               $e->delete = function() use ($t){
                    $args = [];
                    foreach($this->properties as $key=>$value){
                         if(array_key_exists('fixed',$this->getRule($key))){
                              $args['where'] = [$key=>$value];
                         }
                    }
                    $t->delete($args);
               };
               
               $this->reset();
               return($e);
          }
     }
?>