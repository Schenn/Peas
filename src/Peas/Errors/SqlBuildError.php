<?php

namespace Peas\Errors;
use Exception;

/**
* Error Exception for SqlBuilder
*
* When sql is malformed or missing data, an SqlBuildError is thrown
*
* @category Exceptions
*/

class SqlBuildError extends Exception {

  protected $errorList = [
    "Invalid Column data for Insert Spinning.",
    "Missing Table Name",
    "Missing 'set' data for Update Spinning"
  ];

  public function __construct($message,$code, Exception $previous = null){
    $message .= " SqlBuilder ERROR: ".$code.": ".$this->errorList[$code];
    parent::__construct($message, $code, $previous);
  }
}