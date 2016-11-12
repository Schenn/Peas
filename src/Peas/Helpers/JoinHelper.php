<?php
/**
 * Created by PhpStorm.
 * User: schenn
 * Date: 11/11/16
 * Time: 3:36 PM
 */

namespace Peas\Helpers;


class JoinHelper
{
  public function Update($join, $condition){
    $sql = "";
    $block = [];
    $i = 0;
    foreach ($join as $tableMethod) {
      foreach ($tableMethod as $joinMethod => $tableName) {
        $block[$i] = strtoupper($joinMethod) . " " . $tableName;
        $i++;
      }
    }

    if (array_key_exists("on", $condition)) {
      //$this->sql .= "ON ";
      $i = 0;
      foreach ($condition['on'] as $rel) {
        $z = 0;
        foreach ($rel as $table => $column) {
          if (isset($block[$i])) {
            if ($z === 0) {
              $sql .= $block[$i] . " ON " . $table . "." . $column . "=";
              $z++;
            } else {
              $sql .= $table . '.' . $column . " ";
              $z = 0;
            }
          } else {
            break;
          }
        }
        $i++;
      }

    } elseif (array_key_exists("using", $condition)) {
      $sql .= "USING (";
      $using = $condition['using'];
      $sql .= implode(",", $using);
      $sql .= ") ";
    }
    return $sql;
  }

  public function Select($join, $condition){
    $sql = "";
    foreach ($join as $tableMethod) {
      foreach ($tableMethod as $joinMethod => $tableName) {
        $sql .= strtoupper($joinMethod) . " " . $tableName . " ";
      }

    }
    $sql .= " ";

    if (array_key_exists("on", $condition)) {
      $sql .= "ON ";
      $c = count($condition['on']);
      $i = 0;
      foreach ($condition['on'] as $rel) {
        $z = 0;
        foreach ($rel as $tableName => $columnName) {
          $sql .= $tableName . "." . $columnName;
          $sql .=($z < 1) ? "= " : " ";
          $z++;
        }
        if ($i < $c - 1) {
          $sql .= " AND ";
        }
        $i++;
      }

    } elseif (array_key_exists("using", $condition)) {
      $sql .= "USING (";
      $using = $condition['using'];
      $sql .= implode(",", $using);
      $sql .= ") ";
    }
    return $sql;
  }
}