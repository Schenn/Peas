<?php
namespace Peas\Database;

use Peas\Errors\SqlBuildError as SqlBuildError;
use Peas\Helpers\AggregateHelper;
use Peas\Helpers\ColumnHelper;
use Peas\Helpers\JoinHelper;
use Peas\Helpers\SelectHelper;
use Peas\Helpers\SqlHelper as SqlHelper;

/**
 * SqlBuilder generates sql from an argument dictionary
 *
 * It's methods can be chained together until getSQL is called.
 *
 * @see SqlBuilder::getSQL
 * @todo Improve error handling
 * @todo Remove unused variables, clean up and format
 */
class SqlBuilder
{

  /** @var $method string some methods need to know what type of query is being constructed to guide their flow
   * @see sqlSpinner::WHERE
   */
  protected $method;

  /** @var $sql string the working sql string */
  protected $sql;

  /**
   * Begins constructing the sql for a select query
   *
   * SELECT columns FROM table
   *
   * @param array $args list of arguments
   *    REQUIRED
   *        'table'=>      'tableName' | ['tableName', 'tableName']
   *    OPTIONAL
   *        'columns'=>    ['columnName', (aggregateMethod=>['method'=>'values'])]
   *            If columns omitted, SELECT * is used instead
   *    VERY OPTIONAL
   *        'union'=>     true (UNION) Can be used in a subsequent call to select
   *             (e.g. spinner->SELECT($args)->SELECT($argsWithUnion)->getSQL)
   *             (ignored if priority is set -- per mysql docs: HIGH_PRIORITY cannot be used with SELECT statements that are part of a UNION. )
   *        'distinct'=>   'distinct' | 'distinctrow'
   *        'result'=>     'big' | 'small' (sql_big_result | sql_small_result)
   *             Requires either args['distinct'] or args['groupby'] to have value
   *        'priority'=>   true (HIGH_PRIORITY)
   *        'buffer'=>     true (SQL_BUFFER_RESULT)
   *        'cache'=>      true | false (SQL_CACHE | SQL_NO_CACHE)
   *
   * @throws SqlBuildError if no table information is given
   * @return SqlBuilder this
   *
   * @todo Throw SqlBuildError if the provided arguments have invalid values or are being used in invalid ways
   *
   * @api
   */
  function SELECT($args)
  {
    $this->method = 'select';
    $helper = new SelectHelper();
    // union is used to join multiple select statements into a single return value
    $this->sql = $helper->setSelectType($args);

    try {
      $this->sql.= $helper->setDistinct($args);
      $this->sql .= $helper->setSelectOptionals($args);
      $this->sql .=$helper->setSelectColumnsAndTables($args);

    } catch (SqlBuildError $e) {
      echo $e->getMessage();
    }

    return ($this);
  }


  /*
   * Begins constructing the sql as an INSERT query
   *
   * INSERT INTO table (columnName, columnName,...) VALUES (:columnName, :columnName, ...)
   *
   * @param array args
   *             REQUIRED
   *                  'table'=>      'tableName'  if missing, SqlBuilder throws SqlBuildError
   *                  'columns'=>    ['columnName', 'columnName']  if missing, SqlBuilder throws SqlBuildError
   *
   * @throws SqlBuildError if no table or columns provided
   *
   * @return SqlBuilder this
   */
  function INSERT($args)
  {
    $this->method = 'insert';

    try {
      if(empty($args['table']) || !((is_array($args['columns'])) && (isset($args['columns'][0])))){
        throw new SqlBuildError("Invalid Arguments", 1);
      }

      $this->sql = "INSERT INTO" . " " . $args['table'];
      $this->sql .= (new ColumnHelper())->getInsertColumns($args);

    } catch (SqlBuildError $e) {
      echo $e->getMessage();
    }
    return $this;
  }

  /*
   * Begins constructing an UPDATE mysql query
   *
   * UPDATE table
   * sql statement uses placeholders for pdo.  Be sure to match your value array appropriately.
   *
   * @param array args
   *             REQUIRED
   *                  'table'=>      'tableName'  if missing, SqlBuilder throws SqlBuildError
   *                  'set'=>    ['columnName'=>'value']  if missing, SqlBuilder throws SqlBuildError
   *
   * @throws SqlBuildError if no table or set argument provided
   * @return SqlBuilder this
   *
   * @api
   */
  function UPDATE($args)
  {
    $this->method = "update";
    try {
      if (isset($args['table'])) {
        $this->sql = "UPDATE " . $args['table'] . " ";
      } else {
        throw new SqlBuildError("Invalid Arguments", 1);
      }
      if (!isset($args['set'])) {
        throw new SqlBuildError("Invalid Arguments", 2);
      }

    } catch (SqlBuildError $e) {
      echo $e->getMessage();
    }
    return ($this);
  }

