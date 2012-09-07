<?php
/*	
	@copyright: Really Coding Group
	@Id: $Id: class.mysql.php 1596 2006-03-01 14:38:40Z paulsohier $
*/
class mysql{
	static public $query,$num,$result,$database,$db,$db2;
	/*                             
	This function connects to the database
	@param host
	@param username
	@param password
	@param database name
	@param make db or not
	@return connection id of false
	*/
	public static function connect($host,$name,$wachtwoord,$db,$makedb = false){
		if(empty($host) || empty($name) || empty($wachtwoord) || empty($db)){
			//return false;
		}
		self::$database = $db;//Used @ phpbb plugin

		self::$db = @mysql_connect($host,$name,$wachtwoord);
		if(self::$db === false){
			return self::$db;
		}else{
			if($makedb === true){
				$sql = "CREATE DATABASE ".$db;
				if(!@mysql_query($sql,self::$db)){
					st_die(SQL,"ERROR CREATING DB","",__LINE__,__FILE__,$sql);
					self::sql_close();
					return false;
				}
			}
			self::$db2 = @mysql_select_db($db,self::$db);
			if(self::$db2 === false){
				self::sql_close();
				return self::$db2;
			}else{
				 return self::$db;
			}
			
		}
	}
	/*
	This function closes database connection
	@return return of db
	*/
	static function sql_close(){
		if(self::$db){
			if(self::$result){
				self::sql_freeresult(self::$result);
			}
			return @mysql_close(self::$db);
		}
	}
	/*
	This function frees sql result
	@param mysql_query output
	@return result or false
	*/
	static function sql_freeresult($id = 0){
		if(!$id){
			if(self::$result){
				$id = self::$result;
			}else{
				return false;
			}
		}
		if($id){
			return mysql_free_result($id);
		}else{
			return false;
		}
	}
	/*
	This function gets the last insert id
	@return id or false
	*/
	static function sql_nextid(){
		if(self::$db){
			return mysql_insert_id(self::$db);
		}else{
			return false;
		}
	}
	/*
	This function uses the mysql function mysql_data_seek
	@param $rownumber
	@param mysql id
	@return result or false
	*/
	static function sql_rowseek($rownum, $id = 0){
		if(!$id)
		{
			$id = self::$result;
		}
		if($id){
			return mysql_data_seek($id, $rownum);
		}else{
			return false;
		}
	}
	/*
	This function returns the result in array
	@param mysql query output
	@return result or false
	*/
	static function sql_fetchrowset($id = 0){
		if(!$id)
		{
			$id = self::$result;
		}
		if($id){
			$result = array();
			while($rowset = mysql_fetch_array($id)){
				$result[] = $rowset;
			}
			return $result;
		}else{
			return array();
		}
	}
	/*
	This function returns one row of the result in array
	@param mysql query output
	@return result or false
	*/
	static function sql_fetchrow($id = 0,$method = ""){
		if(!$id){
			$id = self::$result;
		}
		if($id){
			if($method == ""){
				$row = mysql_fetch_array($id);
			}else{
				$row = mysql_fetch_array($id,$method);
			}
			return $row;
		}else{
			return false;
		}
	}
	/*
	This function returns the affected rows of the last query
	@return num rows or false
	*/
	static function sql_affectedrows(){
		if(self::$db){
			$result = mysql_affected_rows(self::$db);
			return $result;
		}else{
			return false;
		}
	}
	/*
	This function returns number of results of last query
	@param mysql query output
	@return number or false
	*/
	static function sql_numrows($id = 0){
		if(!$id){
			$id = self::$result;
		}
		if($id){
			$result = mysql_num_rows($id);
			return $result;
		}else{
			return false;
		}
	}
	/*
	This function submits a query
	@param a sql query
	@return result or false
	*/
	static function sql_query($sql){
		if($sql){
			if(is_array($sql)){
				for($i = 0; $i < count($sql);$i++){
					if(!self::sql_query($sql[$i])){
						die("Could not run sql query array.<br/>".$sql[$i]);
					}
				}
				return;
			}
			self::$query = $sql;
			self::$num++;
			self::$result = mysql_query($sql);
			if(!self::$result){
			 	die("Could not run sql query.<br/>".$sql);
			}
			return self::$result;
		}else{
			return false;
		}
	}
	/*
	This function returns the total amount of querys exicuted
	@return number
	*/
	static function sql_numqueries()
	{
		return $this -> num;
	}
	/*
	This function returns the mysql error message if there is one
	@return mysql_error()
	*/
	static function sql_error()
	{
		return mysql_error();
	}
}
?>
