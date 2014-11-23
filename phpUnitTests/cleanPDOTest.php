<?php
include_once("")
use PDOI\Utils\cleanPDO as cleanPDO;

class cleanPDOITest extends PHPUnit_Framework_TestCase {
    private $goodConfig = [
        'dbname'=>'unit_test',
        'username'=>'unit_test',
        'password'=>'wQeR56dAu8pFywFP'
    ];

    public function testCreation(){
        $cleanPDO = new cleanPDO($this->goodConfig);
        $this->assertInstanceOf("PDOI\Utils\cleanPDO", $cleanPDO);
    }

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
     * @depends testCreation
     * */
    public function testCanAccessData(){
        $cleanPDO = new cleanPDO($this->goodConfig);
        $stmt = $cleanPDO->query("SELECT 1 FROM `test_table` ");
        $stmt->execute();
        $res = $stmt->fetch();
        $this->assertTrue($res[0] == 1);
    }

    /**
     * @depends testCanAccessData
     * */
    public function testCanInsertData(){
        $cleanPDO = new cleanPDO($this->goodConfig);
        $cleanPDO->beginTransaction();
        $cleanPDO->query("INSERT INTO test_table VALUES(1)");
        $this->assertTrue($cleanPDO->commit());

        $stmt = $cleanPDO->query("SELECT * FROM `test_table` WHERE `key` = 1");
        $stmt->execute();
        $res = $stmt->fetch();
        $this->assertTrue($res['value'] == 1);

        $cleanPDO->beginTransaction();
        $cleanPDO->query("DELETE FROM `test_table`");
        $cleanPDO->commit();
    }

    /**
     * @depends testCanInsertData
     * */
    public function testCantBeginTransactionWhileInTransaction(){
        $cleanPDO = new cleanPDO($this->goodConfig);
        $cleanPDO->beginTransaction();
        $cleanPDO->query("INSERT INTO test_table VALUES(1)");

        $stmt = $cleanPDO->query("SELECT * FROM `test_table` WHERE `key` = 1");
        $stmt->execute();
        $res = $stmt->fetch();
        $this->assertTrue($res['value'] == 1);

        $nextTrans = $cleanPDO->beginTransaction();
        $this->assertFalse($nextTrans);
        if($nextTrans == true) {
            $cleanPDO->query("DELETE FROM `test_table`");
            $cleanPDO->commit();
        }
    }

    /**
     * @depends testCantBeginTransactionWhileInTransaction
     */
    public function testCanRollback(){
        $cleanPDO = new cleanPDO($this->goodConfig);
        $cleanPDO->beginTransaction();
        $cleanPDO->query("INSERT INTO test_table VALUES(1)");

        $stmt = $cleanPDO->query("SELECT * FROM `test_table` WHERE `key` = 1");
        $stmt->execute();
        $res = $stmt->fetch();
        $this->assertTrue($res['value'] == 1);

        $nextTrans = $cleanPDO->beginTransaction();
        $this->assertFalse($nextTrans);
        if($nextTrans == true) {
            $cleanPDO->query("DELETE FROM `test_table`");
            $cleanPDO->commit();
        } else {
            $this->assertTrue($cleanPDO->rollback());
        }
    }

    /**
     * @depends testCanAccessData
     */
    public function testThrowsPDOExceptionOnError(){
        $cleanPDO = new cleanPDO($this->goodConfig);
        try {

            $cleanPDO->beginTransaction();
            $cleanPDO->query("INSERT INTO testtable VALUES(1)");

        } catch(PDOException $e){
            $this->assertTrue($cleanPDO->rollback());
        }
    }
}