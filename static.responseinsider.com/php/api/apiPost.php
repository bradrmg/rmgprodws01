<?php
require_once("../libs/curl.php");

$db_host = "OR-TSSQL01.response.corp";
$db_user = "dwWEIci16rGByGw8";
$db_pass= "R22tCY2kkK8UguFU";
$database = "LEADS";

$dbhandle = mssql_connect($db_host, $db_user, $db_pass) or die("could not connect to sql server on $serverName");
$selected = mssql_select_db($database, $dbhandle) or die("could not connect to database $database");

$apiID = $_REQUEST['ApiID'];
$companyID = $_REQUEST['CompanyID'];

$sql = "SELECT * from ApiPosts WHERE CompanyID = $companyID AND ApiID = $apiID";
$query = mssql_query($sql);
$result = mssql_fetch_assoc($query);

$postData = $_REQUEST;

if(isset($postData['ApiID'])){unset($postData['ApiID']);}
if(isset($postData['CompanyID'])){unset($postData['CompanyID']);}

$tempArr = array();
foreach($postData as $key => $value){
	if(array_key_exists($key, $result)){
		$tempArray[$result[$key]] = $value;
	}	
}

$vars = http_build_query($tempArray);
$postingUrl = $result['PostingURL'];
$returnRslt = curl_post($postingUrl, count($tempArray), $vars);

/*************************************************************
* writing the result to the database after post
*************************************************************/
$sqlCols = "";
$sqlVals = "";
foreach($_REQUEST as $key => $value){
	switch($key){
        	case "date":
             		if($value != ""){
                		$sqlCols .= "date,";
                        	$sqlVals .= "'" . date("m/d/Y H:i:s", strtotime($value)) . "',";
               		}else{
				$sqlCols .= "date,";
                                $sqlVals .= "'" . date("m/d/Y H:i:s") . "',";
                	}
                	break;
           	default:
			$sqlCols .= urldecode($key) . ",";
		        $sqlVals .= "'" . urldecode(str_replace("'", "''", strip_tags($value))) . "',";
                        break;
        }
}
$sqlCols = substr($sqlCols, 0, strlen($sqlCols)-1);		//this gets rid of the last comma in the string generated by the forloop
$sqlVals = substr($sqlVals, 0, strlen($sqlVals)-1);
//$sqlCols = str_replace("'", "''", strip_tags($sqlCols));	//if the user enters a single quote in the fields, the query will fail, tehrefore the double quotes will escape it
//$sqlVals = str_replace("'", "''", strip_tags($sqlVals));
$returnRslt = str_replace("'", "''", strip_tags($returnRslt));
$sql =<<<EOD
INSERT INTO ApiPostResults (PostingURL,PostVars,PostingResult,$sqlCols)
VALUES ('$postingUrl','$vars','$returnRslt',$sqlVals)
EOD;
mssql_query($sql);
//error_log($sql);
echo "completed";























?>
