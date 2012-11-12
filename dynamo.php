<?php

class validationException extends Exception {
     
     public function __construct($message,$code, Exception $previous = null){
          parent::__construct($message, $code, $previous);
     }
}

/*
 * Name: dynamo
 * Description: Dynamic object.  Can take anonymous functions as methods.
 * Takes: values = ['property'=>'value']
 */
class dynamo implements Iterator{
     private $properties = [];
     private $meta = [];
     
     public function __construct($values = []){
          foreach($values as $name=>$value){
               $this->properties[$name]=$value;
          }
     }
     
     public function __set($name, $value){
          try {
               if(is_callable($value)){
                    $this->$name = $value->bindTo($this); //bindTo($this) grants the function access to $this
               }
               else {
                    if(isset($this->meta[$name])){
                         if(!array_key_exists('fixed',$this->meta)){
                              if($this->meta[$name]['type'] ==="numeric"){
                                   if(abs($value)<=$this->meta[$name]['max'] && $value >= $this->meta[$name]['max'] * -1){
                                        $this->properties[$name] = $value;
                                   }
                                   else {
                                        throw new validationException("$value falls outside of $name available range", 1);
                                   }
                              }
                              elseif($this->meta[$name]['type'] === "string"){
                                   if(array_key_exists("length",$this->meta[$name])){
                                        $value = (string)$value;
                                        if(strlen($value) <= $this->meta[$name]['length']){
                                             $this->properties[$name] = $value;
                                        }
                                        else {
                                             throw new validationException("$value has too many characters for $name",2);
                                        }
                                   }
                                   else {
                                        $value = (string)$value;
                                        $this->properties[$name] = $value;
                                   }
                              }
                              elseif($this->meta[$name]['type'] === "boolean"){
                                   if(is_bool($value)){
                                        $this->properties[$name] = $value;
                                   }
                                   else {
                                        throw new validationException("$name expectes boolean value; not $value",3);
                                   }
                              }
                              elseif($this->meta[$name]['type'] === "date"){
                                   if(get_class($value) === "DateTime"){
                                        if(isset($this->meta[$name]['format'])){
                                             $value->format($this->meta[$name]['format']);
                                        }
                                        $this->properties[$name] = $value;
                                   }
                                   else {
                                        throw new validationException("$value not a date for $name",4);
                                   }
                              }
                         }
                         else {
                              throw new validationException("$name is fixed and cannot be changed to $value",5);
                              a:
                         }
                    }
                    else {
                         $this->properties[$name] = $value;
                    }
               }
          }
          catch (validationException $e){
               echo $e->getMessage();
               goto a;
          }
     }
     
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
     
     public function __isset($name){
          if(array_key_exists($name, $this->properties)){
               return(true);
          }
          else {
               return(false);
          }
     }
     
     public function __unset($name){
          unset($this->properties[$name]);
          if(array_key_exists($name, $this->meta)){
               unset($this->meta);
          }
     }
     
     public function __call($method, $args){
          try {
               if(isset($this->$method)){
                    if(is_callable($this->$method)){
                         $func = $this->$method;
                         $func($args);
                    }
                    else {
                         throw new BadMethodCallException("$method is not a callable function!");
                    }
                    
               }
               else {
                    throw new BadMethodCallException("$method is not set!");
               }
          }
          catch (BadMethodCallException $e){
               echo $e->getMessage();
          }
          catch (Exception $e){
               echo $e->getMessage();
          }
     }
     
     public function __toString(){
          return(json_encode($this->properties));
     }
     
     public function rewind(){
          reset($this->properties);
     }
     
     public function current(){
          return(current($this->properties));
     }
     
     public function key(){
          return(key($this->properties));
     }
     
     public function next(){
          return(next($this->properties));
     }
     
     public function valid(){
          return(key($this->properties) !== null);
     }
     
     public function setValidationRules($vRules = []){
          foreach($vRules as $var=>$rules){
               if(array_key_exists($var,$this->properties)){
                    $this->meta[$var] = [];
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
                    $this->meta[$var]['default'] = $rules['default'];
                    if(isset($rules['primaryKey']) && isset($rules['auto'])){
                         $this->meta[$var]['fixed'] = true;
                    }
               }
          }
     }
     
     public function getRule($key){
          if(isset($this->meta[$key])){
               return($this->meta[$key]);
          }
          else {
               return(false);
          }
     }
     
     public function unsetRule($key){
          unset($this->meta[$key]);
     }
     
     public function unsetRules(){
          $this->meta = [];
     }
     
}
?>