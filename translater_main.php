<?php 
require_once 'translater_login.php';
$connection = new mysqli($hn, $un, $pw, $db);
if ($connection->connect_error) 
	die($connection->connect_error);
session_start();
if (isset($_SESSION['username'])){
	$username = mysql_entities_fix_string($connection, $_SESSION['username']);
	$password = mysql_entities_fix_string($connection, $_SESSION['password']);
	$forename = mysql_entities_fix_string($connection, $_SESSION['forename']);
	$surname = mysql_entities_fix_string($connection, $_SESSION['surname']);

	echo "Welcome back $forename.<br>
	<br><br><br><br>";
	
if (isset($_FILES['textfile']) && isset($_POST['filename']))
{
	$name = mysql_entities_fix_string($connection, $_POST['filename']);
	$content = mysql_entities_fix_string($connection, file_get_contents($_FILES['textfile']['tmp_name']));
	if($_FILES['textfile']['type']=='text/plain'){
		$type = mysql_entities_fix_string($connection, $_FILES['textfile']['type']);
	}else{
		$type = '';
	}
	$filename = mysql_entities_fix_string($connection, $_FILES['textfile']['name']);
	if (empty($name)) {
        echo "Cannot submit an empty file";
    }
    if($type != 'text/plain'){
    	echo "Only supporting txt files";
	}
	if($type){
	$arrayOfWord = explode(",",$content);
	for($i = 0;$i<sizeof($arrayOfWord)/2;$i++){
		$index = 2*$i;
		$indexplusone = $index+1;
		$firstWord = strtolower($arrayOfWord[$index]);
		$secondWord = strtolower($arrayOfWord[$indexplusone]);
		$hash = hash('ripemd128', "$firstWord$secondWord$name$username");
		$query = "INSERT INTO dictionary (englishWord, foreignWord, dictionaryName, owner,hash) 
		VALUES ('$firstWord','$secondWord','$name','$username','$hash')";
		$result = $connection->query($query);
	}
	
	}
}

echo <<<_END
		<form action="" method="POST" enctype="multipart/form-data">
			Upload a dictionary: <br/>
			Language: <input type="text" name="filename"><br/>
            <input type="file" name="textfile" />
            <input type="submit"/>
        </form>
_END;

//$query = "SELECT dictionaryName FROM dictionary WHERE owner = '$username'";

$query = "SELECT DISTINCT dictionaryName FROM dictionary WHERE owner = '$username'";
$result = $connection->query($query);
if (!$result) die ("Database access failed: " . $connection->error);
$rows = $result->num_rows;

for ($j = 0 ; $j < $rows ; ++$j)
{
	$result->data_seek($j);
	$row = $result->fetch_array(MYSQLI_NUM);
	$curDic = $row[0];
	echo "<b>English</b>";
	echo str_repeat("&nbsp",12);
	echo "<b>$curDic</b></br>";
	$subQuery = "SELECT * FROM dictionary WHERE owner = '$username'AND dictionaryName = '$curDic' ";
	$subResult = $connection->query($subQuery);
	if (!$subResult) die ("Database access failed: " . $connection->error);
	$subRows = $subResult->num_rows;
	for($k = 0;$k<$subRows;$k++){
		$subResult->data_seek($k);
		$subRow = $subResult->fetch_array(MYSQLI_NUM);
		echo $subRow[0];
		$whitespace = max(25-2*strlen($subRow[0]),5);
		echo str_repeat("&nbsp;",$whitespace);
		echo $subRow[1];
		//echo "whitespace: $whitespace";
		echo "<br>";
		
	}
	echo"<br>";
}



$fuction_generate_list_of_dictionary = 'generate_list_of_dictionary';
echo <<<_END
	<form action="translater_main.php" method="POST" enctype="multipart/form-data">
	Translate Mode: 
	<select name = "translate_mode">
	<option>Translate Mode</option>
	<option value = "englishToOther">English To Other</option>
	<option value = "otherToEnglish">Other To English</option>
	</select></br>
	Dictionary: 
	{$fuction_generate_list_of_dictionary($rows,$result,"dictionary")}</br>
	Enter Text To Translate: <br/>
	<input type="text" name="inputtext"><br/>
	<input type="submit" name = "submit"/>
	</form>
_END;
if(isset($_POST['inputtext'])&&isset($_POST['submit'])&&isset($_POST['dictionary'])&&isset($_POST['translate_mode'])){
	$inputtext = mysql_entities_fix_string($connection, $_POST['inputtext']);
	$translate_mode=mysql_entities_fix_string($connection, $_POST['translate_mode']);
	$dictionary=mysql_entities_fix_string($connection, $_POST['dictionary']);
	if($translate_mode=='englishToOther'){
		$query = "SELECT foreignWord FROM dictionary WHERE englishWord = '$inputtext' && dictionaryName = '$dictionary'";
		$result = $connection->query($query);
		if (!$result) die ("Database access failed: " . $connection->error);
		elseif ($result->num_rows) {
			$row = $result->fetch_array(MYSQLI_NUM);
			$result->close();
			$toPrint = $row[0];
			echo "\"$inputtext\" in English translates to  \"$toPrint\" in \"$dictionary\"";
		}
		else{
			echo " \"$inputtext\" is not in Dictionary \"$dictionary\"";
		}

	}else if($translate_mode == 'otherToEnglish'){
		$query = "SELECT englishWord FROM dictionary WHERE foreignWord = '$inputtext' && dictionaryName = '$dictionary'";
		$result = $connection->query($query);
		if (!$result) die ("Database access failed: " . $connection->error);
		elseif ($result->num_rows) {
			$row = $result->fetch_array(MYSQLI_NUM);
			$result->close();
			$toPrint = $row[0];
			echo "<b>\"$inputtext\" in $dictionary translates to  \"$toPrint\" in English</b>";
		}
		else{
			echo " \"$inputtext\" is not in Dictionary \"$dictionary\"";
		}
	}


}
}
else{ echo "Please <a href='translater_authenticate.php'>click here</a> to log in.<br/>";
echo "Please <a href='translater_setupusers.php'>click here</a> to sign up.";
}

function destroy_session_and_data() 
{
	$_SESSION = array();
	setcookie(session_name(), '', time() - 2592000, '/');
	session_destroy();
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
function generate_list_of_dictionary($rows,$result,$title){
	$selectcode = '<select name = '.$title.'>';
	$selectcode .= '<option value = "default">Default</option>';
	$rows = $result->num_rows;
	for ($j = 0 ; $j < $rows ; ++$j)
{
	$result->data_seek($j);
	$row = $result->fetch_array(MYSQLI_NUM);
	$curDic = $row[0];
	$selectcode.='<option name = '.$curDic.'>'.$curDic.'</option>';
}
$selectcode .= '</select>';
return $selectcode;
}
?>