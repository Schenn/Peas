<?php
namespace PDOI\Utils;
use Exception, Iterator, JsonSerializable;

class schemaException extends Exception {

}

interface schemaInterface extends Iterator, JsonSerializable {

}

class schema implements schemaInterface {
     private $map = [];
     private $primaryKeys = [];
     private $foreignKeys = [];
     private $masterKey = [];

     public function __construct($maps = []){
          foreach($maps as $table=>$columns){
               $this->map[$table]=[];
               foreach($columns as $column){
                    $this->map[$table][$column]=null;
               }
               $this->primaryKeys[$table] = [];
               $this->foreignKeys[$table] = [];
          }
          if(count($maps)===1){
              $this->masterKey = [array_keys($maps)[0]=>""];
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

     public function jsonSerialize(){
          return(json_encode($this->map));
     }

     public function setForeignKey($relationship, $values = []){
          //relationship = [table.column=>table.column]

          $tableColumn1 = array_keys($relationship)[0];
          $tableColumn2 = $relationship[$tableColumn1];

          $table1 = substr($tableColumn1,0,strpos($tableColumn1,'.'));
          $column1 = substr($tableColumn1,strpos($tableColumn1,'.')+1);
          $table2 = substr($tableColumn2,0,strpos($tableColumn2,'.'));
          $column2 = substr($tableColumn2,strpos($tableColumn2,'.')+1);

          if(!isset($this->foreignKeys[$table1])){
              $this->foreignKeys[$table1] = [];
          }
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

          foreach($this->foreignKeys as $fkTable){
                foreach($fkTable as $column=>$fkRel){
                    $seekTable = array_keys($fkRel)[0];
                    
                    if($seekTable === $table){
                        unset($this->foreignKeys[$fkTable][$column]);
                    }
                }
            }
          
     }

     public function addColumns($table, $cols){
          array_merge($this->map[$table],$cols);
     }

     public function getColumns($table){
          return array_keys($this->map[$table]);
     }

     public function addTable($tables){
          if(is_array($tables)){
               foreach($tables as $table){
                   if(!isset($this->map[$table])){
                        $this->map[$table]=[];
                   }
               }
          }
          elseif(is_string($tables)){
               $this->map[$tables]=[];
               if(count($this->masterKey)===0){
                   $this->masterKey = [$tables=>""];
               }
          }

     }
     
     public function getTables(){
          return(array_keys($this->map));
     }

     public function setPrimaryKey($table, $key){
          $this->primaryKeys[$table]=$key;
          if(array_key_exists($table, $this->masterKey)){
              $this->masterKey[$table]=$key;
          }
     }
     
     public function getPrimaryKey($table){
         $pk = (array_key_exists($table, $this->primaryKeys)) ? $this->primaryKeys[$table] : false;
         return $pk;
     }

     public function addColumn($table, $field){
          $this->map[$table]=[$field=>[]];
          
     }

     public function setMeta($table,$field,$meta=[]){

          $metaTranslate = [];
          $metaTranslate[$field] = [];
          if(count($meta)>=0){
               //get field length
               $sansType = preg_split("/int|decimal|double|float|double|real|bit|bool|serial|date|time|year|char|text|binary|blob|enum|set|geometrycollection|multipolygon|multilinestring|multipoint|polygon|linestring|point|geometry/",
                                        strtolower($meta['Type']));

               if(isset($sansType[1])){
                    $sansParens = preg_split("/\(|\)/",$sansType[1]);
                    if(isset($sansParens[1])){
                         $metaTranslate[$field]['length'] = intval($sansParens[1]);
                    }
               }

               //field type
               $metaTranslate[$field]['type'] = preg_filter("/\(|\d+|\)/","",strtolower($meta['Type']));
               //field default
               $metaTranslate[$field]['default'] = $meta['Default'];

               if(!empty($meta['Key'])){
                   $this->setPrimaryKey($table, $field);
                    //$this->primaryKeys[$table] = $field;
                    $metaTranslate[$field]['primaryKey'] = true;

                    if($meta['Extra'] === "auto_increment"){
                         $metaTranslate[$field]['auto'] = true;
                    }
               }

               if($meta['Null'] === 'NO'){
                    $metaTranslate[$field]['required'] = true;
               }

               if(array_key_exists('Format',$meta)){
                    $metaTranslate[$field]['format'] = $meta['Format'];
               }

          }

          $this->map[$table][$field]=$metaTranslate[$field];
     }

     public function getMeta($table, $field){

          return($this->map[$table][$field]);

     }

     public function getForeignKeys(){
          return $this->foreignKeys;
     }

     public function getPrimaryKeys(){
         return $this->primaryKeys;
     }
     
     public function getMasterKey(){
         return $this->masterKey;
     }
     
     public function setMasterKey($mk){
         //tablename->fieldname
         $this->masterKey = $mk;
     }
     
}
?>