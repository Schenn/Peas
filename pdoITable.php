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
               $this->args['columns'] = $this->columns;
          }
          
          function select($where, $options=[]){
               $a = $this->args;
               $a['where'] = $where;
               array_merge($a, $options);
               return(parent::SELECT($a));
          }
          
          function insert($values){
               $a = $this->args;
               $a['values'] = $values;
               return(parent::INSERT($a));
          }
          
          function update($set, $where, $options=[]){
               $a = $this->args;
               $a['set']=$set;
               $a['where']=$where;
               array_merge($a, $options);
               return(parent::UPDATE($a));
          }
          
          function delete($where, $options=[]){
               $a= $this->args;
               $a['where'] = $where;
               array_merge($a,$options);
               return(parent::DELETE($a));
          }
          
          function display(){
               $display = [$this->tableName=>$this->columns];
               print_r($display);
          }
     }
?>