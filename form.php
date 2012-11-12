
<?php

     if(isset($_GET['action'])){
          print_r($_GET);
     }

     require_once("pdoITable.php");
     
     $config = [
               'dbname'=>'pdoi_tester',
               'username'=>'pdoi_tester',
               'password'=>'pdoi_pass',
               'driver_options'=>[PDO::ATTR_PERSISTENT => true]
          ];
     
     
     function stringWhereBox($col){
          echo("<td><select size = '5' name='where".ucfirst($col)."Method'>
                         <option value='=' selected='selected'>=</option>
                         <option value='!='>!=</option>
                         <option value='not'>NOT</option>
                         <option value='like'>Like</option>
                         <option value='not like'>Not Like</option>
                    </select></td>");
     }
     
     function numWhereBox($col){
          echo("</tr><tr><td></td>
          <td><select size = '5' name='where".ucfirst($col)."Method'>
                         <option value='=' selected='selected'>=</option>
                         <option value='!='>!=</option>
                         <option value='not'>NOT</option>
                         <option value='<'><</option>
                         <option value='less'>Less Than</option>
                         <option value='<='><=</option>
                         <option value='less equal'>Less Than or Equal To</option>
                         <option value='>'>></option>
                         <option value='greater'>Greater Than</option>
                         <option value='>='>>=</option>
                         <option value='greater equal'>Greater Than or Equal To</option>
                    </select></td>
               ");
     }
     
     function aggBox($col){
          echo("<td><select size = '3' name='".$col."Method'>
                         <option value='sum'>Sum</option>
                         <option value='avg'>Average</option>
                         <option value='max'>Max</option>
                         <option value='min'>Min</option>
                         <option value='count'>Count</option>
                    </select></td>
               ");
     }
     
     function orderby(){
          echo("
               <tr>
                    <td><label for='orderby'>Order By:</label></td><td><input type= 'text' name='orderby'/></td>
                    <td><input type='radio' name='orderMethod' value='ASC' />Ascending<br />
                         <input type='radio' name='orderMethod' value='DESC' />Descending<br />
                         <input type='radio' name='orderMethod' value='none' checked='checked' />None
                    </td>
               </tr>
          ");
     }
     
     function endofform($action= "", $limit = false){
          if($limit === true){
               echo("<tr>
                         <td><label for='limit'>Limit:</label></td>
                         <td><input type='text' name='limit' /> </td>
                    </tr>");
          }
          
          echo("<tr>
                         <td><input type=\"hidden\" name=\"action\" value=\"$action\" /></td>
                    </tr>
                    <tr>
                         <td><input type=\"submit\" /></td><td><input type=\"reset\" /></td>
                    </tr>
               </table>
          </form>
               ");
     }
     

     $persons = new pdoITable($config, "persons");
     $ships = new pdoITable($config, "ships");
     
     $person = $persons->Offshoot();
     $ship = $ships->Offshoot();
     
     $genInsertForm = function(){
          $html = "";
          foreach($this as $column=>$value){
               if(!array_key_exists('fixed',$this->getRule($column))){
                    $uColumn = ucfirst($column);
                    $html .= "<tr><td>";
                    $html .= "<label for='$column'>$uColumn:</label>";
                    $html .= "</td><td>";
                    $html .= "<input type='text' name='$column' value='$value' />";
                    $html .= "</td></tr>";
               }
          }
          echo $html;
     };
     
     $genWhereForm = function(){
          foreach($this as $column=>$value){
               $lColumn = ucfirst($column);
               echo("<tr><td><label for='where".$lColumn."'>$lColumn</label></td>");
               $type = $this->getRule($column)['type'];
               if($type==='string'){
                    stringWhereBox($lColumn);
               }
               
               elseif($type ==='numeric') {
                    numWhereBox($lColumn);
               }
               echo("<td><input type='text' name='where".$lColumn."' /></td>
                    ");
          }
     };
     
     $person->insertForm = $genInsertForm;     
     $person->whereForm = $genWhereForm;
     $ship->insertForm = $genInsertForm;
     $ship->whereForm = $genWhereForm;
     
     ?>
<html>
     <head>
          <style>
               #left {
                    float:left;
                    border: 3px solid #000;
               }
               
               #right {
                    float: left;
                    border: 3px solid #000;
               }
          </style>
     </head>
     <body>
          <div id='left'>
               <form action="formTest.php" method="post" >
               <table>
                    <th>Insert New Person</th>
     <?php
          $person->insertForm();
          endofform("insert");
     ?>
          <!-- name, species, planet, system, solar_years, class -->
          <form action="formTest.php" method="get">
               <table>
                    <th>Select</th>
                    <tr>
                         <td><label for='cols'>Columns:</label></td>
                         <td><input type='text' placeholder='"," to seperate' name='cols' /> </td>
                    </tr>
                    <tr>
                         <td><input type='checkbox' name='aggSolar'/>Aggregate?</td>
                         <?php
                              aggBox("aggregate");
                         ?>
                    </tr>
                    <tr><td>From persons</td></tr>
                    <tr><td>Where</td></tr>
                    <?php
                         $person->whereForm();
                         orderby();
                    ?>
                    <tr>
                         <td><label for='groupby'>Group By:</label></td><td><input type= "text" name="groupby"/></td>
                    </tr>
                    <tr>
                         <td><input type='checkbox' name='havingSolar'/>Having?</td>
                         <td><label for='havingMethod'>Having: Aggregate Solar Years:</label></td>
                         <?php
                              aggBox("having");
                              numWhereBox("havingSolar");
                         ?>
                         <td><input type='text' name='havingSolarValue' /></td>
                    </tr>
                   <?php
                         endofform("select1", true);
                   ?>
          <form action="formTest.php" method="post">
               <table>
                    <th>Update persons SET </th>
                     <?php
                         $person->insertForm();
                    ?>
                    <tr><td> Where </td></tr>
                    <?php
                         $person->whereForm();
                         endofform("update", true);
                    ?>
                    
          <form action='formTest.php' method='post'>
               <table>
                    <th>Delete</th>
                    <tr><td>Where</td></tr>
                    <?php
                         $person->whereForm();
                         orderby();
                         endofform("delete", true);
                    ?>
          </div>
          <div id='right'>
               <form action="formTest.php" method="post" >
               <table>
                    <th>Insert New Ship</th>
                     <?php
                         $ship->insertForm();
                         endofform("insertShip");
                    ?>
               <form action="formTest.php" method="get">
               <table>
                    <th>Select</th>
                    <tr>
                         <td><label for='cols'>Columns:</label></td>
                         <td><input type='text' placeholder='"," to seperate' name='cols' /> </td>
                    </tr>
                    <tr><td>From ships</td></tr>
                    <tr><td>Where</td></tr>
                    <?php
                         $ship->whereForm();
                         orderby();
                    ?>
                    <tr>
                         <td><label for='groupby'>Group By:</label></td><td><input type= "text" name="groupby"/></td>
                    </tr>
                    <?php
                         endofform("selectShip", true);
                    ?>
               <form action="formTest.php" method="post">
               <table>
                    <th>Update ships SET </th>
                    <?php
                         $ship->insertForm();
                    ?>
                    <tr><td> Where </td></tr>
                    <?php
                         $ship->whereForm();
                         orderby();
                         endofform("updateShip", true);
                    ?>
          <form action='formTest.php' method='post'>
               <table>
                    <th>Delete From ships</th>
                    <tr><td>Where</td></tr>
                    <?php
                         $ship->whereForm();
                         orderby();
                         endofform("deleteShip", true);
                    ?>
          </div>
     </body>
</html>