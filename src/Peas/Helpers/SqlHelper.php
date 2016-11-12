<?php
namespace Peas\Helpers;


class SqlHelper
{

  /**
   * Return the default values for different mysql types
   *
   * @return array
   */
  public static function typeDefaults(){
    return [
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
  }

  /**
   * Transforms the developer friendly comparison method argument into an sql friendly comparison string
   *
   * @param string $method the intended sql comparison method in developer friendly syntax
   * @return string the sql friendly comparison operator
   *
   * @see sqlSpinner::WHERE
   */
  public static function methodSpin($method){
    switch(strtolower(str_replace(" ", "",$method))){
      case "!=":
      case "not":
        return(" != ");
        break;
      case "<":
      case "less":
        return(" < ");
        break;
      case "<=":
      case "lessequal":
        return(" <= ");
        break;
      case ">":
      case "greater":
        return(" > ");
        break;
      case ">=":
      case "greaterequal":
        return(" >= ");
        break;
      case "like":
        return(" LIKE ");
        break;
      case "notlike":
        return(" NOT LIKE ");
        break;
      default:
        return(" = ");
        break;
    }
  }

}