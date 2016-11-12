<?php
/**
 * Created by PhpStorm.
 * User: schenn
 * Date: 11/11/16
 * Time: 4:08 PM
 */

namespace Peas\Errors;
use Exception;


/**
 * Error Exception for Schema
 *
 * When a Schema is told to build off invalid data, it should throw this error
 *
 * @category Exceptions
 *
 * @todo Expand like validation exception
 */
class SchemaException extends Exception {

}
