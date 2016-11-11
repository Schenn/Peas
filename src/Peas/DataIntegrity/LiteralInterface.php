<?php
namespace Peas\DataIntegrity;
use Iterator, JsonSerializable;
/**
 * Created by PhpStorm.
 * User: Schenn
 * Date: 11/30/2014
 * Time: 2:28 PM
 */

/**
 * Interface LiteralInterface Combines Iterator and JsonSerializable for the Schema class
 *
 * @package PDOI\Utils
 */

interface LiteralInterface extends Iterator, JsonSerializable {

}