<?php

include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();


$conn = new mysqli('localhost', 'root', 'h%f*6sKs3', 're30deh');
// Check connection
if ($conn->connect_error) {
die("Connection failed: " . $conn->connect_error);
}

if (!$conn->set_charset("utf8")) {
	printf("Error loading character set utf8: %s\n", $conn->error);
	exit();
} else {
	//printf("Current character set: %s\n", $conn->character_set_name());
}

$sql="SELECT  * FROM `detected_channels` where status='0' limit 0,1";

$result=$conn->query($sql) ;
foreach ($result as $rec){
	$sql="update `detected_channels` set status ='3',added=1 where detection_id =".$rec['detection_id'];
	$trytryt=$conn->query($sql) ;
	try{
		$Updates = $MadelineProto->channels->joinChannel(['channel' => $rec['link'], ]);
		echo '</br>Joined to '. $rec['link'] .' Successfully!';
		$stat=1;
		$chat = $MadelineProto->get_info($rec['link']);
		if($chat['type']=='channel'){
			$channel_id=$chat['channel_id'];
			$chf_name=$chat['Chat']['title'];
			if(isset($chat['Chat']['username'])){
				$username=$chat['Chat']['username'];
			}else{
				$username='No';
			}
			$sql="INSERT INTO `channelmap` 
			(`cm_id`, `channel_from`,`username`, `chf_name`, `chf_type`, `channel_to`, `active`) VALUES 
			(NULL, '".$channel_id."','".$username."', '".$chf_name."', '', 're30deh', '0')";
			//$result=$conn->query($sql) ;
		}
		
	}catch(\danog\MadelineProto\RPCErrorException $e){
		
		echo '</br>Not Joined! to  '. $rec['link'] ;
		$stat=2;
	}
	
	
}



?>