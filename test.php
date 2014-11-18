<?php
require_once("userTable.php");
$config = [
        'dbname'=>'pdoi_test',
        'username'=>'pdoi_test',
        'password'=>'QzMdPwx3p4UpL4Rq',
        ];

$install = new userTable($config, true);

//$install->init();
if($user = $install->createUser('schenn', 'monkey')){
    echo "Successfully created $user";
    
} else {
    echo "Username already exists";
    if($user = $install->login('schenn', 'monkey')){
        echo "Successfully logged in. {$user} <br/>\n Deleting Schenn.";
        $install->deleteUser('schenn');
    } else {
        echo "Failed to Login";
    }
}

?>
