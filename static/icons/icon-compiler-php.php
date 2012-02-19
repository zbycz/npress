<?php

$icon_h = 16;


$dir = $icon_h."px";
$handle = opendir($dir) or die("Unable to open directory, please, contact the webmaster.");
$files = array();
while(($f = readdir($handle)) !== false){
	if($f != "." AND $f != ".." AND $f != basename($_SERVER["SCRIPT_NAME"]) AND !is_dir($f))
		$files[] = substr($f, 0, strrpos ($f, '.'));
}
if(count($files)){
	reset($files);
	natsort($files);
}

foreach($files as $f){
	$con = base64_encode(file_get_contents("$dir/$f.png"));
	$data .= "'$f' => '$con',\n";
}


file_put_contents("icons$icon_h.php", '<?php
$icons = array(
'.$data.'
);
if(strtolower(realpath(__FILE__)) == strtolower(realpath($_SERVER["SCRIPT_FILENAME"]))){
	header("Content-type: image/png");
	header("Expires: Mon, 03 Oct 2099 12:00:00 GMT");

	$k = $_GET["file"];
	if(isset($k) && isset($icons[$k])){
		echo base64_decode($icons[$k]);
	}
	else{
		echo base64_decode($icons["_blank"]);
	}
}
');
	
