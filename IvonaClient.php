<?php

/*
cezary.siwek@gmail.com

*/
class IvonaClient
{
    const   IVONA_ACCESS_KEY = "PUT_YOUR_ACCESS_KEY_HERE";
    const   IVONA_SECRET_KEY = "PUT_YOUR_SECRET_HERE";

    public $string;

    public $longDate;
    public $shortDate;
    public $IvonaURL = "https://tts.eu-west-1.ivonacloud.com";
    public $inputType = 'text%2Fplain';
    public $outputFormatCodec = 'MP3';
    public $outputFormatSampleRate = '22050';
    public $parametersRate = 'slow';
    public $voiceLanguage = 'en-GB';
    public $voiceName = 'Amy';
    public $xAmzAlgorithm = 'AWS4-HMAC-SHA256';
    public $xAmzSignedHeaders = 'host';
    public $xAmzDate;
    public $xAmzCredential;
    public $enableDebug = 1;
    public $useCache = 1;


    public function __construct()
    {
	$this->debug(__METHOD__." Created instance of ".__CLASS__);
        $this->setDate();
        $this->setCredential();

    }


    private function debug($message)
    {
	if ($this->enableDebug){
		syslog(LOG_DEBUG, $message);
	}

    }

    public function ListVoices($language=null, $gender=null)
    {

	$payloadArray = (object)array();
	if($language){
		$payloadArray->Voice["Language"] = $language;
	}
	if($gender){
		$payloadArray->Voice["Gender"] = $gender;
	}
	
	$obj = json_encode($payloadArray);
        $canonicalizedGetRequest = $this->getCanonicalRequest("ListVoices", $obj);
        $stringToSign = $this->getStringToSign($canonicalizedGetRequest);

        $signature = $this->getSignature($stringToSign);

	$url =  $this->IvonaURL . "/ListVoices";
	$postData = array();
	$postData[] = 'X-Amz-Date: '.$this->xAmzDate;
	$postData[] = 	'Authorization: ' . 'AWS4-HMAC-SHA256 Credential='.$this->xAmzCredential.',SignedHeaders=host,Signature='.$signature;
	$postData[] = 	'Content-Type: '. 'application/json';
	$postData[] = 	'Host: ' . 'tts.eu-west-1.ivonacloud.com';
	$postData[] = 	'User-Agent: ' . 'TestClient 1.0';
	$postData[] = 	'Expect:'; 
	

	$response = $this->reqPost($url, $postData, $obj);

	if ($response){
		return json_decode($response);
	} 
	return 0;

    }

    public function get($text)
    {

	$payloadArray = (object)array();
	$payloadArray->Input["Data"] = $text;
	$payloadArray->Input["Type"] = "text/plain";
	
	$payloadArray->OutputFormat["Codec"] = $this->outputFormatCodec; 
	$payloadArray->OutputFormat["SampleRate"] = (int) $this->outputFormatSampleRate;
 
	$payloadArray->Voice["Name"] = $this->voiceName; 
	$payloadArray->Voice["Language"] = $this->voiceLanguage;
	
	$payloadArray->Parameters["Rate"] = $this->parametersRate;
 

	$obj = json_encode($payloadArray);
        $canonicalizedGetRequest = $this->getCanonicalRequest("CreateSpeech", $obj);
        $stringToSign = $this->getStringToSign($canonicalizedGetRequest);

        $signature = $this->getSignature($stringToSign);
	$url =  $this->IvonaURL . "/CreateSpeech";
	$postData = array();
	$postData[] = 	'X-Amz-Date: '.$this->xAmzDate;
	$postData[] = 	'Authorization: ' . 'AWS4-HMAC-SHA256 Credential='.$this->xAmzCredential.',SignedHeaders=host,Signature='.$signature;
	$postData[] = 	'Content-Type: '. 'application/json';
	$postData[] = 	'Host: ' . 'tts.eu-west-1.ivonacloud.com';
	$postData[] = 	'User-Agent: ' . 'TestClient 1.0';
	$postData[] = 	'Expect:'; 
	

	$response = $this->reqPost($url, $postData, $obj);
	if ($response){
		return $response;
	} 
	return 0;

    }

