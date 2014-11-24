<?php
namespace PDOI\Utils;
use BadMethodCallException, Exception, Iterator, JsonSerializable;
use Closure;

/**
* @author Steven Chennault Schenn@Mash.is
* @link: https://github.com/Schenn/PDOI Repository
*/


/**
* Class validationException
*
* A provided value failed to pass validation
*
* @package PDOI\Utils
* @Category Exceptions
 * @todo Add some error messages like in sqlSpinner
*/
class validationException extends Exception {

 public function __construct($message,$code, Exception $previous = null){
      parent::__construct($message, $code, $previous);
 }
}

/**
* Interface dynamoInterface
*
* Condense Iterator and JsonSerializable interfaces for dynamo to use
* @package PDOI\Utils
*/
interface dynamoInterface extends Iterator, JsonSerializable {

}

/**
* Class dynamo
*
* Dynamic anonymous object. Can be assigned anonymous functions which have be given access to the dynamo as '$this'.
* Uses the metadata provided by a schema at it's construction to validate assigned values.
*
* @see PDOI\Utils\schema
* @see PDOI\pdoITable::asDynamo Where Dynamos are made
*
* @package PDOI\Utils
*@todo Better handling of array properties for sets
*/
class dynamo implements dynamoInterface{
    /** @var array $old The previous value of a property */
    private $old = [];
    /** @var array $properties The current property values */
    private $properties = [];
    /** @var array $meta The metadata for the properties */
    private $meta = [];
    /** @var bool $useMeta Whether or not to validate incoming values. Default is true */
    private $useMeta = true;

    private $failSoft;

    /**
     * Create a Dynamo
     *
     * Creates a new dynamo with the given values and validation rules
     *
     * The parameters are optional, a dynamo can be built from nothing up
     *
     * @param array $values [columnName=>value, ..]
     * @param array $meta column meta data from the pdoITable
     *
     * @see PDOI\pdoITable
     */
    public function __construct($values = [], $meta = [], $failSoft = true){
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
            $this->old[$name] = $this->properties[$name];
            $this->properties[$name] =$value;
            if ($this->old[$name] === null) {
                $this->old[$name] = $this->properties[$name];
            }
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
                    $this->old[$name] = null;
                }
                // If we're validating values and this column has validation data
                if(($this->useMeta) && (isset($this->meta[$name]))){
                    // If the value can be changed
                    if(array_key_exists('fixed',$this->meta[$name])) {
                        // If the value is numeric and is within the min and max values of the type
                        throw new validationException("$name is fixed and cannot be changed to $value", 5);
                    }
                    if($this->meta[$name]['type'] ==="numeric"){
                        if(is_numeric($value)) {
                            if (abs($value) <= $this->meta[$name]['max'] && $value >= $this->meta[$name]['max'] * -1) {
                                $this->setProperty($name, (float)$value);
                            } else {
                                throw new validationException("$value falls outside of $name available range (" . ($this->meta[$name]['max'] * -1) . " to " . $this->meta[$name]['max'] . ")", 1);
                            }
                        } else {
                            throw new validationException("$value is a string, number expected", 0);
                        }
                    }
                    // If the type is a string
                    elseif($this->meta[$name]['type'] === "string"){
                        // If the string has a max length
                        if(array_key_exists("length",$this->meta[$name])){
                            $value = (string)$value;
                            // If the string is less than the max length
                            if(strlen($value) <= $this->meta[$name]['length']){
                                $this->setProperty($name, $value);
                            }
                            else {
                                throw new validationException("$value has too many characters for $name",2);
                            }
                        }
                        // No maximum length
                        else {
                            $value = (string)$value;
                            $this->setProperty($name, $value);
                        }
                    }
                    // If type is a boolean
                    elseif($this->meta[$name]['type'] === "boolean"){
                        if(is_bool($value)){
                            $this->setProperty($name, $value);
                        }
                        else {
                            throw new validationException("$name expects boolean value; not $value",3);
                        }
                    }
                    // If type is a Date
                    elseif($this->meta[$name]['type'] === "date"){
                        if(get_class($value) === "DateTime"){
                            if(isset($this->meta[$name]['format'])){
                                $value->format($this->meta[$name]['format']);
                            }
                            $this->setProperty($name, $value);
                        }
                        else {
                            throw new validationException("$value not a date for $name",4);
                        }
                    }

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
        if(array_key_exists($name, $this->meta)){
            unset($this->meta[$name]);
        }
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
            $this->meta[$var] = [];
            //sets validation type (numeric, boolean, string or date)
            switch($rules['type']){
                case "int":
                case "decimal":
                case "double":
                case "float":
                case "real":
                case "bit":
                case "serial":
                    $this->meta[$var]['type'] = 'numeric';
                    $this->meta[$var]['max'] = pow(10, $rules['length'])-1;
                    break;
                case "bool":
                    $this->meta[$var]['type'] = 'boolean';
                    break;
                case "date":
                case "time":
                case "year":
                    $this->meta[$var]['type']='date';
                    $this->meta[$var]['format'] = $rules['format'];
                    break;
                default:
                    $this->meta[$var]['type']='string';
                    if(array_key_exists('length',$rules)){
                        $this->meta[$var]['length'] = $rules['length'];
                    }
                    break;
            }

            if(isset($rules['default'])) {
                $this->meta[$var]['default'] = $rules['default'];
            }

            if(isset($rules['primaryKey']) && isset($rules['auto'])){
                $this->meta[$var]['fixed'] = true;
            }
            if(array_key_exists('required', $rules)){
                $this->meta[$var]['required'] = $rules['required'];
            }

        }
    }

    /**
     * Returns the validation rule of a property
     *
     * @param string $key The name of the property
     * @return mixed|bool The value of the property or false if the property doesn't have validation rules
     * @api
     *
     * @todo This should return null, not false if there's no validation rule
     */
    public function getRule($key){
        if(isset($this->meta[$key])){
            return($this->meta[$key]);
        }
        else {
            return(false);
        }
    }

    /**
     * Get all the validation rules
     *
     * @return array The dictionary of validation rules
     * @api
     */
    public function getRules(){
        return($this->meta);
    }

    /**
     * Forget validation rules for a property
     *
     * @param string $key The name of the property
     * @api
     */
    public function unsetRule($key){
        unset($this->meta[$key]);
    }

    /**
     * Forget all of the validation rules in the dynamo
     * @api
     *
     * @todo This should remove the validation rules for the columns, not the columns themselves from the dictionary
     */
    public function unsetRules(){
        $this->meta = [];
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

    /**
     * Get the previous value for a property
     *
     * @param string $key The name of the property to get the old value of
     * @return mixed
     * @api
     */
    public function oldData($key){
        return($this->old[$key]);
    }

}
?>