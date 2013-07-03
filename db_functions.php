<?php

define ( 'CUSTOMERS_TABLE', 'web_ext_kunden' );
define ( 'USER_TABLE', 'web_ext_mobile_users' );
define ( 'MANIFEST_TABLE', 'web_ext_gestellungen' );

class DB_Functions {
	
	private $db;
	private $user_table;
	
	function __construct() {
		include_once './db_connect.php';
		$this->db = new DB_Connect();
		$this->db->connect();
	}
	
	function __destruct() {
	}
	
	public function searchManifest($manifestID, $speditionID, $manifestPwd) {
		
		// get spedition name
		$query = "SELECT name FROM " . CUSTOMERS_TABLE;
		$query .= " WHERE zoll_nr = '" . $speditionID . "';";
		$searchQuery = mysql_query($query) or die("cannot select spedition name from web-ext db");
		
		if ($row = mysql_fetch_array($searchQuery))
			$json["spedition_name"] = $row["name"];
			
			// get manifest positions
		$query = "SELECT eori_nr, nl_nr, mawb_prefix, mawb_nr, mrn_nr, extinf_befoerderm_ladeort, extinf_befoerderm_kz, b_status, info_text, error_text, info_kz, error_kz FROM " . MANIFEST_TABLE;
		$query .= " WHERE truckmanifest_lfdnr = " . $manifestID . " AND zoll_nr = '" . $speditionID . "'";
		$query .= " order by mawb_prefix, mawb_nr;";
		$searchQuery = mysql_query($query) or die("cannot select manifest positions from web-ext db");
		
		$json["positions"] = array();
		if (mysql_num_rows($searchQuery) > 0) {
			while ($row = mysql_fetch_array($searchQuery)) {
				$err_str = "";
				if ($row["error_kz"] == 'J') 	$err_str = $row["error_text"];
				else if ($row["info_kz"] == 'J')	$err_str = $row["info_text"];
				
				$position = array();
				$position["eori_nr"] = $row["eori_nr"];
				$position["nl_nr"] = $row["nl_nr"];
				$position["mawb_prefix"] = $row["mawb_prefix"];
				$position["mawb_nr"] = $row["mawb_nr"];
				$position["mrn_nr"] = $row["mrn_nr"];
				$position["extinf_befoerderm_ladeort"] = $row["extinf_befoerderm_ladeort"];
				$position["extinf_befoerderm_kz"] = $row["extinf_befoerderm_kz"];
				$position["b_status"] = $row["b_status"];
				$position["detail_txt"] = $err_str;
				
				array_push($json["positions"], $position);
			}
		}
		return json_encode($json);
	}
	
	/**
	 * Submit presentation to customs
	 */
	public function submitMrns($positions, $speditionID, $manifestID, $regID, $user) {
		
		// ?REQUEST=MOBILE_SUBMIT&positions=["13DE586500007535E7"]&time=1371309528668&speditionID=8999155&manifestID=437&regId=APA91bFxtjS9ztXA5jKOBaQtkQzDpgvjZ9-oBr-_LUx3jLuoVCUcGH8Xg1r60o-N80AJmDXWeh_f4uygC0EizVJ0IE6u7Aiz5j6buROUT9yFATKzXPTfX0ymaoB7_tTqVrOKOnkEIzp25lBfVKm7vTeMk5dKLdq4IQ&name=tfcfddg
		// -->
		// UPDATE web_ext_gestellungen SET b_status = NULL WHERE zoll_nr =
		// '8999155' AND truckmanifest_lfdnr = 437 AND mrn_nr =
		// '13DE586500007535E7'
		
		$query = "UPDATE " . MANIFEST_TABLE . " SET b_status = 11";
		
		// available for push devices only!
		if (isset($regID) && isset($user)) {
			$appID = (isset($regID) && !empty($regID)) ? $this->getAppId($regID) : null;
			if (isset ($appID))
				$query .= ", bearbeiter = '" . $user . "', app_id = " . $appID;
		}
		
		for($i = 0; $i < sizeof($positions); $i ++) {
			$curQuery = $query . " WHERE zoll_nr = '" . $speditionID . "' AND truckmanifest_lfdnr = " . $manifestID . " AND mrn_nr = '" . $positions [$i] . "'";
			$curQuery = mysql_query($curQuery) or die("{'fail' : '$positions[$i]'}");
		}
	}
	
