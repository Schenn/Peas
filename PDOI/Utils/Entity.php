<?php
namespace PDOI\Utils;
include_once("Validator.php");
use BadMethodCallException, Iterator, JsonSerializable;
use Closure;
use PDOI\Utils\Validator;

/**
* @author Steven Chennault Schenn@Mash.is
* @link: https://github.com/Schenn/EmitterDatabaseHandler Repository
*/

/**
* Interface EntityInterface
*
* Condense Iterator and JsonSerializable interfaces for dynamo to use
* @package EmitterDatabaseHandler\Utils
*/
interface EntityInterface extends Iterator, JsonSerializable {

}

/**
* Class Entity
*
* Dynamic anonymous object. Can be assigned anonymous functions which have be given access to the dynamo as '$this'.
* Uses the metadata provided by a schema at it's construction to validate assigned values.
*
* @see PDOI\Utils\schema
* @see EmitterDatabaseHandler\pdoITable::EmitEntity Where Dynamos are made
*
* @package EmitterDatabaseHandler\Utils
*@todo Better handling of array properties for sets
*/
class Entity implements EntityInterface{

    /** @var array $properties The current property values */
    private $properties = [];

    /** @var bool $useMeta Whether or not to validate incoming values. Default is true */
    private $useMeta = true;

    private $failSoft;

    /** @var Validator $validator validates values against their meta data */
    private $validator;

    /**
     * Create a Dynamo
     *
     * Creates a new dynamo with the given values and validation rules
     *
     * The parameters are optional, a dynamo can be built from nothing up
     *
     * @param array $values [columnName=>value, ..]
     * @param array $meta column meta data from the EntityEmitter
     *
     * @see PDOI\pdoITable
     */
    public function __construct($values = [], $meta = [], $failSoft = true){
        $this->validator = new Validator();
        foreach($values as $name=>$value){
           $this->$name = $value;
        }
        $this->setValidationRules($meta);
        $this->failSoft = $failSoft;
     }

    /**
     * Actually set the property
     *
     * @param string $name The name of the property to set
     * @param mixed $value The value to assign to the property
     *
     * @internal
     */
    private function setProperty($name, $value){
        if($value !== $this->properties[$name]) {
            $this->properties[$name] =$value;
        }
    }

    /**
     * Set a value on a property
     *
     * Sets a property on the dynamo.  Verifies the incoming value against the table validation rules which
     * the dynamo is aware of.  Gently fails if value outside valid range. If there is no metadata for the column or
     *  $this->useMeta is false, then validation won't occur and the value will just be set.
     *
     * Determines if incoming property is a method call and if so, binds it to $this.
     *
     * If a value is supposed to be a string, the value will be converted to a string instead of failing unless it can't
     * be converted.
     *
     * __set is a magic method called whenever one attempts to assign a value to a property of the dynamo and that property isn't declared
     * in its class
     *      e.g. $dynamo->foo = bar will call $dynamo->__set(foo, bar)
     *
     * @param string $name The property to set
     * @param mixed|Closure $value The value to assign to the property.
     *
     * @throws validationException If a value fails validation.
     * @todo If the value is fixed, it should throw. Currently, the whole thing is in a big ol' if and throws which isn't required
     * @todo errors shouldn't be echoed, they should be logged
     */
    public function __set($name, $value){
        try {
            // if $value is an anonymous function{
            if(is_a($value, "Closure")){
                //bindTo($this) grants the Closure access to $this
                $this->$name = $value->bindTo($this);
            }
            else {
                // If this is a new property, create space for it
                if(!array_key_exists($name, $this->properties)){
                    $this->properties[$name] = null;
                }
                // If we're validating values and this column has validation data
                if(($this->useMeta) && $this->validator->hasRule($name) && ($this->validator->IsValid($name, $value))){
                    $this->setProperty($name, $value);
                }
                // No validation is being done, just assign it
                else {
                    $this->setProperty($name, $value);
                }
                // Continue from a throw here
                dynamo_continue:
            }
        }
        catch (validationException $e){
            if($this->failSoft) {
                echo $e->getMessage()."\n";
                goto dynamo_continue;
            } else {
                throw new validationException($e->getMessage(), $e->getCode());
            }
        }
    }

