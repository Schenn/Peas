<?php
     namespace PDOI;
     require_once("PDOI.php");
     require_once("Utils/dynamo.php");
     require_once("Utils/schema.php");
     use PDOI\PDOI as PDOI;
     use PDOI\Utils\dynamo as dynamo;
     use PDOI\Utils\schema as schema;
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


      /*
       * tableName = name of the table this pdoITable is primarily working with
       * columns = current listing of columns and default values, joins merge those columns into this array in table.columnName format
       * columnMeta = table data rules retrieved and parsed from the DESCRIBE mysql method
       * !!!!
       * args = preconstructed list which the parent PDOI uses to process basic commands.
       *       REMOVE THIS.  Arguments should be generated at request time from the schema and values, not stored
       * !!!!
       * entity = dynamo class object which represents a row or potential row in a table.
       *       A joinWith function call creates a similar entity but includes the additional column data from the other tables.
       *       For this reason, using non-conflicting names is a good idea if the table will be joined.
       *
       * schema = current relational schema.  Used to generate arguments, maintains relational (and non relational) data between tables.
       *
       */
     class pdoITable extends PDOI {
          protected $tableName;
          protected $columns=[];
          protected $columnMeta=[];
          protected $entity;
          protected $schema;
          protected $args = [];


          public function generateArguments(){
               //uses schema information to generate argument list
               $a = [];
               //if one table

               $tables = $this->schema->getTables();

               if(count($tables)===1){
                    $a['table'] = $tables[0];
                    $a['cols'] = $this->schema->getColumns($tables[0]);

               }
               elseif(count($tables)>1){ //if multiple tables
                    $a['table'] = [];
                    $a['join'] = [];
                    $i=0;
                    foreach($this->schema as $table=>$colData){
                         array_push($a['table'],[$table=>$this->schema->getColumns($table)]);
                         if($i > 0){
                              array_push($a['join'],['inner join'=>$table]);
                         }
                         $i++;
                    }
                    $rels=[];
                    foreach($this->schema->getForeignkeys() as $table=>$fKeys){
                        //table1 = [0=>[table1Column=>[table2=>column2]]]
                        //translate into [table1=>column1, table2=>column2]
                        $table1 = $table;
                        foreach($fKeys as $keys){
                            foreach($keys as $column=>$keyData){
                                foreach($keyData as $table2=>$connectingData){
                                    $thisRel = [$table1=>$column, $table2=>$connectingData];
                                    array_push($rels, $thisRel);
                                }
                            }
                        }
                    }
                    $a['on'] = $rels;
               }

               return($a);
          }


          /* Name: __construct
           * Description:  Controls new pdoITable creation
           * Takes: config = db configuration information, table = "" (table name), debug = false
           */
          function __construct($config, $tables, $debug=false){
               parent::__construct($config, $debug);
               $this->schema = new schema([]);
               $this->entity = new dynamo();
               $this->setTable($tables);
          }


          /* Name: setTable
           * Description:  sets the tablename, calls setcolumns
           * Takes: table = "" (table name)
           */
          function setTable($tables){
               $this->schema->addTable($tables);
               $this->tableName = $tables;
               $this->args['table'] = $this->tableName;
               $this->setColumns();
          }

          /* Name: setColumns
           * Description:  Gets table schema from the db.  becomes aware of column names and validation requirements
           */
          function setColumns(){
               foreach($this->schema as $table=>$columns){
               if(count($columns)===0){
                    $description = parent::describe($table);
                    $cols = [];
                    foreach($description as $row){  //for each column in table
                         $field = $row['Field'];
                         //$true = $table.".".$field;
                         array_push($cols, $field);
                         unset($row['Field']);

                         //set default values to the columns
                         switch(preg_filter("/\(|\d+|\)/","",strtolower($row['Type']))){
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
                                   $row['Format'] = "Y-m-d H:i:s";
                                   break;
                              default:
                                   $this->columns[$field]= (empty($row['Default'])) ? "" : $row['Default'];
                                   break;
                         }

                         $this->schema->setMeta($table,$field,$row);
                         $this->columnMeta[$field] = $this->schema->getMeta($table, $field);
                         $this->entity->$field = $this->columns[$field];

                    }

                    $this->entity->setValidationRules($this->columnMeta); //sets validation rules on dynamic object based off validation information
               }
               }



               // Set up the schema
          }

          /* Name: select
           * Description:  Runs a select query on the table
           * Takes: options = [] (associative array of options.  Overrides currently stored arguments for the query)
           */
          function select($options=[], $entity = null){
               $entity = ($entity !== null ? $entity : clone $this->entity); //if no object supplied to take values from select query, use dynamo
               $a = $this->generateArguments();
               foreach($options as $option=>$setting){ //supplied options override stored arguments
                    $a[$option]=$setting;
               }

               return(parent::SELECT($a, $entity)); //return PDOI select result
          }

          function selectAll(){
               $entity = clone $this->entity;
               $a = $this->generateArguments();
               return(parent::SELECT($a, $entity));
          }

          /* Name: insert
           * Description:  Runs an insert query into the table
           * Takes: options = [] (associative array of options, overrides currently stored arguments for the query)
           */
          function insert($options){
               $a = $this->args;
               //$a = $this->generateArguments();

               //ensures that if the primary key is auto-numbering, no value will be sent
               foreach($a['columns'] as $index=>$key){
                    if(array_key_exists("auto",$this->columnMeta[$key])){
                         $removeIndex = $index;
                    }
               }
               unset($a['columns'][$removeIndex]);

               //resets array indexes for columns in arguments
               $a['columns'] = array_values($a['columns']);
               if(!isset($options['values'])){ //if no values supplied, uses stored information for values
                    $a['values']=[];
                    foreach($this->columns as $column=>$value){
                         if(!array_key_exists("auto",$this->columnMeta[$column])){
                              $a['values'][$column]=$value;
                         }
                    }
               }
               foreach($options as $option=>$setting){ //overrides the arguments, adds in extra info not stored
                    $a[$option]=$setting;
               }

               return(parent::INSERT($a)); //returns result of PDOI->insert
          }

          //sets a column to a value
          function setCol($col,$val){
               //Validate?
               $this->columns[$col]=$val;
          }

          //gets a column value
          function getCol($col){
               return($this->columns[$col]);
          }

          function joinWith($join, $connector=[], $where = []){
               /*
               *'join'=> ['method'=>'tableName']
               *'on'=>[['table'=>'column', 'table'=>'column'], ['table'=>'column', 'table'=>'column']]
               * || 'using' =>['columnName','columnName']
                */
              /*
               $opts = [];
               $opts["join"]=$join;
               $opts['table']=[];
               $opts['table'][$this->tableName]=[];
               foreach($this->columns as $column=>$value){
                    array_push($opts['table'][$this->tableName],$column);
               }
               $e = $this->Offshoot();
               foreach($join as $index=>$joinRules){

               foreach($joinRules as $method=>$table){
                    $meta=[];
                    $cols=[];

                    $opts['table'][$table]=[];
                    $d = $this->describe($table);
                    foreach($d as $columnData){
                         $field = $columnData['Field'];
                         $sansType = preg_split("/int|decimal|double|float|double|real|bit|bool|serial|date|time|year|char|text|binary|blob|enum|set|geometrycollection|multipolygon|multilinestring|multipoint|polygon|linestring|point|geometry/",strtolower($columnData['Type']));
                         if(isset($sansType[1])){
                              $sansParens = preg_split("/\(|\)/",$sansType[1]);
                              if(isset($sansParens[1])){
                                   $meta[$field]['length'] = intval($sansParens[1]);
                              }
                         }
                         $meta[$field]['type'] = preg_filter("/\(|\d+|\)/","",strtolower($columnData['Type']));
                         $meta[$field]['default'] = $columnData['Default'];
                         if($columnData['Key'] === "PRI"){
                              $meta[$field]['primaryKey'] = true;

                              //if its auto_incremented
                              if($columnData['Extra'] === "auto_increment"){
                                   $meta[$field]['auto'] = true;
                              }
                         }


                         if($columnData['Null'] === 'NO'){
                              $this->columnMeta[$field]['required'] = true;
                         }

                         //set default values to the columns
                         switch($meta[$field]['type']){
                              case "int":
                              case "decimal":
                              case "double":
                              case "float":
                              case "real":
                              case "bit":
                              case "serial":
                                   $cols[$field] = (empty($columnData['Default'])) ? 0 : $columnData['Default'];
                                   break;
                              case "bool":
                                   $cols[$field] = (empty($columnData['Default'])) ? false : $columnData['Default'];
                                   break;
                              case "date":
                              case "time":
                              case "year":
                                   $cols[$field]= (empty($columnData['Default'])) ? date("Y-m-d H:i:s") : strtotime($columnData['Default']);
                                   $meta[$field]['format'] = "Y-m-d H:i:s";
                                   break;
                              default:
                                   $cols[$field]= (empty($columnData['Default'])) ? "" : $columnData['Default'];
                                   break;
                         }
                    }
                    foreach($cols as $column=>$def){
                         array_push($opts['table'][$table], $column);
                         $e->$column=$def;
                    }
                    $e->setValidationRules($meta);
               }
               }

               if(array_key_exists("on",$connector)){
                    $opts['on']=$connector["on"];
               }else{
                    $opts['using']=$connector["using"];
               }

               if($where !== []){ //if where, run select operation
                    $opts['where']=$where;
                    $chunk = parent::SELECT($opts, $e);
                    return($chunk);
               }
               else { //if no where, return template for join
                    return($e);
               }
               *
               */
          }

          /* Name: update
           * Description:  Runs an update query on the table
           * Takes: options = [] (associative array of options, overrides currently stored arguments for the query)
           */
          function update($options){
               //$a = $this->args;
               $a = $this->generateArguments();

                //ensures auto_numbering primary key is not 'updated'
               if(is_array($a['table'])){
                    foreach($a['table'] as $tindex=>$tableData){
                        foreach($tableData as $tableName=>$columns){
                            foreach($columns as $cindex=>$column){
                                if(array_key_exists("primaryKey",$this->schema->getMeta($tableName, $column))){
                                   unset($a['table'][$tindex][$tableName][$cindex]);
                                }
                            }
                        }
                    }
               } elseif(is_string($a['table'])){
                   $tableName = $a['table'];
                   $colCount = count($a['cols']);
                   for($i=0;$i<$colCount;$i++){
                       if(array_key_exists("primaryKey",$this->schema->getMeta($tableName, $a['cols'][$i]))){
                            unset($a['cols'][$i]);
                        }
                   }
                   $a['cols'] = array_values($a['cols']);
               }

               //override stored arguments
               foreach($options as $option=>$setting){
                    $a[$option]=$setting;
               }

               return(parent::UPDATE($a)); //return PDOI->update result
          }

          /* Name: delete
           * Description:  Runs a delete query on the table
           * Takes: options = [] (associative array of options, overrides currently stored arguments for the query)
           */
          function delete($options){
               $a = $this->args;
               //$a = $this->generateArguments();
               foreach($options as $option=>$setting){
                    $a[$option]=$setting;
               }
               unset($a['columns']); //no columns in DELETE command
               return(parent::DELETE($a));
          }

          // Adds a function to the currently existing dynamo template
          function addMethodToEntity($name, $method){
               if(is_callable($method)){
                    $this->entity->$name = $method;
               }
          }

          // resets the columns to their default values
          function reset(){
               foreach($this->schema as $table=>$columns){

                    $cols = array_keys($columns);

                    foreach($cols as $col){
                         $this->columns[$col] = $this->schema->getMeta($table,$col)['default'];
                    }

               }
          }

          //displays the current dynamo
          function display(){
               echo($this->Offshoot());
          }

          /* Name: Offshoot
           * Description:  Returns the current entity with the ability to contact its parent table for insert, update and delete commands
           *
           */
          function Offshoot(){
               $e = clone $this->entity;

               //dynamo insert function, uses this pdoITable
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

                    return $t->insert($args);

               };

               //dynamo update function, uses this pdoITable
               $e->update = function() use ($t){
                    $args = [];
                    $args['set'] = [];
                    $args['where'] = [];
                    //t has schema information
                    //use primary keys to create where
                    //compare current values against defaults before adding to 'set'

                    $schema = $t->getSchema();
                    $pkeys = $schema->getPrimaryKeys();
                    $tCount = count($schema->getTables());
                    foreach($schema as $tableName=>$column){
                        foreach($column as $columnName=>$columnData){
                            if(($this->$columnName !== $columnData['default']) &&
                                    (!array_key_exists("primaryKey",$columnData) &&
                                    ($this->$columnName !== $this->oldData($columnName)))){
                                if($tCount > 1){
                                    $args['set'][$tableName]=[$columnName=>$this->$columnName];
                                }
                                else {
                                    $args['set'][$columnName]=$this->$columnName;
                                }
                            }
                        }
                    }

                    foreach($pkeys as $table=>$column){
                        if($tCount > 1){
                            $args['where'][$table] = [$column=>$this->$column];
                        }
                        else {
                            $args['where'][$column]=$this->$column;
                        }

                    }

                    $args['limit']=1;

                    return $t->update($args);
               };

               //dynamo delete function, uses this pdoITable
               $e->delete = function() use ($t){
                    $args = [];
                    foreach($this as $key=>$value){
                         if(array_key_exists('fixed',$this->getRule($key))){
                              $args['where'] = [$key=>$value];
                         }
                    }
                    return $t->delete($args);
               };

               $this->reset();
               return($e); //returns the dynamo with access to the parent table
          }

          function setRelationship($relationships, $values = false){
               foreach($relationships as $fKey=>$pKey){
                   //add tables w/columns to schema
                   $this->schema->addTable([explode(".",$pKey)[0]]);
                   $this->setColumns();
                   $this->schema->setForeignKey([$fKey=>$pKey]);
               }

          }

          function getSchema(){
              return $this->schema;
          }
     }

?>