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
                                   foreach($col['agg'] as $method=>$columnNames){
<<<<<<< HEAD
                                        $this->aggregate($method, $columnValues);
=======
                                        $this->aggregate($method, $columnNames);
>>>>>>> indev
                                   }
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
<<<<<<< HEAD
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
=======
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
>>>>>>> indev
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
<<<<<<< HEAD
                         $this->sql = "UPDATE ".$args['table']." SET (";
=======
                         $this->sql = "UPDATE ".$args['table']." SET ";
>>>>>>> indev
                    }
                    else {
                         throw new sqlSpunError("Invalid Arguments", 1);
                    }
                    $i = 0;
                    if(!isset($args['set'])){
                         throw new sqlSpunError("Invalid Arguments", 2);
<<<<<<< HEAD
                    }
                    $cCount = count($args['set']);
                    foreach($args['set'] as $colmumn=>$value){
                         $this->sql .=":".$column;
                         if($i !== $cCount-1){
                              $this->sql.=", ";
                         }
                    }
                    $this->sql .= ") ";
=======
                    }
                    $cCount = count($args['set']);
                    foreach($args['set'] as $column=>$value){
                         $this->sql .="$column = :set".$column;
                         if($i !== $cCount-1){
                              $this->sql.=", ";
                         }
                    }
                    $this->sql .= " ";
>>>>>>> indev
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

          }
          
          
          function WHERE($where){
               
               if(!empty($where)){
                    $this->sql .=" WHERE ";
                    $wI = 0;
                    $whereCount = count($where);
                    foreach($where as $column=>$value){
                         if(gettype($value)!=='array'){
                              $this->sql .= $column." = :where".$column;
                         }
                         else {
                              foreach($value as $method=>$secondValue){
                                   if(gettype($secondValue)!=='array'){
                                        switch(strtolower(str_replace(" ", "",$method))){
                                             case "=":
                                             case "equal":
<<<<<<< HEAD
                                                  $this->sql .= $column." = :".$column;
                                                  break;
                                             case "not":
                                             case "!=":
                                                  $this->sql .= $column." != :".$column;
=======
                                                  $this->sql .= $column." = :where".$column;
                                                  break;
                                             case "not":
                                             case "!=":
                                                  $this->sql .= $column." != :where".$column;
>>>>>>> indev
                                                  break;
                                             case "like":
                                                  $this->sql .= $column." LIKE :where".$column;
                                                  break;
                                             case "notlike":
                                                  $this->sql .= $column." NOT LIKE :where".$column;
                                                  break;
                                             case "less":
                                             case "<":
<<<<<<< HEAD
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
=======
                                                  $this->sql .= $column." < :where".$column;
                                                  break;
                                             case "lessequal":
                                             case "<=":
                                                  $this->sql .= $column." <= :where".$column;
                                                  break;
                                             case "greater":
                                             case ">":
                                                  $this->sql .= $column." > :where".$column;
                                                  break;
                                             case "greaterequal":
                                             case ">=":
                                                  $this->sql .= $column." >= :where".$column;
>>>>>>> indev
                                                  break;
                                        }
                                   }
                                   else {
                                        $vCount = count($secondValue);
                                        switch(strtolower(trim($method))){
                                            case "between":
                                                  $this->sql .= $column." BETWEEN ";
                                                  for($vI=0;$vI<$vCount;$vI++){
                                                       $this->sql .= ":where".$column.$vI;
                                                       if($vI !== $vCount-1){
                                                            $this->sql .= " AND ";
                                                       }
                                                  }
                                                  break;
                                             case "or":
                                                  $this->sql .=$column." =";
                                                  for($vI=0;$vI<$vCount;$vI++){
                                                       $this->sql .= ":where".$column.$vI;
                                                       if($vI !== $vCount-1){
                                                            $this->sql .= " OR ";
                                                       }
                                                  }
                                                  break;
                                             case "in":
                                                  $this->sql .= $column." IN (";
                                                  for($vI=0;$vI<$vCount;$v++){
                                                       $this->sql .= ":where".$column.$vI;
                                                       if($vI !== $vCount-1){
                                                            $this->sql .= ", ";
                                                       }
                                                  }
                                                  $this->sql .=")";
                                                  break;
                                             case "notin":
                                                  $this->sql .= $column." NOT IN (";
                                                  for($vI=0;$vI<$vCount;$v++){
<<<<<<< HEAD
                                                       $this->sql .=":".$column.$vI;
=======
                                                       $this->sql .=":where".$column.$vI;
>>>>>>> indev
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
<<<<<<< HEAD
                    
=======
>>>>>>> indev
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
<<<<<<< HEAD
               
               
=======
>>>>>>> indev
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
                    $this->sql .= " LIMIT ".$limit;
               }
               return($this);
          }
          
          function DESCRIBE($table, $column = ""){
               $this->sql = "DESC ".$table;
               if($column !== ""){
                    $this->sql .= " " . $column;
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