<?php

     require_once("PDOI.php");
     $config = [
               'dns'=>'mysql:dbname=pdoi_tester;localhost',
               'username'=>'pdoi_tester',
               'password'=>'pdoi_pass',
               'driver_options'=>[PDO::ATTR_PERSISTENT => true]
          ];
     
     if(isset($_POST['name'])){
          $i = new PDOI($config);
          
          $insert = [
               'table'=>'pdoi_test',
               'columns'=>['name','species', 'planet'],
               'values'=>['name'=>$_POST['name'], 'species'=>$_POST['species'], 'planet'=>$_POST['planet']]
          ];
          
          $i->INSERT($insert);
     }
     else if(isset($_POST['getByName'])){
          $i = new PDOI($config);
          
          $select = [
               'table'=>'pdoi_test',
               'columns'=>['name','species', 'planet'],
               'where'=>['name'=>'%'.$_POST['getByName'].'%'],
               'like'=>true
          ];
          $result = $i->SELECT($select);
          print_r($result);
          
     }

?>
<html>
     <body>
          <form action="form.php" method="post" id="PDOI_Demo" name="PDOI_Demo" >
               <input type="text" name="name" required='true' />
               <input type="text" name="species" required='true' />
               <input type="text" name="planet" required='true' />
               <input type="submit" required='true' />
          </form>
          <form action="form.php" method="post" id="PDOI_Demo2" name="PDOI_Demo2" >
               <input type="text" name="getByName" required='true' />
               <input type="submit" />
          </form>
     </body>
</html>