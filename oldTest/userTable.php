<?php
use Peas\EntityGeneration\EmitterDatabaseHandler as EmitterDatabaseHandler;
use Peas\EntityGeneration\EntityEmitter as EntityEmitter;

class userTable {
    protected $config;
    protected $debug;
    protected $conn;
    /** @var  EntityEmitter $userConn The connection to the user database table */
    protected $userConn;
    
    public function __construct($config, $debug){
        $this->config = $config;
        $this->debug = $debug;
    }
    
    // Before running methods off a table, those tables have to exist
    // Use EmitterDatabaseHandler to create tables. Use EntityEmitter to reference tables.
    public function init(){

       $this->conn = new EmitterDatabaseHandler($this->config, $this->debug);
        if(!$this->conn->tableExists("users")) {

            $this->conn->create('users', ['user_id' => [],
                'username' => ['type' => 'varchar', 'length' => 50],
                'hash_id' => ['type' => 'int']]);

            $this->conn->create('hashwords', ['hash_id' => [],
                'hash' => ['type' => 'varchar', 'length' => 350],
                'salt_id' => ['type' => 'int']]);
            $this->conn->create('salts', ['salt_id' => [],
                'salt' => ['type' => 'varchar', 'length' => 350],
                'round_id' => ['type' => 'int']]);
            $this->conn->create('rounds', ['round_id' => [],
                'rounds' => ['type' => 'int']]);
        }
        $this->userConn = new EntityEmitter($this->config, "users", $this->debug);
    }
    
    public function createUser($user, $pass){
        $userExists = ['where'=>[
            'username'=>$user
        ], 'limit'=>1];

        if(!$this->userConn->select($userExists)) {
           $this->userConn->setRelationship([
               'users.hash_id'=>'hashwords.hash_id',
               'hashwords.salt_id'=>'salts.salt_id',
               'salts.round_id'=>'rounds.round_id']);

           $newUser = $this->userConn->EmitEntity();
           $hash = $this->saltAndPepper($pass);

           $newUser->username = $user;
           $newUser->hash = $hash['hash'];
           $newUser->salt = $hash['salt'];
           $newUser->rounds = $hash['rounds'];

           $newUser->insert();

            $this->userConn->endRelationship();
           return($newUser);
        } else {
            return false;
        }
    }
    
    public function deleteUser($username){
        $this->userConn->setRelationship([
               'users.hash_id'=>'hashwords.hash_id',
               'hashwords.salt_id'=>'salts.salt_id',
               'salts.round_id'=>'rounds.round_id']);

        $userExists = ['where'=>['users'=>['username'=>$username]
        ], 'limit'=>1];

        if($user = $this->userConn->select($userExists)) {
            $user->delete();
            $this->userConn->endRelationship();
            return true;
        } else {
            return false;
        }
    }
    
    public function login($username, $pass){
        $this->userConn->setRelationship([
               'users.hash_id'=>'hashwords.hash_id',
               'hashwords.salt_id'=>'salts.salt_id',
               'salts.round_id'=>'rounds.round_id']);
        $userExists = ['where'=>['users'=>['username'=>$username]
           ], 'limit'=>1];
        if($user = $this->userConn->select($userExists)) {
            if($this->checkPassword($pass, $user->hash, $user->salt, $user->rounds)) {
                unset ($user->hash, $user->salt, $user->rounds);
                $this->userConn->endRelationship();
               return $user;
            } else {
                $this->userConn->endRelationship();
               return false;
            }
        } else {
            $this->userConn->endRelationship();
            return false;
        }
    }

    /**
     * Salt and pepper a password
     *
     * @param string $password The password to encode
     * @return array describing the password's validation information
     * @api
     */
    function saltAndPepper($password) {
        $salt = "";
        for($i=0; $i<17; $i++){
            $rnd = rand(0,11);
            $chars= ['a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z'];
            if($rnd <= 3){
                $charDigit = rand(0,9);
                $c = $charDigit;
            } elseif($rnd > 3 && $rnd <= 7){
                $charDigit = rand(0,25);
                $c = $chars[$charDigit];
            } else {
                $charDigit = rand(0,25);
                $c = ucfirst($chars[$charDigit]);
            }
            $salt .= $c;
        }
        $newSalt = hash('sha256', $salt);
        $hash = hash('sha256', $password.$newSalt);
        $max = rand(10, 16785);
        for ($i=0; $i<$max; $i++){
            $hash = hash('sha256', $hash . $newSalt);
        }
        return(['salt'=>$newSalt,'rounds'=>$max,'hash'=>$hash]);
    }

    /**
     * Validate a password against the salt and pepper method
     *
     * @param string $pass
     * @param string $hash
     * @param string $salt
     * @param int $rounds
     * @return bool success
     * @api
     */
    function checkPassword($pass, $hash, $salt, $rounds){
        $hashCheck = hash('sha256', $pass.$salt);
        for ($i=0; $i<$rounds; $i++){
            $hashCheck = hash('sha256', $hashCheck.$salt);
        }
        return($hashCheck === $hash);

    }
}
?>