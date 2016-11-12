<?php
/**
 * Created by PhpStorm.
 * User: schenn
 * Date: 11/11/16
 * Time: 3:08 PM
 */

namespace Peas\Helpers;


use Peas\Errors\SqlBuildError;

class SelectHelper
{
  public function setSelectType($args){
    return (isset($args['union']) && !empty($this->sql) && substr($this->sql, 0, 6) == "SELECT") ? " UNION SELECT " : "SELECT ";
  }

  public function setDistinct($args){
    if (isset($args['distinct'])) {
      $distinct = strtoupper($args['distinct']);

      $sql= ($distinct !== 'ALL')? $distinct . " " : '';

      if (isset($args['result'])) {
        $sql.=$this->setResultSize($args);
      }
      return $sql;
    }
    return '';
  }

  protected function setResultSize($resultSize)
  {
    $results = [
      "BIG"=>" SQL_BIG_RESULT ",
      "SMALL"=>" SQL_SMALL_RESULT "
    ];
    $resultSize = strtoupper($resultSize);

   return (array_key_exists($resultSize, $results)) ? $results[$resultSize] : '';

  }

  public function setSelectOptionals($args){
    $sql = '';
    if (isset($args['groupby']) && isset($args['result'])) {
      $sql .= $this->setResultSize($args['result']);
    }

    if (isset($args['priority']) && !isset($args['union'])) {
      $sql .= " HIGH_PRIORITY ";
    }

    if (isset($args['buffer'])) {
      $sql .= " SQL_BUFFER_RESULT ";
    }

    if (isset($args['cache'])) {
      $sql .= ($args['cache'] === true) ? "SQL_CACHE " : "SQL_NO_CACHE ";
    }

    return $sql;
  }

  public function setSelectColumnsAndTables($args){
    if (!isset($args['table'])) {
      throw new SqlBuildError("Invalid Arguments", 1);
    }
    $sql =(new ColumnHelper())->getSelectColumns($args);
    $sql .= "FROM ";
    $sql .= (is_array($args['table'])) ? implode(", ", $args['table']) : $args['table'];
    $sql .= " ";
    return $sql;
  }
}