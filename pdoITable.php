<?php
     namespace PDOI;
     require_once("PDOI.php");
     require_once("Utils/dynamo.php");
     use PDOI\PDOI as PDOI;
     use PDOI\Utils\dynamo as dynamo;
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

     class pdoITable extends PDOI {
          protected $tableName;
          protected $columns=[];
          protected $columnMeta=[];
          protected $args = [];
          protected $entity;


          /* Name: __construct
           * Description:  Controls new pdoITable creation
           * Takes: config = db configuration information, table = "" (table name), debug = false
           */
          function __construct($config, $table, $debug=false){
               parent::__construct($config, $debug);
               $this->setTable($table);
          }


          /* Name: setTable
           * Description:  sets the tablename, calls setcolumns
           * Takes: table = "" (table name)
           */
          function setTable($table){
               $this->tableName = $table;
               $this->args['table'] = $this->tableName;
               $this->setColumns();
          }

          /* Name: setColumns
           * Description:  Gets table schema from the db.  becomes aware of column names and validation requirements
           */
          function setColumns(){
               $description = parent::describe($this->tableName);
               foreach($description as $row){  //for each column in table
                    $field = $row['Field'];
                    unset($row['Field']);

                    //get field length
                    $sansType = preg_split("/int|decimal|double|float|double|real|bit|bool|serial|date|time|year|char|text|binary|blob|enum|set|geometrycollection|multipolygon|multilinestring|multipoint|polygon|linestring|point|geometry/",strtolower($row['Type']));
                    if(isset($sansType[1])){
                         $sansParens = preg_split("/\(|\)/",$sansType[1]);
                         if(isset($sansParens[1])){
                              $this->columnMeta[$field]['length'] = intval($sansParens[1]);
                         }
                    }

                    //get field data type
                    $type = preg_filter("/\(|\d+|\)/","",strtolower($row['Type']));

                    $this->columnMeta[$field]['type'] = $type;
                    $this->columnMeta[$field]['default'] = $row['Default'];

                    //if its a primary key
                    if($row['Key'] === "PRI"){
                         $this->columnMeta[$field]['primaryKey'] = true;

                         //if its auto_incremented
                         if($row['Extra'] === "auto_increment"){
                              $this->columnMeta[$field]['auto'] = true;
                         }
                    }


                    //set default values to the columns
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
               $this->args['columns']=[]; //sets arguments for select, update, delete and insert commands
               foreach($this->columns as $column=>$n){
                    array_push($this->args['columns'], $column);
               }
               $this->entity = new dynamo($this->columns); //generates new dynamic object to template rows from the table
               $this->entity->setValidationRules($this->columnMeta); //sets validation rules on dynamic object based off validation information

          }

          /* Name: select
           * Description:  Runs a select query on the table
           * Takes: options = [] (associative array of options.  Overrides currently stored arguments for the query)
           */
          function select($options, $entity = null){
               $entity = ($entity !== null ? $entity : clone $this->entity); //if no object supplied to take values from select query, use dynamo
               $a = $this->args;
               foreach($options as $option=>$setting){ //supplied options override stored arguments
                    $a[$option]=$setting;
               }

               return(parent::SELECT($a, $entity)); //return PDOI select result
          }

          function selectAll(){
               $entity = clone $this->entity;
               $a = $this->args;
               return(parent::SELECT($a, $entity));
          }

          /* Name: insert
           * Description:  Runs an insert query into the table
           * Takes: options = [] (associative array of options, overrides currently stored arguments for the query)
           */
          function insert($options){
               $a = $this->args;

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
          }

          /* Name: update
           * Description:  Runs an update query on the table
           * Takes: options = [] (associative array of options, overrides currently stored arguments for the query)
           */
          function update($options){
               $a = $this->args;
               //ensures auto_numbering primary key is not 'updated'
               foreach($a['columns'] as $index=>$key){
                    if(array_key_exists("primaryKey",$this->columnMeta[$key])){
                         $removeIndex = $index;
                    }
               }
               unset($a['columns'][$removeIndex]);
               $a['columns'] = array_values($a['columns']);

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
               $a= $this->args;
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
               foreach($this->columns as $key=>$value){
                    $this->columns[$key] = $this->columnMeta[$key]['default'];
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

                    $t->insert($args);

               };

               //dynamo update function, uses this pdoITable
               $e->update = function() use ($t){
                    $args = [];

                    foreach($this as $key=>$value){
                         if(!array_key_exists('fixed',$this->getRule($key))){
                              $setKey = $key;
                              $args['set'][$setKey]=$value;
                         }
                         else {
                              $whereKey = $key;
                              $args['where'][$whereKey] = $value;
                         }
                    }
                    $args['limit']=1;
                    $t->update($args);
               };

               //dynamo delete function, uses this pdoITable
               $e->delete = function() use ($t){
                    $args = [];
                    foreach($this as $key=>$value){
                         if(array_key_exists('fixed',$this->getRule($key))){
                              $args['where'] = [$key=>$value];
                         }
                    }
                    $t->delete($args);
               };

               $e->spinForm = function($formData) {
                    if(isset($formData[0])){
                         $formData = $formData[0];
                    }
                    $html = "<form method = '".$formData['method']."' action = '".$formData['action']."' ";

                    if(isset($formData['name'])){
                         $html .= 'name = '.$formData["name"];
                    }
                    if(isset($formData['class'])){
                         $html .= 'class = '.$formData["class"];
                    }
                    if(isset($formData['id'])){
                         $html .= 'id = '.$formData["id"];
                    }

                    $html .= " >
                         <table>
                              <th>".$formData['heading']."</th>";

                    foreach($this as $column=>$value){
                         $colRules = $this->getRule($column);
                         $html .="<tr><td>";
                         $html .="<label for=".$column.">".ucfirst($column)."</label></td><td>";
                         if($colRules['type'] === 'string' || $colRules['type'] === 'numeric'){
                              if(isset($colRules['length']) || isset($colRules['max'])){
                                   if($column !== 'password'){
                                        $html .= "<input type='text' ";
                                   }
                                   else {
                                        $html .= "<input type='password' ";
                                   }
                                   $html .= "name=".$column." value = '".$value."' ";
                                   if(isset($colRules['length'])){
                                        $html .= "maxlength=".$colRules['length']." ";
                                   }
                                   if(isset($colRules['max'])){
                                        $html .=" max=".$colRules['max']." ";
                                        if(isset($colRules['min'])){
                                             $html .= "min=".$colRules['min']." ";
                                        }
                                        else {
                                             $html .= "min=".($colRules['max']*-1)." ";
                                        }
                                   }
                                   if(isset($colRules['fixed'])){
                                        $html .= "readonly ";
                                   }
                                   $html .= " />";
                              }
                              else {
                                   $html .= "<textarea name=".$column." ";
                                   if(isset($colRules['fixed'])){
                                        $html .= "readonly ";
                                   }
                                   $html .= ">".$value;
                                   $html .="</textarea>";
                              }
                         }
                         elseif($colRules['type']==='boolean'){
                              $html .="<select name=".$column.">";
                              $html .="<option value='1'>True</option>";
                              $html .="<option value='0'>False</option>";
                              $html .="</select>";
                         }
                         elseif($colRules['type']==='date'){
                              $html .="<input type='datetime' name='$column' ";
                              if(isset($colRules['max'])){
                                   $html .="min=".$colRules['min']." max=".$colRules['max']." ";
                              }
                              if(isset($colRules['fixed'])){
                                   $html .= "readonly ";
                              }
                              $html .= " />";
                         }
                         $html .="</td></tr>";
                    }
                    $html .= "
                         </table>
                    </form>";

                    echo($html);
               };

               $this->reset();
               return($e); //returns the dynamo with access to the parent table
          }
     }

?>