    /**
     * Retrieve a property
     *
     * If the property doesn't exist, it will trigger a php error.
     * __get is a magic method which is called when a property is referenced on an object and that property isn't declared
     * in its class
     *      e.g. dynamo->foo calls __get(foo)
     *
     * @param string $name The name of the property
     * @return mixed|null The property value
     */
    public function __get($name){
        if(array_key_exists($name, $this->properties)){
             return($this->properties[$name]);
        }
        else {
            $trace = debug_backtrace();
            trigger_error(
                'Undefined property via __get(): ' . $name .
                ' in ' . $trace[0]['file'] .
                ' on line ' . $trace[0]['line'],
                E_USER_NOTICE);
            return null;
        }
    }

    /**
     * Determine if a property exists
     *
     * @param string $name The name of the property
     * @return bool Whether or not the property exists
     * @todo Should this also return false if the property is null like isset does?
     */
    public function __isset($name){
        if(array_key_exists($name, $this->properties)){
            return(true);
        }
        else {
            return(false);
        }
    }

    /**
     * Removes a property and it's validation data
     *
     * @param string $name The name of the field to unset
     */
    public function __unset($name){
        unset($this->properties[$name]);
        $this->validator->unsetRule($name);
    }

    /**
     * Attempt to call an attached method.
     *
     * __call is a magic php method which is called whenever an object method is called and that function isn't declared
     * in its class.
     *      e.g. foo->bar() == foo->__call("bar", []);  foo->bar(a, b, c) == foo->__call("bar", [a,b,c]);
     *
     * @param string $method The method name
     * @param array $args The list of arguments passed into the method
     * @throws BadMethodCallException if the method doesn't exist or isn't callable.
     */
    public function __call($method, $args){
        if(isset($this->$method)){
            if(is_callable($this->$method)){
                $func = $this->$method;
                return ($func($args));
            }
            else {
                throw new BadMethodCallException("$method is not a callable function!");
            }
        } else {
            throw new BadMethodCallException("$method is not set!");
        }
    }

    /**
     * Converts the dynamo to a string
     *
     * Returns the dynamo properties as a json encoded string
     * @return string json encoded dictionary ($this->properties)
     */
    public function __toString(){
         return(json_encode($this->properties));
    }

    /**
     * Move to the start of the properties dictionary
     */
    public function rewind(){
         reset($this->properties);
    }

    /**
     * Return the current property value
     * @return mixed
     */
    public function current(){
         return(current($this->properties));
    }

    /**
     * Return the current property name
     * @return mixed
     */
    public function key(){
         return(key($this->properties));
    }

    /**
     * Move the iterator to the next property
     */
    public function next(){
         next($this->properties);
    }

    /**
     * Only Serialize the properties, not the old values or methods
     *
     * @return array $this->properties
     */
    public function jsonSerialize(){
         return($this->properties);
    }

    /**
     * Returns whether the current key of the properties dictionary is not null
     * @return bool
     */
    public function valid(){
         return(key($this->properties) !== null);
    }

    /**
     * Sets the metadata for the properties of the object.
     *
     * This meta data should represent the values which the property can safely take.
     * For example:  if the mysql database entry which this dynamo represents has a max length of 11 for a
     *        varchar field, the metadata should have a 'length' value which represents that limitation (11).
     *
     * @param array $validationRules The dictionary of rules which should have come from a Schema or been prepared in a similar manner
     *      [type=>"", length=>"", default=>"", "primaryKey"=>true, "auto"=>true] auto stands for auto-incrementing
     *      if primaryKey and auto are true, the field is set to 'fixed' meaning it cannot be changed or it will throw a validation error
     *      if the type is numeric, the length field is changed to a max value representation.
     *          (length of 1 = max values of 9 and -9, length of 2 = 99 and -99))
     * @api
     * @todo We should be able to set the validation rules without having already set properties. In that case, we should be assigning those properties and possibly assigning the default values
     * @todo If the property doesn't have a value and a default value was provided, set the property to the default
     */
    public function setValidationRules($validationRules = []){
        foreach($validationRules as $var=>$rules){
            $this->validator->SetMetaData($var, $rules);

        }
        $this->useMeta = true;
    }

    /**
     * Returns the validation class which contains the metadata for properties
     *
     * @param string $key The name of the property
     * @return mixed|bool The value of the property or false if the property doesn't have validation rules
     * @api
     *
     * @todo This should return null, not false if there's no validation rule
     */
    public function getValidator(){
        return($this->validator);
    }

    /**
     * Don't validate the incoming values for a while.
     *
     * Useful for sending a dynamo to the database for loading as primary key's could be fixed and thus would not be able to take a value
     * @api
     */
    public function stopValidation(){
        $this->useMeta = false;
    }

    /**
     * Resume validating incoming rules
     * @api
     * @todo validate the current property values against the meta data
     */
    public function startValidation(){
        $this->useMeta = true;
    }

}
?>