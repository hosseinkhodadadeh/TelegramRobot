<?php
require_once 'madeline.php';
require_once __DIR__ .'/hossein/TextProcessor.php';
//require 'vendor/autoload.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$me = $MadelineProto->start();
$me = $MadelineProto->get_self();
//\danog\MadelineProto\Logger::log($me);
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

		$sql="select *,(select max(code) from incommingposts) as code from `incommingposts` inc
		left join channelmap chmap
		on(inc.channel_id=chmap.channel_from)
		where inc.processed='0' and chmap.active=1 and inc.created_at>".(time()-24*3600)." limit 0,200";
		
		$ids='0 ';
		$posted=1;
		$result=$conn->query($sql) ;
		try{
				foreach ($result as $rec){
					
					$sql="update `incommingposts` set processed ='1' where id =".$rec['id'];
					$ans=$conn->query($sql) ;
					
					echo '</br>id is : '. $rec['id'].'</br><pre>';
					$ids=$ids .', '. $rec['id'];
					//var_dump($rec);
					
					$output_text=$rec['text'];
					//$output_text=html_entity_decode($output_text);
					$output_text='RE30DEH-'.str_pad(($rec['code']+$posted), 6,"0", STR_PAD_LEFT). "\r\n " .$output_text;
					
					
					$textprocessor=new TextProcessor;
					$textprocessor->setText($output_text);
					if($textprocessor->PassForbiddenWords()==false){
						echo 'Has Forbidden Text!';
						continue;
					}
					if($rec['chf_type']=="omde"){
						$plus=3;
						$increase_type='percent';
					}elseif($rec['chf_type']=="tak"){
						$plus=10;
						$increase_type='constant';
					}else{
						continue;
					}
					
					if($textprocessor->UpdatePrices($plus,$increase_type)){
						$textprocessor->AddAdminText();
						$output_text=$textprocessor->gettext();
						
						
						if(file_exists (__DIR__. "/tmp/".$rec['img_src'] .'.jpg')){
							//creating re30deh watermark on product image
							/*
							$logo = imagecreatefrompng( __DIR__. "/hossein/channel_logo.png");
							$src = imagecreatefromjpeg(__DIR__. "/tmp/".$rec['img_src'] .'.jpg');
							imagealphablending($logo, false);
							imagesavealpha($logo, true);
							$logo_width=imagesx ($src)/4;
							$logo=imagescale ($logo,$logo_width);
							imagecopymerge_alpha($src, $logo, 10, 9, 0, 0, $logo_width, $logo_width, 100); 
							*/
							
							$text = $rec['code']+$posted;
							$logo = imagecreatefrompng(__DIR__. "/hossein/channel_logo.png");
							imagealphablending($logo, false);
							imagesavealpha($logo, true);


							$white = imagecolorallocate($logo, 255, 255, 255);
							$grey = imagecolorallocate($logo, 128, 128, 128);
							$black = imagecolorallocate($logo, 0, 0, 0);
							$red = imagecolorallocate($logo, 207, 0, 35);
							
							$font = __DIR__.'/IRANSans_Medium.ttf';

							// Add some shadow to the text
							imagettftext($logo, 50, 0, 140, 350, $grey, $font, $text);
							// Add the text
							imagettftext($logo, 50, 0, 140, 350, $white, $font, $text);


							$src = imagecreatefromjpeg(__DIR__. "/tmp/".$rec['img_src'] .'.jpg');
							$logo_width=imagesx ($src)/4;
							$logo=imagescale ($logo,$logo_width);
							imagecopymerge_alpha($src, $logo, 10, 9, 0, 0, $logo_width, $logo_width, 100);
							
							
							
							
							if(imagesx($src)>1080){
								//$src=imagescale ($src,1080);
								echo 'image name was '.$rec['img_src'] .'.jpg and resized to 1080px;';
							}elseif(imagesx($src)<300){
								continue;
							}
							imagejpeg ($src,__DIR__. "/tmp/".$rec['img_src'] .'.jpg');
							//watermark finished
							
							//start sending post to channel
							
							$sentMessage = $MadelineProto->messages->sendMedia([
								'peer' => '@'.$rec['channel_to'],
								'media' => [
									'_' => 'inputMediaUploadedPhoto',
									'file' => __DIR__ . '/tmp/'. $rec['img_src'] .'.jpg'
									//'file' => str_replace('\\', '/',__DIR__ . '\\tmp\\' ) . $rec['img_src'] .'.jpg'
								],
								'message' => $output_text,
								'parse_mode' => 'Markdown'
							]);
							echo ' </br>sending post done';
							$newIndex=$rec['code']+$posted;
								$sql="update `incommingposts` set processed ='2',code='".$newIndex."' where id =".$rec['id'];
								$ans=$conn->query($sql) ;
								$posted++;
								//processed 2 means sent to telegram successfully
							
						}else{
							echo " </br>image does not exist! ";
							
						}
						
						
					}else{
						echo " </br>Does not have a Price! ";
						
					}
					//echo 'output is ';
					//var_dump($output_text);
				}
				
			
		}catch(\danog\MadelineProto\RPCErrorException $e){
			foreach ($result as $rec){
				$ids=$ids .', '. $rec['id'];
			}
			$sql="update `incommingposts` set processed ='1' where id in($ids)";
			$result=$conn->query($sql) ;
		}
		
		$conn->close();



function imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct){
	// creating a cut resource
	$cut = imagecreatetruecolor($src_w, $src_h);
	// copying relevant section from background to the cut resource
	imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h);
	// copying relevant section from watermark to the cut resource
	imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h);
	// insert cut resource to destination image
	imagecopymerge($dst_im, $cut, $dst_x, $dst_y, 0, 0, $src_w, $src_h, $pct);
} 



?>




