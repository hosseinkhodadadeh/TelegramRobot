<?php

require_once __DIR__ .'/hossein/TextProcessor.php';

		$conn = new mysqli('localhost', 'root', 'h%f*6sKs3', 're30deh');
		
		if ($conn->connect_error) {
			die("Connection failed: " . $conn->connect_error);
		}
		
		if (!$conn->set_charset("utf8")) {
			printf("Error loading character set utf8: %s\n", $conn->error);
			exit();
		} else {
			
		}

		$sql="select * from `incommingposts` inc
		where inc.category is null and inc.created_at>".(time()-24*3600)." limit 0,5";
		echo $sql;
		$ids='0 ';
		$posted=1;
		$result=$conn->query($sql) ;
		
		foreach ($result as $rec){
			
			$sql="update `incommingposts` set category ='Unknown' where id =".$rec['id'];
			$ans=$conn->query($sql) ;
			
			echo '</br>id is : '. $rec['id'].'</br><pre>';
			$ids=$ids .', '. $rec['id'];
			//var_dump($rec);
			$output_text=$rec['text'];
			$textprocessor=new TextProcessor;
			$textprocessor->setText($output_text);
			if($textprocessor->PassForbiddenWords()==false){
				echo 'Has Forbidden Text!';
				continue;
			}
			if($textprocessor->getProductType()!='Unknown'){
				$sql="update `incommingposts` set category ='".$textprocessor->getProductType()."' where id =".$rec['id'];
				$ans=$conn->query($sql) ;
			}
		}
		$conn->close();
?>




