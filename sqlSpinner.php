<?php

     class sqlSpunError extends Exception {
          
          protected $errorList = [
               "Invalid Column data for Insert Spinning.",
               "Missing Table Name",
               "Missing 'set' data for Update Spinning"
          ];
          
          public function __construct($message,$code, Exception $previous = null){
               $message .= " sqlSpinner ERROR: ".$code.": ".$this->errorList[$code];
               parent::__construct($message, $code, $previous);
          }
     }

     function instantiate($instance){
          return($instance);
     }

     class sqlSpinner {
          protected $method;
          protected $sql;

          protected function aggregate($aggMethod, $aggValues=[]){
               //if columnNames is empty, * is used
               $this->sql .= strtoupper($aggMethod)."(";
               $cNameCount = count($aggValues);
               if($cNameCount === 0){
                    $this->sql .= "*";
               }
               else {
                    for($vc=0;$vc<$cNameCount;$vc++){
                         $this->sql .= $aggValues[$vc];
                         if($vc !== $cNameCount-1){
                              $this->sql .= ", ";
                         }
                         else {
                              $this->sql .= " ";
                         }
                    }
               }
               $this->sql.=")";
          }
          
          function SELECT($args){
               $this->method = 'select';
               $this->sql = "SELECT ";
               
               try {
                    
                    if(isset($args['distinct'])){
                         $distinct = strtoupper($args['distinct']);
                         if($distinct !== 'ALL'){
                              $this->sql .= $distinct." ";
                              if(isset($args['result'])){
                                   $resultSize = strtoupper($args['result']);
                                   if($resultSize==='BIG'){
                                        $this->sql.="SQL_BIG_RESULT ";
                                   }
                                   elseif($resultSize==='SMALL'){
                                        $this->sql.="SQL_SMALL_RESULT ";
                                   }
                              }
                         }
                    }
                    
                    if(isset($args['groupby'])){
                         if(isset($args['result'])){
                              $resultSize = strtoupper($args['result']);
                              if($resultSize==='BIG'){
                                   $this->sql.="SQL_BIG_RESULT ";
                              }
                              elseif($resultSize==='SMALL'){
                                   $this->sql.="SQL_SMALL_RESULT ";
                              }
                         }
                    }
                    
                    if(isset($args['priority'])){
                         if(isset($args['union'])){
                              unset($args['union']);
                         }
                         $this->sql .= "HIGH_PRIORITY ";
                    }
                    
                    if(isset($args['buffer'])){
                         $this->sql .= "SQL_BUFFER_RESULT ";
                    }
                    
                    if(isset($args['cache'])){
                         if($args['cache']===true){
                              $this->sql .= "SQL_CACHE ";
                         }
                         elseif($args['cache'] === false){
                              $this->sql .= "SQL_NO_CACHE";
                         }
                    }
                    
                    
                    if(isset($args['columns'])){
                         $i=0;
                         $cols = count($args['columns']);
                         foreach($args['columns'] as $col){
                              if(!isset($col['agg'])){
                                   if($i !== $cols-1){
                                        $this->sql .="$col, ";
                                   }
                                   else {
                                        $this->sql .= $col . ' ';
                                   }
                              }
                              else {
<<<<<<< HEAD
                                   foreach($col['agg'] as $method=>$columnNames){
                                        $this->aggregate($method, $columnValues);
                                   }
=======
                                   $this->sql .= $col . ' ';
                              }
                         }
                         else {
                              foreach($col['agg'] as $method=>$columnNames){
                                   $this->aggregate($method, $columnValues)
>>>>>>> ffd1497b0ccdb2878a30efc7e79883b59ab7b6e2
                              }
                              $i++;
                                   
                         }
                    }
                    else {
                         $this->sql .= " * ";
                    }
                    
                    if(isset($args['table'])){
                         $this->sql .= "FROM ".$args['table'];
                    }
                    else {
                         throw new sqlSpunError("Invalid Arguments",1);
                    }
               } catch(sqlSpunError $e){
                    echo $e->getMessage();
               }
               
               return($this);
          }
          
          function INSERT($args){
               $this->method = 'insert';
<<<<<<< HEAD
=======
               $this->sql = "INSERT INTO ".$args['table'];
>>>>>>> ffd1497b0ccdb2878a30efc7e79883b59ab7b6e2
               
               try {
                    if(isset($args['table'])){
                         $this->sql = "INSERT INTO ".$args['table'];
                    }
                    else {
                         throw new sqlSpunError("Invalid Arguments", 1);
                    }
               
                    
                    if((gettype($args['columns'])==="array") && (isset($args['columns'][0]))){
                         $columnCount = count($args['columns']);
                    }
                    else {
                         throw new sqlSpunError("Invalid Arguments",0);
                    }
                    
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
               } catch(sqlSpunError $e){
                    echo $e->getMessage();
               }
          }

          function UPDATE($args){
               $this->method = "update";
               try {
                    if(isset($args['table'])){
                         $this->sql = "UPDATE ".$args['table']." SET (";
                    }
                    else {
                         throw new sqlSpunError("Invalid Arguments", 1);
                    }
                    $i = 0;
                    if(!isset($args['set'])){
                         throw new sqlSpunError("Invalid Arguments", 2);
                    }
                    $cCount = count($args['set']);
                    foreach($args['set'] as $colmumn=>$value){
                         $this->sql .=":".$column;
                         if($i !== $cCount-1){
                              $this->sql.=", ";
                         }
                    }
                    $this->sql .= ") ";
                    return($this);
               }
               catch (sqlSpunError $e){
                    echo $e->getMessage();
               }
          }
          
          function DELETE($args){
               $this->method = "delete";
               try {
                    if(isset($args['table'])){
                         $this->sql = "DELETE FROM ".$args['table'];
                    }
                    else {
                         throw new sqlSpunError("Invalid Arguments",1);
                    }
                    return($this);
               }
               catch (sqlSpunError $e){
                    echo $e->getMessage();
               }

<<<<<<< HEAD
=======
          function UPDATE($args){
               $this->method = "update";
               $this->sql = "UPDATE ".$args['table']." SET (";
               $i = 0;
               $cCount = count($args['set']);
               foreach($args['set'] as $colmumn=>$value){
                    $this->sql .=":".$column;
                    if($i !== $cCount-1){
                         $this->sql.=", ";
                    }
               }
               $this->sql .= ") ";
               return($this);
          }
          
          function DELETE($args){
               $this->method = "delete";
               $this->sql = "DELETE FROM ".$args['table'];
               return($this);
>>>>>>> ffd1497b0ccdb2878a30efc7e79883b59ab7b6e2
          }
          
          
          function WHERE($where){
               
               if(!empty($where)){
                    $this->sql .=" WHERE ";
                    $wI = 0;
                    $whereCount = count($where);
                    foreach($where as $column=>$value){
                         if(gettype($value)!=='array'){
                              $this->sql .= $column." = :".$column;
                         }
                         else {
                              foreach($value as $method=>$secondValue){
                                   if(gettype($secondValue)!=='array'){
                                        switch(strtolower(str_replace(" ", "",$method))){
                                             case "=":
                                             case "equal":
                                                  $this->sql .= $column." = :".$column;
                                                  break;
                                             case "not":
                                             case "!=":
                                                  $this->sql .= $column." != :".$column;
                                                  break;
                                             case "like":
                                                  $this->sql .= $column." LIKE :".$column;
                                                  break;
                                             case "notlike":
                                                  $this->sql .= $column." NOT LIKE :".$column;
                                                  break;
                                             case "less":
                                             case "<":
                                                  $this->sql .= $column." < :".$column;
                                                  break;
                                             case "lessequal":
                                             case "<=":
                                                  $this->sql .= $column." <= :".$column;
                                                  break;
                                             case "greater":
                                             case ">":
                                                  $this->sql .= $column." > :".$column;
                                                  break;
                                             case "greaterequal":
                                             case ">=":
                                                  $this->sql .= $column." >= :".$column;
                                                  break;
                                        }
                                   }
                                   else {
                                        $vCount = count($secondValue);
                                        switch(strtolower(trim($method))){
                                            case "between":
                                                  $this->sql .= $column." BETWEEN ";
                                                  for($vI=0;$vI<$vCount;$vI++){
                                                       $this->sql .= ":".$column.$vI;
                                                       if($vI !== $vCount-1){
                                                            $this->sql .= " AND ";
                                                       }
                                                  }
                                                  break;
                                             case "or":
                                                  $this->sql .=$column." =";
                                                  for($vI=0;$vI<$vCount;$vI++){
                                                       $this->sql .= ":".$column.$vI;
                                                       if($vI !== $vCount-1){
                                                            $this->sql .= " OR ";
                                                       }
                                                  }
                                                  break;
                                             case "in":
                                                  $this->sql .= $column." IN (";
                                                  for($vI=0;$vI<$vCount;$v++){
                                                       $this->sql .= ":".$column.$vI;
                                                       if($vI !== $vCount-1){
                                                            $this->sql .= ", ";
                                                       }
                                                  }
                                                  $this->sql .=")";
                                                  break;
                                             case "notin":
                                                  $this->sql .= $column." NOT IN (";
                                                  for($vI=0;$vI<$vCount;$v++){
                                                       $this->sql .=":".$column.$vI;
                                                       if($vI !== $vCount-1){
                                                            $this->sql .= ", ";
                                                       }
                                                  }
                                                  $this->sql .=")";
                                                  break;
                                        }
                                   }
                              }
                         }
                         
                         if($wI !== $whereCount - 1){
                              if($this->method === "select"){
                                   $this->sql .= " AND ";
                              }
                              else if($this->method ==="update"){
                                   $this->sql .= ", ";
                              }
                         }
                         
                         $wI++;
                    }
               }
               
               return($this);
          }
          
          function GROUPBY($groupby = []){
               
               if(!empty($groupby)){
                    $this->sql.=" GROUP BY ";
                    $groupCount = count($groupby);
                    for($i=0;$i<$groupCount;$i++){
                         $this->sql .=$groupby[$i];
                         if($i !== $groupCount-1){
                              $this->sql .= ", ";
                         }
                    }
               }
               
               return($this);
          }
          
          function HAVING($having=[]){
               
               //having = [aggmethod=>[columnNames]]
               //DO NOT USE HAVING TO REPLACE A WHERE
               //Having should only use group by columns for accuracy
               
               
               if(!empty($having)){
                    $this->sql .= " HAVING ";
                    
                    $method = $having['aggMethod'];
                    $columns = (isset($having['columns'])) ? $having['columns'] : [];
                    $comparison = $having['comparison']['method'];
                    $compareValue = $having['comparison']['value'];
                    
                    $this->aggregate($method, $columns);
                    
                    switch(strtolower(str_replace(" ", "",$comparison))){
                         case "=":
                         case "equal":
                              $this->sql .= " = ".$compareValue;
                              break;
                         case "not":
                         case "!=":
                              $this->sql .= " != ".$compareValue;
                              break;
                         case "like":
                              $this->sql .= " LIKE ".$compareValue;
                              break;
                         case "notlike":
                              $this->sql .= " NOT LIKE ".$compareValue;
                              break;
                         case "less":
                         case "<":
                              $this->sql .= " < ".$compareValue;
                              break;
                         case "lessequal":
                         case "<=":
                              $this->sql .= " <= ".$compareValue;
                              break;
                         case "greater":
                         case ">":
                              $this->sql .= " > ".$compareValue;
                              break;
                         case "greaterequal":
                         case ">=":
                              $this->sql .= " >= ".$compareValue;
                              break;
                    }
               }
               
               
               return($this);
          }
          
          function ORDERBY($sort = []){
               //$sort = ['column'=>'method','column'=>'method']
               
               if(!empty($sort)){
                    $this->sql .= " ORDER BY ";
                    $i = 0;
                    
                    if($sort==='NULL'){
                         $this->sql.= "NULL ";
                    }
                    else {
                         $orderCount = count($sort);
                         if(gettype($sort)==='array'){
                              foreach($sort as $column=>$method){
                                   $method = strtoupper($method);
                                   $this->sql .= $column." ".strtoupper($method);
                                   if($i < $orderCount-1){
                                        $this->sql .=", ";
                                   }
                                   $i++;
                              }
                         }
                         else {
                              $this->sql .= $sort;
                         }
                    }
               }
               
               
               return($this);
               
          }
          
          function LIMIT($limit = null){
               if($limit !== null){
<<<<<<< HEAD
                    $this->sql .= " LIMIT ".$limit;
               }
               return($this);
          }
          
          function DESCRIBE($table, $column = ""){
               $this->sql = "DESC ".$table;
               if($column !== ""){
                    $this->sql .= " " . $column;
=======
                    $this->sql .= "LIMIT ".$limit;
>>>>>>> ffd1497b0ccdb2878a30efc7e79883b59ab7b6e2
               }
               return($this);
          }
          
          function getSQL(){
               $sql = $this->sql;
               $this->sql = "";
               return($sql);
          }
          
     }
?>