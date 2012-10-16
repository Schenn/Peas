<?php

     function instantiate($instance){
          return($instance);
     }

     class sqlSpinner {
          protected $method;
          protected $sql;

          
          function SELECT($args){
               $this->method = 'select';
               $this->sql = "SELECT ";
               
               if(isset($args['columns'])){
                    $i=0;
                    $cols = count($args['columns']);
                    foreach($args['columns'] as $col){
                         if($i !== $cols-1){
                              $this->sql .="$col, ";
                         }
                         else {
                              $this->sql .= $col . ' ';
                         }
                         $i++;
                    }
               }
               else {
                    $this->sql .= " * ";
               }
               
               $this->sql .= "FROM ".$args['table'];
               
               return($this);
          }
          
          function INSERT($args){
               $this->method = 'select';
               $this->sql = "INSERT INTO ".$args['table'];
               
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

          function WHERE($sqlArgs){
               
               $where = $sqlArgs['where'];
               
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
                                        switch(strtolower(trim($method))){
                                             case "not":
                                                  $this->sql .= $column." != :".$column;
                                                  break;
                                             case "like":
                                                  $this->sql .= $column." LIKE :".$column;
                                                  break;
                                             case "notlike":
                                                  $this->sql .= $column." NOT LIKE :".$column;
                                                  break;
                                             case "less":
                                                  $this->sql .= $column." < :".$column;
                                                  break;
                                             case "lessequal":
                                                  $this->sql .= $column." <= :".$column;
                                                  break;
                                             case "greater":
                                                  $this->sql .= $column." > :".$column;
                                                  break;
                                             case "greaterequal":
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
                                                       $this->sql .= ":".$column.$vI;
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
                         }
                         
                         $wI++;
                    }
               }
               
               return($this);
          }
          
          function ORDERBY($sort = []){
               //$sort = ['column'=>'method','column'=>'method']
               
               if(!empty($sort)){
                    $this->sql .= " ORDER BY ";
                    $i = 0;
                    $orderCount = count($sort);
                    foreach($sort as $column=>$method){
                         $method = strtoupper($method);
                         $this->sql .= $column." ".strtoupper($method);
                         if($i < $orderCount){
                              $this->sql .=", ";
                         }
                         $i++;
                    }
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