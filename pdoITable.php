<?php
     require_once('PDOI.php');
     
     class pdoITable extends PDOI {
          protected $tableName;
          protected $columns;
          protected $args = [];
          
          
          function __construct($config, $table, $debug=false){
               parent::__construct($config, $debug);
               $this->setTable($table);
          }
          
          function setTable($table){
               $this->tableName = $table;
               $this->args['table'] = $this->tableName;
               $this->setColumns();
          }
          
          function setColumns(){
               $this->columns = parent::getColumns($this->tableName);
               $this->args['columns']=[];
               foreach($this->columns as $column=>$n){
                    array_push($this->args['columns'], $column);
               }
          }
          
          function select($options){
               $a = $this->args;
               foreach($options as $option=>$setting){
                    $a[$option]=$setting;
               }
               return(parent::SELECT($a));
          }
          
          function insert($options){
               $a = $this->args;
               if(!isset($options['values'])){
                    $a['values']=[];
                    foreach($this->columns as $column=>$value){
                         $a['values'][$column]=$value;
                    }
               }
               foreach($options as $option=>$setting){
                    $a[$option]=$setting;
               }
               return(parent::INSERT($a));
          }
          
          function setCol($col,$val){
               $this->columns[$col]=$val;
          }
          
          function getCol($col){
               return($this->columns[$col]);
          }
          
          function update($options){
               $a = $this->args;
               foreach($options as $option=>$setting){
                    $a[$option]=$setting;
               }
               return(parent::UPDATE($a));
          }
          
          function delete($options){
               $a= $this->args;
               foreach($options as $option=>$setting){
                    $a[$option]=$setting;
               }
               return(parent::DELETE($a));
          }
          
          function display(){
               $display = [$this->tableName=>$this->columns];
               print_r($display);
          }
     }
?>