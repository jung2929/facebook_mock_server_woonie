<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
ini_set('default_charset', 'utf8mb4');

//error_reporting(E_ALL);

//ini_set("display_errors", 1);

    use Firebase\JWT\JWT;
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    use Google\Auth\ApplicationDefaultCredentials;
    use GuzzleHttp\Client;
    use GuzzleHttp\HandlerStack;

    function getSQLErrorException($errorLogs, $e, $req){
        $res = (Object) Array();
        http_response_code(500);
        $res->code = 500;
        $res->message = "SQL Exception -> " . $e->getTraceAsString();
        echo json_encode($res);

        addErrorLogs($errorLogs, $res, $req);
    }

    function isValidHeader($jwt, $key){
        try{
            $data = getDataByJWToken($jwt, $key);
//        var_dump($data);

            return isValidUser($data);
        }catch(Exception $e){
            return false;
        }
    }

    function idlengthcheck($req)
    {
        if((mb_strlen($req->uesr_Id,'utf-8')>0)&&(mb_strlen($req->user_Id,'utf-8')<20))
        {
            return true;
        }
    }

   function pwlengthcheck($req)
   {
        if((mb_strlen($req->user_Id,'utf-8')>0)&&(mb_strlen($req->user_Id,'utf-8')<10))
        {
          return true;
        }
   }

   function contentlengthcheck($req)
   {
        if((mb_strlen($req->content,'utf-8')<1))
        {
          return false;
        }
   }

    function titlelengthcheck($req)
   {
    if((mb_strlen($req->title,'utf-8')<1))
    {
      return false;
    }
   }

    function sendFcm($fcmToken, $data, $key, $deviceType) {
        $url = 'https://fcm.googleapis.com/fcm/send';

        $headers = array (
            'Authorization: key=' . $key,
            'Content-Type: application/json'
        );

        $fields['data'] = $data;

        if ($deviceType == 'IOS'){
            $notification['title'] = $data['title'];
            $notification['body'] = $data['body'];
            $notification['sound'] = 'default';
            $fields['notification'] = $notification;
        }

        $fields['to'] = $fcmToken;
        $fields['content_available'] = true;
        $fields['priority'] = "high";

        $fields = json_encode ($fields, JSON_NUMERIC_CHECK);

//    echo $fields;

        $ch = curl_init ();
        curl_setopt ( $ch, CURLOPT_URL, $url );
        curl_setopt ( $ch, CURLOPT_POST, true );
        curl_setopt ( $ch, CURLOPT_HTTPHEADER, $headers );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $fields );

        $result = curl_exec ( $ch );
        if ($result === FALSE) {
            //die('FCM Send Error: ' . curl_error($ch));
        }
        curl_close ( $ch );
        return $result;
    }

    function getTodayByTimeStamp(){
        return date("Y-m-d H:i:s");
    }

    function getJWToken($Id, $Pw, $secretKey){
        $data = array(
            'user_Id' => (string)$Id,
	    'user_Pw' => (string)$Pw,
	    'date' => (string)getTodayByTimeStamp()
        );

//    echo json_encode($data);

        return $jwt = JWT::encode($data, $secretKey);

//    echo "encoded jwt: " . $jwt . "n";
//    $decoded = JWT::decode($jwt, $secretKey, array('HS256'))
//    print_r($decoded);
    }

    function getDataByJWToken($jwt, $secretKey){
        $decoded = JWT::decode($jwt, $secretKey, array('HS256'));
//    print_r($decoded);
        return $decoded;

    }

    function checkAndroidBillingReceipt($credentialsPath, $token, $pid){

        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $credentialsPath);
        $client = new Google_Client();
        $client->useApplicationDefaultCredentials();
        $client->addScope("https://www.googleapis.com/auth/androidpublisher");
        $client->setSubject("bigs-admin@api-6265089933527833864-983283.iam.gserviceaccount.com");


        $service = new Google_Service_AndroidPublisher($client);
        $optParams = array('token' => $token);
