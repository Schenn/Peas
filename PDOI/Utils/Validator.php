<?php
namespace PDOI\Utils;
use Exception;

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
Class Validator {

    private $meta = [];

    public function SetMetaData($prop, $rules){
        // Preserve any already existing meta data
        if(!isset($this->meta[$prop])){
            $this->meta[$prop] = [];
        }


        $isPrimary = false;
        $isAuto = false;
        foreach($rules as $rule=>$values){
            if($rule == 'type'){
                switch($values){
                    case "int":
                    case "decimal":
                    case "double":
                    case "float":
                    case "real":
                    case "bit":
                    case "serial":
                        $this->meta[$prop]['type'] = 'numeric';
                        $this->meta[$prop]['max'] = (isset($rules['max'])) ?  $rules['max'] : pow(10, $rules['length'])-1;
                        if(isset($rules['min'])) {
                            $this->meta[$prop]['min'] = $rules['min'];
                        }
                        break;
                    case "bool":
                        $this->meta[$prop]['type'] = 'boolean';
                        break;
                    case "date":
                    case "time":
                    case "year":
                        $this->meta[$prop]['type']='date';
                        $this->meta[$prop]['format'] = $rules['format'];
                        break;
                    default:
                        $this->meta[$prop]['type']='string';
                        if(array_key_exists('length',$rules)){
                            $this->meta[$prop]['length'] = $rules['length'];
                        }
                        break;
                }
            }
            else if($rule == 'primaryKey' || $rule == 'auto'){
                if($rule == 'primaryKey')
                    $isPrimary = true;
                if($rule == 'auto'){
                    $isAuto = true;
                }
                if($isPrimary && $isAuto){
                    $this->meta[$prop]['fixed'] = true;
                }
            }else {
                $this->meta[$prop][$rule] = $values;
            }
        }
    }

    public function IsValid($name, &$value){
        if(array_key_exists('fixed',$this->meta[$name])) {
            // If the value is numeric and is within the min and max values of the type
            throw new validationException("$name is fixed and cannot be changed to $value", 5);
        }
        if($this->meta[$name]['type'] ==="numeric"){
            // If Numeric String
            if(is_string($value) && is_numeric($value)){
                // If the string contains a decimal, cast to float, else cast to int
                $value = (strpos($value, '.') > -1) ? (float)$value : (int) $value;
            }else if(is_bool($value)) {
                $value = (int) $value;
            } else {
                if(!is_numeric($value)) {
                    throw new validationException("$value is not numeric, number expected", 0);
                }
            }

            $max = $this->meta[$name]['max'];
            $min = (isset($this->meta[$name]['min'])) ? $this->meta[$name]['min'] : $max * -1;
            if (abs($value) <= $max && $value >= $min) {
                return true;
            } else {
                throw new validationException("$value falls outside of $name available range (" . ($this->meta[$name]['max'] * -1) . " to " . $this->meta[$name]['max'] . ")", 1);
            }
        }
        // If the type is a string
        elseif($this->meta[$name]['type'] === "string"){
            // If the string has a max length
            if(array_key_exists("length",$this->meta[$name])){
                $value = (string)$value;
                // If the string is less than the max length
                if(strlen($value) <= $this->meta[$name]['length']){
                    // Check against required characters
                    if(isset($this->meta[$name]['required_chars'])) {
                        foreach($this->meta[$name]['required_chars'] as $char){
                            if(strpos($value, $char) == -1){
                                throw new validationException("$value is missing required character $char", 6);
                            }
                        }
                    }
                    return true;
                }
                else {
                    throw new validationException("$value has too many characters for $name",2);
                }
            }
            // No maximum length
            else {
                return true;
            }
        }
        // If type is a boolean
        elseif($this->meta[$name]['type'] === "boolean"){
            if(is_bool($value)){
                return true;
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
                return true;
            }
            else {
                throw new validationException("$value not a date for $name",4);
            }
        }
        return true;
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

    public function hasRule($key){
        return array_key_exists($key, $this->meta);
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

}