<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Drnxloc\LaravelHtmlDom\HtmlDomParser;



class UserController extends Controller
{

    function getAccessToken($header){
        $arrHeader = explode("accessToken=",$header);
        $arrHeader = explode(";",$arrHeader[1]);
        $accessToken = $arrHeader[0];
        return $accessToken;
    }

    function responseJson($data, $message = '', $code = 200)
    {
        return response()->json(
        	[
                'data' => $data,
                'message' => $message,
                'status' => $code,
            ], 
        	$code,
        	[
                'Message' => $message,
            ]
    	);
    }

    function getUserDataFromHTML($curl, $accessToken)
    {
        $url = 'https://www.farmasi.ro/account/index';

        $user_agent='Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_COOKIE => 'accessToken='.$accessToken,
            CURLOPT_USERAGENT      => $user_agent, //set user agent
            CURLOPT_COOKIEFILE     =>"cookie.txt", //set cookie file
            CURLOPT_COOKIEJAR      =>"cookie.txt", //set cookie jar
        ));
        $response = curl_exec($curl);

        $dom = HtmlDomParser::str_get_html( $response );

        //Consultant Id
        $consultantIdElem = $dom->find('input[name=_consultantId]')[0];
        $consultantId = $consultantIdElem->attr['value'];
        //Consultant Image
        $imageElem = $dom->find('img[id=topBannerLeftImage]')[0];
        $image = $imageElem->attr['src'];
        //Full Name
        $fullNameElem = $dom->find('span[class=name]')[0];
        $fullName = $fullNameElem->innerText();
        $fullName = trim(preg_replace('/\s\s+/', ' ', $fullName));
        //Name Title
        $nameTitleElem = $dom->find('span[class=nameTitle]')[0];
        $nameTitle = $nameTitleElem->innerText();
        $nameTitle = trim(preg_replace('/\s\s+/', ' ', $nameTitle));
        //Address
        $addressDeatailElem = $dom->find('span[class=adressDetail]')[0];
        $addressDeatail = $addressDeatailElem->innerText();
        $addressDeatail = trim(preg_replace('/\s\s+/', ' ', $addressDeatail));

        $data = array(json_encode([
            "consultant_id" => $consultantId,
            "image" => $image,
            "full_name" => $fullName,
            "title" => $nameTitle,
            "address" => $addressDeatail
        ]));

        return $data;
    }

    //
    function login(Request $req){

        //Request URL: https://www.farmasi.ro/Account/_Login/
        //Params: consultantNo, password

        $url = 'https://www.farmasi.ro/Account/_Login/';

        $postFields = array(
            "consultantNo" => env('CONSULTANT_NO'),
            "password" => env('CONSULTANT_PASS')
        );

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_HEADER => true,
            CURLOPT_COOKIEJAR => 'cookie.txt',

        ));

        $response = curl_exec($curl);


        // Then, after your curl_exec call:
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        $body = json_decode($body);
       
        $accessToken = $this->getAccessToken($header);

        $success = $body->Success;
        $errorMessage = count( $body->Errors ) > 0 ? $body->Errors[0]->Message : '';


        $data = [];
      
        if($success == true){
            $data = $this->getUserDataFromHTML($curl, $accessToken);
        }

        $err = curl_error($curl);
        curl_close($curl);

        if ($err) 
        {
           $errorMessage = "cURL Error #:" . $err;
        }

        return $this->responseJson($data, $errorMessage, 200);
    }

  
}