  /**
   * Adds the SET segment to an UPDATE query
   *
   * Appends the SET segment to the UPDATE query. Creates placeholders for pdo to bind a value to
   * UPDATE table SET column = :setColumn, column = :setColumn, ...
   *
   * @param array $args
   *         REQUIRED
   *             'set'=> ['columnName'=>value, 'columnName'=>value,...]]
   * @return SqlBuilder this
   * @api
   * @todo Error Catching
   */
  function SET($args)
  {
    if ($this->method == "update") {
      $this->sql .= "SET ";
      $this->sql .= (new ColumnHelper())->getSetColumns($args);
    }
    return ($this);
  }

  /*
   * Begins constructing the sql as a DELETE query
   *
   * DELETE FROM table
   *
   * @param array args
   *             REQUIRED
   *                  'table'=>      'tableName'  if missing, SqlBuilder throws SqlBuildError
   * @throws SqlBuildError if no table provided
   * @return SqlBuilder this
   * @api
   */
  function DELETE($args)
  {
    $this->method = "delete";
    try {
      if(empty($args['table'])){
        throw new SqlBuildError("Invalid Arguments", 1);
      }
      $this->sql = "DELETE FROM " . $args['table'] . " ";

    } catch (SqlBuildError $e) {
      echo $e->getMessage();
    }
    return ($this);
  }

  /*
   * Begins constructing the sql as a CREATE statement
   *
   * CREATE TABLE tableName IF NOT EXISTS (prop details, prop details, .., PRIMARY KEY (primary key));
   * Most of the properties have default types or lengths which can be found in this typeBasics
   *
   * @see SqlBuilder::typeBasics
   *
   * @param string tableName name of the table to create
   * @param array props
   *              'props'=>['primary_key_name'=>['type','length','noai','null'], ['field_name'=>['type','length','notnull',default],...]
   *              The first provided property is assigned as the primary key
   * @return SqlBuilder this
   * @api
   *
   * @todo Error Catching
   */
  function CREATE($tableName, $props)
  {
    $this->method = 'create';
    if (!empty($tableName)) {
      $this->sql .= "DROP TABLE IF EXISTS {$tableName};CREATE TABLE IF NOT EXISTS " . $tableName . " (";
      $this->sql .= (new ColumnHelper())->getCreateColumns($props);

    }
    return ($this);
  }

  /**
   * Constructs the sql as a DROP table query
   *
   * DROP TABLE IF EXISTS tableName
   * @param string $tableName Name of the table to drop
   * @return SqlBuilder this
   *
   * @api
   * @todo Error catching
   */
  function DROP($tableName)
  {
    $this->sql = "DROP TABLE IF EXISTS {$tableName}";
    return ($this);
  }


  /**
   * Generates the join segment of an sql query
   *
   * Generates one of the following
   *  .. JOIN ON table1.foreignKey = table2.primaryKey, ...
   *  .. JOIN USING col1, col2, col3 ...
   *
   * @param array $join [tableName, tableName]
   * @param array $condition ['on'=>['table1.foreignKey'=>'table2.primaryKey',..]] || ['using'=>['col1', 'col2', 'col3']]
   * @throws SqlBuildError if no join information provided
   *
   * @return SqlBuilder this
   *
   * @api
   */

  function JOIN($join = [], $condition = [])
  {
    $helper = new JoinHelper();
    if ($this->method == 'update' && !empty($join)) {
        $this->sql .= $helper->Update($join, $condition);
    } else if(!empty($join)) {
        $this->sql.= $helper->Select($join, $condition);
    } else {
      throw new SqlBuildError("Missing Join Arguments", 1);
    }

    return ($this);
  }

  /**
   * Appends the WHERE clause to the current sql query
   *
   * Generates the WHERE segment of the sql query
   *
   * @param array $where
   *                  columnName=>columnValue   generates
   *                       columnName = :columnName
   *                  columnName=>[method=>columnValue]     generates
   *                       columnName . (this->methodSpin(method)) . :where.columnName
   *                  columnName=>[method=>columnValues]    generates
   *                       method = 'between' = columnName BETWEEN :where.columnName.0 AND :where.columnName.1 (AND :where.columnName.2)
   *                       method = 'or' = columnName = :where.columnName.0 OR :where.columnName.1 (OR :where.columnName.2)
   *                       method = 'in' = columnName IN (:where.columnName.0, :where.columnName.1(,:where.columnName.2))
   *                       method = 'not in' = columnName NOT IN (:where.columnName.0, :where.columnName.1(,:where.columnName.2))
   *
   * @return SqlBuilder this
   *
   * @api
   *
   * @todo Error Catching
   */
  function WHERE($where)
  {

    if (!empty($where)) {
      $this->sql .= "WHERE ";
      $this->sql .= (new ColumnHelper())->getWhereColumns($where, $this->method);
      $this->sql .= " ";
    }
    return ($this);
  }


