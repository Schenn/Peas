<?php

     require_once("pdoITable.php");
     $config = [
               'dns'=>'mysql:dbname=pdoi_tester;localhost',
               'username'=>'pdoi_tester',
               'password'=>'pdoi_pass',
               'driver_options'=>[PDO::ATTR_PERSISTENT => true]
          ];
     
     $pdoi_test = new pdoITable($config, 'pdoi_test', true);

     if(isset($_POST['action'])){         
          if($_POST['action']==="insert"){
               $values = [];
               foreach($_POST as $column=>$value){
                    if($column!=="action"){
                         $values[$column]=$value;
                    }
               }
               $a = ['values'=>$values];
               if($pdoi_test->insert($a)){
                    $pdoi_test->display();
               }
          }
     }
     if(isset($_GET['action'])){
          if($_GET['action']==='select1'){
               if($_GET['method'] !== "=" && $_GET['method'] !== "equal"){
                    $opts = ['where'=>
                                   [$_GET['column']=>
                                        [$_GET['method']=>$_GET['colvalue']]
                                   ]
                              ];
               }
               else {
                    $opts = ['where'=>
                                   [$_GET['column']=>$_GET['colvalue']]
                             ];
               }
               $result = $pdoi_test->select($opts);
               echo("<br />\n");
               foreach($result as $row){
                    foreach($row as $col=>$val){
                         echo($col.": ".$val."<br />\n");
                    }
               }
          }
     }
     

?>