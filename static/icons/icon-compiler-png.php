<?php



$icon_h = 16;
$dest_h = 20;


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



//icon height + top padding
$padding = ceil(($dest_h-$icon_h)/2);
$h = $icon_h + $padding;
$ico = imagecreatetruecolor($icon_h, $h*count($files));

//enable alpha channel
imagesavealpha($ico, true);
imagealphablending($ico, false);

//set padding color to transparent
$transparent = imagecolorallocatealpha($ico, 255,255,255, 127);
imagefilledrectangle($ico, 0,0, imagesx($ico), imagesy($ico), $transparent);

//copy all icons
$i=0;
foreach($files as $file){
  $tile = imagecreatefrompng("$dir/$file.png");
  $y = $h*($i++);
  imagecopyresampled($ico, $tile, 0,$y+$padding, 0,0, $icon_h,$icon_h, $icon_h,$icon_h);
  //imagecopyresampled(int dstX,Y, int srcX,Y, int dstW,H, int srcW,H)
}
$def = "$h," . implode(',', $files);

file_put_contents('fileicons.txt', $def);
imagepng($ico, "fileicons.png");


echo "<pre>$def\n<img src='fileicons.png'>";



