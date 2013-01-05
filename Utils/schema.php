<?php
namespace PDOI\Utils;
use Exception, Iterator, JsonSerializable;

class schemaException extends Exception {

}

interface schemaInterface extends Iterator, JsonSerializable {

}

class schema implements schemaInterface {
     private $this->map = [];
     private $this->primaryKeys = [];
     private $this->foreignKeys = [];

     public function __construct($maps = []){
          foreach($maps as $table=>$columns){
               $this->map[$table]=[];
               foreach($columns as $column){
                    $this->map[$table][$column]=null;
               }
               $this->primaryKeys[$table] = [];
               $this->foreignKeys[$table] = [];
          }
     }

     public function __set($table, $columnList){
          $this->map[$table] = [];
          foreach($columnList as $column){
               array_push($this->map[$table],[$column=>[]]);
          }
          $this->primaryKeys[$table] = [];
          $this->foreignKeys[$table] = [];
     }

     public function __get($table){
          $cols = [];
          foreach($this->map[$table] as $column){
               array_push($cols, $column);
          }

          return($cols);
     }

     public function setForeignKey($relationship, $values = []){
          //relationship = [table.column=>table.column]

          $tableColumn1 = array_keys($relationship)[0];
          $tableColumn2 = $relationship[$tableColumn1];

          $table1 = substr($tableColumn1,0,strpos($tableColumn1,'.')-1);
          $column1 = substr($tableColumn1,strpos($tableColumn1,'.')+1);
          $table2 = substr($tableColumn2,0,strpos($tableColumn2,'.')-1);
          $column2 = substr($tableColumn2,strpos($tableColumn2,'.')+1);

          array_push($this->foreignKeys[$table1], [$column1=>[$table2=>$column2]]);

          if(count($values)!==0){
               $this->map[$table1][$column1] = ['table'=>$table2,'column'=>$column2, $values];
          }
     }

     public function getMap(){
          $map = [];
          foreach($this->map as $table=>$columns){
               $map[$table]=[];
               foreach($columns as $column=>$values){
                    array_push($map[$table],$column);
               }
          }

          return($map);
     }

     /* Name: rewind
      * Description:  Iterator required function, returns property list to first index
      */
     public function rewind(){
          reset($this->map);
     }

     /* Name: rewind
      * Description:  Iterator required function, returns current property in property list
      */
     public function current(){
          return(current($this->map));
     }

     /* Name: key
      * Description:  Iterator required function, returns key of current property
      */
     public function key(){
          return(key($this->map));
     }

     /* Name: next
      * Description:  Iterator required function, moves property list to next index
      */
     public function next(){
          return(next($this->map));
     }

     /* Name: valid
      * Description:  Iterator required function, returns whether the next key in the properties is not null
      */
     public function valid(){
          return(key($this->map) !== null);
     }

     /* Name: __isset
      * Description:  Determines whether a property exists within the object
      */
     public function __isset($table){
          if(array_key_exists($table, $this->map)){
               return(true);
          }
          else {
               return(false);
          }
     }

     /* Name: __unset
      * Description:  Removes a property from the object and any validation information for that property
      */
     public function __unset($table){
          unset($this->map[$table]);
          unset($this->primaryKeys[$table]);
          unset($this->foreignKeys[$table]);

     }

     public function addColumns($table, $cols){
          array_merge($this->map[$table],$cols);
     }

     public function addTable($tables){
          if(is_array($tables)){
               foreach($tables as $table){
                    $this->map[$table]=[];
               }
          }
          elseif(is_string($tables)){
               $this->map[$tables]=[];
          }

     }

}
?>