	/**
	 * Update flight number and location on AWB position
	 */
	public function editFlight($speditionID, $manifestID, $awbPrefix, $awbNo, $flightNo, $flightLoc) {
		
		$query = "UPDATE " . MANIFEST_TABLE . " SET extinf_befoerderm_ladeort = '" . $flightLoc . "', flight = '" . $flightNo . "', extinf_befoerderm_kz = '" . $flightNo . "'";
		$query .= " WHERE mawb_nr = '" . $awbNo . "' AND mawb_prefix = '" . $awbPrefix . "' AND truckmanifest_lfdnr = '" . $manifestID . "' AND zoll_nr = '" . $speditionID . "'";
		
		$query = mysql_query($query) or die ("{'fail' : '$awbNo'}");
	}
	
	/**
	 * Storing new GCM user (Google PUSH service)
	 * 
	 * @return user details
	 */
	public function storeUser($gcm_regid, $email, $name, $phone_no) {
		
		// user exists but reg id or gmail account changed? -> update & return.
		if ($this->updateUser($name, $email, $phone_no, $gcm_regid))
			return true;
			
			// else: create new user.
		$result = "INSERT INTO " . USER_TABLE . "(gcm_regid, email, user_name, tel, created_at) ";
		$result .= "VALUES('$gcm_regid', '$email', '$name', '$phone_no', NOW())";
		$result = mysql_query($result);
		
		// check for successful store & return user details
		if ($result) {
			$id = mysql_insert_id(); // last inserted id
			$result = mysql_query("SELECT * FROM " . USER_TABLE . " WHERE app_id = $id") or die(mysql_error());
			
			if (mysql_num_rows($result) > 0) {
				return mysql_fetch_array($result);
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	/**
	 * Getting all users
	 */
	public function getAllUsers() {
		$result = mysql_query("select * FROM " . USER_TABLE);
		return $result;
	}
	
	/**
	 * Getting row by app_id
	 * called by push2appid()
	 */
	public function getUserByAppid($appid) {
		$result = mysql_query("select * FROM " . USER_TABLE . " WHERE app_id = $appid");
		return $result;
	}
	
	public function updateUser($name, $mail, $phone, $gcm_regid) {
		$result = "UPDATE " . USER_TABLE . " ";
		
		if ($this->userExistsGcmId($gcm_regid)) {
			$result .= "SET user_name='$name', email='$mail', tel='$phone' ";
			$result .= "WHERE gcm_regid = '$gcm_regid'";
			$result = mysql_query ($result);
			return true;
		}
		
		if ($this->userExistsGmail($mail)) {
			$result .= "SET user_name='$name', gcm_regid = '$gcm_regid', tel='$phone' ";
			$result .= "WHERE email = '$mail'";
			$result = mysql_query($result);
			return true;
		}
		return false;
	}
	
	public function getAppId($regId) {
		$result = mysql_query ("SELECT app_id FROM " . USER_TABLE . " WHERE gcm_regid = '" . $regId . "'" );
		if ($result)
			return mysql_fetch_array($result);
		else
			return - 1;
	}
	
	public function getAppIdByMail($email) {
		$result = mysql_query("SELECT app_id FROM " . USER_TABLE . " WHERE email = '$email'");
		return $result;
	}
	
	public function userExists($regId, $gmail) {
		return ($this->userExistsGcmId($regId) || $this->userExistsGmail($gmail));
	}
	
	public function userExistsGcmId($regId) {
		return (isset($gmail) && $this->getAppId($regId) > 0);
	}
	
	public function userExistsGmail($gmail) {
		return (isset($gmail) && mysql_num_rows($this->getAppIdByMail($gmail)) > 0);
	}
	
	public function deleteUser($gcm_regid, $email) {
		$result = "DELETE FROM " . USER_TABLE . " WHERE gcm_regid = '$gcm_regid'";
		if (isset ($email) && ! empty ($email))
			$result .= " OR email = '$email'";
		
		$result = mysql_query($result);
		
		if (mysql_num_rows($result) > 0) {
			return mysql_fetch_array($result);
		} else {
			return false;
		}
		return $result;
	}

}

?>