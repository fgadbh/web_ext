<?php
if (isset($_GET["regId"]) && isset($_GET["message"])) {
    $regId = $_GET["regId"];
    $message = $_GET["message"];
    
    include_once './gcm.php';
    $gcm = new GCM();
    
    $registration_ids = array($regId);
    
    $message = array("message" => $message);
    
    $result = $gcm->send_notification($registration_ids, $message);
    echo $result;

}
?>