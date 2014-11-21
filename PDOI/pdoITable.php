<?php
     namespace PDOI;
     use PDOI\PDOI as PDOI;
     use PDOI\Utils\dynamo as dynamo;
     use PDOI\Utils\schema as schema;

     /**
      * @author Steven Chennault schenn@mash.is
      * @link: https://github.com/Schenn/PDOI Repository
      */

     /**
      * Class pdoITable
      *
      * pdoITable acts as a wrapper over a relationship of tables. The relationship could be of a single table or more.
      * It acts as a facilitator for retrieving, updating or removing data from the wrapped tables. When you create and
      * assign tables to the pdoITable, it loads the metadata about those tables into a Schema. If you request data from
      * a pdoITable and don't provide an object, it will use a dynamo to hold onto the data.
      *
      * pdoITable can create dynamo's upon request. The dynamos will have the same structure as the table relationship
      * schema that has been created for pdoITable. The dynamos are given the capacity to save themselves when they are
      * created by a pdoITable.
      *
      * @uses PDOI\Utils\schema
      * @uses PDOI\Utils\dynamo
      * @uses PDOI\Utils\sqlSpinner
      *
      * @package PDOI
      * @todo Should we be holding on to args or can that be removed?
      */

     class pdoITable extends PDOI {
         /** @var string|array $tableName name or names of the table(s) this pdoITable is currently working with */
          protected $tableName;
         /** @var array $columns The columns for the tables. [columnName =>value, columnName=>value, ..] */
          protected $columns=[];
         /** @var array $columnMeta The column meta data for the columns */
          protected $columnMeta=[];
         /** @var schema $schema The schema object */
          protected $schema;
         /** @var array arguments Used to generate the SQL queries through sqlSpinner */
          protected $args = [];

         /**
          * Create a new pdoITable
          *
          * @param array $config Dictionary of database configuration data
          * @param string|array $tables The tableName(s) to assign to the pdoITable
          * @param bool $debug Whether or not to log debug information
          *
          * @throws \Exception
          */
          function __construct($config, $tables, $debug=false){
               parent::__construct($config, $debug);
               $this->schema = new schema();
               $this->setTable($tables);
          }


         /**
          * Sets the table(s) for the pdoITable.
          *
          * Runs setColumns after the tables are set. Sets the table argument.
          *
          * @see pdoITable::setColumns
          * @param string|array $tables The table name(s) to add
          */
          function setTable($tables){
               $this->schema->addTable($tables);
               $this->tableName = $tables;
               $this->args['table'] = $this->tableName;
               $this->setColumns();
          }


         /**
          * Sets and initializes the columns that a table should have
          *
          * Adds the column name to the columns dictionary and assigns it a default value
          *
          * @uses PDOI\Describe
          * @internal
          *
          * @todo Remove unused variable $cols
          */
          function setColumns(){
               foreach($this->schema as $table=>$columns){
               if(count($columns)===0){
                    $description = parent::describe($table);
                    $cols = [];
                   //for each column in table
                    foreach($description as $row){
                         $field = $row['Field'];
                         //$true = $table.".".$field;
                         array_push($cols, $field);
                         unset($row['Field']);

                         //set the column default value
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

                    }
               }
               }
               // Set up the schema
          }

         /**
          * Generate the relationship arguments for the sqlSpinner
          *
          * Prepare the relationship arguments for the sqlSpinner. Such as table, columns, join and on arguments
          *
          * @see PDOI\Utils\sqlSpinner
          *
          * @return array The table relationships as a dictionary the sqlSpinner can parse.
          *
          * @internal
          */
         public function generateArguments(){
             //uses schema information to generate argument list
             $arguments = [];

             $tables = $this->schema->getTables();

             //if one table
             if(count($tables)===1){
                 $arguments['table'] = $tables[0];
                 $arguments['columns'] = $this->schema->getColumns($tables[0]);

             }
             elseif(count($tables)>1){
                 //if multiple tables
                 $arguments['table'] = [];
                 $arguments['join'] = [];
                 $i=0;
                 foreach($this->schema as $table=>$colData){
                     array_push($arguments['table'],[$table=>$this->schema->getColumns($table)]);
                     if($i > 0){
                         array_push($arguments['join'],['inner join'=>$table]);
                     }
                     $i++;
                 }
                 $relationships=[];
                 foreach($this->schema->getForeignkeys() as $table=>$foreignKeys){
                     $tablePrimary = $table;
                     foreach($foreignKeys as $keys){
                         foreach($keys as $column=>$keyData){
                             foreach($keyData as $tableForeign=>$connectingData){
                                 $thisRel = [$tablePrimary=>$column, $tableForeign=>$connectingData];
                                 array_push($relationships, $thisRel);
                             }
                         }
                     }
                 }
                 $arguments['on'] = $relationships;
             }

             return($arguments);
         }

         /**
          * Retrieve data from the wrapped table relationships
          *
          * Creates a new dynamo and passes it and the relationship information as a dictionary to PDOI::Select
          *
          * @uses PDOI::SELECT
          *
          * @param array $options see PDO::SELECT for more information on options
          * @param null|object $entity The object to assign the data from the query to. Passed by reference so the
          *     object will be mutated without having to do something with the return value
          *
          * @return array|dynamo|bool|null
          *
          * @api
          */
          function select($options=[], $entity = null){
              //if no object supplied to take values from select query, use dynamo
               $entity = ($entity !== null ? $entity : $this->asDynamo());
               if(array_key_exists('table', $options) && array_key_exists('columns', $options)) {
                   $a = $options;
               } else {
                    $a = $this->generateArguments();
                   //supplied options override stored arguments
                    foreach($options as $option=>$setting){
                         $a[$option]=$setting;
                    }
                    if(count($a['table'])==1){
                        unset($a['join']);
                        unset($a['on']);
                    }
               }
               return(parent::SELECT($a, $entity)); //return PDOI select result
          }

         /**
          * Get all records from the wrapped table relationships
          *
          * Constructs the arguments to select all records and uses a dynamo to hold the data
          *
          * @uses PDOI\Utils\dynamo
          * @return array|bool|null
          *
          * @api
          */
          function selectAll(){
               $entity = $this->asDynamo();
               $a = $this->generateArguments();
               return(parent::SELECT($a, $entity));
          }


         /**
          * Insert data into the wrapped table relationship
          *
          * Validates the values and generates the arguments to insert data into the wrapped tables.
          *
          * @param array $options (associative array of options, overrides currently stored arguments for the query)
          * @todo Go over this method. Pretty sure this can be cleaned up a fair bit
          *
          * @api
          */
          function insert($options){
               //$a = $this->args;
              if(!isset($options['columns'])){
                $arguments = $this->generateArguments();
              } else {
                  $arguments = $options;
              }

               //ensures that if the primary key is auto-numbering, no value will be sent
               foreach($arguments['columns'] as $index=>$key){
                   $meta = $this->schema->getMeta($arguments['table'], $key);
                   
                    if(array_key_exists("auto",$meta)){
                         unset($arguments['columns'][$index]);
                    }

                    if(isset($arguments['columns'][$index]))
                    {
                        if(isset($options['values'][$key])){
                            // Strip out default values
                            if($options['values'][$key] === $meta['default']){
                                unset($arguments['columns'][$index]);
                            }
                        }
                        else {
                            unset($arguments['columns'][$index]);
                        }
                    }
               }
               //resets array indexes for columns in arguments
               $arguments['columns'] = array_values($arguments['columns']);
               $meta = null;
              //if no values supplied, uses stored information for values
               if(!isset($options['values'])){
                    $arguments['values']=[];
                    foreach($this->columns as $column=>$value){
                        $meta = $this->schema->getMeta($arguments['table'], $column);
                         if(!array_key_exists("auto",$meta[$column])){
                             if($arguments['values'][$column] !== $meta[$column]['default']){
                                $arguments['values'][$column]=$value;
                             }
                         }
                    }
               }
              //overrides the arguments, adds in extra info not stored
               foreach($options as $option=>$setting){
                    $arguments[$option]=$setting;
                    
               }

               return(parent::INSERT($arguments)); //returns result of PDOI->insert
          }

         /**
          * Sets a column to a value
          *
          * @param string $col The columnName to set
          * @param mixed $val The value to assign to the columnName
          *
          * @api
          */
          function setCol($col,$val){
               //Validate?
               $this->columns[$col]=$val;
          }

         /**
          * Gets a column value
          *
          * @param string $col The columnName to get
          * @return mixed the value of the column
          *
          * @api
          */
          function getCol($col){
               return($this->columns[$col]);
          }

         /**
          * Updates data in the wrapped relationships
          *
          * Generates the relationship arguments for the sqlSpinner to use when it generates the UPDATE query
          *
          * @param array $options Dictionary of options. See PDOI::UPDATE for more information on available options
          * @return bool success
          *
          * @api
          */
          function update($options){
               $arguments = $this->generateArguments();
               
                //ensures each auto_numbering primary key is not 'updated' to prevent errors
               if(is_array($arguments['table'])){
                    foreach($arguments['table'] as $tableIndex=>$tableData){
                        foreach($tableData as $tableName=>$columns){
                            foreach($columns as $columnIndex=>$column){
                                if(array_key_exists("primaryKey",$this->schema->getMeta($tableName, $column))){
                                   unset($arguments['table'][$tableIndex][$tableName][$columnIndex]);
                                }
                            }
                        }
                    }
               } elseif(is_string($arguments['table'])){
                   $tableName = $arguments['table'];
                   $colCount = count($arguments['columns']);
                   for($i=0;$i<$colCount;$i++){
                       if(array_key_exists("primaryKey",$this->schema->getMeta($tableName, $arguments['columns'][$i]))){
                            unset($arguments['columns'][$i]);
                        }
                   }
                   $arguments['columns'] = array_values($arguments['columns']);
               }

               //override stored arguments
               foreach($options as $option=>$setting){
                    $arguments[$option]=$setting;
               }
               
               return(parent::UPDATE($arguments)); //return PDOI->update result
          }

         /**
          * Deletes data from the wrapped relationships
          *
          * Uses the wrapped relationships to generate arguments for sqlSpinner to delete data.
          *
          * @param array $options See PDOI::DELETE for more information on what options are available
          * @return bool success
          *
          * @api
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

         /**
          * Drop the wrapped tables from the database
          *
          * @return bool success
          *
          * @api
          */
          function drop(){
              if(is_array($this->tableName)){
                  foreach($this->tableName as $table){
                      parent::DROP($table);
                  }
              } else if(is_string($this->tableName)){
                  parent::DROP($this->tableName);
              }
          }

         /**
          * resets the columns to their default values
          *
          * @api
          */
          function reset(){
               foreach($this->schema as $table=>$columns){
                    $cols = array_keys($columns);

                    foreach($cols as $col){
                         $this->columns[$col] = $this->schema->getMeta($table,$col)['default'];
                    }
               }
          }

         /**
          * echo the current dynamo
          */
          function display(){
               echo($this->asDynamo());
          }

         /**
          * Use a given schema
          *
          * When a dynamo needs to interact with its pdoITable, the pdoITable's schema may have changed.
          *
          * @param schema $schema The schema to use
          * @todo Error Catching, also is this necessary since dynamo's hold onto their origin schema now?
          *
          * @internal
          */
         function setSchema($schema){
             if( is_a($schema, "PDOI\Utils\Schema" )){
                 $this->schema = $schema;
             }
         }

         /**
          * Create a Dynamo based off the current wrapped table relationship schema
          *
          * Creates a dynamo and gives the dynamo access to the pdoITable's insert, update, delete and select methods
          *
          * @uses PDOI\Utils\dynamo
          * @uses PDOI\Utils\schema
          *
          * @return dynamo
          *
          * @api
          */
          function asDynamo(){

               //dynamo insert function, uses this pdoITable
               $dynamo = new dynamo($this->columns, $this->columnMeta);
               $this->reset();
              // Give the object a reference to the table schema.
              // The table schema may have relationships added or removed by the time we go into the database
                $dynamo->TableSchema = $this->getSchema();
                $pdoITable = $this;

              /** @method PDOI\Utils\dynamo::insert
               * Gives the dynamo access to inserting itself into the database
               */
                $dynamo->insert = function() use(&$pdoITable){
                    $pdoITable->insertDynamo($this);
                };

              /** @method PDOI\Utils\dynamo::load
               * Gives the dynamo access to filling itself with data from the database
               */
                $dynamo->load = function($pKey = null) use(&$pdoITable){
                    $pdoITable->loadDynamo($this, $pKey);
                };
              /** @method PDOI\Utils\dynamo::update
               * Gives the dynamo access to updating it's data in the database
               */
                $dynamo->update= function() use(&$pdoITable){
                    $args = [];
                    $args['set'] = [];
                    $args['where'] = [];
                    //t has schema information
                    //use primary keys to create where
                    //compare current values against defaults before adding to 'set'

                    $schema = $pdoITable->getSchema();
                    $primaryKeys = $schema->getPrimaryKeys();
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

                    foreach($primaryKeys as $table=>$column){
                        if($tCount > 1){
                            $args['where'][$table] = [$column=>$this->$column];
                        }
                        else {
                            $args['where'][$column]=$this->$column;
                        }

                    }

                    $args['limit']=1;

                    return $pdoITable->update($args);
                };

              /** @var dynamo->load Gives the dynamo access to remove itself from the database */
                $dynamo->delete = function() use(&$pdoITable){
                    $args = [];
                    $schema = $pdoITable->getSchema();
                    $fkeys = $schema->getForeignKeys();
                    if(is_array($fkeys)){
                        foreach($fkeys as $tableName=>$relationships){
                                foreach($relationships as $index=>$relationship){
                                    foreach($relationship as $pcolumn=>$fk){
                                        foreach($fk as $ftable=>$fcolumn){
                                            $args = ['table'=>$ftable,
                                                'where'=>[$fcolumn=>$this->$fcolumn]];
                                            $pdoITable->delete($args);
                                        }
                                    }
                                }
                        }
                        $mk = $schema->getMasterKey();
                        foreach($mk as $table=>$column){
                            $args = ['where'=>[$column=>$this->$column]];
                            return($pdoITable->delete($args));
                        }
                    } else {
                        foreach($this as $key=>$value){
                             if(array_key_exists('fixed',$this->getRule($key))){
                                  $args['where'] = [$key=>$value];
                             }
                        }
                        return $pdoITable->delete($args);
                    }
                };
               
               return($dynamo); //returns the dynamo with access to the parent table
          }

         /**
          * Insert a dynamo into the database
          *
          * Using the schema from the dynamo, insert it's data into the database
          * @param dynamo $dynamo
          *
          * @api
          */
         function insertDynamo($dynamo)
         {
             $args = [];
             $args['values'] = [];

             // The pdoITable schema may have changed after the dynamo was spawned. Use the schema which was assigned to
             // the dynamo at its creation
             $schema = $dynamo->TableSchema;
             $foreignKeys = array_reverse($schema->getForeignKeys());

             if ($this->debug) {
                 print_r($foreignKeys);
             }
             if ($foreignKeys) {
                 foreach ($foreignKeys as $tableName => $relationships) {
                     foreach ($relationships as $index => $relationship) {
                         foreach ($relationship as $primaryColumn => $foreignKey) {
                             foreach ($foreignKey as $foreignTable => $foreignColumn) {
                                 $foreignCols = $schema->getColumns($foreignTable);
                                 $values = [];

                                 foreach ($foreignCols as $column) {
                                     $columnMeta = $schema->getMeta($foreignTable, $column);
                                     if (!array_key_exists('primaryKey', $columnMeta) && !array_key_exists('auto', $columnMeta)) {
                                         if (isset($dynamo->$column)) {
                                             $values[$column] = $dynamo->$column;
                                         }
                                     } else {
                                         if (($key = array_search($column, $foreignCols)) !== false) {
                                             unset($foreignCols[$key]);
                                             $foreignCols = array_values($foreignCols);
                                         }
                                     }
                                 }
                                 if ($this->debug) {
                                     var_dump($foreignTable);
                                     var_dump($foreignCols);
                                     var_dump($values);
                                 }

                                 // Insert foreign table data
                                 $this->insert(['table' => $foreignTable, 'columns' => $foreignCols, 'values' => $values]);

                                 $primaryKey = $schema->getPrimaryKey($foreignTable);

                                 $selectOptions = ['where' => $values,
                                     'columns' => [$primaryKey],
                                     'table' => $foreignTable,
                                     'orderby' => [$primaryKey => 'DESC'],
                                     'limit' => 1
                                 ];

                                 $row = $this->select($selectOptions);

                                 $this->$foreignColumn = $row->$foreignColumn;
                             }
                         }
                     }
                 }


                 $masterKey = $schema->getMasterKey();
                 //get master table
                 foreach ($masterKey as $masterTable => $primaryKey) {
                     //get columns from master table
                     //unset primary key from columns
                     $cols = $schema->getColumns($masterTable);
                     if (($key = array_search($primaryKey, $cols)) !== false) {
                         unset($cols[$key]);
                         $cols = array_values($cols);
                     }
                     $values = [];
                     //get master table column values from $this
                     foreach ($cols as $col) {
                         $values[$col] = $dynamo->$col;
                     }
                     $this->insert(['table' => $masterTable, 'columns' => $cols, 'values' => $values]);

                     $select = ['where' => $values,
                         'columns' => [$primaryKey],
                         'table' => $masterTable,
                         'orderby' => [$primaryKey => 'DESC'],
                         'limit' => 1
                     ];
                     //run an insert on the master
                     $row = $this->select($select);
                     //get the user_id and return it
                     $dynamo->$primaryKey = $row->$primaryKey;
                 }

                 if ($this->debug) echo $dynamo;

             }
         }

         /**
          * Using the schema of a given dynamo, fill it with it's related data
          *
          * @param dynamo $dynamo The dynamo to fill with data
          * @param mixed $pKey The primary key to load from
          * @api
          */
         function loadDynamo(&$dynamo, $pKey = null){
             // set the pdoITable schema to the dynamo schema
             $oldSchema = $this->getSchema();
             $this->setSchema($dynamo->TableSchema);

             // run a select off the table using the provided pKey

             //this function takes the pKey provided and prepares a properly formatted select call based off the current schema using the pkey as the where value
             $args = [];
             $args['limit']=1;
             $schema = $this->getSchema();
             $mk = $schema->getMasterKey();
             foreach($mk as $table=>$key){
                 if($pKey === null){
                     $pKey = $this->$key;
                 }
                 if(count($schema->getTables())===1){
                     $args['where'] = [$key=>$pKey];
                 } else {
                     $args['where'] = [$table=>[$key=>$pKey]];
                 }
             }

             // assign the return dynamo values to this
             $dynamo->stopValidation();
             // pdo fetch_into should be assigning the values to 'this'. We shouldn't need to copy the values out of the return into this
             $newMe = $this->select($args,$this);
             foreach($newMe as $key=>$val){
                 $this->$key = $val;
             }

             $dynamo->startValidation();

             // Return pdoITable to its original schema
             $this->setSchema($oldSchema);
         }

         /**
          * Create a relationship in the schema
          *
          * Set's up relationship data in the schema
          *
          * @param array $relationships The relationship to create [tableName.foreignKey => foreignTableName.primaryKey]
          * @param bool $values
          * @api
          */
          function setRelationship($relationships, $values = false){
               foreach($relationships as $fKey=>$pKey){
                   //add tables w/columns to schema
                   $this->schema->addTable([explode(".",$pKey)[0]]);
                   $this->setColumns();
                   $this->schema->setForeignKey([$fKey=>$pKey]);
               }
          }

         /**
          * Terminates a relationship
          *
          * Destroys the relationship data which the tables are based on. If given an object which was mapped to that data,
          *     it will remove that data from the object.
          *
          * @param array $tables List of table names
          * @param dynamo|null $entity A class which has been mapped to the data that the relationship was based on.
          * @api
          */
          function endRelationship($tables=[], &$entity = null){
              //properly end the fkey=>pkey relationships provided (or all relationships) and 
              //remove the table(s) information from the schema. Be sure to leave the original schema unaffected.
                if(empty($tables)){
                    $tables = $this->schema->getTables();
                }
                $masterTable = array_keys($this->schema->getMasterKey())[0];

                if(is_object($entity)){
                    $entity=[$entity];
                }
                foreach($tables as $table){
                    if($table !== $masterTable){
                        if(is_array($entity)){
                            $cols = $this->schema->getColumns($table);
                            foreach($entity as $ent){
                                foreach($cols as $col){
                                    unset($ent->$col);
                                }
                            }
                        }
                        unset($this->schema->$table);
                    }
                }
                
                if(count($entity)===1){
                    $entity = $entity[0];
                }
          }

         /**
          * Retrieve the current working schema
          *
          * @return schema
          * @api
          */
          function getSchema(){
              return $this->schema;
          }
     }
?>