    public function getSave($text)
    {

//	$content = $this->get($text);
	$uniqueString = $text . '_' . $this->outputFormatCodec . '_' . $this->outputFormatSampleRate . '_' . $this->voiceName . '_' . $this->voiceLanguage . '_' . $this->parametersRate;
	$this->debug(__METHOD__ . ' Unique string of TTS is: '.$uniqueString);
	if($this->useCache == TRUE){
		if ($cached = $this->checkIfCached($uniqueString)){
			$this->debug(__METHOD__ . ' File already Cached: '.$cached);
			return $cached;
		}	
	} 
	$content = $this->get($text);
	return $this->save($content, $uniqueString);

    }
	

    private function reqPost($url, $headers, $payload){

   	$ch2 = curl_init();
	curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch2, CURLOPT_URL, $url);
	curl_setopt($ch2, CURLOPT_POST, true);
	
	curl_setopt($ch2, CURLOPT_POSTFIELDS, $payload);
	curl_setopt($ch2, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
	$response = curl_exec($ch2);

	if(!curl_errno($ch2))
	{
 		$info = curl_getinfo($ch2);
 		$this->debug(__METHOD__.' Took ' . $info['total_time'] . ' seconds to send a request to ' . $info['url'] . " Response Code: ".$info['http_code']);
	} else {
		$this->debug(__METHOD__ . ' CURL returned ERROR:'. curl_error($ch2));
	}

	return $response;

    }


    public function setDate()
    {
        $this->longDate = gmdate('Ymd\THis\Z', time());
        $this->shortDate = substr($this->longDate, 0, 8);
        $this->xAmzDate = $this->longDate;
    }

    public function setCredential()
    {
        $this->xAmzCredential = self::IVONA_ACCESS_KEY . "/" . $this->shortDate . "/eu-west-1/tts/aws4_request";
    }


    private function getCanonicalRequest($service, $payload=null)
    {
        $canonicalizedGetRequest =
            "POST" .
            "\n/$service" .
            "\n" .
            "\nhost:tts.eu-west-1.ivonacloud.com" .
            "\n" .
            "\nhost" .
            "\n" . hash("sha256", $payload);
        return $canonicalizedGetRequest;
    }

    private function getStringToSign($canonicalizedGetRequest)
    {
        $stringToSign = "AWS4-HMAC-SHA256" .
            "\n$this->longDate" .
            "\n$this->shortDate/eu-west-1/tts/aws4_request" .
            "\n" . hash("sha256", $canonicalizedGetRequest);

        return $stringToSign;
    }

    private function getSignature($stringToSign)
    {
        $dateKey = hash_hmac('sha256', $this->shortDate, "AWS4" . self::IVONA_SECRET_KEY, true);
        $dateRegionKey = hash_hmac('sha256', "eu-west-1", $dateKey, true);
        $dateRegionServiceKey = hash_hmac('sha256', "tts", $dateRegionKey, true);
        $signingKey = hash_hmac('sha256', "aws4_request", $dateRegionServiceKey, true);
        $signature = hash_hmac('sha256', $stringToSign, $signingKey);

        return $signature;
    }


    private function getFileName($string)
    {
	$fileName = hash('md5', $string);
	if ($this->outputFormatCodec == "MP3"){
		$fileName .= '.mp3';
	} elseif ($this->outputFormatCodec == "OGG") {
		$fileName .= '.ogg';
	}
	
	return $fileName;
    }

    private function checkIfCached($string)
    {
	$fileName = $this->getFileName($string); 
        $savePath = $fileName{0} . '/' . $fileName{1} . '/' . $fileName{2};
        $dbPath = $fileName{0} . '/' . $fileName{1} . '/' . $fileName{2} . '/' . $fileName;
	if (file_exists($dbPath))
	{
		return $dbPath;
	}
	return 0;
    }

    private function save($resource, $string)
    {
	$fileName = $this->getFileName($string); 
        $savePath = $fileName{0} . '/' . $fileName{1} . '/' . $fileName{2};
        $dbPath = $fileName{0} . '/' . $fileName{1} . '/' . $fileName{2} . '/' . $fileName;

	

        if (!is_dir($savePath)) {
            // dir doesn't exist, make it
            mkdir($savePath, 0755, true);
        }

        file_put_contents($savePath . '/' . $fileName, $resource);
	$this->debug(__METHOD__ . ' File saved:'. $dbPath);
        return $dbPath;
    }


}
