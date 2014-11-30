<?php
namespace PDOI\Utils;
use Exception, Iterator, JsonSerializable;

/**
 * @author:  Steven Chennault schenn@mash.is
 * @link: https://github.com/Schenn/EmitterDatabaseHandler Repository
*/

/**
 * Error Exception for schema
 *
 * When a schema is told to build off invalid data, it should throw this error
 *
 * @category Exceptions
 */
class schemaException extends Exception {

}

/**
 * Interface schemaInterface Combines Iterator and JsonSerializable for the Schema class
 * @package EmitterDatabaseHandler\Utils
 */
interface schemaInterface extends Iterator, JsonSerializable {

}

/**
 * Class schema
 *
 * Schema maintains table relationship and column information in a manner which allows other classes to iterate over that information
 * In addition, Schema maintains the meta data for the table columns so that values can be validated before attempting to store them
 *
 * @package EmitterDatabaseHandler\Utils
 */
class schema implements schemaInterface {
    /** @var array $map The schema dictionary of table, column and metadata */
    private $map = [];
    /** @var array $primaryKeys A dictionary of table=>primary keys */
    private $primaryKeys = [];
    /** @var array $foreignKeys A dictionary of table=>[foreign key=>[table=>key]] */
    private $foreignKeys = [];
    /** @var array $masterKey The dictionary which represents the base table of the schema. [table=>primary_key] */
    private $masterKey = [];

    /**
     * Create a new Schema
     *
     * From a map of table=>[columns] prepare our schema dictionaries
     *
     * @param array $maps [tableName=>[columnName, columnName, ...]]
     */
    public function __construct($maps = []){
        foreach($maps as $table=>$columns){
            $this->map[$table]=[];
            foreach($columns as $column){
                $this->map[$table][$column]=null;
            }
            $this->primaryKeys[$table] = [];
            $this->foreignKeys[$table] = [];
        }
        // If only one table was provided, set that table as the master
        if(count($maps)===1){
            $this->masterKey = [array_keys($maps)[0]=>""];
        }
    }

    /**
     * Add a table to the schema
     *
     * @param string $table The name of the table to add
     * @param array $columnList The list of columnNames that belong to the table
     */
     public function __set($table, $columnList){
          $this->map[$table] = [];
          foreach($columnList as $column){
               array_push($this->map[$table],[$column=>[]]);
          }
          $this->primaryKeys[$table] = [];
          $this->foreignKeys[$table] = [];

     }

    /**
     * Retrieve a table from the schema
     *
     * @param string $table The table name to retrieve
     * @return array The list of columns from the table
     */
     public function __get($table){
          $cols = [];
          foreach($this->map[$table] as $column){
               array_push($cols, $column);
          }

          return($cols);
     }

    /**
     * Rewinds the iterator to the first position
     */
    public function rewind(){
        reset($this->map);
    }

    /**
     * Retrieve the current table content from the map
     *
     * @return array [table=>[columnName=>MetaData]]
     */
    public function current(){
        return(current($this->map));
    }

    /**
     * Get the tableName of the current position of the map
     *
     * @return string tableName
     */
    public function key(){
        return(key($this->map));
    }

    /**
     * Moves the map to the next position
     */
    public function next(){
        next($this->map);
    }

    /**
     * Does the current position of the map not have a null key
     *
     * @return bool
     */
    public function valid(){
        return(key($this->map) !== null);
    }

    /**
     * Determine if a tableName has been assigned to this schema
     *
     * @param string $table the name of the table to check
     * @return bool
     */
    public function __isset($table){
        if(array_key_exists($table, $this->map)){
            return(true);
        }
        else {
            return(false);
        }
    }

    /**
     * Removes a table and it's related information from the schema
     *
     * @param string $table The name of the table to remove
     */
    public function __unset($table){
        unset($this->map[$table], $this->primaryKeys[$table], $this->foreignKeys[$table]);

        foreach($this->foreignKeys as $fkTable){
            foreach($fkTable as $column=>$fkRel){
                if(array_keys($fkRel)[0] === $table){
                    unset($this->foreignKeys[$fkTable][$column]);
                }
            }
        }

    }

    /**
     * Retrieve the schema as a json object
     *
     * @return string $this->map as a json string
     */
     public function jsonSerialize(){
          return(json_encode($this->map));
     }

    /**
     * Set a foreign key in the schema
     *
     * Update our foreignKeys dictionary using a list of relationships so that we can understand how these tables are connected
     *
     * @param $relationship [table.column=>table.column]
     * @param array $values propagate the map with the values assigned to the given columns
     * @api
     */
    public function setForeignKey($relationship, $values = []){
        $tableColumn1 = array_keys($relationship)[0];
        $tableColumn2 = $relationship[$tableColumn1];

        $table1Args = preg_split("[\.]", $tableColumn1);
        $table2Args = preg_split("[\.]", $tableColumn2);

        // TableName == position 0
        // ColumnName == position 1
        if(!isset($this->foreignKeys[$table1Args[0]])){
            $this->foreignKeys[$table1Args[0]] = [];
        }
        array_push($this->foreignKeys[$table1Args[0]], [$table1Args[1]=>[$table2Args[0]=>$table2Args[1]]]);
        if(count($values)!==0){
             $this->map[$table1Args[0]][$table1Args[1]] = ['table'=>$table2Args[0],'column'=>$table2Args[1], $values];
        }
    }

    /**
     * Get the schema without metadata
     *
     * This method returns the map without any metadata. It's used to interpret schema structure
     * @return array $map A dictionary of [table =>[columnName, columnName, ...], ...]
     * @api
     */
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



