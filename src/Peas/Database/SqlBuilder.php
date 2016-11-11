<?php
     namespace Peas\Database;
     use Exception;
     /*
      * @author:  Steven Chennault schenn@mash.is
      * @link: https://github.com/Schenn/EmitterDatabaseHandler Repository
      */

     /*
      * Error Exception for SqlBuilder
      *
      * When sql is malformed or missing data, an SqlBuildError is thrown
      *
      * @category Exceptions
      */

     class SqlBuildError extends Exception {

          protected $errorList = [
               "Invalid Column data for Insert Spinning.",
               "Missing Table Name",
               "Missing 'set' data for Update Spinning"
          ];

          public function __construct($message,$code, Exception $previous = null){
               $message .= " SqlBuilder ERROR: ".$code.": ".$this->errorList[$code];
               parent::__construct($message, $code, $previous);
          }
     }

     /*
      * SqlBuilder generates sql from an argument dictionary
      *
      * It's methods can be chained together until getSQL is called.
      *
      * @see SqlBuilder::getSQL
      * @todo Improve error handling
      * @todo Remove unused variables, clean up and format
      */
     class SqlBuilder {
         /** @var string some methods need to know what type of query is being constructed to guide their flow
          *  @see sqlSpinner::WHERE
          */
          protected $method;
         /** @var  string the working sql string */
          protected $sql;
         /** @var array list of mysql types and their default values.
          *     primary_key is a special marker that tells the spinner what type the primary key should be */
          protected $typeBasics = [
              'primary_key'=>'int',
              'ai'=>true,
              'bit'=>1,
              'tinyint'=>4,
              'smallint'=>6,
              'mediumint'=>9,
              'int'=>11,
              'bigint'=>20,
              'decimal'=>'(10,0)',
              'float'=>'',
              'double'=>'',
              'boolean'=>['tinyint'=>1],
              'date'=>'',
              'datetime',
              'timestamp'=>'',
              'time'=>'',
              'year'=>4,
              'char'=>1,
              'varchar'=>255,
              'binary'=>8,
              'varbinary'=>16,
              'tinyblob'=>255,
              'tinytext'=>255,
              'blob'=>'',
              'text'=>'',
              'mediumblob'=>'',
              'mediumtext'=>'',
              'longblob'=>'',
              'longtext'=>'',
              'enum'=>'',
              'set'=>''
          ];

          /*
           * Constructs an aggregate function
           *
           * Some sql requires summing or counting or getting the min or max values. This method constructs that segment
           * of the sql query. This method does not return the spinner as it is not part of the api
           *
           * @param string aggMethod "" | (sum | avg | count | min | max)
           * @param array aggValues a list of column names
           *
           * @see SqlBuilder::SELECT
           *
           * @internal
           */

          protected function aggregate($aggMethod, $aggValues=[], $alias = ""){
              $this->sql .= strtoupper($aggMethod)."(";

              //if 'columnNames' is empty, * is used as the column
              $cNameCount = count($aggValues);
              $this->sql .= ($cNameCount === 0) ? "*" : implode(", ", $aggValues);
              $this->sql.=")";

              // alias allows mysql to give a name to the value of the function result
              if(empty($alias)){
                  // Use the first column name or the method being used if no columns are given
                  $alias = ($cNameCount > 0) ? $aggMethod.$aggValues[0] : $aggMethod;
              }

              $this->sql.= " AS ".$alias." ";
          }


         protected function setResultSize($resultSize) {
             $resultSize = strtoupper($resultSize);
             if($resultSize==='BIG'){
                 $this->sql.=" SQL_BIG_RESULT ";
             }
             elseif($resultSize==='SMALL'){
                 $this->sql.=" SQL_SMALL_RESULT ";
             }
         }

         /**
          * Transforms the developer friendly comparison method argument into an sql friendly comparison string
          *
          * @param string $method the intended sql comparison method in developer friendly syntax
          * @return string the sql friendly comparison operator
          *
          * @see sqlSpinner::WHERE
          * @internal
          */
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

          /**
           * Begins constructing the sql for a select query
           *
           * SELECT columns FROM table
           *
           * @param array $args list of arguments
           *    REQUIRED
           *        'table'=>      'tableName' | ['tableName', 'tableName']
           *    OPTIONAL
           *        'columns'=>    ['columnName', (aggregateMethod=>['method'=>'values'])]
           *            If columns omitted, SELECT * is used instead
           *    VERY OPTIONAL
           *        'union'=>     true (UNION) Can be used in a subsequent call to select
           *             (e.g. spinner->SELECT($args)->SELECT($argsWithUnion)->getSQL)
           *             (ignored if priority is set -- per mysql docs: HIGH_PRIORITY cannot be used with SELECT statements that are part of a UNION. )
           *        'distinct'=>   'distinct' | 'distinctrow'
           *        'result'=>     'big' | 'small' (sql_big_result | sql_small_result)
           *             Requires either args['distinct'] or args['groupby'] to have value
           *        'priority'=>   true (HIGH_PRIORITY)
           *        'buffer'=>     true (SQL_BUFFER_RESULT)
           *        'cache'=>      true | false (SQL_CACHE | SQL_NO_CACHE)
           *
           * @throws SqlBuildError if no table information is given
           * @return SqlBuilder this
           *
           * @todo Throw SqlBuildError if the provided arguments have invalid values or are being used in invalid ways
           *
           * @api
           */
          function SELECT($args){
               $this->method = 'select';
              // union is used to join multiple select statements into a single return value
              if(isset($args['union']) && !empty($this->sql) && substr($this->sql,0,6) == "SELECT"){
                  $this->sql .= " UNION SELECT ";
              } else {
                  $this->sql = "SELECT ";
              }

               try {

                    if(isset($args['distinct'])){
                         $distinct = strtoupper($args['distinct']);
                         if($distinct !== 'ALL'){
                              $this->sql .= $distinct." ";
                              if(isset($args['result'])){
                                  $this->setResultSize($args['result']);
                              }
                         }
                    }

                    if(isset($args['groupby']) && isset($args['result'])){
                         $this->setResultSize($args['result']);
                    }

                    if(isset($args['priority']) && !isset($args['union'])){
                         $this->sql .= " HIGH_PRIORITY ";
                    }

                    if(isset($args['buffer'])){
                         $this->sql .= " SQL_BUFFER_RESULT ";
                    }

                    if(isset($args['cache'])){
                        $this->sql .= ($args['cache']===true) ? "SQL_CACHE " : "SQL_NO_CACHE ";
                    }


                    if(!empty($args['columns'])){
                         $i=0;
                         $cols = count($args['columns']);
                         if(is_array($args['columns'])){
                            foreach($args['columns'] as $index=>$col){
                                if(!isset($col['agg'])){
                                    $this->sql .= ($i !== $cols-1) ? "$col, " : $col . ' ';
                                }
                                else {
                                    $alias = (is_string($index)) ? $index : "";
                                    foreach($col['agg'] as $method=>$columnNames){
                                        $this->aggregate($method, $columnNames, $alias);
                                    }
                                }
                                $i++;
                            }
                         }
                        else if(is_string($args['columns']))  {
                            $this->sql .= $args['columns'] . " ";
                        }
                    }
                    else {
                         $this->sql .= " * ";
                    }

                    if(isset($args['table'])){
                        $this->sql .= "FROM ";
                        $this->sql .= (is_array($args['table'])) ? implode(", ", $args['table']) : $args['table'];
                        $this->sql .= " ";
                    }
                    else {
                         throw new SqlBuildError("Invalid Arguments",1);
                    }
               } catch(SqlBuildError $e){
                    echo $e->getMessage();
               }

               return($this);
          }

       /*
        * Begins constructing the sql as an INSERT query
        *
        * INSERT INTO table (columnName, columnName,...) VALUES (:columnName, :columnName, ...)
        *
        * @param array args
        *             REQUIRED
        *                  'table'=>      'tableName'  if missing, SqlBuilder throws SqlBuildError
        *                  'columns'=>    ['columnName', 'columnName']  if missing, SqlBuilder throws SqlBuildError
        *
        * @throws SqlBuildError if no table or columns provided
        *
        * @return SqlBuilder this
        */
         function INSERT($args){
               $this->method = 'insert';

               try {
                    if(isset($args['table'])){
                         $this->sql = "INSERT INTO"." ".$args['table'];
                    }
                    else {
                         throw new SqlBuildError("Invalid Arguments", 1);
                    }


                    if((is_array($args['columns'])) && (isset($args['columns'][0]))){
                         $columnCount = count($args['columns']);
                    }
                    else {
                         throw new SqlBuildError("Invalid Arguments",0);
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


               } catch(SqlBuildError $e){
                    echo $e->getMessage();
               }
             return($this);
          }

          /*
           * Begins constructing an UPDATE mysql query
           *
           * UPDATE table
           * sql statement uses placeholders for pdo.  Be sure to match your value array appropriately.
           *
           * @param array args
           *             REQUIRED
           *                  'table'=>      'tableName'  if missing, SqlBuilder throws SqlBuildError
           *                  'set'=>    ['columnName'=>'value']  if missing, SqlBuilder throws SqlBuildError
           *
           * @throws SqlBuildError if no table or set argument provided
           * @return SqlBuilder this
           *
           * @api
           */
          function UPDATE($args){
               $this->method = "update";
               try {
                    if(isset($args['table'])){
                         $this->sql = "UPDATE ".$args['table']." ";
                    }
                    else {
                         throw new SqlBuildError("Invalid Arguments", 1);
                    }
                    if(!isset($args['set'])){
                         throw new SqlBuildError("Invalid Arguments", 2);
                    }
                    

               }
               catch (SqlBuildError $e){
                    echo $e->getMessage();
               }
              return($this);
          }
          
         /**
          * Adds the SET segment to an UPDATE query
          *
          * Appends the SET segment to the UPDATE query. Creates placeholders for pdo to bind a value to
          * UPDATE table SET column = :setColumn, column = :setColumn, ...
          *
          * @param array $args
          *         REQUIRED
          *             'set'=> ['columnName'=>value, 'columnName'=>value,...]]
          * @return SqlBuilder this
          * @api
          * @todo Error Catching
          */
          function SET($args){
              if($this->method == "update") {
                  $this->sql .= "SET ";
                  $i = 0;
                  $cCount = count($args['set']);
                  foreach ($args['set'] as $column => $value) {
                      $this->sql .= "$column = :set" . str_replace(".", "", $column);
                      if ($i !== $cCount - 1) {
                          $this->sql .= ", ";
                      }
                      $i++;
                  }
                  $this->sql .= " ";
              }
              return($this);
          }

          /*
           * Begins constructing the sql as a DELETE query
           *
           * DELETE FROM table
           *
           * @param array args
           *             REQUIRED
           *                  'table'=>      'tableName'  if missing, SqlBuilder throws SqlBuildError
           * @throws SqlBuildError if no table provided
           * @return SqlBuilder this
           * @api
           */
          function DELETE($args){
               $this->method = "delete";
               try {
                    if(isset($args['table'])){
                         $this->sql = "DELETE FROM"." ".$args['table']." ";
                    }
                    else {
                         throw new SqlBuildError("Invalid Arguments",1);
                    }

               }
               catch (SqlBuildError $e){
                    echo $e->getMessage();
               }
              return($this);
          }
          
          /*
           * Begins constructing the sql as a CREATE statement
           *
           * CREATE TABLE tableName IF NOT EXISTS (prop details, prop details, .., PRIMARY KEY (primary key));
           * Most of the properties have default types or lengths which can be found in this typeBasics
           *
           * @see SqlBuilder::typeBasics
           *
           * @param string tableName name of the table to create
           * @param array props
           *              'props'=>['primary_key_name'=>['type','length','noai','null'], ['field_name'=>['type','length','notnull',default],...]
           *              The first provided property is assigned as the primary key
           * @return SqlBuilder this
           * @api
           *
           * @todo Error Catching
           */
          function CREATE($tableName, $props){
              $this->method = 'create';
              if(!empty($tableName)){
                  $this->sql .= "DROP TABLE IF EXISTS {$tableName};CREATE TABLE IF NOT EXISTS ".$tableName." (";
                  $i=0;
                  foreach($props as $field=>$prop){
                      $this->sql .= $field.' ';
                      $isPrimary = false;
                      if($i===0){
                          $isPrimary = true;
                          $type = (isset($prop['type'])) ? $prop['type'] : $this->typeBasics['primary_key'];
                      } else {
                          $type = $prop['type'];
                      }
                      $length = (isset($prop['length'])) ? $prop['length'] : $this->typeBasics[$type];
                      $this->sql .= $type . '(' . $length . ') ';
                      
                      if($isPrimary){
                          $this->sql .= "PRIMARY KEY ";
                      }
                      
                      if(!isset($prop['null'])){
                          $this->sql .= "NOT NULL ";
                      }
                      
                      if($isPrimary && !isset($prop['noai'])){
                          $this->sql .= "AUTO_INCREMENT ";
                      }
                      
                      if($type==="timestamp"){
                          $prop['default'] = 'CURRENT_TIMESTAMP ';
                          
                      }
                      
                      if(!$isPrimary && isset($prop['default'])){
                          $this->sql .= " DEFAULT = ".$prop['default'].' ';
                      }
                      
                      if($type==="timestamp"){
                          if(isset($prop['update'])){
                            $this->sql .= "ON UPDATE CURRENT_TIMESTAMP ";
                          }
                      }
                      if($i < count($props)-1){
                          $this->sql .= ", ";
                      } else {
                          $this->sql .= ")";
                      }
                      $i++;
                      
                  }
                  
              }
              return($this);
          }

         /**
          * Constructs the sql as a DROP table query
          *
          * DROP TABLE IF EXISTS tableName

          * @param string $tableName Name of the table to drop
          * @return SqlBuilder this
          *
          * @api
          * @todo Error catching
          */
          function DROP($tableName){
              $this->sql = "DROP TABLE IF EXISTS {$tableName}";
              return($this);
          }


         /**
          * Generates the join segment of an sql query
          *
          * Generates one of the following
          *  .. JOIN ON table1.foreignKey = table2.primaryKey, ...
          *  .. JOIN USING col1, col2, col3 ...
          *
          * @param array $join [tableName, tableName]
          * @param array $condition ['on'=>['table1.foreignKey'=>'table2.primaryKey',..]] || ['using'=>['col1', 'col2', 'col3']]
          * @throws SqlBuildError if no join information provided
          *
          * @return SqlBuilder this
          *
          * @api
          */

          function JOIN($join = [], $condition = []){
              if($this->method=='update'){
                  if(!empty($join)){
                      $block = [];
                      $i=0;
                      foreach($join as $tableMethod){
                             foreach($tableMethod as $joinMethod=>$tableName){
                                 $block[$i] = strtoupper($joinMethod)." ".$tableName;
                                 $i++;
                                  //$this->sql .= strtoupper($joinMethod)." ".$tableName;
                             }

                        }

                        if(array_key_exists("on", $condition)){
                             //$this->sql .= "ON ";
                             $c = count($condition['on']);
                             $i=0;
                             foreach($condition['on'] as $rel){
                                 $z = 0;
                                 foreach($rel as $table=>$column){
                                     if(isset($block[$i])){
                                            if($z===0){
                                               $this->sql.= $block[$i]." ON ".$table.".".$column."=";
                                               $z++;
                                            }
                                            else {
                                                $this->sql .= $table.'.'.$column." ";
                                                $z=0;
                                            }
                                     }
                                     else {
                                         break;
                                     }
                                 }
                                 $i++;
                                 //$this->sql.= $block[$i]." ON ".$rel[0]."=".$rel[1];
                             }

                        }
                        elseif(array_key_exists("using", $condition)){
                             $this->sql .= "USING (";
                             $using = $condition['using'];
                             $this->sql .= implode(",", $using);
                             $this->sql.=") ";
                        }

                  }
              } else{
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
                         $i=0;
                         foreach($condition['on'] as $rel){
                             $z=0;
                             foreach($rel as $tableName=>$columnName){
                                 $this->sql .= $tableName.".".$columnName;
                                 if($z <1){
                                     $this->sql .= "= ";
                                 }
                                 else {
                                     $this->sql .= " ";
                                 }
                                 $z++;
                             }
                             if($i<$c-1){
                                 $this->sql .= " AND ";
                             }
                             $i++;
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
              }
              
               return($this);
          }

          /**
           * Appends the WHERE clause to the current sql query
           *
           * Generates the WHERE segment of the sql query
           *
           * @param array $where
           *                  columnName=>columnValue   generates
           *                       columnName = :columnName
           *                  columnName=>[method=>columnValue]     generates
           *                       columnName . (this->methodSpin(method)) . :where.columnName
           *                  columnName=>[method=>columnValues]    generates
           *                       method = 'between' = columnName BETWEEN :where.columnName.0 AND :where.columnName.1 (AND :where.columnName.2)
           *                       method = 'or' = columnName = :where.columnName.0 OR :where.columnName.1 (OR :where.columnName.2)
           *                       method = 'in' = columnName IN (:where.columnName.0, :where.columnName.1(,:where.columnName.2))
           *                       method = 'not in' = columnName NOT IN (:where.columnName.0, :where.columnName.1(,:where.columnName.2))
           *
           * @return SqlBuilder this
           *
           * @api
           *
           * @todo Error Catching
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
                                                  for($vI=0;$vI<$vCount;$vI++){
                                                       $this->sql .= ":where".str_replace(".","",$column).$vI;
                                                       if($vI !== $vCount-1){
                                                            $this->sql .= ", ";
                                                       }
                                                  }
                                                  $this->sql .=")";
                                                  break;
                                            case "notin":
                                                  $this->sql .= $column." NOT IN (";
                                                  for($vI=0;$vI<$vCount;$vI++){
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
                              if(($this->method === "select") || ($this->method === "delete") || ($this->method === "update")){
                                   $this->sql .= " AND ";
                              }
                         }
                         $wI++;
                    }
                    $this->sql .= " ";
               }
               return($this);
          }


          /**
           * Generates the GROUPBY segment of the sql query
           *
           * GROUP BY columnName, columnName, ..
           * @param array $groupBy
           *         [columnName, columnName]
           * @return SqlBuilder this
           * @api
           */
          function GROUPBY($groupBy = []){

               if(!empty($groupBy)){
                    $this->sql.="GROUP BY ";
                    $this->sql .= implode(", ",$groupBy)." ";
               }

               return($this);
          }

          /**
           * Appends HAVING clause to the sql statement.
           *
           * Must use aggregate method in HAVING.  DO NOT use HAVING to replace a WHERE clause.
           * Where does not handle sql aggregate functions.
           *
           * @param array $having
           *             aggMethod=>    aggregate method (see this->aggregate())
           *             'columns'=>    ['columnName', 'columnName']
           *             'comparison'=> [
           *                            'method'=>(see this->methodSpin())
           *                            'value'=>value to compare aggregate result to
           *                            ]
           *
           * @see sqlSpinner::methodSpin
           *
           * @return SqlBuilder this;
           *
           * @api
           * @todo Error Catching
           *
           */
          function HAVING($having=[]){

               //having = [aggMethod=>[columnNames]]
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

          /**
           * Appends Order By sql clause to query
           *
           * you want to set sort to a column, array of columns or NULL for speed sake if groupby was appended to sql statement
           *
           * Sorting by NULL prevents mysql from attempting to sort a group by result set. This increases the performance of the query
           * @see http://dev.mysql.com/doc/refman/5.0/en/order-by-optimization.html
           *
           * ORDER BY columnName(, columnName)
           *
           * @param array|string $sort
           *    ['columnName'=>method (asc | desc)] |
           *    [[columnName=>method (asc | desc)], [columnName=>method (asc | desc)], ..] |
           *    'NULL' |
           *    null (the value)
           *
           * @return SqlBuilder this
           *
           * @api
           *
           * @todo Error Catching
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

          /**
           * Appends LIMIT clause to sql statement.
           *
           * LIMIT n
           * @param int $limit the limit value
           *
           * @return SqlBuilder this
           * @api
           */
          function LIMIT($limit = null){
               if($limit !== null){
                    $this->sql .= "LIMIT ".$limit." ";
               }
               return($this);
          }

          /**
           * Generates a DESC sql query
           *
           * DESC is used to determine information about a table's Schema.
           * DESC table to get Schema information on the whole table
           * DESC table column to get Schema information on a column
           *
           * @param string $table the tableName
           * @param string $column the columnName
           *
           * @return SqlBuilder this
           * @api
           *
           * @todo Error Catching
           */
          function DESCRIBE($table, $column = ""){
               $this->sql = "DESC ".$table;
               if($column !== ""){
                    $this->sql .= " " . $column." ";
               }
               return($this);
          }

          /**
           * Returns the constructed sql query
           *
           * Stops generating the sql query and returns the constructed value of $this->sql
           *
           * @return string $this->sql
           * @api
           */
          function getSQL(){
               $sql = $this->sql;
               $this->sql = "";
               return($sql);
          }
     }
?>