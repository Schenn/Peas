<?php
include_once("../PDOI/Utils/dynamo.php");
use PDOI\Utils\dynamo as dynamo;

/**
 * Class dynamoTest
 *
 * Unit Tests for PDOI\Utils\dynamo, An anonymous object with type validation and the capacity to take
 * delta functions.
 */
class dynamoTest extends PHPUnit_Framework_TestCase {

    /** @var  dynamo $dynamo The Dynamo object */
    private $dynamo;
    // Metadata follows mysql metadata rules
    private $meta = [
        "x"=>["type"=>"int", "length"=>3],
        "y"=>["type"=>"string", "length"=>5],
        "p"=>["type"=>"int", "length"=>5, "auto"=>true, "primaryKey"=>true]
    ];

    /**
     * Initialize and Give the dynamo some starting properties
     */
    function setUp()
    {
        $this->dynamo = new dynamo();
        $this->dynamo->x = 1;
        $this->dynamo->y = "string";
        $this->dynamo->p = 10;
    }

    /**
     * Dynamo properties can hold any value type
     */
    public function testDynamoCanSetValueOfAnyType(){

        $this->dynamo = new dynamo();
        // The properties don't even exist yet
        // isset on dynamo returns true if the property exists, regardless of value
        $this->assertFalse(isset($this->dynamo->x));
        $this->assertFalse(isset($this->dynamo->y));
        $this->assertFalse(isset($this->dynamo->b));
        $this->assertFalse(isset($this->dynamo->a));
        $this->assertFalse(isset($this->dynamo->d));

        // Set the properties
        $this->dynamo->x = 1;
        $this->dynamo->y = "string";
        $this->dynamo->b = true;
        $this->dynamo->a = [1,2];
        $this->dynamo->d = date("y-m-d");

        // Check the properties to see if they have the expected value
        $this->assertThat($this->dynamo->x, $this->identicalTo(1));
        $this->assertThat($this->dynamo->y, $this->identicalTo("string"));
        $this->assertThat($this->dynamo->b, $this->identicalTo(true));
        $this->assertThat($this->dynamo->a[0], $this->identicalTo(1));
        $this->assertThat($this->dynamo->a[1], $this->identicalTo(2));
        $this->assertThat($this->dynamo->d, $this->identicalTo(date("y-m-d")));
    }

    /**
     * Dynamo can erase values
     *
     * @depends testDynamoCanSetValueOfAnyType
     */
    public function testDynamoCanUnsetValue(){
        $this->assertThat($this->dynamo->x, $this->identicalTo(1));

        unset($this->dynamo->x);
        // The property doesn't exist anymore
        $this->assertFalse(isset($this->dynamo->x));

        $propExists = false;
        foreach($this->dynamo as $prop=>$val){
            if($prop == "x"){
                $propExists = true;
            }
        }

        $this->assertFalse($propExists);
    }

    /**
     * Dynamo can take closures and we can get data from those closures
     */
    public function testDynamoCanTakeClosures(){
        $this->dynamo->z = 2;
        $test = $this;
        $this->dynamo->add = function() use (&$test) {
            $test->assertInstanceOf("PDOI\Utils\dynamo", $this);
            $sum = $this->x + $this->z;
            return $sum;
        };

        $sum = $this->dynamo->add();
        $this->assertThat($sum, $this->identicalTo(3));
    }

    /**
     * BadMethodCall if you call a method that doesn't exist
     *
     * @expectedException BadMethodCallException
     */
    public function testDynamoThrowsBadMethod(){
        $this->dynamo->foo();
    }

    /**
     * Properties arn't methods, but methods can be a property
     *
     * BadMethodException when calling a property like a method
     *
     * @expectedException BadMethodCallException
     */
    public function testDynamoThrowsBadMethodContinued(){
        $this->dynamo->foo = 5;

        $this->dynamo->foo();
    }

    /**
     * Dynamo can validate its values against expected constraints
     */
    public function testDynamoCanValidate(){
        // Set Validation Rules
        $this->dynamo->setValidationRules($this->meta);
        // Dynamo's fail softly by default
        $this->dynamo->x = "frank";
        // Property still has original value
        $this->assertThat($this->dynamo->x, $this->identicalTo(1));

        // Strings convert incoming values
        $this->dynamo->y = 15;
        $this->assertThat($this->dynamo->y, $this->identicalTo("15"));

        // Primary Keys can't be altered from their original value
        $this->dynamo->p = 19;
        $this->assertThat($this->dynamo->p, $this->identicalTo(10));

    }

    /**
     * Dynamo can be set to fail hard, when it does it throws a validationException on invalid data
     * @expectedException PDOI\Utils\validationException
     */
    public function testDynamoThrowsValidationExceptionOnBadData(){
        // Dynamos can be set to fail hard by passing false as the third construction argument
        $this->dynamo=new dynamo([["x"=>1],["y"=>"beep"]],$this->meta, false);
        $this->dynamo->x = "frank";
        $this->assertThat($this->dynamo->x, $this->identicalTo(1));
    }

    /**
     * When working with dynamos that have been generated (and are empty), we may want to change the primary key
     * to allow the dynamo to be loaded by a database.
     */
    public function testDynamoCanFlipBetweenValidationStates(){
        $this->dynamo->setValidationRules($this->meta);
        $this->dynamo->p = 5;
        $this->assertThat($this->dynamo->p, $this->identicalTo(10));

        // We can stop validation so that we can set primary keys for loading against
        $this->dynamo->stopValidation();
        $this->dynamo->p = 5;
        $this->assertThat($this->dynamo->p, $this->identicalTo(5));

        // However, any property can be set to any value.
        $this->dynamo->x = "frank";
        $this->assertThat($this->dynamo->x, $this->identicalTo("frank"));

        // Currently, that value will be maintained even if we resume validation
        $this->dynamo->startValidation();
        $this->assertThat($this->dynamo->x, $this->identicalTo("frank"));

        // But they can only be 'set' to a valid value
        $this->dynamo->x = "jim";
        $this->assertThat($this->dynamo->x, $this->identicalTo("frank"));

        // They will maintain their last set value until a valid value is applied
        $this->dynamo->x = 10;
        $this->assertThat((int)$this->dynamo->x, $this->identicalTo(10));
    }

    /**
     * Dynamos allow you to iterate over their properties
     */
    public function testCanIterateOverDynamo(){
        foreach($this->dynamo as $prop=>$var){
            switch($prop) {
                case "x":
                    $this->assertThat($var, $this->identicalTo(1));
                    break;
                case "y":
                    $this->assertThat($var, $this->identicalTo("string"));
                    break;
                case "p":
                    $this->assertThat($var, $this->identicalTo(10));
                    break;
            }
        }
    }
}
