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
          $opts = [];
          if($_GET['action']==='select1'){
               if($_GET['column'] !== ""){
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
               }
               
               if($_GET['orderby'] !== ""){
                    $opts['orderby'] = $_GET['orderby'];
               }
          }
          elseif($_GET['action']==='selectwheremultiple'){
               $opts = ['where'=>
                    [$_GET['column']=>
                         [$_GET['method']=>[
                              $_GET['value1'], $_GET['value2']
                              ]
                         ]
                    ]
               ];
          }
               
          if($_GET['orderby'] !== ""){
               if($_GET['method'] === 'none'){
                    $opts['orderby'] = $_GET['orderby'];
               }
               else {
                    $opts['orderby'] = [$_GET['orderby']=>$_GET['orderMethod']];
               }
          }
          
          if($_GET['groupby'] !== ""){
               $opts['groupby'] = ['column'=>[$_GET['groupby']]];
               if($_GET['aggMethod'] !== "none"){
                    $having = ['aggMethod'=>$_GET['aggMethod']];
                    if($_GET['havingColumns']!=='null'){
                         $having['columns']=explode(", ",$_GET['havingColumns']);
                    }
                    else {
                         $having['columns'] = [];
                    }
                    $having['comparison'] = ['method'=>$_GET['comparison'], 'value'=>$_GET['comparisonValue']];
                    $opts['groupby']['having'] = $having;
               }
          }
          
               
          $result = $pdoi_test->select($opts);
          echo("<br />\n");
          foreach($result as $row){
               foreach($row as $col=>$val){
                    echo($col.": ".$val."<br />\n");
               }
          }
     }
     

?>