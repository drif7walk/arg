<?php
session_start();

include("../desas.php");

$_SESSION["magicvar"] = rand(1000, 9999);

$conn = establish();

        $repid = "0";

        if (isset($_GET["repid"]))
               $repid= (is_numeric($_GET["repid"]) ? $_GET["repid"] : "0");

	$rep = "";
	foreach($conn->query('SELECT * FROM changelog ORDER BY id DESC LIMIT 3') as $row) {
		$rep .= "<div>";
		$rep .= str_replace("-","/",$row["date"]);
		$rep .= " ".$row["text"];
		$rep .= "</div>";
	}

	$__changelog__ = $rep;

	$s = "__posts__";
	$rep ="";

        $q = "";
        if ($repid == "0")
        {
            $q = 'SELECT * FROM `posts` WHERE `replyto`="0" ORDER BY `date` DESC LIMIT 25';
            $qr = $conn->prepare($q);
        }
        else
        {
            $q = 'SELECT * FROM `posts` WHERE (`replyto`=0 AND `id`=:rep) OR `replyto`=:replyto ORDER BY `id` ASC';
            $qr = $conn->prepare($q);
            $qr->bindValue(":replyto", $repid, PDO::PARAM_INT);
            $qr->bindValue(":rep", $repid, PDO::PARAM_INT);
        }
        
        $qr->execute();
        $r = $qr->fetchAll();

	foreach( $r as $row) {

		$rep .= '<div class="replybox">';

		if ($row["replyto"] == 0 && $repid == "0")
                        $rep .= '<span class="postid"><a href="index.php?repid='.$row["id"].'">[Ielikt]</a></span>';
                //else
                        //$rep .= '<span class="postid"><a name="'.$row["id"].'" href="#'.$row["id"].'">Ielikt</span>';

		// id
		$rep .= ' <span class="posthref"><a href="#'.$row["id"].'" name="'.$row["id"].'">#'. $row["id"] .'</a></span>';

                $st = 'SELECT * FROM `posts` WHERE `replyto`=:replyto';
                $qrq = $conn->prepare($st);
                $qrq->bindValue(":replyto", $row["id"], PDO::PARAM_INT);
                $qrq->execute();
                $rw = $qrq->rowCount();
		
		/* If not on main page, don't bother showing replies. */
		if (!isset($_GET["repid"]) && $rw > 0)
                	$rep .= '<span class="repnum">('.$rw.')</span>';

	
	        $rep .= ' <span><a href="./uploads/'.$row["imgsrc"].'">'.(strlen(s($row["imgalt"])) > 30 ? substr(s($row["imgalt"]), 0, 14).'[...]'.substr(s($row["imgalt"]), strrpos(s($row["imgalt"]), '.')) : s($row["imgalt"]))."</a></span>";
		

		$col = "";
		$rep .= '<table class="replytable"><tr><td>';
		/* Original filename */

		
		if (pathinfo($row["imgsrc"], PATHINFO_EXTENSION) != "webm" && $row["imgsrc"] != "0")
		{
			$rep .= '<a href="./uploads/'.$row["imgsrc"].'">'.'<img src="./uploads/'.$row["imgsrc"].'"  alt="'.s($row["imgalt"]).'" title="'.s($row["imgalt"]).'" class="image"/></a>';
		}
		else if (pathinfo($$row["imgsrc"], PATHINFO_EXTENSION) == "pdf")
		{
		}
		 /* Webm */ 
		else if (pathinfo($row["imgsrc"], PATHINFO_EXTENSION) == "webm")
		{

			
			/* Embed .webm */
			$rep .= '<video class="video" title="'.s($row["imgalt"]).'" controls><source src="./uploads/'.$row["imgsrc"].'" type="video/webm" ></video>';
			/* Stop embed webm */

		} else if ($row["imgsrc"] == "0") /* No picture */
		{
			$rep .= "";
		}
 //number of replis
                $rep .= '</td><td>'.nl2br(s(colorize($row["content"]))).'</td></tr>';
		$rep .= '</table>';


		$rep .= ' <span style="font-size: small; font-color: #545454">'.lvdate($row["date"]).'</span>';

		// tag
		//$rep .= '<span style="font-size: x-small; font-color: #545454">'.$row["tags"].'</span>';

		$rep .= '</div>';
	}	

	$__posts__ = $rep;

        $s = "__checkboxes__";
	$rep = "";
	

?>
<!DOCTYPE html>
<html lang="en">


<head>
	<meta http-equiv="Content-Type" content="text/html" charset="utf-8" />
	<title>Anonīmo Repliku Grēda</title>
	<link rel="icon" type="image/ico" href="fav.ico"/>
	<link href="./style/basic.css" rel="stylesheet" type="text/css">
</head>

<body>

<?php
if ($repid != "0")
{
?>
<a href="."><<</a>
<?php
}
?>
<form action="post.php" method="post" enctype="multipart/form-data">
<div class="postboxcontainer">
<table class="postbox"  >
	
	<tr>
		<td><textarea class="textarea" name="content" placeholder="Ne vairāk par 2048 burtiem."></textarea></td>
	</tr>
         <tr>
		<td style="font-size: small;"><?php echo $_SESSION["magicvar"]; ?><input type="text" name="alanpls" placeholder="pelmeni"></td>
	</tr>
	<tr>
	<td> <input type="file" name="img" /> <span style="font-size: xx-small; float: right;">16MB: WEBM; JPG; PNG; GIF; PDF<input type="submit" value="Likt"/>v1.9</span><input type="hidden" name="repid" value="<?php echo $repid; ?>"></td>
	</tr>
</table>
</div>
</form>

<div class="about">

<?php echo $__changelog__; ?>

</div>

<div class="postscontainer">


<?php echo $__posts__; ?>


</div>

</body>
</html>
