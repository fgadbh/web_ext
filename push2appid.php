<?php
include_once ("./db_functions.php");

$i_AppId = 0;
// Get arguments
if (isset($_GET["appid"]))
    $i_AppId = $_GET["appid"];
if ($i_AppId == null)
    $i_AppId = 0;
if (isset($_GET["msg"]))
    $s_Msg = $_GET["msg"];
if ($s_Msg == null)
    $s_Msg = "";
    
    // Check on valid AppId
if (! is_numeric($i_AppId) || $i_AppId <= 0) {
    echo "\r\nArgumentenfehler\r\n";
    return (- 1);
}

// Get and check appid in database
$no_of_users = 0;
$db = new DB_Functions();
$user = $db->getUserByAppid($i_AppId); // Get web_ext_mobile_users.* where
                                          // AppId
if ($user != false)
    $no_of_users = mysql_num_rows($user);
if ($no_of_users <= 0) {
    echo "\r\nApp_Id " . $i_AppId . " unbekannt!\r\n";
    return (- 1);
}
// Get values
$row = mysql_fetch_array($user);
$RegId = $row["gcm_regid"];
if ($RegId == "" || $RegId == null) {
    echo "\r\nGCM_RegId (" . $i_AppId . ") unbekannt!\r\n";
    return (- 1);
}

include_once './gcm.php';
$gcm = new GCM();

$registration_ids = array($RegId);

$message = array("message" => $s_Msg);

$result = $gcm->send_notification($registration_ids, $message);
if ($result === FALSE) {
    echo "\r\nPushNotification fehlgeschlagen!\r\n";
    return (- 1);
}
return (1);

?>
