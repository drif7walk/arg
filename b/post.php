<?php
//bpuneA3WRxpect3d
session_start();

include("../desas.php");

deleteOrphanFiles();

$ban = isbanned();
if ($ban != false)
{
	?>
	Diemžēl esi izlikts.<br/>
	Iemesls: <?php echo $ban["reason"]; ?></br>
	Vari mēģināt atgriezties <?php echo lvdate($ban["unbandate"]); ?>.
	<?php
	die();
}

if (empty($_POST))
{
	die("Kā tu šeit nonāci?");
}

/* CAPTCHA. FIX THIS LATER */
if (true)
{
$cap = $_SESSION["magicvar"];
unset($_SESSION["magicvar"]);
if ($_POST["alanpls"] != $cap || !isset($_POST["content"]) || is_null($_POST["content"]))
{
	//tick("captcha");
	die("Vai nu tu nepareizi ierakstīji captcha, vai arī tev ir atspējoti 'cookies', tādēļ tiec uzskatīts par robotu.");
}
}

/* Get input from boxes */
$ip = $_SERVER["REMOTE_ADDR"];
$content = htmlentities($_POST["content"]);
$hidden = 0;
$fname = 0;
$die=0;
$tags = "##"; //$_POST["tags"]; //Dangerous - is not checked for injections
$replyto = (is_numeric($_POST["repid"]) ? $_POST["repid"] : "0");


try {
	$conn = new PDO("mysql:host=localhost;dbname=site;charset=utf8", "board", "bpuneA3WRxpect3d");
} catch (PDOException $pe)
{
	die("ERROR: " . $pe->getMessage());
}

$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

// Update parent row
if ($replyto != "0")
{
/* Check if parent row repto is 0, if not, then the user is trying to reply to a reply. */

$sql = "UPDATE `posts` SET `date`=now() WHERE `id`=:repto";
$q = $conn->prepare($sql);
$q->bindValue(":repto", $replyto, PDO::PARAM_INT);
$q->execute();
}

//Check spam
if (!empty($content))
{
$sql = "SELECT * FROM `posts` WHERE `content`=:content";
$q = $conn->prepare($sql);
$q->bindValue(":content", colorize($content), PDO::PARAM_STR);
$q->execute();
$result = $q->fetch(PDO::FETCH_ASSOC);
$nofile = 0;
if ($result)
{
	tick("dupepost");
	die("Tava replika ir spams.");
}
}
//End check spam

//Begin 
try {
   
    // Undefined | Multiple Files | $_FILES Corruption Attack
    // If this request falls under any of them, treat it invalid.
    if (
        !isset($_FILES['img']['error']) ||
        is_array($_FILES['img']['error'])
    ) {
        throw new RuntimeException('Invalid parameters.');
	$die=1;
    }

    // Check $_FechoILES['upfile']['error'] value.
    switch ($_FILES['img']['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            $nofile = 1;
            throw new RuntimeException('No file sent.');
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
	    $die=1;
	    tick("filetoobig");
            throw new RuntimeException('Exceeded filesize limit.');
        default:
	    $die=1;
            throw new RuntimeException('Unknown errors.');
    }
	
    // Detect barbarians --- WTF???
    /* No idea what the f this is supposed to be...
    $sUser = 'my_username01';
	$aValid = array('-', '_');

	if(!ctype_alnum(str_replace($aValid, '', $sUser))) {
	    die("Nebūs.");
	} 
    */

    // Webm  maximum size in bytes - max is 16mb ( so just multiply existing 4mb by 4 to avoid having to google. HAXX)
    // 4mb for images.
    $fourmb = 4194304;

    // DO NOT TRUST $_FILES['upfile']['mime'] VALUE !!
    // Check MIME Type by yourself.
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    if (false === $ext = array_search(
        $finfo->file($_FILES['img']['tmp_name']),
        array(
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
	    'pdf' => 'application/pdf',
	    'webm'=> 'video/webm'
        ),
        true
    )) {
	$die=1;
	tick("badfileformat");
        throw new RuntimeException('Invalid file format.');
    }

    if ($ext == "webm" && $_FILES['img']['size'] > $fourmb*4) {
	$die=1;
	throw new RuntimeException('Exceeded filesize limit(16mb)');
    }
    else if ($ext != "webm" && $_FILES['img']['size'] > $fourmb)
    {
	$die=1;
	throw new RuntimeException('Exceeded filesize limit (4mb)');
    }
    
    // Do not allow more than 2000x2000px in size
    $fname = $_FILES['img']['tmp_name'];
    $size = getimagesize($fname);
    if ($size[0] > 2000 || $size[1] > 2000)
    {
       $die=1;
       throw new RuntimeException('Exceeded filesize limit.');
    }


    // You should name it uniquely.
    // DO NOT USE $_FILES['upfile']['name'] WITHOUT ANY VALIDATION !!
    // On this example, obtain safe unique name from its binary data.
    $fname = substr(sha1_file($_FILES['img']['tmp_name']), 0, 8).".".$ext;
    $dir = "./uploads/";
    if (file_exists($dir.$fname))
    {
	tick("dupepic");
	die("Tava bilde jau ir tikusi ievietota.");
    }

    if (strlen($_FILES['img']['name']) > 128)
    {
	$die=1;
	tick("filenametoolong");
	throw new RuntimeException('Datnes nosaukums ir par garu.');
    }

    if (!move_uploaded_file($_FILES['img']['tmp_name'],$dir . $fname))
    {	
	$die=1;
        throw new RuntimeException('Failed to move uploaded file.');
    }
    
    echo $fname." uploaded successfully.";

} catch (RuntimeException $e) {
    echo($e->getMessage());

    if ($nofile == 1 && empty($content))
    {
	die("Replika nedrīkst būt tukša.");
    }

    if ($die==1)
	die();
}



if (isset($_POST["hidden"]) && $_POST["hidden"] == 1)
	$hidden = 1;

if (strlen($content) > 2048)
{
	tick("toolong");
	die("Mierīgāk, Šekspīr...");
}

$content = colorize($content);

$sql = "INSERT INTO `posts` (id, ip, content, hidden, imgsrc, imgalt, tags, replyto) VALUES (NULL, :ip, :content, :hidden, :imgsrc, :imgalt, :tags, :replyto)";
$q = $conn->prepare($sql);
$q->execute(array(':ip'=>$ip, ':content'=>$content, ':hidden'=>$hidden, ':imgsrc'=>$fname, ':imgalt'=>$_FILES['img']['name'], ':tags'=>$tags, ':replyto'=>$replyto));

echo "Gatavs!";

if ($replyto != 0)
	header( "Location: /b/index.php?repid=".$replyto );		
else
	header( "Location: /b/index.php");
?>
