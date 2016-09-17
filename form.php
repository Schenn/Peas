<?php
     require_once("autoload.php");
     use EntityGenerator\EntityEmitter;

     if(isset($_GET['action'])){
          print_r($_GET);
     }

     $config = [
               'dbname'=>'pdoi_test',
               'username'=>'pdoi_test',
               'password'=>'QzMdPwx3p4UpL4Rq',
               'driver_options'=>[PDO::ATTR_PERSISTENT => true]
          ];


     function stringWhereBox($col, &$html = ""){
          $bit="<td><select size = '5' name='where".ucfirst($col)."Method'>
                         <option value='=' selected='selected'>=</option>
                         <option value='!='>!=</option>
                         <option value='not'>NOT</option>
                         <option value='like'>Like</option>
                         <option value='not like'>Not Like</option>
                    </select></td>";
          if($html !== ""){
               $html .= $bit;
          }
          else {
               echo($bit);
          }

     }

     function numWhereBox($col, &$html = ""){
          $bit = "</tr><tr><td></td>
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
               ";
          if($html !== ""){
               $html .= $bit;
          }
          else {
               echo($bit);
          }

     }

     function aggBox($col, &$html = ""){
          $bit = "<td><select size = '3' name='".$col."Method'>
                         <option value='sum'>Sum</option>
                         <option value='avg'>Average</option>
                         <option value='max'>Max</option>
                         <option value='min'>Min</option>
                         <option value='count'>Count</option>
                    </select></td>
               ";
          if($html !== ""){
               $html .= $bit;
          }
          else {
               echo($bit);
          }

     }

     function orderby(){
          $bit = "
               <tr>
                    <td><label for='orderby'>Order By:</label></td><td><input type= 'text' name='orderby'/></td>
                    <td><input type='radio' name='orderMethod' value='ASC' />Ascending<br />
                         <input type='radio' name='orderMethod' value='DESC' />Descending<br />
                         <input type='radio' name='orderMethod' value='none' checked='checked' />None
                    </td>
               </tr>
          ";
          echo($bit);
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


     $persons = new EntityEmitter($config, "persons");
     $ships = new EntityEmitter($config, "ships");


     $person = $persons->EmitEntity();
     $ship = $ships->EmitEntity();

     $genInsertForm = function(){
          $html = "";
          foreach($this as $column=>$value){
               $rules = $this->getRule($column);
               if(!array_key_exists('fixed',$rules)){
                    $uColumn = ucfirst($column);
                    $html .= "<tr><td>";
                    $html .= "<label for='$column'>$uColumn:</label>";
                    $html .= "</td><td>";
                    $html .= "<input type='text' name='$column' value='$value' ";

                    if(array_key_exists('length', $rules)){
                         $html .= "size = '".$rules['length']."' maxlength = '".$rules['length'];
                    }
                    elseif(array_key_exists('max', $rules)){
                         $html .= 'max = '.$rules['max'];
                    }
                    $html .= "' /></td></tr>";
               }

          }
          echo $html;
     };

     $genWhereForm = function(){
          $html = "";
          foreach($this as $column=>$value){
               $lColumn = ucfirst($column);
               $rules = $this->getRule($column);
               $html .= "<tr><td><label for='where".$lColumn."'>$lColumn</label></td>";
               if($rules['type']==='string'){
                    stringWhereBox($lColumn, $html);
               }

               elseif($rules['type'] ==='numeric') {
                    numWhereBox($lColumn, $html);
               }
               $html .= "<td><input type='text' name='where".$lColumn."' ";

               if(array_key_exists('length', $rules)){
                    $html .= "size = '".$rules['length']."' maxlength = '".$rules['length'];
               }
               elseif(array_key_exists('max', $rules)){
                    $html .= 'max = '.$rules['max'];
               }
               $html .= "' /></td></tr>";
          }
          echo($html);
     };

     $person->insertForm = $genInsertForm;
     $person->whereForm = $genWhereForm;
     $ship->insertForm = $genInsertForm;
     $ship->whereForm = $genWhereForm;

     ?>
<html>
     <head>
          <style>

               div {
                    border: 3px solid #000;
                    float: left;
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
          <div class='left'>

          <form action='formTest.php' method='post'>
               <table>
                    <th>Add Person to Ship</th>
                    <tr><td><label for='ship_id'>Ship</label></td>
                         <td><select name="ship_id">
                              <?php
                                   $shipCollection = $ships->select();
                                   foreach($shipCollection as $index=>$aShip){
                                        echo "<option value='".$aShip->ship_id."'>".$aShip->ship_name."</option>";
                                   }
                              ?>
                         </select></td>
                    </tr>
                    <tr><td><label for='person_id'>Person</label></td>
                         <td><select name="person_id">
                              <?php
                                   $personCollection = $persons->select();
                                   foreach($personCollection as $index=>$aPerson){
                                        echo "<option value='".$aPerson->id."'>".$aPerson->name."</option>";
                                   }
                              ?>
                         </select></td>
                    </tr>
                    <tr>
                         <td><label for='role'>Role</label></td>
                         <td><input type='text' name='role'/></td>
                    </tr>
               <?php
                    endofform("manifestAdd");
               ?>
          <form action='formTest.php' method='get'>
               <table>
                    <th>
                         SHOW ALL CREW FOR
                    </th>
                    <tr>
                         <td><label for='ship_name'>Ship</label></td>
                         <td><select id="ship_name" name="ship_name">
                              <?php
                                   foreach($shipCollection as $index=>$aShip){
                                        echo "<option value='".$aShip->ship_name."'>".$aShip->ship_name."</option>";
                                   }
                              ?>
                         </select></td>
                    </tr>
               <?php
                    endofform("selectCrew");
               ?>
          <form action='formTest.php' method='get'>
               <table>
                    <th>
                         WORKS WITH
                    </th>
                    <tr>
                         <td><label for='name'>Person</label></td>
                         <td><select name="name">
                              <?php
                                   foreach($personCollection as $index=>$aPerson){
                                        echo "<option value='".$aPerson->name."'>".$aPerson->name."</option>";
                                   }
                              ?>
                         </select></td>
                    </tr>
               <?php
                    endofform("worksWith");
               ?>
          <!-- update join -- Send ship on mission.  (updates all solar_years by mission length) -->
          <form action='formTest.php' method='post'>
               <table>
                    <th>
                         Send on a Mission
                    </th>
                    <tr>
                         <td><label for='ship_name'>Ship</label></td>
                         <td><select name="ship_name">
                              <?php
                                   foreach($shipCollection as $index=>$aShip){
                                        echo "<option value='".$aShip->ship_name."'>".$aShip->ship_name."</option>";
                                   }
                              ?>
                         </select></td>
                    </tr>
                    <tr>
                         <td><label for='mission_years'>Mission Years</label></td>
                         <td><input type='text' value='1' name='mission_years' /></td>
                    </tr>
               <?php
                    endofform("sendMission");
               ?>
          </div>

     </body>
</html>