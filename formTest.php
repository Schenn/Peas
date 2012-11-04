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
          else if($_POST['action'] === 'update'){
               $opts = [];
               $opts['set'] = [];
               if(trim($_POST['name']) !== ""){
                    $opts['set']['name']=$_POST['name'];
               }
               if(trim($_POST['species']) !== ""){
                    $opts['set']['species']=$_POST['species'];
               }
               if(trim($_POST['planet']) !== ""){
                    $opts['set']['planet']=$_POST['planet'];
               }
               if(trim($_POST['system']) !== ""){
                    $opts['set']['system']=$_POST['system'];
               }
               if(trim($_POST['solar_years']) !== ""){
                    $opts['set']['solar_years']=$_POST['solar_years'];
               }
               if(trim($_POST['class']) !== ""){
                    $opts['set']['class']=$_POST['class'];
               }
               
               //Where
               $opts['where']=[];
                    //name
               if(trim($_POST['whereName']) !== ""){
                    if($_POST['whereNameMethod'] === "="){
                         $opts['where']['name']=$_POST['whereName'];
                    }
                    else {
                         $opts['where']['name'] = [$_POST['whereNameMethod']=>$_POST['whereName']];
                    }
               }
                    //species
               if(trim($_POST['whereSpecies']) !== ""){
                    if($_POST['whereSpeciesMethod'] === "="){
                         $opts['where']['species']=$_POST['whereSpecies'];
                    }
                    else {
                         $opts['where']['species'] = [$_POST['whereSpeciesMethod']=>$_POST['whereSpecies']];
                    }
               }
                    //planet
               if(trim($_POST['wherePlanet']) !== ""){
                    if($_POST['wherePlanetMethod'] === "="){
                         $opts['where']['planet']=$_POST['wherePlanet'];
                    }
                    else {
                         $opts['where']['planet'] = [$_POST['wherePlanetMethod']=>$_POST['wherePlanet']];
                    }
               }
                    //system
               if(trim($_POST['whereSystem']) !== ""){
                    if($_POST['whereSystemMethod'] === "="){
                         $opts['where']['system']=$_POST['whereSystem'];
                    }
                    else {
                         $opts['where']['system'] = [$_POST['whereSystemMethod']=>$_POST['whereSystem']];
                    }
               }
                    //solar_years
               if(trim($_POST['whereSolar']) !== ""){
                    if($_POST['whereSolarMethod'] === "="){
                         $opts['where']['solar_years']=$_POST['whereSolar'];
                    }
                    else {
                         $opts['where']['solar_years'] = [$_POST['whereSolarMethod']=>$_POST['whereSolar']];
                    }
               }
                    //class
               if(trim($_POST['whereClass']) !== ""){
                    if($_POST['whereClassMethod'] === "="){
                         $opts['where']['class']=$_POST['whereClass'];
                    }
                    else {
                         $opts['where']['class'] = [$_POST['whereClassMethod']=>$_POST['whereClass']];
                    }
               }
               
               if(trim($_POST['orderby']) !== ""){
                    if($_POST['orderMethod'] === 'none'){
                         $opts['orderby'] = $_POST['orderby'];
                    }
                    else {
                         $opts['orderby'] = [$_POST['orderby']=>$_POST['orderMethod']];
                    }
               }
               if(trim($_POST['limit'])){
                    $opts['limit'] = $_POST['limit'];  
               }
               
               if($pdoi_test->update($opts)){
                    $pdoi_test->display();
               }
          }
          else if($_POST['action'] === "delete"){
               $opts = [];
               $opts['where']=[];
                    //name
               if(trim($_POST['whereName']) !== ""){
                    if($_POST['whereNameMethod'] === "="){
                         $opts['where']['name']=$_POST['whereName'];
                    }
                    else {
                         $opts['where']['name'] = [$_POST['whereNameMethod']=>$_POST['whereName']];
                    }
               }
                    //species
               if(trim($_POST['whereSpecies']) !== ""){
                    if($_POST['whereSpeciesMethod'] === "="){
                         $opts['where']['species']=$_POST['whereSpecies'];
                    }
                    else {
                         $opts['where']['species'] = [$_POST['whereSpeciesMethod']=>$_POST['whereSpecies']];
                    }
               }
                    //planet
               if(trim($_POST['wherePlanet']) !== ""){
                    if($_POST['wherePlanetMethod'] === "="){
                         $opts['where']['planet']=$_POST['wherePlanet'];
                    }
                    else {
                         $opts['where']['planet'] = [$_POST['wherePlanetMethod']=>$_POST['wherePlanet']];
                    }
               }
                    //system
               if(trim($_POST['whereSystem']) !== ""){
                    if($_POST['whereSystemMethod'] === "="){
                         $opts['where']['system']=$_POST['whereSystem'];
                    }
                    else {
                         $opts['where']['system'] = [$_POST['whereSystemMethod']=>$_POST['whereSystem']];
                    }
               }
                    //solar_years
               if(trim($_POST['whereSolar']) !== ""){
                    if($_POST['whereSolarMethod'] === "="){
                         $opts['where']['solar_years']=$_POST['whereSolar'];
                    }
                    else {
                         $opts['where']['solar_years'] = [$_POST['whereSolarMethod']=>$_POST['whereSolar']];
                    }
               }
                    //class
               if(trim($_POST['whereClass']) !== ""){
                    if($_POST['whereClassMethod'] === "="){
                         $opts['where']['class']=$_POST['whereClass'];
                    }
                    else {
                         $opts['where']['class'] = [$_POST['whereClassMethod']=>$_POST['whereClass']];
                    }
               }
               
               if(trim($_POST['orderby']) !== ""){
                    if($_POST['orderMethod'] === 'none'){
                         $opts['orderby'] = $_POST['orderby'];
                    }
                    else {
                         $opts['orderby'] = [$_POST['orderby']=>$_POST['orderMethod']];
                    }
               }
               if(trim($_POST['limit'])){
                    $opts['limit'] = $_POST['limit'];  
               }
               
               if($pdoi_test->DELETE($opts)){
                    $pdoi_test->display();
               }
          }
     }
     
     if(isset($_GET['action'])){
          $opts = [];
          if($_GET['action']==='select1'){
               
               //Select Columns
               $opts['columns']=[];
               if(trim($_GET['cols'])!==""){
                    $opts['columns'] = explode(",",trim($_GET['cols']));
               }
               
               if(isset($_GET['aggSolar'])){
                    $opts['columns']['solar_years'] = [];
                    $opts['columns']['solar_years']['agg'] = [$_GET['aggregateMethod']=>['solar_years']];
               }
               
               //Where
               $opts['where']=[];
                    //name
               if(trim($_GET['whereName']) !== ""){
                    if($_GET['whereNameMethod'] === "="){
                         $opts['where']['name']=$_GET['whereName'];
                    }
                    else {
                         $opts['where']['name'] = [$_GET['whereNameMethod']=>$_GET['whereName']];
                    }
               }
                    //species
               if(trim($_GET['whereSpecies']) !== ""){
                    if($_GET['whereSpeciesMethod'] === "="){
                         $opts['where']['species']=$_GET['whereSpecies'];
                    }
                    else {
                         $opts['where']['species'] = [$_GET['whereSpeciesMethod']=>$_GET['whereSpecies']];
                    }
               }
                    //planet
               if(trim($_GET['wherePlanet']) !== ""){
                    if($_GET['wherePlanetMethod'] === "="){
                         $opts['where']['planet']=$_GET['wherePlanet'];
                    }
                    else {
                         $opts['where']['planet'] = [$_GET['wherePlanetMethod']=>$_GET['wherePlanet']];
                    }
               }
                    //system
               if(trim($_GET['whereSystem']) !== ""){
                    if($_GET['whereSystemMethod'] === "="){
                         $opts['where']['system']=$_GET['whereSystem'];
                    }
                    else {
                         $opts['where']['system'] = [$_GET['whereSystemMethod']=>$_GET['whereSystem']];
                    }
               }
                    //solar_years
               if(trim($_GET['whereSolar']) !== ""){
                    if($_GET['whereSolarMethod'] === "="){
                         $opts['where']['solar_years']=$_GET['whereSolar'];
                    }
                    else {
                         $opts['where']['solar_years'] = [$_GET['whereSolarMethod']=>$_GET['whereSolar']];
                    }
               }
                    //class
               if(trim($_GET['whereClass']) !== ""){
                    if($_GET['whereClassMethod'] === "="){
                         $opts['where']['class']=$_GET['whereClass'];
                    }
                    else {
                         $opts['where']['class'] = [$_GET['whereClassMethod']=>$_GET['whereClass']];
                    }
               }
               
               if(trim($_GET['orderby']) !== ""){
                    if($_GET['orderMethod'] === 'none'){
                         $opts['orderby'] = $_GET['orderby'];
                    }
                    else {
                         $opts['orderby'] = [$_GET['orderby']=>$_GET['orderMethod']];
                    }
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
               if(trim($_GET['limit'])){
                    $opts['limit'] = $_GET['limit'];  
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