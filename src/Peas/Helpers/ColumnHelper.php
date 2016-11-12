<?php

namespace Peas\Helpers;

class ColumnHelper
{

  private $typeBasics = [
    'primary_key'=>'int',
    'ai'=>true,
    'bit'=>1,
    'tinyint'=>4,
    'smallint'=>6,
    'mediumint'=>9,
    'int'=>11,
    'bigint'=>20,
    'decimal'=>'(10,0)',
    'float'=>'',
    'double'=>'',
    'boolean'=>['tinyint'=>1],
    'date'=>'',
    'datetime',
    'timestamp'=>'',
    'time'=>'',
    'year'=>4,
    'char'=>1,
    'varchar'=>255,
    'binary'=>8,
    'varbinary'=>16,
    'tinyblob'=>255,
    'tinytext'=>255,
    'blob'=>'',
    'text'=>'',
    'mediumblob'=>'',
    'mediumtext'=>'',
    'longblob'=>'',
    'longtext'=>'',
    'enum'=>'',
    'set'=>''
  ];

  public function getSelectColumns($args){

    $sql = '';
    $aggHelper = new AggregateHelper();
    if (!empty($args['columns'])) {
      $i = 0;
      $cols = count($args['columns']);
      if (is_array($args['columns'])) {
        foreach ($args['columns'] as $index => $col) {
          if (!isset($col['agg'])) {
            $sql .= ($i !== $cols - 1) ? "$col, " : $col . ' ';
          } else {
            $alias = (is_string($index)) ? $index : "";
            foreach ($col['agg'] as $method => $columnNames) {

              $sql .= $aggHelper->aggregate($method, $columnNames, $alias);
            }
          }
          $i++;
        }
      } else if (is_string($args['columns'])) {
        $sql .= $args['columns'] . " ";
      }
    } else {
      $sql .= " * ";
    }

    return $sql;
  }

  public function getInsertColumns($args){
    $sql = "(".implode(", ", $args['columns']).") VALUES (";

    $columnCount = count($args['columns']);
    for ($i = 0; $i < $columnCount; $i++) {
      $sql .= ":" . $args['columns'][$i];
      if ($i !== $columnCount - 1) {
        $sql .= ", ";
      }
    }
    $sql .= ")";

    return $sql;
  }

  public function getSetColumns($args){
    $i = 0;
    $sql = "";
    $cCount = count($args['set']);
    foreach ($args['set'] as $column => $value) {
      $sql .= "$column = :set" . str_replace(".", "", $column);
      if ($i !== $cCount - 1) {
        $sql .= ", ";
      }
      $i++;
    }
    $sql .= " ";
    return $sql;
  }

  public function getCreateColumns($props){
    $sql = '';
    $i = 0;
    foreach ($props as $field => $prop) {
      $sql .= $field . ' ';
      $isPrimary = false;
      if ($i === 0) {
        $isPrimary = true;
        $type = (isset($prop['type'])) ? $prop['type'] : $this->typeBasics['primary_key'];
      } else {
        $type = $prop['type'];
      }
      $length = (isset($prop['length'])) ? $prop['length'] : $this->typeBasics[$type];
      $sql .= $type . '(' . $length . ') ';

      if ($isPrimary) {
        $sql .= "PRIMARY KEY ";
      }

      if (!isset($prop['null'])) {
        $sql .= "NOT NULL ";
      }

      if ($isPrimary && !isset($prop['noai'])) {
        $sql .= "AUTO_INCREMENT ";
      }

      if ($type === "timestamp") {
        $prop['default'] = 'CURRENT_TIMESTAMP ';

      }

      if (!$isPrimary && isset($prop['default'])) {
        $sql .= " DEFAULT = " . $prop['default'] . ' ';
      }

      if ($type === "timestamp") {
        if (isset($prop['update'])) {
          $sql .= "ON UPDATE CURRENT_TIMESTAMP ";
        }
      }
      if ($i < count($props) - 1) {
        $sql .= ", ";
      } else {
        $sql .= ")";
      }
      $i++;

    }
    return $sql;
  }

  public function whereColumnGenerator($column, $vCount, $concatWith){
    $sql = "";
    for ($vI = 0; $vI < $vCount; $vI++) {
      $sql .= ":where" . str_replace(".", "", $column) . $vI;
      if ($vI !== $vCount - 1) {
        $sql .= " $concatWith ";
      }
    }
    return $sql;
  }

  public function getWhereColumns($where, $statementMethod){
    $wI = 0;
    $whereCount = count($where);
    $sql = "";
    foreach ($where as $column => $value) {
      if (!is_array($value)) {
        $sql .= $column . " = :where" . str_replace(".", "", $column);
      } else {
        foreach ($value as $method => $secondValue) {
          if (!is_array($secondValue)) {
            $sql .= $column . SqlHelper::methodSpin($method) . ":where" . str_replace(".", "", $column);
          } else {
            $vCount = count($secondValue);
            switch (strtolower(trim($method))) {
              case "between":
                $sql .= $column . " BETWEEN ";
                $sql .= $this->whereColumnGenerator($column, $vCount, "AND");
                break;
              case "or":
                $sql .= $column . " =";
                $sql .= $this->whereColumnGenerator($column, $vCount, "OR");
                break;
              case "in":
                $sql .= $column . " IN (";
                $sql .= $this->whereColumnGenerator($column, $vCount, ",");
                $sql .= ")";
                break;
              case "notin":
                $sql .= $column . " NOT IN (";
                $sql .= $this->whereColumnGenerator($column, $vCount, ",");
                $sql .= ")";
                break;
            }
          }
        }
      }

      if ($wI !== $whereCount - 1) {
        $concatList = ["select", "delete", "update"];
        if (in_array($statementMethod, $concatList)) {
          $sql .= " AND ";
        }
      }
      $wI++;
    }

    return $sql;
  }

  public function getOrderByColumns($sort){
    $orderCount = count($sort);
    $i = 0;
    $sql = "";
    foreach ($sort as $column => $method) {
      $method = strtoupper($method);
      $sql .= $column . " " . strtoupper($method);
      if ($i < $orderCount - 1) {
        $sql .= ", ";
      }
      $i++;
    }
    return $sql;
  }

}