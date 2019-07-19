<?php
require_once __DIR__ . '\..\madeline.php';
require_once __DIR__ .'\TextProcessor.php';
//require 'vendor/autoload.php';


$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$me = $MadelineProto->start();
$me = $MadelineProto->get_self();
\danog\MadelineProto\Logger::log($me);



$MadelineProto->messages->sendMessage(['peer' => '@Hossein_khodadadeh', 'message' => "دوستای گلم توجه داشته باشین که ارسال همه خریدهای شما به صورت کاملا رایگان توسط ما انجام میشه و بابت ارسال هیچ کالایی شما هیچ گونه هزینه اضافی پرداخت نمی کنید
\r\n
بوتیک آنلاین رسیده
\r\n
@re30deh
\r\n
پشتیبانی خرید
\r\n
@Elham_simayii
"]);
//$MadelineProto->messages->sendMessage(['peer' => '@Hossein_khodadadeh', 'message' => "My message to a public channel "]);




		$conn = new mysqli('localhost', 'root', '', 're30deh');
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

		$sql="select * from `incommingposts` where processed='0' and img_src!='' and text!='' limit 0,1";
		
		$ids='0 ';
		$result=$conn->query($sql) ;
		try{
				foreach ($result as $rec){
					$ids=$ids .', '. $rec['id'];
					$output_text=$rec['text'];
					$output_text=html_entity_decode($output_text);
					$output_text=makenumsen2($output_text);
					$output_text=str_replace("  ", ' ', $output_text);
					$output_text=str_replace("  ", ' ', $output_text);
					$output_text='RE30DEH-'.str_pad($rec['id'], 6,"0", STR_PAD_LEFT). "\r\n " .$output_text;
					$output_text=str_replace("خريد اینترنتی", '', $output_text );
					$output_text=str_replace("پرداخت درب منزل", '', $output_text );
					$output_text=str_replace("خرید سریع", ' ', $output_text );
					$output_text=str_replace("خریدآسان", '', $output_text );
					$output_text=str_replace("پرداخت درمحل", '', $output_text );
					$output_text=str_replace("خرید", '', $output_text );
					$output_text=str_replace("آسان", '', $output_text );
					$output_text=str_replace("جهت سفارش", '', $output_text );
					$output_text=str_replace("مزون یشیل", '', $output_text );
					$output_text=str_replace(":", '', $output_text );
					$output_text=str_replace(".", ',', $output_text );
					$output_text=str_replace("  ", ' ', $output_text );
					
					
					$output_text=str_replace("آ یدی سفارش", '', $output_text );
					$output_text=str_replace("مدل خاتون", '', $output_text );
					$output_text=str_replace("کدسفارش", '', $output_text );
					$output_text=str_replace("کد سفارش", '', $output_text );
					$output_text=str_replace("کد", '', $output_text );
					$output_text=str_replace("Sharifimod", '', $output_text );
					$output_text=str_replace("collection mojde", '', $output_text );
					$output_text=str_replace("collection", '', $output_text );
					$output_text=str_replace("با گارانتی تعویض", '', $output_text );
					$output_text=str_replace("گارانتی تعویض", '', $output_text );
					$output_text=str_replace("?", '', $output_text );
					$output_text=str_replace(" کافه لباس باتخفیف ویژه برای شما", '', $output_text );
					$output_text=str_replace("هزینه درب منزل", '', $output_text );
					$output_text=str_replace("پرو و برای تهران", '', $output_text );
					$output_text=str_replace("سلنا", '', $output_text );
					$output_text=str_replace("مخصوص شما شیک پوشان", '', $output_text );
					$output_text=str_replace("ارزانسرای کیف و کفش یاسمن", '', $output_text );
					$output_text=str_replace("autumun", '', $output_text );
					$output_text=str_replace("autumun", '', $output_text );
					$output_text=str_replace("autumun", '', $output_text );
					$output_text=str_replace("autumun", '', $output_text );
					$output_text=str_replace("autumun", '', $output_text );
					$output_text=str_replace("autumun", '', $output_text );
					$output_text=str_replace("autumun", '', $output_text );
					$output_text=str_replace("autumun", '', $output_text );
					$output_text=str_replace("autumun", '', $output_text );
					$output_text=str_replace("autumun", '', $output_text );
					$output_text=str_replace("autumun", '', $output_text );
					$output_text=str_replace("autumun", '', $output_text );
					$output_text=str_replace("autumun", '', $output_text );
					$output_text=str_replace("autumun", '', $output_text );
					$output_text=str_replace("autumun", '', $output_text );
					$output_text=str_replace("autumun", '', $output_text );
					$output_text=str_replace("autumun", '', $output_text );
					$output_text=str_replace("autumun", '', $output_text );
					$output_text=str_replace("autumun", '', $output_text );
					$output_text=str_replace("autumun", '', $output_text );

					
					
					
					unset($matches);
					unset($price);
					$output_text2='';
					echo 'id is : '. $rec['id'].'</br>';
					

						$patterns[0]="([0-9\,]+ تومن)";
						$patterns[1]="([0-9\,]+ تومان)";
						$patterns[2]="([0-9\,]+ ریال)";
					
						$patterns[4]="([0-9\,]+تومن)";
						$patterns[5]="([0-9\,]+تومان)";
						$patterns[6]="([0-9\,]+ریال)";
						
						$patterns[8]="(قیمت [0-9\,]+)";
						
						$patterns[9]="('فروش[0-9\,]+)";
						$patterns[10]="(فروش [0-9\,]+)";
						$patterns[11]="([0-9\,]+ قیمت)";
						$patterns[12]="(قیمت[0-9\,]+)";
						$patterns[13]="(قیمت [0-9\,]+)";
						//$patterns[14]="([0-9\,]+)";
						
						echo 'source is ';
						//var_dump($output_text);
						unset($prices);
						$counter=1000;
						foreach($patterns as $num1=> $pattern){
							
							preg_match($pattern , $output_text, $m1);
							if(isset($m1)){
								foreach($m1 as $num2=> $match){
									$counter=$counter+1;
									$output_text=str_replace($match, "GTGTGTGTGTGTGTGTGTGTGTG".$counter, $output_text );
									$price_phrase=str_replace(",", '', $match);
									$price_phrase=str_replace("000", '', $price_phrase);
									$removable_phrase = preg_replace('/[0-9]+/', '', $price_phrase);
									$price=str_replace($removable_phrase, '', $price_phrase);
									$price=intval($price)+10 . " هزار تومان";
									$prices[$counter]=$price;
								}
							}
						}
						if(isset($prices)){
							foreach($prices as $num=> $price){
								$output_text=str_replace('GTGTGTGTGTGTGTGTGTGTGTG'.$num, $price, $output_text );
							}
							$output_text=str_replace("تومانت", 'تومان', $output_text );
							$output_text=str_replace("هزار تومانهزار تومان", 'هزار تومان', $output_text );
							$output_text=str_replace("هزار تومان هزار تومان", 'هزار تومان', $output_text );
							if(strlen($output_text)<175){
								$output_text=$output_text."\r\n"."سفارش"."\r\n"."@Elham_simayii";
							}
							if(strlen($output_text)<155){
								$output_text=$output_text."\r\n"."بوتیک آنلاین رسیده"."\r\n"."@re30deh";
							}
							//$output_text=substr ($output_text,0,200);
							$sentMessage = $MadelineProto->messages->sendMedia([
								'peer' => '@Hossein_khodadadeh',
								'media' => [
									'_' => 'inputMediaUploadedPhoto',
									'file' => str_replace('\\', '/',__DIR__ . '\\tmp\\' ) . $rec['img_src'] .'.jpg'
								],
								'message' => $output_text,
								'parse_mode' => 'Markdown'
							]);
							
						}
						//echo 'output is ';
						//var_dump($output_text);
				}
				
			$sql="update `incommingposts` set processed ='1' where id in($ids)";
			$result=$conn->query($sql) ;
		}catch(\danog\MadelineProto\RPCErrorException $e){
			foreach ($result as $rec){
				$ids=$ids .', '. $rec['id'];
			}
			$sql="update `incommingposts` set processed ='1' where id in($ids)";
			$result=$conn->query($sql) ;
		}
		
		$conn->close();




function makenumsen2($englishnumbers)
{
$englishnumbers = str_replace('۰','0', $englishnumbers);
$englishnumbers = str_replace('٠','0', $englishnumbers);
$englishnumbers = str_replace('١', '1' , $englishnumbers);
$englishnumbers = str_replace('۱', '1' , $englishnumbers);
$englishnumbers = str_replace('٢', '2' , $englishnumbers);
$englishnumbers = str_replace('۲', '2' , $englishnumbers);
$englishnumbers = str_replace('٣' ,'3', $englishnumbers);
$englishnumbers = str_replace('۳' ,'3', $englishnumbers);
$englishnumbers = str_replace('۴' ,'4', $englishnumbers);
$englishnumbers = str_replace('۵' ,'5', $englishnumbers);
$englishnumbers = str_replace('٥' ,'5', $englishnumbers);
$englishnumbers = str_replace('۶' ,'6', $englishnumbers);
$englishnumbers = str_replace('٧' ,'7', $englishnumbers);
$englishnumbers = str_replace('۷' ,'7', $englishnumbers);
$englishnumbers = str_replace('٨' ,'8', $englishnumbers);
$englishnumbers = str_replace('۸' ,'8', $englishnumbers);
$englishnumbers = str_replace('٩' ,'9', $englishnumbers);
$englishnumbers = str_replace('۹' ,'9', $englishnumbers);
return $englishnumbers;
}


?>




