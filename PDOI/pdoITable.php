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
          protected $schema;
          protected $args = [];


          public function generateArguments(){
               //uses schema information to generate argument list
               $a = [];
               //if one table

               $tables = $this->schema->getTables();

               if(count($tables)===1){
                    $a['table'] = $tables[0];
                    $a['columns'] = $this->schema->getColumns($tables[0]);
                    
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

                    }
               }
               }
               // Set up the schema
          }

          /* Name: select
           * Description:  Runs a select query on the table
           * Takes: options = [] (associative array of options.  Overrides currently stored arguments for the query)
           */
          function select($options=[], $entity = null){ 
              //if no object supplied to take values from select query, use dynamo
               $entity = ($entity !== null ? $entity : $this->Offshoot()); 
               if(array_key_exists('table', $options) && array_key_exists('columns', $options)) {
                   $a = $options;
               } else {
                    $a = $this->generateArguments();
                    foreach($options as $option=>$setting){ //supplied options override stored arguments
                         $a[$option]=$setting;
                    }
                    if(count($a['table'])==1){
                        unset($a['join']);
                        unset($a['on']);
                    }
               }
               return(parent::SELECT($a, $entity)); //return PDOI select result
          }

          function selectAll(){
               $entity = $this->Offshoot();
               $a = $this->generateArguments();
               return(parent::SELECT($a, $entity));
          }

          /* Name: insert
           * Description:  Runs an insert query into the table
           * Takes: options = [] (associative array of options, overrides currently stored arguments for the query)
           */
          function insert($options){
               //$a = $this->args;
              if(!isset($options['columns'])){
                $a = $this->generateArguments();
              } else {
                  $a = $options;
              }

               //ensures that if the primary key is auto-numbering, no value will be sent
               foreach($a['columns'] as $index=>$key){
                   $meta = $this->schema->getMeta($a['table'], $key);
                   
                    if(array_key_exists("auto",$meta)){
                         unset($a['columns'][$index]);
                    }
                    
                    if(isset($a['columns'][$index]))
                    {
                        if(isset($options['values'][$key])){
                            if($options['values'][$key] === $meta['default']){
                                unset($a['columns'][$index]);
                            }
                        }
                        else {
                            unset($a['columns'][$index]);
                        }
                    }
               }
               //resets array indexes for columns in arguments
               $a['columns'] = array_values($a['columns']);
               $meta = null;
               if(!isset($options['values'])){ //if no values supplied, uses stored information for values
                    $a['values']=[];
                    foreach($this->columns as $column=>$value){
                        $meta = $this->schema->getMeta($a['table'], $column);
                         if(!array_key_exists("auto",$meta[$column])){
                             if($a['values'][$column] !== $meta[$column]['default']){
                                $a['values'][$column]=$value;
                             }
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
                   $colCount = count($a['columns']);
                   for($i=0;$i<$colCount;$i++){
                       if(array_key_exists("primaryKey",$this->schema->getMeta($tableName, $a['columns'][$i]))){
                            unset($a['columns'][$i]);
                        }
                   }
                   $a['columns'] = array_values($a['columns']);
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
          
          function drop(){
              if(is_array($this->tableName)){
                  foreach($this->tableName as $table){
                      parent::DROP($table);
                  }
              } else if(is_string($this->tableName)){
                  parent::DROP($this->tableName);
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
           * @return dynamo
           */
          function Offshoot(){

               //dynamo insert function, uses this pdoITable
               $e = new dynamo($this->columns, $this->columnMeta);
               $this->reset();
               
               $t = $this;
               $e->insert = function() use($t){
                    $args = [];
                    $args['values'] = [];

                    $schema = $t->getSchema();
                    $fkeys = array_reverse($schema->getForeignKeys());
                    
                    if($t->debug){     
                        print_r($fkeys);
                    }
                    if($fkeys){
                        foreach($fkeys as $tableName=>$relationships){
                            foreach($relationships as $index=>$relationship){
                                foreach($relationship as $pcolumn=>$fk){
                                    foreach($fk as $ftable=>$fcolumn){
                                        $fcols = $schema->getColumns($ftable);
                                        $values = [];
                                        
                                        foreach($fcols as $column){
                                            $cmeta = $schema->getMeta($ftable, $column);
                                            if(!array_key_exists('primaryKey', $cmeta) && !array_key_exists('auto', $cmeta)){
                                                if(isset($this->$column)){
                                                    $values[$column]=$this->$column;
                                                }
                                            } else {
                                                if(($key = array_search($column, $fcols)) !== false) {
                                                   unset($fcols[$key]);  
                                                   $fcols = array_values($fcols);
                                                }
                                            }
                                        }
                                        var_dump($ftable);
                                        var_dump($fcols);
                                        var_dump($values);
                                        
                                        $t->insert(['table'=>$ftable,'columns'=>$fcols,'values'=>$values]);

                                        $pk= $schema->getPrimaryKey($ftable);
                                        
                                        $selectopts = ['where'=>$values, 
                                            'columns'=>[$pk],
                                            'table'=>$ftable, 
                                            'orderby'=>[$pk=>'DESC'],
                                            'limit'=>1
                                        ];
                                        
                                        $row = $t->select($selectopts);
                                        
                                        $this->$fcolumn = $row->$fcolumn;
                                    }
                                }
                            }
                        }
                        
                        
                        $mk = $schema->getMasterKey();
                        //get master table
                        foreach($mk as $mt=>$pk){
                            //get columns from master table
                            //unset primary key from columns
                            $cols = $schema->getColumns($mt);
                            if (($key = array_search($pk, $cols)) !== false) {
                                unset($cols[$key]); 
                                $cols = array_values($cols);  
                            }
                            $vals = [];
                            //get master table column values from $this
                            foreach($cols as $col){
                                $vals[$col] = $this->$col;
                            }
                            $t->insert(['table'=>$mt, 'columns'=>$cols, 'values'=>$vals]);
                            
                            $select = ['where'=>$vals,
                                'columns'=>[$pk],
                                'table'=>$mt,
                                'orderby'=>[$pk=>'DESC'],
                                'limit'=>1
                            ];
                            //run an insert on the master
                            $row = $t->select($select);
                            //get the user_id and return it
                            $this->$pk = $row->$pk;
                        }
                        
                        if($t->debug) echo $this;

                    }
                    else {
                    /*
                    foreach($schema as $tableName=>$columns){
                        $args['table'] = $tableName;
                        foreach($columns as $columnName=>$columnData){
                            if(!array_key_exists('fixed', $columnData)){
                                if(isset($this->$columnName)){
                                    if($this->$columnName != $columnData['default'] && $this->$columnName !== null){
                                        $args['values'][$columnName] = $this->$columnName;
                                    }
                                }
                            }
                        }

                        if(!$t->insert($args)){
                            return false;
                            break;
                        }
                        $args['values'] = [];
                    }
                    */
                    }
                    return true;
                };
            
                $e->load = function($pkey = null) use($t){
                        //this function takes the pKey provided and prepares a properly formatted select call based off the current schema using the pkey as the where value
                       $args = [];
                       $args['limit']=1;
                       $schema = $t->getSchema();
                       $mk = $schema->getMasterKey();
                       $field = "";
                       foreach($mk as $table=>$key){
                            if($pKey === null){
                                $pKey = $this->$key;
                            }
                            if(count($schema->getTables())===1){
                                $args['where'] = [$key=>$pKey];
                            } else {
                                $args['where'] = [$table=>[$key=>$pKey]];
                            }
                            $field = $key;
                       }

                       $this->stopValidation();
                       $newMe = $this->t->select($args,$this);
                       foreach($newMe as $key=>$val){
                           $this->$key = $val;
                       }
                       
                       $this->startValidation();

                       return($this);
                };

                $e->update= function() use($t){
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
                

                $e->delete = function() use($t){
                    $args = [];
                    $schema = $t->getSchema();
                    $fkeys = $schema->getForeignKeys();
                    if(is_array($fkeys)){
                        foreach($fkeys as $tableName=>$relationships){
                                foreach($relationships as $index=>$relationship){
                                    foreach($relationship as $pcolumn=>$fk){
                                        foreach($fk as $ftable=>$fcolumn){
                                            $args = ['table'=>$ftable,
                                                'where'=>[$fcolumn=>$this->$fcolumn]];
                                            $t->delete($args);
                                        }
                                    }
                                }
                        }
                        $mk = $schema->getMasterKey();
                        foreach($mk as $table=>$column){
                            $args = ['where'=>[$column=>$this->$column]];
                            return($t->delete($args));
                        }
                    } else {
                        foreach($this as $key=>$value){
                             if(array_key_exists('fixed',$this->getRule($key))){
                                  $args['where'] = [$key=>$value];
                             }
                        }
                        return $t->delete($args);
                    }
                };
               
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
          
          
          function saltAndPepper($password) {
              $salt = "";
                for($i=0; $i<17; $i++){
                    $rnd = rand(0,11);
                    $chrs= ['a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z'];
                    if($rnd <= 3){
                        $cdig = rand(0,9);
                        $c = $cdig;
                    } elseif($rnd > 3 && $rnd <= 7){
                        $cdig = rand(0,25);
                        $c = $chrs[$cdig];
                    } else {
                        $cdig = rand(0,25);
                        $c = ucfirst($chrs[$cdig]);
                    }
                    $salt .= $c;
                }
                $newsalt = hash('sha256', $salt);
                $hash = hash('sha256', $password.$newsalt);
                $max = rand(10, 16785);
                for ($i=0; $i<$max; $i++){
                   $hash = hash('sha256', $hash . $newsalt);
                }
                return(['salt'=>$newsalt,'rounds'=>$max,'hash'=>$hash]);
          }
          
          function checkPassword($pass, $hash, $salt, $rounds){
              $hashcheck = hash('sha256', $pass.$salt);
              for ($i=0; $i<$rounds; $i++){
                  $hashcheck = hash('sha256', $hashcheck.$salt);
              }
              return($hashcheck === $hash);
              
          }
          
          function getSchema(){
              return $this->schema;
          }
          
          
     }

     
     class userTable {
         protected $config;
         protected $debug;
         protected $conn;
         
         public function __construct($config, $debug){
             $this->config = $config;
             $this->debug = $debug;
            
         }
         
         public function init(){
            $this->conn = new PDOI($this->config, $this->debug);
            
            echo $this->conn->create('users',['user_id'=>[], 
                               'username'=>['type'=>'varchar','length'=>50],
                               'hash_id'=>['type'=>'int']]);
            $this->conn->create('hashwords',['hash_id'=>[],
                               'hash'=>['type'=>'varchar','length'=>350],
                               'salt_id'=>['type'=>'int']]);
            $this->conn->create('salts',['salt_id'=>[],
                               'salt'=>['type'=>'varchar','length'=>350],
                               'round_id'=>['type'=>'int']]);
            $this->conn->create('rounds',['round_id'=>[],
                               'rounds'=>['type'=>'int']]);
            return true;
         }
         
         public function createUser($user, $pass){
             $this->conn = new pdoITable($this->config, 'users', $this->debug);
             $userExists = ['where'=>[
                 'username'=>$user
             ], 'limit'=>1];
             if(!$this->conn->select($userExists)) {
                $this->conn->setRelationship([
                    'users.hash_id'=>'hashwords.hash_id',
                    'hashwords.salt_id'=>'salts.salt_id',
                    'salts.round_id'=>'rounds.round_id']);

                $newuser = $this->conn->Offshoot();
                $hash = $this->conn->saltAndPepper($pass);

                $newuser->username = $user;
                $newuser->hash = $hash['hash'];
                $newuser->salt = $hash['salt'];
                $newuser->rounds = $hash['rounds'];

                $newuser->insert();
                return($newuser);
             } else {
                 return false;
             }
         }
         
         public function deleteUser($username){
             $this->conn = new pdoITable($this->config, 'users', $this->debug);
             $this->conn->setRelationship([
                    'users.hash_id'=>'hashwords.hash_id',
                    'hashwords.salt_id'=>'salts.salt_id',
                    'salts.round_id'=>'rounds.round_id']);
             $userExists = ['where'=>['users'=>['username'=>$username]
             ], 'limit'=>1];
             if($user = $this->conn->select($userExists)) {
                 $user->delete();
                 return true;
             } else {
                 return false;
             }
         }
         
         public function login($username, $pass){
             $this->conn = new pdoITable($this->config, 'users', $this->debug);
             $this->conn->setRelationship([
                    'users.hash_id'=>'hashwords.hash_id',
                    'hashwords.salt_id'=>'salts.salt_id',
                    'salts.round_id'=>'rounds.round_id']);
             $userExists = ['where'=>['users'=>['username'=>$username]
                ], 'limit'=>1];
             if($user = $this->conn->select($userExists)) {               
                 if($this->conn->checkPassword($pass, $user->hash, $user->salt, $user->rounds)) {
                     unset ($user->hash);
                     unset ($user->salt);
                     unset ($user->rounds);
                    return $user;
                 } else {
                    return false;
                 }
             } else {
                 return false;
             }
         }
         
     }
?>