//    $results = $service->inappproducts->listInappproducts('kr.co.bigsapp.www', $optParams);


//    $res = new Google_Service_AndroidPublisher_Resource_PurchasesProducts($service, "androidpublisher", 'products', array(
//        'methods' => array(
//            'get' => array(
//                'path' => '{packageName}/purchases/products/{productId}/tokens/{token}',
//                'httpMethod' => 'GET',
//                'parameters' => array(
//                    'packageName' => array(
//                        'location' => 'kr.co.bigsapp.www',
//                        'type' => 'string',
//                        'required' => true,
//                    ),
//                    'productId' => array(
//                        'location' =>$pid,
//                        'type' => 'string',
//                        'required' => true,
//                    ),
//                    'token' => array(
//                        'location' => $token,
//                        'type' => 'string',
//                        'required' => true,
//                    ),
//                ),
//            ),
//        )));


        return $service->purchases_products->get("kr.co.bigsapp.www", $pid, $token);
    }

    function addAccessLogs($accessLogs, $body){

        if(isset($_SERVER['HTTP_X_ACCESS_TOKEN']))
            $logData["JWT"] = getDataByJWToken($_SERVER['HTTP_X_ACCESS_TOKEN'], 'JWT_SECRET_KEY');
        $logData["GET"] = $_GET;
        $logData["BODY"] = $body;
        $logData["REQUEST_METHOD"] = $_SERVER["REQUEST_METHOD"];
        $logData["REQUEST_URI"] = $_SERVER["REQUEST_URI"];
//    $logData["SERVER_SOFTWARE"] = $_SERVER["SERVER_SOFTWARE"];
        $logData["REMOTE_ADDR"] = $_SERVER["REMOTE_ADDR"];
        $logData["HTTP_USER_AGENT"] = $_SERVER["HTTP_USER_AGENT"];
        $accessLogs->addInfo(json_encode($logData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    }

    function addErrorLogs($errorLogs, $res, $body){
        if(isset($_SERVER['HTTP_X_ACCESS_TOKEN']))
            $req["JWT"] = getDataByJWToken($_SERVER['HTTP_X_ACCESS_TOKEN'], JWT_SECRET_KEY);
        $req["GET"] = $_GET;
        $req["BODY"] = $body;
        $req["REQUEST_METHOD"] = $_SERVER["REQUEST_METHOD"];
        $req["REQUEST_URI"] = $_SERVER["REQUEST_URI"];
//    $req["SERVER_SOFTWARE"] = $_SERVER["SERVER_SOFTWARE"];
        $req["REMOTE_ADDR"] = $_SERVER["REMOTE_ADDR"];
        $req["HTTP_USER_AGENT"] = $_SERVER["HTTP_USER_AGENT"];

        $logData["REQUEST"] = $req;
        $logData["RESPONSE"] = $res;

        $errorLogs->addError(json_encode($logData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

//        sendDebugEmail("Error : " . $req["REQUEST_METHOD"] . " " . $req["REQUEST_URI"] , "<pre>" . json_encode($logData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "</pre>");
    }

    function getLogs($path){
        $fp = fopen($path, "r" , FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$fp) echo "error";

        while (!feof($fp)) {
            $str = fgets($fp, 10000);
            $arr[] = $str;
        }
        for ($i = sizeof($arr) - 1; $i >= 0; $i--) {
            echo $arr[$i] . "<br>";
        }
//        fpassthru($fp);
        fclose($fp);
    }

function fileupload()
    {
        $uploaddir = '/home/ubuntu/rest-api-test/upload';
        $uploadfile = $uploaddir . basename($_FILES['userfile']['name']);

        if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) 
        {
		echo "파일이 유효하고, 성공적으로 업로드 되었습니다.\n";
		return;
        }
	else
        {
		print "파일 업로드 공격의 가능성이 있습니다!\n";
		return;
        }

        echo '자세한 디버깅 정보입니다:';
        print_r($_FILES);

        return;
    }
