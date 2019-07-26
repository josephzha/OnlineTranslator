<?php // setupusers.php
require_once 'translater_login.php';
$connection = new mysqli($hn, $un, $pw, $db);
if ($connection->connect_error) 
	die($connection->connect_error);
// $query = "CREATE TABLE users (
// forename VARCHAR(32) NOT NULL,
// surname VARCHAR(32) NOT NULL,
// username VARCHAR(32) NOT NULL PRIMARY KEY,
// password VARCHAR(32) NOT NULL
// )";
// $result = $connection->query($query);
if (isset($_POST['fname']) && isset($_POST['lname']) &&isset($_POST['uname']) && isset($_POST['password']))
{
	$forename = mysql_entities_fix_string($connection, $_POST['fname']);
	$surname = mysql_entities_fix_string($connection, $_POST['lname']);
	$username = mysql_entities_fix_string($connection, $_POST['uname']);
	$password = mysql_entities_fix_string($connection, $_POST['password']);
	$salt1 = "qm&h*";
	$salt2 = "pg!@";
	$token = hash('ripemd128', "$salt1$password$salt2");
	add_user($connection, $forename, $surname, $username, $token,"default");
	echo"reach redirect";
	header("Location: translater_main.php");
}
else
{
	echo <<<_END
	<form action="translater_setupusers.php" method="post"><pre>
	First Name: <input type="text" name="fname">
	Last Name: <input type="text" name="lname">
	User Name: <input type="text" name="uname">
	Password: <input type="text" name="password">
	<input type="submit" value="ADD RECORD">
	</pre></form>
_END;
}

$connection->close();

function add_user($connection, $fn, $sn, $un, $pw,$defaultDictionaryName) {
	$query = "INSERT INTO users VALUES('$fn', '$sn', '$un', '$pw','$defaultDictionaryName')";
	$result = $connection->query($query);
	if (!$result) die($connection->error);
}
function mysql_entities_fix_string($connection, $string)
{
	return htmlentities(mysql_fix_string($connection, $string));
}
function mysql_fix_string($connection, $string)
{
	if (get_magic_quotes_gpc()) $string = stripslashes($string);
	return $connection->real_escape_string($string);
}
?>