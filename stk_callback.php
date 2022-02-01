<?php
  include_once 'sms.php';

  $stkCallbackResponse = file_get_contents('php://input');
  $logFile = "stkPushCallbackResponse.json";
  $result = json_decode($stkCallbackResponse);
  $resultCode = $result->Body->stkCallback->ResultCode;

  // Communicate results of transaction with DB to update account status based on transaction success/failure
  if ($resultCode == 0) {
    // Tell DB Transaction is complete and update account status
    fwrite($log, "Transaction Successful ".$resultCode);
  } else {
    // Tell DB transaction is incomplete
    fwrite($log, "Transaction Failed ".$resultCode);
  }
  fclose($log);
 ?>
