<?php
/**
 * Created by PhpStorm.
 * User: schenn
 * Date: 11/11/16
 * Time: 4:14 PM
 */

namespace Peas\DataIntegrity;


class TableSchema
{
  private $columns = [];
  private $masterKey = [];
  private $foreignKey = [];
  private $name = "";

  public function __construct($name, $columns)
  {
    $this->name = $name;
    $this->columns = $columns;
  }

  public function addForeignKey(){

  }

  public function addPrimaryKey(){

  }

}