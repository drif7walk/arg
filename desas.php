<?php
include("Parsedown.php");

function deleteOrphanFiles()
{
	/* Get a list of files from /b/uploads */
	$flarr = scandir("/var/www/html/b/uploads");

	/* Find filename in mysql */
	foreach ($flarr as &$imname)
	{
		$conn = establish();
		$sql = "SELECT * FROM `posts` WHERE `imgsrc`=:imagename";
		$q = $conn->prepare($sql);
		$q->bindValue(":imagename", $imname, PDO::PARAM_STR);
		$q->execute();
		$res = $q->fetch(PDO::FETCH_ASSOC);
	
		if (!$res)
			unlink($imname);		
	}

}

function raise404()
{
	header('HTTP/1.1 404 Not Found');
	die;
}

function isbanned()
{
	$conn = establish();

	$sql = "SELECT * FROM `bans` WHERE `ip`=:content";
	$q = $conn->prepare($sql);
	$q->bindValue(":content", $_SERVER["REMOTE_ADDR"], PDO::PARAM_STR);
	$q->execute();
	$result = $q->fetch(PDO::FETCH_ASSOC);
	$nofile = 0;

	if ($result)
	{
		/* First check if it has not expired. */
		if (time() > strtotime($result["unbandate"])) 
		{
			$sql = "DELETE FROM `bans` WHERE `ip`=:content";
			$q = $conn->prepare($sql);
			$q->bindValue(":content", $_SERVER["REMOTE_ADDR"], PDO::PARAM_STR);
			$q->execute();

			return false;
		}	

		return $result;
	}
	return false;
}

function s($str)
{
	//return htmlspecialchars($str, ENT_QUOTES);
	return $str;
}

/* Actually markdown */
function colorize($str)
{
	$Parsedown = new Parsedown();

	$changeme = explode("\n",$str);
	$composite = "";
	foreach ($changeme as $linestr)
	{
		$ls = $linestr;
		if (substr($ls, 0, 4) == "&gt;")
			$ls = '<span class="greentext">'.$ls.'</span>'; // Iffy - </span> is on new line.

		$composite .= $ls."\n";
	}
	return $Parsedown->text($composite);
}

function establish()
{
	/* Replace these values */
	try {
	$conn = new PDO("mysql:host=localhost;dbname=site;charset=utf8", "_MYSQLUSERNAME_", "_MYSQLPASSWORD_");
	} catch (PDOException $pe)
	{
		die("ERROR: " . $pe->getMessage());
	}
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

	return $conn;
}

function tick($addto) 
{
	/* This function is evil. */
	return;

	$conn = establish();	

	try {
	// First check for existing ip entries
	$sql = "SELECT * FROM `postluck` WHERE `ip`=:ipaddr";
	$q = $conn->prepare($sql);
	$q->bindValue(":ipaddr", $_SERVER["REMOTE_ADDR"], PDO::PARAM_STR);
	$q->execute();
	$result = $q->fetch(PDO::FETCH_ASSOC);
	$nofile = 0;
	if (!$result) //If does not exist then create.
	{
        	
		$sql = "INSERT INTO `postluck` (`id`, `ip`) VALUES (NULL, :ip)";
		$q = $conn->prepare($sql);
		$q->bindValue(":ip", $_SERVER["REMOTE_ADDR"], PDO::PARAM_STR);
		$q->execute();
	}

	$sql = "UPDATE `postluck` SET `_addto_`=`_addto_`+1 WHERE `ip`=:addr";
	$sql = str_replace("_addto_",$addto,$sql);	
	$q = $conn->prepare($sql);
	$q->execute(array(':addr'=>$_SERVER["REMOTE_ADDR"]));
	
	} catch (PDOEXCEPTION $e)
	{
		//XXX
		die("ERROR: " . $e->getMessage());
	}

}

function lvdate($date)
{
	$men = array("", "Janvārī", "Februārī", "Martā", "Aprīlī", "Maijā", "Jūnijā", "Jūlijā", "Augustā", "Septembrī", "Oktobrī", "Novembrī", "Decembrī");
	$darr = explode("-", $date);
	$datestr = explode(" ", $date)[1];
	$datestr .= ", " . $darr[0] . ". gada ";
	$darr = explode(" ", $date);
	$datestr .= explode("-",$darr[0])[2] .". " . $men[explode("-",$darr[0])[1]];
	return $datestr;
}



?>
