<?php
     require_once("pdoITable.php");
     use PDOI\pdoITable as pdoITable;
     use PDOI\PDOI as PDOI;
     $config = [
               'dbname'=>'pdoi_tester',
               'username'=>'pdoi_tester',
               'password'=>'pdoi_pass'
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
                    if(trim($_POST[$key]) !== "" && $_POST[$key] != $defValue){
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

          if(array_key_exists("orderby", $_POST)){
          if(trim($_POST['orderby']) !== ""){
               $opts['orderby'] = ($_POST['orderMethod'] === "none") ? $_POST['orderby'] : [$_POST['orderby']=>$_POST['orderMethod']];
          }
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

     function selectjoin1($where,$persons, $ret = null) {
          $shipmanifest = clone $persons;
          $shipmanifest->setRelationship(['persons.id'=>'manifest.person_id', 'manifest.ship_id'=>'ships.ship_id'], false);

          $entities = $shipmanifest->select(['where'=>$where]);

          if(is_object($entities)){
               if($ret !== null){
                    return($entities);
               }
               echo($entities."<br/>");
          }
          elseif(is_array($entities)){
               if($ret !== null){
                    return($entities);
               }
               foreach($entities as $index=>$result){
                    echo($result."<br/>");
               }
          }
          else {
               echo "<br />Select Failed";
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
          else if($_POST['action'] === "sendMission"){
               //get each person on the ship
               //add mission time to their solar_years
               //add 100xp per mission time to their experience
               //run update on everyone
               //add 1 to ship mission count
               $crew = selectjoin1(['ships'=>['ship_name'=>$_POST['ship_name']]], $persons, true);
               $years = (int)$_POST['mission_years'];
               $success = false;
               $crewTemplate = $persons->Offshoot();

               foreach($crew as $index=>$crewman){
                    foreach($crewTemplate as $key=>$val){
                         $crewTemplate->$key = $crewman->$key;
                    }
                    $crewTemplate->solar_years += $years;
                    $crewTemplate->experience_points += $years * 100;
                    $success = $crewTemplate->update();
               }

               $opts = ['where'=>["ship_id"=>$crew[0]->ship_id]];
               $ship = $ships->select($opts);

               $sTemp = $ships->Offshoot();
               foreach($sTemp as $key=>$val){
                    $sTemp->$key = $ship->$key;
               }
               $sTemp->ship_mission_count = intval($ship->ship_mission_count) + 1;

               $sTemp->update();

          }
     }

     if(isset($_GET['action'])){
          if($_GET['action'] === 'selectCrew'){
               selectjoin1(['ships'=>['ship_name'=>$_GET['ship_name']]], $persons);
          }
          else if($_GET['action'] === 'worksWith'){

               //get shipid of person
              $persons->setRelationship(['persons.id'=>'manifest.person_id', 'manifest.ship_id'=>'ships.ship_id'], false);
              $where = ['persons'=>['name'=>$_GET['name']]];
              $entity = $persons->select(['where'=>$where]);

              $entities = $persons->select(['where'=>['ships'=>['ship_id'=>$entity->ship_id]]]);
              
               if(is_object($entities)){
                    echo($entities."<br/>");
               }
               elseif(is_array($entities)){
                    foreach($entities as $index=>$result){
                         echo($result."<br/>");
                    }
               }
               else {
                    echo "<br />Select Failed";
               }
          }
          else if($_GET['action'] === "select1"){
               select($persons);
          }
          else if($_GET['action'] === "selectShip"){
               select($ships);
          }
     }
?>
<html>
     <body>
          <br />
          <a href="form.php">Back to form</a>
     </body>
</html>