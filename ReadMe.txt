*****************************************
Author: Steven Chennault
Project: PDOI
Description:

As of 2011, mysql_functions() in php have begun
the process of being depreciated.  In the meantime
we get to use the fantastic PDO created to simplify
and secure the current system of database access and 
maintenance.  

This static class (and it's paired config object), serve as 
a simple way of interacting with and retrieving data from a 
database.

*****************************************

Currently, the PDOI only reliably interacts with MySQL database.  
Help contribute to the project and improve its versatility!


*******************
class PDOIConfig 

	Contains the connection data for the database of choice.
		- dbtype
		- dbhost
		- dbuser
		- dbpass
		- dbname

	Each of these has a setter/getter ready for it. 
		setType, setHost, setUser, setPass, setDB
		getType, getHost, getUser, getPass, getDB


The PDOIConfig object is set up this way to allow the controlling system the ability to 
access different databases using one config object.

*******************

static class PDOI

	protected static parameters
		- pdo (an instantiated PDO)
		- hasActiveTransaction (boolean to mark if a transaction has already begun to avoid conflicts)

		
	Public Static Methods
		- init 
			# Instantiates the PDO with data from the config object.  Run before doing any calls

		- SELECT
			( table -- the table in the database to connect to,
			  params -- array of fields in the table you are selecting from
			  where -- associative array of fields and values you are limiting your selection to (eg: ["id"=>"1"])
			  sort -- field to sort by and the order (eg: ["by"=>"userid","method"=>"ASC"])
			)
			# Returns the collection found as an associative array on success, false on fail
	
		- INSERT 
			( 
			  table -- the table to insert into
			  params -- array of fields with data
			  values -- associative array of fields and values (eg: ["username"=>"jimBob"])
			)
			# Returns the pdo statement execution result

		- UPDATE 
			(
			  table -- the table being updated
			  set -- associative array of fields=>values to update (eg: ["username"=>"marySue"])
			  where -- associative array of fields=>values to limit update to (eg: ["userid"=>"1"])
			)
			# Returns the pdo statement execution result
		
		- DELETE 
			(
			  table -- table to delete from
			  where -- associative array of fields=>values to limit deletion to (eg: ["username"=>"jimBob","username"=>"marySue"])
			)
			# Returns the pdo statement execution result

		- PREPARE
			(
			  query -- a sql query to be converted into a prepared statement
			)
			# Returns the pdo->prepare(query) result

******************

Any help or comments are appreciated!