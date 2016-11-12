<?php
/**
 * Created by PhpStorm.
 * User: schenn
 * Date: 11/11/16
 * Time: 3:16 PM
 */

namespace Peas\Helpers;


class AggregateHelper
{
  /**
   * Constructs an aggregate function
   *
   * Some sql requires summing or counting or getting the min or max values. This method constructs that segment
   * of the sql query. This method does not return the spinner as it is not part of the api
   *
   * @param string $aggMethod "" | (sum | avg | count | min | max)
   * @param array $aggValues a list of column names
   *
   * @return string
   * @see SqlBuilder::SELECT
   *
   */

  public function aggregate($aggMethod, $aggValues = [], $alias = "")
  {
    $sql = '';
    $sql .= strtoupper($aggMethod) . "(";

    //if 'columnNames' is empty, * is used as the column
    $cNameCount = count($aggValues);
    $sql .= ($cNameCount === 0) ? "*" : implode(", ", $aggValues) . ")";

    // alias allows mysql to give a name to the value of the function result
    if (empty($alias)) {
      // Use the first column name or the method being used if no columns are given
      $alias = ($cNameCount > 0) ? $aggMethod . $aggValues[0] : $aggMethod;
    }

    $sql .= " AS " . $alias . " ";

    return $sql;
  }
}