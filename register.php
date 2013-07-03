<?php

// response json
$json = array();

/**
 * Registering a user device & Store reg id in users table
 */
if (isset($_POST["name"]) && isset($_POST["regId"])) {
    
    $gcm_regid = $_POST["regId"]; // GCM Registration ID
    $name = $_POST["name"];
    $email = (isset($_POST["email"])) ? $_POST["email"] : "";
    $phone_no = (isset($_POST["phone_no"])) ? $_POST["phone_no"] : "";
    
    include_once './db_functions.php';
    include_once './GCM.php';
    
    $db = new DB_Functions();
    $gcm = new GCM();
    
    $res = $db->storeUser($gcm_regid, $email, $name, $phone_no);
    
    $registration_ids = array($gcm_regid);
    
    $message = array("register" => "success");
    
    $result = $gcm->send_notification($registration_ids, $message);
    
    echo $result;
} else {
    // user details missing
}
?>