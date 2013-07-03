<?php
$json = array();
include_once './db_functions.php';
include_once './gcm.php';

$db = new DB_Functions();
$gcm = new GCM();
$request = $_REQUEST["REQUEST"];
$result = 0;

switch ($request) {
    
    /**
     * Registering a user device & Store reg id in users table
     */
    case "MOBILE_REGISTER":
        
        if (isset($_REQUEST["name"]) && isset($_REQUEST["regId"])) {
            
            $gcm_regid = $_REQUEST["regId"]; // GCM Registration ID
            $name = $_REQUEST["name"];
            $email = (isset($_REQUEST["email"])) ? $_REQUEST["email"] : "";
            $phone_no = (isset($_REQUEST["phone_no"])) ? $_REQUEST["phone_no"] : "";
            
            $res = $db->storeUser($gcm_regid, $email, $name, $phone_no);
            $result = 1;
        
        }
        echo $result;
        die();
        return;
    /**
     * Unregister a user device
     */
    case "MOBILE_UNREGISTER":
        if (isset($_REQUEST["regId"])) {            
            $gcm_regid = $_REQUEST["regId"];
            $result = $db->deleteUser($gcm_regid, $_REQUEST["email"]);        
        }
        echo $result;
        die();
        return;
    
    case "MOBILE_IS_REGISTERED":
        if (isset($_REQUEST["regId"]))
            $result = $db->userExists($_REQUEST["regId"], $_REQUEST["email"]);
        
        echo $result;
        die();
        return;
    
    case "MOBILE_SEARCH":
        $result = - 1;
        
        // check input params and pwd
        $manifestID = (isset($_REQUEST["manifestID"])) ? $_REQUEST["manifestID"] : "";
        $speditionID = (isset($_REQUEST["speditionID"])) ? $_REQUEST["speditionID"] : "";
        $manifestPwd = (isset($_REQUEST["manifestPwd"])) ? html_entity_decode($_REQUEST["manifestPwd"]) : "";

        if ($manifestID != "" && $speditionID != "" && $manifestPwd != "") {
            $result = $db->searchManifest($manifestID, $speditionID, $manifestPwd);
        }
        
        echo $result;
        die();
        return;
    
    case "MOBILE_EDIT_FLIGHT":
        $result = - 1;
        
        $speditionID = $_REQUEST["speditionID"];
        $manifestID = $_REQUEST["manifestID"];
        $awbTotal = $_REQUEST["mawb_nr"];
        
        $flightNo = $_REQUEST["extinf_befoerderm_kz"];
        $flightLoc = $_REQUEST["extinf_befoerderm_ladeort"];
        
        if (isset($manifestID) && isset($speditionID) && isset($awbTotal) && isset($flightNo) && isset($flightLoc)) {
                    
			$awbTotal = explode("-", $awbTotal);
			if (count($awbTotal) > 1) {
				$result = $db->editFlight($speditionID, $manifestID, $awbTotal[0], $awbTotal[1], $flightNo, $flightLoc);
			}
		}
                
		echo $result;
		die();
		return;
	
	case "MOBILE_SUBMIT":
		$result = - 1;
		
		if (isset($_REQUEST["positions"]) && isset($_REQUEST["speditionID"]) && isset($_REQUEST["manifestID"])) {
			$positions = json_decode($_REQUEST["positions"]);
			$speditionID = $_REQUEST["speditionID"];
			$manifestID = $_REQUEST["manifestID"];
			
			if (isset($positions) && ! empty($positions)) {
				$regId = isset($_REQUEST["regId"]) ? $_REQUEST["regId"] : null;
				$name = isset($_REQUEST["name"]) ? $_REQUEST["name"] : null;
				$result = $db->submitMrns($positions, $speditionID,	$manifestID, $regId, $name);
			}
		}
		echo $result;
		die();
		return;
}
?>