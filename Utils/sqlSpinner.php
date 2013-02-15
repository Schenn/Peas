<?php
     namespace PDOI\Utils;
     use Exception;
     /*
      *   Author: Steven Chennault
      *   Email: schenn@gmail.com
      *   Github: https://github.com/Schenn/PDOI
      *   Name: sqlSpinner.php
      *   Description:  sqlSpinner is a chainable class which generates an sql string based off
      *             an associative array of arguments.  This allows applications to ensure that
      *             their sql statements are prepared properly.
      *
      */

     /*
      * Name: sqlSpunError
      * Description:  Error exception for sqlSpinner
      */

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

     /*
      * Name: sqlSpinner
      * Description: generates sql statements from an argument array
      *        It is chainable, returning the object with every function except
      *        getSQL.  Call getSQL to retrieve the sql statement and end the chain.
      */
     class sqlSpinner {
          public $method;
          protected $sql;

          /*
           * Name: aggregate
           * Takes: aggMethod = "" (sum, avg, count, min, max)
           *        aggValues = ['columnName']
           * Description:  generates an aggregate mysql function
           */

          protected function aggregate($aggMethod, $aggValues=[]){
               //if columnNames is empty, * is used
               $this->sql .= strtoupper($aggMethod)."(";
               $cNameCount = count($aggValues);
               if($cNameCount === 0){
                    $this->sql .= "*";
               }
               else {
                    $this->sql .= implode(", ", $aggValues);
               }
               $this->sql.=")";
          }


          protected function methodSpin($method){
               switch(strtolower(str_replace(" ", "",$method))){
                    case "!=":
                    case "not":
                         return(" != ");
                         break;
                    case "<":
                    case "less":
                         return(" < ");
                         break;
                    case "<=":
                    case "lessequal":
                         return(" <= ");
                         break;
                    case ">":
                    case "greater":
                         return(" > ");
                         break;
                    case ">=":
                    case "greaterequal":
                         return(" >= ");
                         break;
                    case "like":
                         return(" LIKE ");
                         break;
                    case "notlike":
                         return(" NOT LIKE ");
                         break;
                    default:
                         return(" = ");
                         break;
               }
          }

          /*
           * Name: SELECT
           * Takes: args = [
           *             REQUIRED
           *                  'table'=>      'tableName' | ['tableName', 'tableName']  if table is missing, sqlSpinner throws sqlSpunError
           *             OPTIONAL
           *                  'columns'=>    ['columnName', (agg=>['method'=>'values'])]  agg represents aggregate method. If columns omitted, SELECT * is used instead
           *             VERY OPTIONAL
                              'distinct'=>   'distinct' | 'distinctrow'
                              'result'=>     'big' | 'small' (sql_big_result | sql_small_result) Requires either args['distinct'] or args['groupby']
                              'priority'=>   true (HIGH_PRIORITY) unsets args['union']
                              'buffer'=>     true (SQL_BUFFER_RESULT)
                              'cache'=>      true | false (SQL_CACHE | SQL_NO_CACHE)
                         ]
           * Description: Sets this->sql to a SELECT statement up to the tablename.  Also sets this->method to select as where clause relies on how statement began
           */
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
                                        $this->sql.=" SQL_BIG_RESULT ";
                                   }
                                   elseif($resultSize==='SMALL'){
                                        $this->sql.=" SQL_SMALL_RESULT ";
                                   }
                              }
                         }
                    }

                    if(isset($args['groupby'])){
                         if(isset($args['result'])){
                              $resultSize = strtoupper($args['result']);
                              if($resultSize==='BIG'){
                                   $this->sql.=" SQL_BIG_RESULT ";
                              }
                              elseif($resultSize==='SMALL'){
                                   $this->sql.=" SQL_SMALL_RESULT ";
                              }
                         }
                    }

                    if(isset($args['priority'])){
                         if(isset($args['union'])){
                              unset($args['union']);
                         }
                         $this->sql .= " HIGH_PRIORITY ";
                    }

                    if(isset($args['buffer'])){
                         $this->sql .= " SQL_BUFFER_RESULT ";
                    }

                    if(isset($args['cache'])){
                         if($args['cache']===true){
                              $this->sql .= "SQL_CACHE ";
                         }
                         elseif($args['cache'] === false){
                              $this->sql .= "SQL_NO_CACHE";
                         }
                    }


                    if(!empty($args['columns'])){
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
                                        $this->aggregate($method, $columnNames);
                                   }
                              }
                              $i++;

                         }
                    }
                    else {
                         $this->sql .= " * ";
                    }

                    if(isset($args['table'])){
                         $this->sql .= "FROM ";
                         if(is_array($args['table'])){
                              $this->sql .= implode(", ", $args['table']);
                         }
                         elseif(is_string($args['table'])){
                              $this->sql .= $args['table'];
                         }
                         $this->sql .= " ";
                    }
                    else {
                         throw new sqlSpunError("Invalid Arguments",1);
                    }
               } catch(sqlSpunError $e){
                    echo $e->getMessage();
               }

               return($this);
          }

          /*
           * Name: INSERT
           * Takes: args = [
           *             REQUIRED
           *                  'table'=>      'tableName'  if missing, sqlSpinner throws sqlSpunError
           *                  'columns'=>    ['columnName', 'columnName']  if missing, sqlSpinner throws sqlSpunError
                         ]
               Description: this->sql = INSERT INTO tableName (columnName, columName) VALUES (:columnName, :columnName)
                         sql statement uses placeholders for pdo.  Be sure to match your value array appropriately.
           *
           */
          function INSERT($args){
               $this->method = 'insert';

               try {
                    if(isset($args['table'])){
                         $this->sql = "INSERT INTO ".$args['table'];
                    }
                    else {
                         throw new sqlSpunError("Invalid Arguments", 1);
                    }


                    if((is_array($args['columns'])) && (isset($args['columns'][0]))){
                         $columnCount = count($args['columns']);
                    }
                    else {
                         throw new sqlSpunError("Invalid Arguments",0);
                    }

                    $this->sql .="(";
                    $this->sql .= implode(", ", $args['columns']);
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

          /*
           * Name: UPDATE
           * Takes: args = [
           *             REQUIRED
           *                  'table'=>      'tableName'  if missing, sqlSpinner throws sqlSpunError
           *                  'set'=>    ['columnName'=>'value']  if missing, sqlSpinner throws sqlSpunError
                         ]
               Description: this->sql = UPDATE tableName SET columnName = :setColumnName
                         sql statement uses placeholders for pdo.  Be sure to match your value array appropriately.
           *
           */
          function UPDATE($args){
               $this->method = "update";
               try {
                    if(isset($args['table'])){
                         $this->sql = "UPDATE ".$args['table']." ";
                    }
                    else {
                         throw new sqlSpunError("Invalid Arguments", 1);
                    }
                    if(!isset($args['set'])){
                         throw new sqlSpunError("Invalid Arguments", 2);
                    }
                    return($this);
               }
               catch (sqlSpunError $e){
                    echo $e->getMessage();
               }
          }

          function SET($set){
                $i = 0;
                $cCount = count($set);
                foreach($set as $column=>$value){
                     $this->sql .="$column = :set".str_replace(".","",$column);
                     if($i !== $cCount-1){
                          $this->sql.=", ";
                     }
                     $i++;
                }
                $this->sql .= " ";

                return $this;
          }
          
          /*
           * Name: DELETE
           * Takes: args = [
           *             REQUIRED
           *                  'table'=>      'tableName'  if missing, sqlSpinner throws sqlSpunError
                         ]
               Description: this->sql = INSERT INTO tableName (columnName, columName) VALUES (:columnName, :columnName)
                         sql statement uses placeholders for pdo.  Be sure to match your value array appropriately.
           *
           */
          function DELETE($args){
               $this->method = "delete";
               try {
                    if(isset($args['table'])){
                         $this->sql = "DELETE FROM ".$args['table']." ";
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

          function JOIN($join = [], $condition = []){
              if($this->method == "select"){
                if($join !== []){
                     foreach($join as $tableMethod){
                          foreach($tableMethod as $joinMethod=>$tableName){
                               $this->sql .= strtoupper($joinMethod)." ".$tableName. " ";
                          }

                     }
                     $this->sql .=" ";

                     if(array_key_exists("on", $condition)){
                          $this->sql .= "ON ";
                          $c = count($condition['on']);
                          $match = $condition['on'];
                          for($i=0; $i<$c; $i += 2){
                               $pre = $match[$i];
                               $post = $match[$i+1];

                               if(count($pre) ===1){
                                 foreach($pre as $tableName=>$column){
                                      $this->sql .= $tableName.".".$column."=";
                                 }
                                 foreach($post as $tableName=>$column){
                                      $this->sql .= $tableName.'.'.$column." ";
                                 }
                                 if($i < $c-2){
                                    $this->sql .= "AND ";
                                 }
                               } else {
                                   $z=1;
                                   foreach($pre as $tableName=>$column){
                                       $this->sql .= $tableName.".".$column;
                                       if($z===1){
                                           $this->sql.= "=";
                                       }
                                       else {
                                           $this->sql .=" AND ";
                                       }
                                       $z++;
                                   }

                                   $z=1;
                                   foreach($post as $tableName=>$column){
                                       $this->sql .= $tableName.".".$column;
                                       if($z===1){
                                           $this->sql.= "=";
                                       }
                                       else {
                                           if($z < count($post)){
                                             $this->sql .=" AND ";
                                           }
                                           else {
                                               $this->sql .= " ";
                                           }
                                       }
                                       $z++;
                                   }

                               }


                          }
                     }
                     elseif(array_key_exists("using", $condition)){
                          $this->sql .= "USING (";
                          $using = $condition['using'];
                          $uC = count($using);
                          $this->sql .= implode(",", $using);
                          $this->sql.=") ";
                     }
                }
              } elseif($this->method == "update") {
                  foreach($join as $tableMethod){
                      foreach($tableMethod as $method=>$table){
                          if(array_key_exists('on', $condition)){
                              $on = $condition['on'];
                              foreach($on as $relation){
                                  var_dump($relation);
                              }
                          }
                      }
                  }
              }

               return($this);
          }

          /*
           * Name: WHERE
           * Takes: where = [
           *                  columnName=>columnValue ||    columnName = :columnName
           *                  columnName=>[method=>columnValue] ||
           *                      = columnName . (this->methodSpin(method)) . :where.columnName
           *
           *                  columnName=>[method=>columnValues]
           *                       method = 'between' = columnName BETWEEN :where.columnName.0 AND :where.columnName.1 (AND :where.columnName.2)
           *                       method = 'or' = columnName = :where.columnName.0 OR :where.columnName.1 (OR :where.columnName.2)
           *                       method = 'in' = columnName IN (:where.columnName.0, :where.columnName.1(,:where.columnName.2))
           *                       method = 'not in' = columnName NOT IN (:where.columnName.0, :where.columnName.1(,:where.columnName.2))
                         ]
               Description: Appends WHERE clause to current sql statement with pdo placeholders
           *
           */
          function WHERE($where){

               if(!empty($where)){
                    $this->sql .="WHERE ";
                    $wI = 0;
                    $whereCount = count($where);
                    foreach($where as $column=>$value){
                         if(!is_array($value)){
                              $this->sql .= $column." = :where".str_replace(".","",$column);
                         }
                         else {
                              foreach($value as $method=>$secondValue){
                                   if(!is_array($secondValue)){
                                        $this->sql .= $column.$this->methodSpin($method).":where".str_replace(".","",$column);
                                   }
                                   else {
                                        $vCount = count($secondValue);
                                        switch(strtolower(trim($method))){
                                            case "between":
                                                  $this->sql .= $column." BETWEEN ";
                                                  for($vI=0;$vI<$vCount;$vI++){
                                                       $this->sql .= ":where".str_replace(".","",$column).$vI;
                                                       if($vI !== $vCount-1){
                                                            $this->sql .= " AND ";
                                                       }
                                                  }
                                                  break;
                                             case "or":
                                                  $this->sql .=$column." =";
                                                  for($vI=0;$vI<$vCount;$vI++){
                                                       $this->sql .= ":where".str_replace(".","",$column).$vI;
                                                       if($vI !== $vCount-1){
                                                            $this->sql .= " OR ";
                                                       }
                                                  }
                                                  break;
                                             case "in":
                                                  $this->sql .= $column." IN (";
                                                  for($vI=0;$vI<$vCount;$v++){
                                                       $this->sql .= ":where".str_replace(".","",$column).$vI;
                                                       if($vI !== $vCount-1){
                                                            $this->sql .= ", ";
                                                       }
                                                  }
                                                  $this->sql .=")";
                                                  break;
                                             case "notin":
                                                  $this->sql .= $column." NOT IN (";
                                                  for($vI=0;$vI<$vCount;$v++){
                                                       $this->sql .=":where".str_replace(".","",$column).$vI;
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
                              if(($this->method === "select") || ($this->method === "delete")){
                                   $this->sql .= " AND ";
                              }
                              else if($this->method === "update"){
                                   $this->sql .= ", ";
                              }
                         }
                         $wI++;
                    }
                    $this->sql .= " ";
               }
               return($this);
          }


          /*
           * Name: DELETE
           * Takes: groupby = [columnName, columnName]
               Description: Appends GROUP BY columnName(, columnname) to SQL statement
           *
           */
          function GROUPBY($groupby = []){

               if(!empty($groupby)){
                    $this->sql.="GROUP BY ";
                    $this->sql .= implode(", ",$groupby)." ";
               }

               return($this);
          }

          /*
           * Name: HAVING
           * Takes: having = [
           *             aggMethod=>    aggregate method (see this->aggregate())
           *             'columns'=>    ['columnName', 'columnName']
           *             'comparison'=> [
           *                            'method'=>(see this->methodSpin())
           *                            'value'=>value to compare aggregate result to
           *                            ]
           *            ]
               Description: Appends HAVING clause to the sql statement.  Must use aggregate in having.
               DO NOT use HAVING to replace a WHERE clause.  Where does not handle sql aggregate functions.
           *
           */
          function HAVING($having=[]){

               //having = [aggmethod=>[columnNames]]
               //DO NOT USE HAVING TO REPLACE A WHERE
               //Having should only use group by columns for accuracy

               if(!empty($having)){
                    $this->sql .= "HAVING ";
                    $method = $having['aggMethod'];
                    $columns = (isset($having['columns'])) ? $having['columns'] : [];
                    $comparison = $having['comparison']['method'];
                    $compareValue = $having['comparison']['value'];

                    $this->aggregate($method, $columns);

                    $this->sql .= $this->methodSpin($comparison).$compareValue." ";
               }

               return($this);
          }

          /*
           * Name: ORDERBY
           * Takes: sort = ['columnName'=>method (asc | desc) | [columnName=>method (asc | desc), columnName=>method (asc | desc)] | 'NULL' | null
               Description: Appends ORDER BY columnName(, columnname) to SQL statement
               you want to set sort to a column, array of columns or NULL for speed sake if groupby was appended to sql statement
           *
           */
          function ORDERBY($sort = []){

               if(!empty($sort)){
                    $this->sql .= "ORDER BY ";
                    $i = 0;

                    if($sort==='NULL' || $sort === null){
                         $this->sql.= "NULL ";
                    }
                    else {
                         $orderCount = count($sort);
                         if(is_array($sort)){
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
                    $this->sql .= " ";
               }
               return($this);
          }

          /*
           * Name: LIMIT
           * Takes: limit = int
               Description: Appends LIMIT clause to sql statement.
           *
           */
          function LIMIT($limit = null){
               if($limit !== null){
                    $this->sql .= "LIMIT ".$limit." ";
               }
               return($this);
          }

          /*
           * Name: DESCRIBE
           *  Takes: REQUIRED
           *             table = 'tableName'
           *        OPTIONAL
           *             column = 'columnName'
               Description: Generates DESC table || DESC table columnname statement which is used to get information on the schema of a table
           *
           */
          function DESCRIBE($table, $column = ""){
               $this->sql = "DESC ".$table;
               if($column !== ""){
                    $this->sql .= " " . $column." ";
               }
               return($this);
          }

          /*
           * Name: getSQL
           * Description: returns the sql statement and resets this->sql to an empty string
           */
          function getSQL(){
               $sql = $this->sql;
               $this->sql = "";
               return($sql);
          }
     }
?>