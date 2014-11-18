<?php
require_once("PDOI/pdoiTable.php");
use PDOI\PDOI as PDOI;
use PDOI\pdoITable as pdoITable;

class userTable {
    protected $config;
    protected $debug;
    protected $conn;
    
    public function __construct($config, $debug){
        $this->config = $config;
        $this->debug = $debug;
       
    }
    
    // Before running methods off a table, those tables have to exist
    // Use PDOI to create tables. Use pdoITable to reference tables.
    public function init(){
       $this->conn = new PDOI($this->config, $this->debug);
       
       $this->conn->create('users',['user_id'=>[], 
                          'username'=>['type'=>'varchar','length'=>50],
                          'hash_id'=>['type'=>'int']]);
       $this->conn->create('hashwords',['hash_id'=>[],
                          'hash'=>['type'=>'varchar','length'=>350],
                          'salt_id'=>['type'=>'int']]);
       $this->conn->create('salts',['salt_id'=>[],
                          'salt'=>['type'=>'varchar','length'=>350],
                          'round_id'=>['type'=>'int']]);
       $this->conn->create('rounds',['round_id'=>[],
                          'rounds'=>['type'=>'int']]);
       return true;
    }
    
    public function createUser($user, $pass){
        $this->conn = new pdoITable($this->config, 'users', $this->debug);
        $userExists = ['where'=>[
            'username'=>$user
        ], 'limit'=>1];
        if(!$this->conn->select($userExists)) {
           $this->conn->setRelationship([
               'users.hash_id'=>'hashwords.hash_id',
               'hashwords.salt_id'=>'salts.salt_id',
               'salts.round_id'=>'rounds.round_id']);

           $newuser = $this->conn->Offshoot();
           $hash = $this->conn->saltAndPepper($pass);

           $newuser->username = $user;
           $newuser->hash = $hash['hash'];
           $newuser->salt = $hash['salt'];
           $newuser->rounds = $hash['rounds'];

           $newuser->insert();
           return($newuser);
        } else {
            return false;
        }
    }
    
    public function deleteUser($username){
        $this->conn = new pdoITable($this->config, 'users', $this->debug);
        $this->conn->setRelationship([
               'users.hash_id'=>'hashwords.hash_id',
               'hashwords.salt_id'=>'salts.salt_id',
               'salts.round_id'=>'rounds.round_id']);
        $userExists = ['where'=>['users'=>['username'=>$username]
        ], 'limit'=>1];
        if($user = $this->conn->select($userExists)) {
            $user->delete();
            return true;
        } else {
            return false;
        }
    }
    
    public function login($username, $pass){
        $this->conn = new pdoITable($this->config, 'users', $this->debug);
        $this->conn->setRelationship([
               'users.hash_id'=>'hashwords.hash_id',
               'hashwords.salt_id'=>'salts.salt_id',
               'salts.round_id'=>'rounds.round_id']);
        $userExists = ['where'=>['users'=>['username'=>$username]
           ], 'limit'=>1];
        if($user = $this->conn->select($userExists)) {               
            if($this->conn->checkPassword($pass, $user->hash, $user->salt, $user->rounds)) {
                unset ($user->hash);
                unset ($user->salt);
                unset ($user->rounds);
               return $user;
            } else {
               return false;
            }
        } else {
            return false;
        }
    }
        
    }
?>