<?php

     require_once("pdoITable.php");
     $config = [
               'dns'=>'mysql:dbname=pdoi_tester;localhost',
               'username'=>'pdoi_tester',
               'password'=>'pdoi_pass',
               'driver_options'=>[PDO::ATTR_PERSISTENT => true]
          ];
     
     $pdoi_test_control = new pdoITable($config, 'pdoi_test', true);
     
     if(isset($_POST['action'])==="insert"){
          $values = [];
          foreach($_POST as $column=>$value){
               if($column!=="action"){
                    $values[$column]=$value;
               }
          }
          if($pdoi_test_control->insert($values)){
               $pdoi_test_control->display();
          }
     }
     

?>
<html>
     <body>
          <!-- name, species, planet, system, solar_years, class -->
          <div>
          </div>
          <form action="form.php" method="post" >
               <table>
                    <th>Insert</th>
                    <tr>
                         <td><label for="name">Name:</label></td><td><input type="text" name="name" /></td>
                    </tr>
                    <tr>
                         <td><label for="species">Species:</label></td><td><input type="text" name="species" /></td>
                    </tr>
                    <tr>
                         <td><label for="planet">Planet:</label></td><td><input type="text" name="planet" /></td>
                    </tr>
                    <tr>
                         <td><label for="system">System:</label></td><td><input type="text" name="system" /></td>
                    </tr>
                    <tr>
                         <td><label for="solar_years">Age:</label></td><td><input type="text" name="solar_years" /></td>
                    </tr>
                    <tr>
                         <td><label for="class">Class:</label></td><td><input type="text" name="class" /></td>
                    </tr>
                    <tr>
                         <td><input type="hidden" name="action" value="insert" /></td>
                    </tr>
                    </tr>
                         <td><input type="submit" /></td><td><input type="reset" /></td>
                    <tr>
               </table>
          </form>
         
     </body>
</html>