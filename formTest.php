<?php

     require_once("pdoITable.php");
     $config = [
               'dbname'=>'pdoi_tester',
               'username'=>'pdoi_tester',
               'password'=>'pdoi_pass',
               'driver_options'=>[PDO::ATTR_PERSISTENT => true]
          ];
     
     $persons = new pdoITable($config, 'persons', true);
     $ships = new pdoITable($config, 'ships', true);
     $manifest = new pdoITable($config, 'manifest', true);
     
     function insert($entity){
          foreach($entity as $column=>$value){
               if(!array_key_exists("fixed",$entity->getRule($column))){
                    $entity->$column = $_POST[$column];
               }
          }
          $entity->insert();
     }
     
     function update($pdoit){
          $opts = [];
          //Set
          $opts['set'] = [];
          $entity = $pdoit->Offshoot();
          foreach($entity as $key=>$defValue){
               if(!array_key_exists("fixed", $entity->getRule($key))){
                    if(trim($_POST[$key]) !== ""){
                         $opts['set'][$key]=$_POST[$key];
                    }
               }
          }
          
          //Where
          $opts['where']=[];
          
          foreach($entity as $key=>$value){    
               $whereKey = 'where'.ucfirst($key);
               $whereMethod = 'where'.ucfirst($key)."Method";
               
               if(trim($_POST[$whereKey]) !== ""){
                    $opts['where'][$key] = ($_POST[$whereMethod] === "=") ? $_POST[$whereKey] : [$_POST[$whereMethod]=>$_POST[$whereKey]];
               }
          }
          
          if(trim($_POST['orderby']) !== ""){
               $opts['orderby'] = ($_POST['orderMethod'] === "none") ? $_POST['orderby'] : [$_POST['orderby']=>$_POST['orderMethod']];
          }
          if(trim($_POST['limit']) !== ""){
               $opts['limit'] = $_POST['limit'];  
          }
          
          if($pdoit->update($opts)){
               echo "Update Successful<br/>";
          }
     }
     
     function delete($pdoit){
          $entity = $pdoit->Offshoot();
          $opts = [];
          $opts['where']=[];
               //name
          foreach($entity as $key=>$value){    
               $whereKey = 'where'.ucfirst($key);
               $whereMethod = 'where'.ucfirst($key)."Method";
               
               if(trim($_POST[$whereKey]) !== ""){
                    $opts['where'][$key] = ($_POST[$whereMethod] === "=") ? $_POST[$whereKey] : [$_POST[$whereMethod]=>$_POST[$whereKey]];
               }
          }
          
          print_r($opts);
          
          if(trim($_POST['orderby']) !== ""){
               $opts['orderby'] = ($_POST['orderMethod'] === "none") ? $_POST['orderby'] : [$_POST['orderby']=>$_POST['orderMethod']];
          }
          if(trim($_POST['limit']) !== ""){
               $opts['limit'] = $_POST['limit'];  
          }
          
          if($pdoit->delete($opts)){
               echo "Delete Successful<br/>";
          }
     }
     
     function select($pdoit){
          $opts = [];
          $entity = $pdoit->Offshoot();
          //Select Columns
          $opts['columns'] = (trim($_GET['cols'])!=="") ? explode(",",trim($_GET['cols'])): [];
          
          if(isset($_GET['aggSolar'])){
               $opts['columns']['solar_years'] = [];
               $opts['columns']['solar_years']['agg'] = [$_GET['aggregateMethod']=>['solar_years']];
          }
          
          //Where
          $opts['where']=[];
          foreach($entity as $key=>$value){    
               $whereKey = 'where'.ucfirst($key);
               $whereMethod = 'where'.ucfirst($key)."Method";
               
               if(count($_POST)>0){
                    if(trim($_POST[$whereKey]) !== ""){
                         $opts['where'][$key] = ($_POST[$whereMethod] === "=") ? $_GET[$whereKey] : [$_GET[$whereMethod]=>$_GET[$whereKey]];
                    }
               }
               else{
                    if(trim($_GET[$whereKey]) !== ""){
                         $opts['where'][$key] = ($_GET[$whereMethod] === "=") ? $_GET[$whereKey] : [$_GET[$whereMethod]=>$_GET[$whereKey]];
                    }
               }
          }
          
          if(trim($_GET['orderby']) !== ""){
               $opts['orderby'] = ($_GET['orderMethod'] === 'none') ? $_GET['orderby'] : [$_GET['orderby']=>$_GET['orderMethod']];
          }
          
          if(trim($_GET['groupby']) !== ""){
               $opts['groupby'] = ['column'=>[$_GET['groupby']]];
               if(isset($_GET['havingSolar'])){
                    $having = ['aggMethod'=>$_GET['havingMethod']];
                    $having['columns'] = ['solar_years'];
                    $having['comparison'] = ['method'=>$_GET['havingSolarMethod'], 'value'=>$_GET['havingSolarValue']];
                    $opts['groupby']['having'] = $having;
               }
          }
          if(trim($_GET['limit'])!== ""){
               $opts['limit'] = $_GET['limit'];  
          }
          
          $appendDisplay = function(){
               echo($this."<br/>");
          };
          
          $result = $pdoit->select($opts);
          echo("<br />\n");
          
          if($result){
               if(is_array($result)){
                    foreach($result as $row){
                         //since we are using the pdoITable object, $result is a row of dynamic objects.  We can add functions to those objects here.
                         $row->show = $appendDisplay;
                         $row->show();
                    }
               }
               elseif(is_object($result)){
                    $result->show = $appendDisplay;
                    $result->show();
               }
          }
          else {
               echo("No records found!");
          }
     }
     
     if(isset($_POST['action'])){         
          if($_POST['action']==="insert"){
               $person = $persons->Offshoot();
               insert($person);
          }
          elseif($_POST['action']==="insertShip"){
               $ship = $ships->Offshoot();
               insert($ship);
          }
          elseif($_POST['action']==="manifestAdd"){
               $crew = $manifest->Offshoot();
               
               echo($crew);
               foreach($crew as $key=>$value){
                    if(!array_key_exists("fixed", $crew->getRule($key))){
                         $crew->$key = $_POST[$key];
                    }
                    
               }
               $crew->insert();
               
          }
          else if($_POST['action'] === 'update'){
               update($persons);
          }
          else if($_POST['action'] === 'updateShip'){
               update($ships);
          }
          else if($_POST['action'] === "delete"){
               delete($persons);
          }else if($_POST['action'] === "deleteShip"){
               delete($ships);
          }
     }
     
     if(isset($_GET['action'])){
          if($_GET['action']==='select1'){
               select($persons);
          }
          else if($_GET['action']==='selectShip'){
               select($ships);
          }
          else if($_GET['action']==='selectCrew'){
               $p = new pdoi($config, true);
               $opts = [];
               $opts['table'] = ['persons'=>[], 'ships'=>[],
                                 'manifest'=>['role']];
               $person=$persons->Offshoot();
               foreach($person as $key=>$val){
                    if(!array_key_exists("fixed", $person->getRule($key))){
                         array_push($opts['table']['persons'], $key);
                    }
               }
               $ship = $ships->Offshoot();
               foreach($ship as $key=>$val){
                    if(!array_key_exists("fixed", $ship->getRule($key))){
                         array_push($opts['table']['ships'], $key);
                    }
               }
               $opts['join']=[['inner join'=>'manifest'], ['inner join'=>'ships']];
               $opts['on']=[['manifest'=>'person_id'],['persons'=>'id'],['manifest'=>'ship_id'], ['ships'=>'ship_id']];
               $opts['where']=['ships'=>['ship_name'=>$_GET['ship_name']]];
               
               $chunk = $p->select($opts, instantiate(new dynamo));
               
               if(is_object($chunk)){
                    echo ($chunk."<br/>");
               }
               elseif(is_array($chunk)){
                    foreach($chunk as $index=>$hero){
                         echo($hero."<br/>");
                    }
               }
               else {
                    echo "SELECT failed!";
               }
               
          }
     }
?>
<html>
     <body>
          <br />
          <a href="form.php">Back to form</a>
     </body>
</html>