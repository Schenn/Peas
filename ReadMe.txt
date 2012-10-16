*****************************************
Author: Steven Chennault
Project: PDOI
Required Files: PDOI.php, sqlSpinner.php

*****************************************
	function instantiate(instance)
		- takes 'new instance' and returns 'instance'
		- used for chaining to eliminate the need for seperate lines in object creation and chain implementation.
			$x = instantiate(new object)->method1()->method2(argument)->etc()


	class sqlSpinner
		
		This class is a chaining class which constructs a sql statement through a set of function calls.  It is designed to be used by the pdoI
	class but can be used by anyone.  The sql query it generates is prepared to work with php's PDO system.  If you use this outside of the PDOI object
	make sure to check your bindings compatable via the method explained below.

		protected attributes:
			method
				- Guides the spinner through its process to ensure proper formatting of the query
			sql
				- the sql statement being generated.  call getSQL() to return the currently constructed sql phrase

		public functions:
			__construct
				takes associative array of pdo connection information
					- dns:  dbtype:dbname=dbname;host  (mysql:dbname=pdoi_tester;localhost)
					- username: database username
					- password: associated password
					- driver_options: array of pdo driver options ([PDO::ATTR_PERSISTANT => true])


			SELECT
				takes associative array of arguments
				Required:
					- table: table to select from
				Optional:
					- columns: array of column names, if unset * is used
					- distinct: distinct or distinctrow
					- result: big or small; Used with Distinct or groupby to specify large or small returns
					- priority: Sets the select to high priority; HIGH_PRIORITY sql select option
					- buffer: Enables SQL_BUFFER_RESULT sql select option
					- cache: true or false; Sets SQL_CACHE and SQL_NO_CACHE sql select options

				Description:
					- Sets the method to 'select', this tells the Where method to use ' AND ' instead of ', ', 
					- 'SELECT ['column1','column2','column3'] || * from table
					- Returns sqlSpinner object

			INSERT
				Takes associative array of arguments
					- table: the table to insert into
					- columns: array of column names

			WHERE
				takes associate array of arguments 
					comparisonMethod is trimmed and converted to lowercase so you can type it how you prefer
						column=>value
							- "column = :column"
						column=>['comparisonMethod'=>comparisonValue]
							- 'not': column != :column
							- 'like': column LIKE :column
							- 'not like': column NOT LIKE :column
							- 'less': column < :column
							- 'less equal': column <= :column
							- 'greater' : column > :column
							- 'greater equal': column >= :column
						column=>['comparisonMethod'=>comparisonValueArray]
							- between : column BETWEEN :column.comparisonValueArrayIndex AND :column.NextComparisonValueArrayIndex (AND :column.NextComparisonValueArrayIndex)
							- or : column = :column.comparisonValueArrayIndex OR :column.NextComparisonValueArrayIndex (OR :column.NextComparisonValueArrayIndex)
							- in : column IN (:column.comparisonValueArrayIndex, :column.NextComparisonValueArrayIndex (, :column.NextComparisonValueArrayIndex))
							- not in : column NOT IN (:column.comparisonValueArrayIndex, :column.NextComparisonValueArrayIndex (, :column.NextComparisonValueArrayIndex))

					- appends ' AND ' or ', ' depending on method set by the starter method
					- returns the spinner object		
			
			ORDERBY
				takes associative array of arguments
					sortColumn=>sortMethod
						sortMethod - asc || desc || ASC || DESC
						' ORDER BY sortColumn sortMethod(,sortColumn sortMethod)
			
			getSQL
				clears and returns the current sql query

	class cleanPDO
		
		This class offers a pdo with a safer failure message and safer transaction management

		protected attributes:
			hasActiveTransaction
				- Flag for if pdo is in a transaction
		
		public functions
			beginTransaction
				- Determines if a transaction is occuring and if not, begins a new transaction
			commit
				- Commits the current transaction and clears the transaction flag
			rollback
				- rolls back the current transaction and clears the transaction flag

	class PDOI
		
		This is the class to use to simplify your interactions with the pdo.  It handles failures safely and greatly reduces the amount of code that 
	YOU need to work with the pdo.  Takes 2 arguments, an associative array containing the config information for the cleanPDO and a boolean to set the
	debug flag.  ($pdoI = new PDOI($configArray, false))
		
		protected attributes:
			pdo
				- cleanPDO
			debug (alpha only)
				- boolean that determines whether to display the sql statement and arguments for troubleshooting

		public functions
			SELECT
				- Argument Array:
				Required:
					table=>name of table you are selecting from
				optional:
					columns=>array of column names in the table you wish to select ['','']
					where=>takes an array of [column=>comparisonValue] or [column=>[comparisonMethod, comparisonValue]] 
						or [column=>[comparisonMethod, comparisonValueArray]] depending on what method and condition you are trying to set.
						See the sqlSpinner class above to determine how to set 'comparisonMethod'.
					orderby=>array of column=>methods ('id'=>'asc')
				Does:
					Runs the argument array through the sqlSpinner to generate a proper sql query for the pdo, extracts the
					'where' conditionals and binds them to placeholders for the pdo object, then executes the query with the placeholders.
				Success:
					Returns the result set in associate array form
				Failure:
					Returns False
		
			INSERT
				- Argument Array
				Required:
					table=>name of the table you are inserting into
					columns=>array of column names
					values=>associative array of column=>value pairings  OR
						Array of associative array of column=>value pairings
				Does:
					First it runs the arguments through the sqlSpinner which generates the prepared INSERT statement then begins a transaction
						with the cleanPDO for safety.
					if the values are a associative array of column=>value pairings it
						prepares a value argument with the prepared column names and executes the insert
					or if the values are an array of associative arrays then
						([0=>[column=>value,column=>value,column=>value], 1=>[column=>value,column=>value],etc) 
						//note that 1 has a different number of fields than 0.  If your table should have optional fields, 
							you can leave out the respective columns
						it binds the placeholders in the query to variable variables named for the column and runs through the different
						sets of value pairings, executing the insert and resetting the variable variables to null.
				Success:
					Values inserted into the table.  Returns true.
				Failure:
					transaction is rolled-back (data being input is lost but no corrupt data goes into the table)
					failure message displayed
					returns false

			queue
				-associative array of methods and their related argument arrays.  ['select'=>['table'=>'']]
				Does:
					Begins a transaction with the cleanPDO
					goes through the list of instructions and calls the named method with the attached array of arguments
					When the queue is done running, it commits the transaction
				Success:
					Database operations completed successfully,
					returns true
				Failure:
					Transaction Rollback
					Displays failure
					returns false
