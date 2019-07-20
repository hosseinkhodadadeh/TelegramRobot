<?php
$lastRunFile=__DIR__.'/incommingLastRun.txt';
if(file_exists($lastRunFile)){
	$lastRun=file_get_contents($lastRunFile);
}else{
	$lastRun=0;
}
if(($lastRun+120)<time()){
	echo 'It is more than one minute I think the previous run has stopped!';
}else{
	echo 'The script has run less than one minute ago and seems running so We should Die';
	die();
}
require_once 'madeline.php';
require_once __DIR__ .'/hossein/TextProcessor.php';
//require 'vendor/autoload.php';
//require_once  'Files-Copy.php';
$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$me = $MadelineProto->start();
$me = $MadelineProto->get_self();
\danog\MadelineProto\Logger::log($me);


$MadelineProto->settings['updates']['handle_updates'] = false;

class EventHandler extends \danog\MadelineProto\EventHandler
{
	private $postString;
	private $fileName;
	private $channelId;
	private $cat;
	private $links;
	private $conn;
	private $telegramMessageId;
	
	
    public function __construct($MadelineProto)
    {
        parent::__construct($MadelineProto);
    }
    public function onAny($update)
    {
        \danog\MadelineProto\Logger::log(date('Y-m-d H:i:s')."Khodadadeh: Received an update of type ".$update['_']);
    }
    public function onLoop()
    {
		global $lastRunFile;
		file_put_contents($lastRunFile,time());
		if(date('H',time())=='1'){
			die('It is one AM and we sleep at this time!');
		}
        \danog\MadelineProto\Logger::log("New  loop in hossin_incomming is called!".date('Y-m-d H:i:s'));
    }
    public function onUpdateNewChannelMessage($update)
    {
        $this->onUpdateNewMessage($update);
    }
    public function onUpdateNewMessage($update)
    {
		if(!isset($this->conn)){
			$this->getConToDB();	
		}
		
		
		switch ($update['_']) {
            case 'updateNewMessage':
			if (isset($update['message']['out']) && $update['message']['out']) {
            return;
        }
		if(!isset($update['message']['message'])){
			return;
		}
			
			$this->postString=$update['message']['message'];
			if($update['message']['to_id']['_']!='peerChat'){
				return false;
			}
			if($update['message']['to_id']['chat_id']=='364695571'){
				$this->forwardMessage($update);
				//$update['message']['to_id']
			}
			break;
            case 'updateNewChannelMessage':
        if (isset($update['message']['out']) && $update['message']['out']) {
            return;
        }
		if(!isset($update['message']['message'])){
			return;
		}
		
		unset($this->postString);
		unset($this->channelId);
		unset($this->fileName);
		unset($this->cat);
		unset($this->links);
		unset($this->telegramMessageId);
		
		
        $res = json_encode($update, JSON_PRETTY_PRINT);
		if ($res == '') {
			try{
				//$res = var_export($update, true);
			}catch(\danog\MadelineProto\RPCErrorException $e){
				$res='Not Available!';
			}
		}
		
        try {
			$this->postString=$update['message']['message'];
			
			
			$textprocessor=new TextProcessor;
			$textprocessor->setText($this->postString);
			$this->links=$textprocessor->findlinks();
			$this->registerLinks();
			$this->cat=$textprocessor->getProductType();
			
			if($textprocessor->PassForbiddenWords() and $textprocessor->HasPrice() and $textprocessor->PriceAd()==false){
				$this->postString=$textprocessor->getText();
				
				if(isset($update['message']['to_id']['channel_id'])){
					$this->channelId=$update['message']['to_id']['channel_id'];
					
				}else{
					return false;
				}
				try {
					if (isset($update['message']['media']) && ($update['message']['media']['_'] == 'messageMediaPhoto' || $update['message']['media']['_'] == 'messageMediaDocument')) {
						$time = microtime(true);
						$myCustomDir = str_replace('\\', '/',__DIR__ . '\\tmp' );
						$this->fileName='filename' . microtime(true);
						//$this->setDownloadFileName($this->fileName);
						//$file = $this->download_to_dir($update,$myCustomDir );
						$file = $this->download_to_dir($update,$myCustomDir );
						//$this->messages->sendMessage(['peer' => $update, 'message' => $file.' in '.(microtime(true) - $time).' seconds', 'reply_to_msg_id' => $update['message']['id'], 'entities' => [['_' => 'messageEntityPre', 'offset' => 0, 'length' => strlen($res), 'language' => 'json']]]);
						//$this->messages->sendMessage(['peer' => $update, 'message' => 'Downloaded to '.$file.' in '.(microtime(true) - $time).' seconds', 'reply_to_msg_id' => $update['message']['id'], 'entities' => [['_' => 'messageEntityPre', 'offset' => 0, 'length' => strlen($res), 'language' => 'json']]]);
					}
				} catch (\danog\MadelineProto\RPCErrorException $e) {
					$this->messages->sendMessage(['peer' => '@Hossein_khodadadeh', 'message' => $e->getCode().': '.$e->getMessage().PHP_EOL.$e->getTraceAsString()]);
				}
				if(empty($this->fileName)){$this->fileName='';}
				if(empty($this->postString)){$this->postString='';}
				if(strlen($this->postString)>1 and strlen($this->fileName)>1){
					if($this->isRepetitive($this->postString)){
						return false;
					}
					$this->telegramMessageId=$update['message']['id'];
					$this->submitPost();
				}else{
					
					
				}
				
			}else{
				//the text seems to be advertisment!
			}
			
            //$this->messages->sendMessage(['peer' => $update, 'message' => 'ddd '.$res, 'reply_to_msg_id' => $update['message']['id'], 'entities' => [['_' => 'messageEntityPre', 'offset' => 0, 'length' => strlen($res), 'language' => 'json']]]);
            //$this->messages->sendMessage(['peer' => "@hossein_khodadadeh", 'message' => 'ddd '.$post_liks." ".$res, 'reply_to_msg_id' => $update['message']['id'], 'entities' => [['_' => 'messageEntityPre', 'offset' => 0, 'length' => strlen($res), 'language' => 'json']]]);
			//$this->messages->sendMessage(['peer' => '@hossein_khodadadeh', 'message' => 'ddd '.$post_liks." ".$res]);
		
		} catch (\danog\MadelineProto\RPCErrorException $e) {
            $this->messages->sendMessage(['peer' => '@Hossein_khodadadeh', 'message' => $e->getCode().': '.$e->getMessage().PHP_EOL.$e->getTraceAsString()]);
        }

    }
		
		

		
		
    }
	public function forwardMessage($update){
		$re30dehCode=$update['message']['message'];
		$sql="select * from `incommingposts` where code='".$this->conn->real_escape_string(intval($re30dehCode))."' ";
		$result=$this->conn->query($sql);
		if($result->num_rows>0){
				$res=$result->fetch_assoc();
				if(!isset($res['channel_id']) or !isset($res['telegramId'])){
					return false;
				}
				//$qq=json_encode($update, JSON_PRETTY_PRINT);
				$InputPeer = 'channel#'.$res['channel_id'];
				$this->messages->forwardMessages(['silent' => true, 'background' => true, 'with_my_score' => true, 'grouped' => false, 'from_peer' => $InputPeer, 'id' => [$res['telegramId']], 'to_peer' => $update, ]);
		}else{
			$messageText=' چنین کدی در سیستم تعریف نشده است';
			$this->messages->sendMessage(['peer' => $update, 'message' =>$messageText , 'reply_to_msg_id' => $update['message']['id'], 'entities' => [['_' => 'messageEntityPre', 'offset' => 0, 'length' => strlen($messageText), 'language' => 'json']]]);
		}
	}
	public function registerLinks(){
		
		if(is_array($this->links)){ 
			foreach($this->links as $link){
				$sql="select * from `detected_channels` where link='".$this->conn->real_escape_string($link)."' ";
				$result=$this->conn->query($sql);
				
				if($result->num_rows>0){
					echo date('Y-m-d H:i:s').'this link is repeated';
				}else{
					$sql="INSERT INTO `detected_channels`(`detection_id`, `link`, `status`, `added`) VALUES (NULL, '".$this->conn->real_escape_string($link)."', 0, 0);";
					$this->conn->query($sql);
				}
			}
			
			return true;
		}else{
			
			return false;
		}
		
		
	}
	public function getConToDb(){
		$conn = new mysqli('localhost', 'root', 'kijNmS62%78ijK', 'lookshik');
		if ($conn->connect_error) {
			die("Connection failed: " . $conn->connect_error);
		}
		
		if (!$conn->set_charset("utf8")) {
			printf("Error loading character set utf8: %s\n", $conn->error);
			exit();
		} else {
			//printf("Current character set: %s\n", $conn->character_set_name());
		}
		$this->conn=$conn;
		return true;
	}
	public function isRepetitive(){
		
		$sql="select * from `incommingposts` where text='".$this->conn->real_escape_string($this->postString)."' and created_at>".(time()-(72*3600));
		$result=$this->conn->query($sql);
		if($result->num_rows>0){
			echo date('Y-m-d H:i:s').'this post is repeated'.$sql;
			return true;
		}
		return false;
	}
	public function submitPost(){
		
		$sql="INSERT INTO `incommingposts` 
(`id`,`telegramId`, `img_src`, `text`, `channel_id`,  `processed`, `created_at`,`links`,`stat`, `insta_group`, `insta_order_in_group`,`category`) VALUES 
(NULL,".$this->telegramMessageId." ,'".$this->fileName."', '".$this->conn->real_escape_string($this->postString)."', '".$this->channelId."', '0', '".time()."','','',0,0,'".$this->cat."')";
		//echo $sql;
		$tempsqlresult=$this->conn->query($sql);
		$sql="update channelmap set joinStatus='Joined',posts=(posts+1) where channel_from=".$this->channelId;
		$this->conn->query($sql);
		
	}
}



$MadelineProto->setEventHandler('\EventHandler');
$MadelineProto->loop();