    /**
     * Add Columns to a table
     *
     * @param string $table The name of the table to add the columns too
     * @param array $cols [columnName=>MetaData, columnName=>MetaData]
     * @api
     */
     public function addColumns($table, $cols){
          array_merge($this->map[$table],$cols);
     }

    /**
     * Get the column names from a table
     *
     * @param string $table the name of the table
     * @return array A list of the columnNames
     * @api
     */
     public function getColumns($table){
          return array_keys($this->map[$table]);
     }

    /**
     * Add Tables to the schema
     *
     * @param array $tables A list of table names to add to the map
     * @api
     */
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

    /**
     * Get the table names in the schema
     *
     * @return array The list of table names
     * @api
     */
     public function getTables(){
          return(array_keys($this->map));
     }

    /**
     * Set the primary key of a table
     *
     * @param string $table The name of the table
     * @param string $key The column name of the primary key
     */
     public function setPrimaryKey($table, $key){
          $this->primaryKeys[$table]=$key;
          if(array_key_exists($table, $this->masterKey)){
              $this->masterKey[$table]=$key;
          }
     }

    /**
     * Retrieve the primary key of a table
     *
     * @param string $table the table to get the primary key of
     * @return string|bool Returns the primary key of the table or false if one isn't assigned.
     * @api
     *
     * @todo this should probably return null instead of false on fail
     */
     public function getPrimaryKey($table){
         $pk = (array_key_exists($table, $this->primaryKeys)) ? $this->primaryKeys[$table] : false;
         return $pk;
     }

    /**
     * Add a column to a table
     *
     * @param string $table The table name to add the column to
     * @param string $field The name of the column to add to the table
     * @api
     */
     public function addColumn($table, $field){
          $this->map[$table]=[$field=>[]];
          
     }

    /**
     * Set the validation rules for a table column.
     *
     * These rules can be used to determine if a provided value is valid for the field.
     *
     * @see PDOI\pdoITable::setColumns
     * @see PDOI\PDOI::describe The meta data expected is the meta data provided by a mysql describe query
     * @see PDOI\Utils\Dynamo Dynamo uses these meta values to validate its incoming values when a
     * value is trying to be set. If the value is invalid, it soft fails and announces an error
     *
     * @param string $table The tableName the meta rules apply to
     * @param string $field The columnName the meta rules apply to
     * @param array $meta The dictionary of meta data about the rules
     * @api
     * @todo Error Catching
     */
     public function setMeta($table,$field,$meta=[]){
          $metaTranslate = [];
          $metaTranslate[$field] = [];
          if(count($meta)>=0){
               //get field length which is a number inside parenthesis following the type string
                    //e.g. retrieve '100' from int(100)

               // Remove the type string from the $meta["Type"] information
               $sansType = preg_split("/int|decimal|double|float|double|real|bit|bool|serial|date|time|year|char|text|binary|blob|enum|set|geometrycollection|multipolygon|multilinestring|multipoint|polygon|linestring|point|geometry/",
                                        strtolower($meta['Type']));

               // Remove the parenthesis from whats left of the type string
               if(isset($sansType[1])){
                    $sansParenthesis = preg_split("/\(|\)/",$sansType[1]);
                    if(isset($sansParenthesis[1])){
                         $metaTranslate[$field]['length'] = intval($sansParenthesis[1]);
                    }
               }

               // Get the type
               // Remove the length and parenthesis from the type string
               $metaTranslate[$field]['type'] = preg_filter("/\(|\d+|\)/","",strtolower($meta['Type']));

               // Set the default value for the field
               $metaTranslate[$field]['default'] = $meta['Default'];

               // If this column has been marked as the primary key
               if(!empty($meta['Key'])){
                   $this->setPrimaryKey($table, $field);
                    $metaTranslate[$field]['primaryKey'] = true;

                   // If this primary key has been marked as auto incrementing
                    if($meta['Extra'] === "auto_increment"){
                         $metaTranslate[$field]['auto'] = true;
                    }
               }

               // If Null values are not allowed, mark the field as required (can't update or insert if value isn't set)
              // 'NO' is how mysql desc says whether or not null is allowed
               if($meta['Null'] === 'NO'){
                    $metaTranslate[$field]['required'] = true;
               }

               // If format data was provided
               if(array_key_exists('Format',$meta)){
                    $metaTranslate[$field]['format'] = $meta['Format'];
               }

          }
          // Set the meta data for the column in the schema dictionary
          $this->map[$table][$field]=$metaTranslate[$field];
     }

    /**
     * Get the meta data for a table column
     *
     * @param string $table The name of the table
     * @param string $field the name of the column
     * @return array mixed The meta data for the field
     * @api
     */
     public function getMeta($table, $field){

          return($this->map[$table][$field]);

     }

    /**
     * Retrieve the foreign keys
     *
     * @return array $this->foreignKeys
     * @api
     */
     public function getForeignKeys(){
          return $this->foreignKeys;
     }

    /**
     * Retrieve the primary keys
     *
     * @return array $this->primaryKeys
     * @api
     */
     public function getPrimaryKeys(){
         return $this->primaryKeys;
     }

    /**
     * Retrieve the Master Key
     *
     * @return array $this->masterKey
     * @api
     */
     public function getMasterKey(){
         return $this->masterKey;
     }

    /**
     * Sets the master key for the schema
     *
     * @param $masterKey [tableName->columnName]
     */
     public function setMasterKey($masterKey){
         $this->masterKey = $masterKey;
     }
     
}
?>