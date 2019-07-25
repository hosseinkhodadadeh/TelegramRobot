<?php
//deleting files by date modified
  $files = glob(__DIR__ ."/tmp/*.*");
  $now   = time();

  foreach ($files as $file) {
    if (is_file($file)) {
      if ($now - filemtime($file) >= 3600 * 24 * 10) { // 10 days
		echo "$file time is " . filemtime($file) . " which is equal to " .date('Y-m-d H:i:s',filemtime($file)) ."\n";
        unlink($file);
		continue;
      }
	  if(filesize($file)>1000000){
		  echo "$file size " . filesize($file) . "\n";
		  unlink($file);
		  
	  }
    }
  }
  

 //Deleting from database
 /*
$conn = new mysqli('localhost', 'root', 'h%f*6sKs3', 're30deh');

if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
}
if (!$conn->set_charset("utf8")) {
	printf("Error loading character set utf8: %s\n", $conn->error);
	exit();
} else {
	//printf("Current character set: %s\n", $conn->character_set_name());
}


$sql="select * from `incommingposts` where created_at<".(time()-30*24*3600)." and (processed='0' or processed=1) order by created_at asc limit 0,200";
$result=$conn->query($sql) ;
foreach ($result as $rec){
	$fileName=__DIR__. "/tmp/".$rec['img_src'] .'.jpg';
	
	if(file_exists ($fileName)){
		//echo 'id is '.$rec['id'].' and the file is '.$fileName .' Deleted </br>';
		unlink($fileName);
	}else{
		//echo 'id is '.$rec['id'].' and the file is '.$fileName .' Not Found </br>';
	}
	
	$sql="delete from `incommingposts` where id =".$rec['id'];
	$conn->query($sql) ;
}
*/


?>