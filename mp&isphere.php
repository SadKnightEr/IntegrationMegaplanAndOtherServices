<?php
        include_once "Client.php";
        include_once "Request.php";
$raw = file_get_contents('php://input');
$deal = json_decode($raw,true);
$file = 'data.txt';
$fileopen=fopen($file, "a");
ftruncate($fileopen,0);
$contcractorId= $deal['data']['id'];
$last_name= $deal['data']['lastName'];
$first_name=$deal['data']['firstName'];
$middle_name=$deal['data']['middleName'];
$birth_date = $deal['data']['birthday']['day'].'.'.$deal['data']['birthday']['month'].
        '.'.$deal['data']['birthday']['year'];
$mobile_phone='';
$email='';
$social_links='';
$contactInfo=$deal['data']['contactInfo'];
if(is_array($deal['data']['contactInfo']))
{
     $contactInfo=$deal['data']['contactInfo'];
    for($i = 0; $i < count($contactInfo); $i++) {
	    if($contactInfo[$i]['type']=='phone')
	    {
            $tel=$contactInfo[$i]['value'];
            $tel=preg_replace("/[^0-9]/", '', $tel);
	        $mobile_phone=$tel;
        }
      /*if($contactInfo[$i]['type']=='email')
	    {
	    $email=$contactInfo[$i]['value'];
        }*/
		if($contactInfo[$i]['type']=='telegram' || $contactInfo[$i]['type']=='skype'
				|| $contactInfo[$i]['type']=='whatsapp' || $contactInfo[$i]['type']=='viber'
				|| $contactInfo[$i]['type']=='icq' || $contactInfo[$i]['type']=='jabber')
	    {
	        $social_links.=$contactInfo[$i]['value'];
        }
	}
	      
}
/*fwrite($fileopen,'$contcractorId'. $contcractorId);
fwrite($fileopen, 'last_name'.$last_name);
fwrite($fileopen, ' first_Name'.$first_name);
fwrite($fileopen, ' middle_Name'.$middle_name);
fwrite($fileopen, ' birthday'.$birth_date);
fwrite($fileopen, ' mobile_phone'.$mobile_phone);
//fwrite($fileopen, ' email'.$email);
fwrite($fileopen, ' social_links'.$social_links);*/
fwrite($fileopen,'Read Megaplan Data-Ok. ');
fclose($fileopen);

if($mobile_phone!='' && $social_links==''){
    
    $file = 'data.txt';
    $fileopen=fopen($file, "a");
    fwrite($fileopen, 'Mobile phone existed and client dont have a social_links. ');
    
    //set params for isphere
    $passport_series = '';
    $passport_number = '';
    $issue_date = '';
    $region_id = 0;
    $home_phone = '';
    $work_phone = '';
    $additional_phone = '';
    
    $sources = 'fms,fns,fssp,rossvyaz';
    $userid = 'XXXXX';
    $password = 'XXXXX';
    $serviceurl = 'https://www.i-sphere.ru/2.00/';
    
    //get info from isphere
     $xml ="
<Request>
        <UserID>{$userid}</UserID>
        <Password>{$password}</Password>
        <requestId>".time()."</requestId>
        <sources>{$sources}</sources>"
 .(!$last_name && !$passport_number ? "" : "
        <PersonReq>
            <first>{$first_name}</first>
            <middle>{$middle_name}</middle>
            <paternal>{$last_name}</paternal>"
 . (!$birth_date ? "" : "
            <birthDt>{$birth_date}</birthDt>"
) . (!$passport_number ? "" : "
            <passport_series>{$passport_series}</passport_series>
            <passport_number>{$passport_number}</passport_number>"
) . (!$issue_date ? "" : "
            <issueDate>{$issue_date}</issueDate>"
) . (!$region_id ? "" : "
            <region_id>{$region_id}</region_id>"
) . "
        </PersonReq>"
) . (!$mobile_phone ? "" : "
        <PhoneReq>
            <phone>{$mobile_phone}</phone>
        </PhoneReq>"
) . (!$home_phone ? "" : "
        <PhoneReq>
            <phone>{$home_phone}</phone>
        </PhoneReq>"
) . (!$work_phone ? "" : "
        <PhoneReq>
            <phone>{$work_phone}</phone>
        </PhoneReq>"
) . (!$additional_phone ? "" : "
        <PhoneReq>
            <phone>{$additional_phone}</phone>
        </PhoneReq>"
) . (!$email ? "" : "
        <EmailReq>
            <email>{$email}</email>
        </EmailReq>"
) . "
</Request>";
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $serviceurl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
    curl_setopt($ch, CURLOPT_POST, 1);
    
    $answer = curl_exec($ch);
    curl_close($ch);
    $xml = simplexml_load_string($answer);
    if($xml!=false){
    fwrite($fileopen, 'GET Isphere XML-Answer- OK ');
    $json = json_encode($xml,JSON_UNESCAPED_UNICODE);
    $jsonAnswer=json_decode($json, true);
    $last_name= $jsonAnswer["Request"]["PersonReq"]["paternal"].' тест';
    $first_name= $jsonAnswer["Request"]["PersonReq"]["first"].' тест';
    $middle_Name= $jsonAnswer["Request"]["PersonReq"]["middle"].' тест';
    fwrite($fileopen, 'GET Isphere JSON- OK ');
    if($contcractorId!=''){
        
        $response = new  \Megaplan\SimpleClient\Client('XXXXX.megaplan.ru');
        $response->auth('XXXXX', 'XXXXX');
        $data = [
            'Id' => $contcractorId,
        'Model[FirstName]' => $first_name,
        'Model[LastName]' => $last_name,
        'Model[MiddleName]'=>$middle_Name,
        'Model[TypePerson]'=>'human',
        'Model[Email]'=>'1@mail.ru'
        ];
        $answer=$response->get('/BumsCrmApiV01/Contractor/save.api', $data);
        $jsonAnswer=json_encode($answer);
        print_r($answer);
        fwrite($fileopen, 'Megaplan update client is successfull ');
        
}
    }
    else
     fwrite($fileopen, 'xml read error ');
    fclose($fileopen);
}


?>
