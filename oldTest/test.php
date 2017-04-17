<?php
use Peas\Tables\UserTable;

require_once(__DIR__."/../"."autoload.php");
$config = [
        'dbname'=>'pdoi_test',
        'username'=>'schenn',
        'password'=>'monkey',
        ];

$install = new UserTable($config, true);


$install->init();

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