  /**
   * Generates the GROUPBY segment of the sql query
   *
   * GROUP BY columnName, columnName, ..
   * @param array $groupBy
   *         [columnName, columnName]
   * @return SqlBuilder this
   * @api
   */
  function GROUPBY($groupBy = [])
  {

    if (!empty($groupBy)) {
      $this->sql .= "GROUP BY ";
      $this->sql .= implode(", ", $groupBy) . " ";
    }

    return ($this);
  }

  /**
   * Appends HAVING clause to the sql statement.
   *
   * Must use aggregate method in HAVING.  DO NOT use HAVING to replace a WHERE clause.
   * Where does not handle sql aggregate functions.
   *
   * @param array $having
   *             aggMethod=>    aggregate method (see this->aggregate())
   *             'columns'=>    ['columnName', 'columnName']
   *             'comparison'=> [
   *                            'method'=>(see this->methodSpin())
   *                            'value'=>value to compare aggregate result to
   *                            ]
   *
   * @see sqlSpinner::methodSpin
   *
   * @return SqlBuilder this;
   *
   * @api
   * @todo Error Catching
   *
   */
  function HAVING($having = [])
  {

    //having = [aggMethod=>[columnNames]]
    //DO NOT USE HAVING TO REPLACE A WHERE
    //Having should only use group by columns for accuracy

    if (!empty($having)) {
      $this->sql .= "HAVING ";
      $method = $having['aggMethod'];
      $columns = (isset($having['columns'])) ? $having['columns'] : [];
      $comparison = $having['comparison']['method'];
      $compareValue = $having['comparison']['value'];
      $aggHelper = new AggregateHelper();
      $this->sql .= $aggHelper->aggregate($method, $columns);

      $this->sql .= $this->methodSpin($comparison) . $compareValue . " ";
    }

    return ($this);
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

  /**
   * Appends Order By sql clause to query
   *
   * you want to set sort to a column, array of columns or NULL for speed sake if groupby was appended to sql statement
   *
   * Sorting by NULL prevents mysql from attempting to sort a group by result set. This increases the performance of the query
   * @see http://dev.mysql.com/doc/refman/5.0/en/order-by-optimization.html
   *
   * ORDER BY columnName(, columnName)
   *
   * @param array|string $sort
   *    ['columnName'=>method (asc | desc)] |
   *    [[columnName=>method (asc | desc)], [columnName=>method (asc | desc)], ..] |
   *    'NULL' |
   *    null (the value)
   *
   * @return SqlBuilder this
   *
   * @api
   *
   * @todo Error Catching
   *
   */
  function ORDERBY($sort = [])
  {
    if (!empty($sort)) {
      $this->sql .= "ORDER BY ";
      if ($sort === 'NULL' || $sort === null) {
        $this->sql .= "NULL ";
      } else {
        $this->sql .= (is_array($sort)) ?(new ColumnHelper())->getOrderByColumns($sort) : $sort;
      }
      $this->sql .= " ";
    }
    return ($this);
  }

  /**
   * Appends LIMIT clause to sql statement.
   *
   * LIMIT n
   * @param int $limit the limit value
   *
   * @return SqlBuilder this
   * @api
   */
  function LIMIT($limit = null)
  {
    if (!empty($limit) && $limit > 0) {
      $this->sql .= "LIMIT " . $limit . " ";
    }
    return ($this);
  }

  /**
   * Generates a DESC sql query
   *
   * DESC is used to determine information about a table's Schema.
   * DESC table to get Schema information on the whole table
   * DESC table column to get Schema information on a column
   *
   * @param string $table the tableName
   * @param string $column the columnName
   *
   * @return SqlBuilder this
   * @api
   *
   * @todo Error Catching
   */
  function DESCRIBE($table, $column = "")
  {
    $this->sql = "DESC " . $table;
    if ($column !== "") {
      $this->sql .= " " . $column . " ";
    }
    return ($this);
  }

  /**
   * Returns the constructed sql query
   *
   * Stops generating the sql query and returns the constructed value of $this->sql
   *
   * @return string $this->sql
   * @api
   */
  function getSQL()
  {
    $sql = $this->sql;
    $this->sql = "";
    return ($sql);
  }
}