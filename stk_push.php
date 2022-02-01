<?php

require_once './vendor/autoload.php';
$json = file_get_contents('php://input');

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

// Converts it into a PHP object
$data = json_decode($json);

$phoneNumber = $data->mobile;
$phoneNumber = str_replace("whatsapp:", "", $phoneNumber);
$amount = $data->amount;

send_stkpush($phoneNumber, $amount);

function send_stkpush($phoneNumber, $amount) {
    date_default_timezone_set('Africa/Nairobi');

    # access token
    $consumerKey = getenv('MPESA_CONSUMER_KEY'); //Fill with your app Consumer Key
    $consumerSecret = getenv('MPESA_CONSUMER_SECRET'); // Fill with your app Secret

    # define the variales
    # provide the following details, this part is found on your test credentials on the developer account
    $businessShortCode = getenv('BUSINESS_SHORT_CODE');
    $passkey = getenv('MPESA_PASS_KEY');  

    /*
      This are your info, for
      $PartyA should be the ACTUAL clients phone number or your phone number, format 2547********
      $AccountRefference, it maybe invoice number, account number etc on production systems, but for test just put anything
      TransactionDesc can be anything, probably a better description of or the transaction
      $Amount this is the total invoiced amount, Any amount here will be 
      actually deducted from a clients side/your test phone number once the PIN has been entered to authorize the transaction. 
      for developer/test accounts, this money will be reversed automatically by midnight.
    */

    $partyA = str_replace('+', '', $phoneNumber); // This is your phone number, 
    $accountReference = 'testapi';
    $transactionDesc = 'testapi';

    # Get the timestamp, format YYYYmmddhms -> 20181004151020
    $timestamp = date('YmdHis');    

    # Get the base64 encoded string -> $password. The passkey is the M-PESA Public Key
    $password = base64_encode($businessShortCode.$passkey.$timestamp);

    # header for access token
    $headers = ['Content-Type:application/json; charset=utf8'];

      # M-PESA endpoint urls
    $access_token_url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
    $initiate_url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

    # callback url
    $callBackURL = getenv('STK_PUSH_CALLBACK_URL');  

    $curl = curl_init($access_token_url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl, CURLOPT_HEADER, FALSE);
    curl_setopt($curl, CURLOPT_USERPWD, $consumerKey.':'.$consumerSecret);
    $result = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $result = json_decode($result);
    $access_token = $result->access_token;  
    curl_close($curl);

    # header for stk push
    $stkheader = ['Content-Type:application/json','Authorization:Bearer '.$access_token];

    # initiating the transaction
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $initiate_url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $stkheader); //setting custom header

    $curl_post_data = array(
      //Fill in the request parameters with valid values
      'BusinessShortCode' => $businessShortCode,
      'Password' => $password,
      'Timestamp' => $timestamp,
      'TransactionType' => 'CustomerPayBillOnline',
      'Amount' => $amount,
      'PartyA' => $partyA,
      'PartyB' => $businessShortCode,
      'PhoneNumber' => $partyA,
      'CallBackURL' => $callBackURL,
      'AccountReference' => $accountReference,
      'TransactionDesc' => $transactionDesc
    );

    $data_string = json_encode($curl_post_data);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
    $curl_response = curl_exec($curl);

    echo $curl_response;

}