<?php
/**
 * Created by PhpStorm.
 * User: schen_000
 * Date: 11/19/2014
 * Time: 1:42 PM
 */
require_once("../autoload.php");
use PDOI\EmitterDatabaseHandler as PDOI;

class PDOITest extends PHPUnit_Framework_TestCase {

    protected static $config  = [
        'dbname'=>'pdoi_test',
        'username'=>'pdoi_test',
        'password'=>'QzMdPwx3p4UpL4Rq'
    ];

    // This information is just used for PDOITest. For deeper tests, see PDOITableTest, UserTableTest and ComplexTest
    protected static $primaryTableName = "pdoi_test_script";
    // primary_key, primary_string_data, primary_int_data, primary_int_foreignKey
    protected static $foreignTableName = "pdoi_test_script_foreign";
    // primary_key, foreign_string_data, foreign_int_data

    protected static $pdoi;

    public static function setUpBeforeClass(){
        self::$pdoi = new EmitterDatabaseHandler(self::$config);
    }

    public function testTableExists(){
        $this->assertTrue(self::$pdoi->tableExists(self::$primaryTableName), "Users table doesn't exist");
    }

    public function testTableDoesntExist(){
        $this->assertFalse(self::$pdoi->tableExists(self::$primaryTableName), "Users table exists");
    }

    /**
     * @depends testTableDoesntExist
     */
    public function testCreate(){
        $this->assertTrue(self::$pdoi->CREATE(self::$primaryTableName,
        ['primary_key'=>[],
                'primary_string_data'=>['type'=>'varchar','length'=>50],
                'primary_int_data'=>['type'=>'int']
        ]));
    }

    /**
     * @depends testTableDoesntExist
     */
    public function testCreateThrowOnInvalidField(){

    }

    /**
     * @depends testTableExists
     */
    public function testSelectFromSingleTable(){

    }

    /**
     * @depends testTableExists
     */
    public function testSelectFromSingleTableThrowOnInvalidArgument(){

    }
} 