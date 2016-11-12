<?php
 namespace Peas\EntityGeneration;
 use Peas\DataIntegrity\Schema as schema;

 /**
  * @author Steven Chennault schenn@mash.is
  * @link: https://github.com/Schenn/EmitterDatabaseHandler Repository
  */

 /**
  * Class EntityEmitter
  *
  * EntityEmitter acts as a wrapper over a relationship of tables. The relationship could be of a single table or more.
  * It acts as a facilitator for retrieving, updating or removing data from the wrapped tables. When you create and
  * assign tables to the EntityEmitter, it loads the metadata about those tables into a Schema. If you request data from
  * a EntityEmitter and don't provide an object, it will use an entity to hold onto the data.
  *
  * EntityEmitter can create entity's upon request. The entities will have the same structure as the table relationship
  * Schema that has been created for EntityEmitter. The entities are given the capacity to save themselves when they are
  * created by a EntityEmitter.
  *
  * @uses EntityGenerator\Utils\Schema
  * @uses EntityGenerator\Utils\Entity
  *
  * @package EmitterDatabaseHandler
  * @todo Should we be holding on to args or can that be removed?
  * @todo Emitter should have a EDH, not extend from it
  */

 class EntityEmitter extends EmitterDatabaseHandler {
     /** @var string|array $tableName name or names of the table(s) this EntityEmitter is currently working with */
      protected $tableName;
     /** @var array $columns The columns for the tables. [columnName =>value, columnName=>value, ..] */
      protected $columns=[];
     /** @var array $columnMeta The column meta data for the columns */
      protected $columnMeta=[];
     /** @var Schema $Schema The Schema object */
      protected $schema;
     /** @var array arguments Used to generate the SQL queries through SqlBuilder */
      protected $args = [];

     /**
      * Create a new EntityEmitter
      *
      * @param array $config Dictionary of database configuration data
      * @param string|array $tables The tableName(s) to assign to the EntityEmitter
      * @param bool $debug Whether or not to log debug information
      *
      * @throws \Exception
      */
      function __construct($config, $tables, $debug=false){
           parent::__construct($config, $debug);
           $this->schema = new Schema();
           $this->setTable($tables);
      }


     /**
      * Sets the table(s) for the EntityEmitter.
      *
      * Runs setColumns after the tables are set. Sets the table argument.
      *
      * @see EntityEmitter::setColumns
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
           // Set up the Schema
      }

     /**
      * Generate the relationship arguments for the SqlBuilder
      *
      * Prepare the relationship arguments for the SqlBuilder. Such as table, columns, join and on arguments
      *
      * @see PDOI\Utils\sqlSpinner
      *
      * @return array The table relationships as a dictionary the SqlBuilder can parse.
      * @todo This could be moved to the Schema class as all of its data comes from the Schema
      *
      * @internal
      */
     public function generateArguments(){
         //uses Schema information to generate argument list
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
      * Creates a new entity and passes it and the relationship information as a dictionary to EmitterDatabaseHandler::Select
      *
      * @uses PDOI::SELECT
      *
      * @param array $options see PDO::SELECT for more information on options
      * @param null|Entity $entity The object to assign the data from the query to. Passed by reference so the
      *     object will be mutated without having to do something with the return value
      *
      * @return array|entity|bool|null
      *
      * @api
      */
      function select($options=[], $entity = null){
          //if no object supplied to take values from select query, use entity
          $isEntity = false;
          if($entity == null){
              $isEntity = true;
              $entity = $this->EmitEntity();
              $entity->stopValidation();
          }
           $entity = ($entity !== null) ? $entity : $this->EmitEntity();
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
        print_r($a);
          $res = parent::SELECT($a, $entity);
          if($isEntity && $res !== null){
              if(is_array($res)){
                  foreach($res as $ent){
                      $ent->startValidation();
                  }
              } else {
                  $res->startValidation();
              }

          }
           return($res); //return EmitterDatabaseHandler select result
      }

     /**
      * Get all records from the wrapped table relationships
      *
      * Constructs the arguments to select all records and uses a entity to hold the data
      *
      * @uses PDOI\Utils\Entity
      * @return array|bool|null
      *
      * @api
      */
      function selectAll(){
           $entity = $this->EmitEntity();
           $arguments = $this->generateArguments();
           return(parent::SELECT($arguments, $entity));
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
      * @return bool Result of insertion
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

           return(parent::INSERT($arguments)); //returns result of EmitterDatabaseHandler->insert
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
      * Generates the relationship arguments for the SqlBuilder to use when it generates the UPDATE query
      *
      * @param array $options Dictionary of options. See EmitterDatabaseHandler::UPDATE for more information on available options
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

           return(parent::UPDATE($arguments)); //return EmitterDatabaseHandler->update result
      }

     /**
      * Deletes data from the wrapped relationships
      *
      * Uses the wrapped relationships to generate arguments for SqlBuilder to delete data.
      *
      * @param array $options See EmitterDatabaseHandler::DELETE for more information on what options are available
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
      function destroy(){
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
      * print an Entity
      */
      function display(){
           echo($this->EmitEntity());
      }

     /**
      * Use a given Schema
      *
      * When a entity needs to interact with its EntityEmitter, the EntityEmitter's Schema may have changed.
      *
      * @param Schema $schema The Schema to use
      * @todo Error Catching, also is this necessary since Entity's hold onto their origin Schema now?
      *
      * @internal
      */
     function setSchema($schema){
         if( is_a($schema, "PDOI\Utils\Schema")){
             $this->schema = $schema;
         }
     }

     /**
      * Create a Entity based off the current wrapped table relationship Schema
      *
      * Creates a Entity and gives the Entity access to the EntityEmitter's insert, update, delete and select methods
      *
      * @uses PDOI\Utils\Entity
      * @uses PDOI\Utils\schema
      *
      * @return Entity
      *
      * @api
      */
      function EmitEntity($failSoft = true){

           //Entity insert function, uses this EntityEmitter
           $entity = new Entity($this->columns, $this->columnMeta, $failSoft);
           $this->reset();
          // Give the object a reference to the table Schema.
          // The table Schema may have relationships added or removed by the time we go into the database
            $entity->TableSchema = $this->getSchema();
            $entityEmitter = $this;

          /** @method EmitterDatabaseHandler\Utils\Entity::insert
           * Gives the Entity access to inserting itself into the database
           */
            $entity->insert = function() use(&$entityEmitter){
                $entityEmitter->insertEntity($this);
            };

          /** @method EmitterDatabaseHandler\Utils\Entity::load
           * Gives the Entity access to filling itself with data from the database
           */
            $entity->load = function($pKey = null) use(&$entityEmitter){
                $entityEmitter->loadEntity($this, $pKey);
            };
          /** @method EmitterDatabaseHandler\Utils\Entity::update
           * Gives the Entity access to updating it's data in the database
           */
            $entity->update= function() use(&$entityEmitter){
                $args = [];
                $args['set'] = [];
                $args['where'] = [];
                //t has Schema information
                //use primary keys to create where
                //compare current values against defaults before adding to 'set'

                $schema = $entityEmitter->getSchema();
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

                return $entityEmitter->update($args);
            };

          /** @var entity->load Gives the Entity access to remove itself from the database */
            $entity->delete = function() use(&$entityEmitter){
                $args = [];
                $schema = $entityEmitter->getSchema();
                $foreignKeys = $schema->getForeignKeys();
                if(is_array($foreignKeys)){
                    foreach($foreignKeys as $tableName=>$relationships){
                            foreach($relationships as $index=>$relationship){
                                foreach($relationship as $primaryColumn=>$fk){
                                    foreach($fk as $foreignTable=>$foreignColumn){
                                        $args = ['table'=>$foreignTable,
                                            'where'=>[$foreignColumn=>$this->$foreignColumn]];
                                        $entityEmitter->delete($args);
                                    }
                                }
                            }
                    }
                    $mk = $schema->getMasterKey();
                    foreach($mk as $table=>$column){
                        $args = ['where'=>[$column=>$this->$column]];
                        return($entityEmitter->delete($args));
                    }
                } else {
                    foreach($this as $key=>$value){
                         if(array_key_exists('fixed',$this->getRule($key))){
                              $args['where'] = [$key=>$value];
                         }
                    }
                    return $entityEmitter->delete($args);
                }
            };

           return($entity); //returns the Entity with access to the parent table
      }

     /**
      * Insert a Entity into the database
      *
      * Using the Schema from the entity, insert it's data into the database
      * @param entity $entity
      *
      * @api
      */
     function insertEntity(&$entity)
     {
         $args = [];
         $args['values'] = [];

         // The EntityEmitter Schema may have changed after the entity was spawned. Use the Schema which was assigned to
         // the entity at its creation
         $schema = $entity->TableSchema;

         $foreignKeys = array_reverse($schema->getForeignKeys());

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
                                     if (isset($entity->$column)) {
                                         $values[$column] = $entity->$column;
                                     }
                                 } else {
                                     if (($key = array_search($column, $foreignCols)) !== false) {
                                         unset($foreignCols[$key]);
                                         $foreignCols = array_values($foreignCols);
                                     }
                                 }
                             }

                             // Insert foreign table data
                             $id = $this->insert(['table' => $foreignTable, 'columns' => $foreignCols, 'values' => $values]);

                             $entity->stopValidation();
                             $entity->$foreignColumn = $id;
                             $entity->startValidation();
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
                     $values[$col] = $entity->$col;
                 }
                 $id = $this->insert(['table' => $masterTable, 'columns' => $cols, 'values' => $values]);

                 //get the user_id and return it
                 $entity->stopValidation();
                 $entity->$primaryKey = $id;
                 $entity->startValidation();
             }
             if ($this->debug) echo $entity;

         }
     }

     /**
      * Using the Schema of a given entity, fill it with it's related data
      *
      * @param Entity entity The entity to fill with data
      * @param mixed $pKey The primary key to load from
      * @api
      */
     function loadEntity(&$entity, $pKey = null){
         // set the EntityEmitter Schema to the Entity Schema
         $oldSchema = $this->getSchema();
         $this->setSchema($entity->TableSchema);

         // run a select off the table using the provided pKey

         //this function takes the pKey provided and prepares a properly formatted select call based off the current Schema using the pkey as the where value
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

         // assign the return entity values to this
         $entity->stopValidation();
         // pdo fetch_into should be assigning the values to 'this'. We shouldn't need to copy the values out of the return into this
         $newMe = $this->select($args,$this);
         foreach($newMe as $key=>$val){
             $this->$key = $val;
         }

         $entity->startValidation();

         // Return EntityEmitter to its original Schema
         $this->setSchema($oldSchema);
     }

     /**
      * Create a relationship in the Schema
      *
      * Set's up relationship data in the Schema
      *
      * @param array $relationships The relationship to create [tableName.foreignKey => foreignTableName.primaryKey]
      * @api
      *
      * @todo Accept an array of FQNs of components. Those components will be attached to the entity and filled with the entity's data for the corresponding table.
      */
      function setRelationship($relationships){
           foreach($relationships as $fKey=>$pKey){
               //add tables w/columns to Schema
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
      * @param entity|null $entity A class which has been mapped to the data that the relationship was based on.
      * @api
      */
      function endRelationship($tables=[], &$entity = null){
          //properly end the fkey=>pkey relationships provided (or all relationships) and
          //remove the table(s) information from the Schema. Be sure to leave the original Schema unaffected.
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
      * Retrieve the current working Schema
      *
      * @return Schema
      * @api
      */
      function getSchema(){
          return $this->schema;
      }
 }
?>