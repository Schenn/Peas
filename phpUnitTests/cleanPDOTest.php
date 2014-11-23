<?php
include_once("..\PDOI\Utils\cleanPDO.php");
use PDOI\Utils\cleanPDO as cleanPDO;

/**
 * Class cleanPDOITest
 *
 * Unit Tests for PDOI\Utils\cleanPDO, the 'safe' pdo
 */
class cleanPDOITest extends PHPUnit_Framework_TestCase {
    // Disable the persistent nature of cleanPdo due to the unique nature of the test environment.
    // Lots of new pdos in this test and it was leading to a crash in php
    private $goodConfig = [
        'dbname'=>'unit_test',
        'username'=>'unit_test',
        'password'=>'wQeR56dAu8pFywFP',
        'driver_options'=> [PDO::ATTR_PERSISTENT => false]
    ];

    /**
     * Ensure we can create a new cleanPDO with a good config
     */
    public function testCreation(){
        $cleanPDO = new cleanPDO($this->goodConfig);
        $this->assertInstanceOf("PDOI\Utils\cleanPDO", $cleanPDO);
    }

    /**
     * Ensure that attempting to create a new cleanPDO with a bad config throws an Exception
     */
    public function testCreationFails(){
        try {
            $cleanPDO = new cleanPDO([
                'username'=>'unit_test',
                'password'=>'wQeR56dAu8pFywFP'
            ]);
            $this->assertTrue(false);
        }catch(Exception $e){
            $this->assertTrue(true);
        }

        try {
            $cleanPDO = new cleanPDO([
                'dbname'=>'unit_test'
            ]);
            $this->assertTrue(false);
        }catch(Exception $e){
            $this->assertTrue(true);
        }
    }

    /**
     * Ensure the cleanPDO can insert and retrieve the data it inserted into the database
     *
     * @depends testCreation
     * */
    public function testCanInsertAndRetrieveData(){
        $cleanPDO = new cleanPDO($this->goodConfig);
        $cleanPDO->beginTransaction();
        $cleanPDO->query("INSERT INTO test_table VALUES(null, 1)");

        $id = $cleanPDO->lastInsertId();
        $this->assertTrue($id != 0);
        $stmt = $cleanPDO->query("SELECT * FROM `test_table` WHERE `key` = $id");

        $this->assertTrue($cleanPDO->commit());
        $stmt->execute();
        $res = $stmt->fetch();
        $this->assertTrue($res['value'] == 1);

    }

    /**
     * @depends testCanInsertAndRetrieveData
     * */
    public function testCantBeginTransactionWhileInTransaction(){
        $cleanPDO = new cleanPDO($this->goodConfig);
        $cleanPDO->beginTransaction();
        $cleanPDO->query("INSERT INTO test_table VALUES(null, 1)");

        $stmt = $cleanPDO->query("SELECT * FROM `test_table` WHERE `value` = 1 LIMIT 1");
        $stmt->execute();
        $res = $stmt->fetch();
        $this->assertTrue($res['value'] == 1);

        $this->assertFalse($cleanPDO->beginTransaction());
    }

    /**
     * @depends testCantBeginTransactionWhileInTransaction
     */
    public function testCanRollback(){
        $cleanPDO = new cleanPDO($this->goodConfig);
        $cleanPDO->beginTransaction();
        $cleanPDO->query("INSERT INTO test_table VALUES(null, 1)");

        $stmt = $cleanPDO->query("SELECT * FROM `test_table` WHERE `value` = 1 LIMIT 1");
        $stmt->execute();
        $res = $stmt->fetch();
        $this->assertTrue($res['value'] == 1);

        $nextTrans = $cleanPDO->beginTransaction();
        $this->assertFalse($nextTrans);
        $this->assertTrue($cleanPDO->rollback());

    }

    /**
     * @depends testCreation
     * @expectedException PDOException
     * */
    public function testThrowsPDOExceptionOnError(){
        $cleanPDO = new cleanPDO($this->goodConfig);
        $cleanPDO->beginTransaction();
        $cleanPDO->query("INSERT INTO `testtable` VALUES(null, 1)");
        $this->assertTrue($cleanPDO->rollback());
    }

    /**
     * @depends testCanInsertAndRetrieveData
     * */
    public function testCanDestroyData(){
        $cleanPDO = new cleanPDO($this->goodConfig);
        $cleanPDO->beginTransaction();
        $cleanPDO->query("DELETE FROM `test_table` WHERE `value` = 1");
        $this->assertTrue($cleanPDO->commit());
    }
}