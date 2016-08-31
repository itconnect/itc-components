<?php

$DEV_MODE = 0; // set to 0 for production DB (cactools), 1 for test DB (cactoolstest)

############## Configuration stuff ##############

function connectDB($mode = 'all'){
	global $DEV_MODE;

	if ($mode=='RO'){
		$user = "wwwro";
	}else{
		$user = "www";
	}

	if ($DEV_MODE){
		echo "<h3 style=\"color: red\">THIS IS USING THE TEST DATABASE</h3>";
		$db= pg_connect("host=sequel1.cac.washington.edu dbname=cactoolstest user=$user");
	} else {
		$db= pg_connect("host=sequel1.cac.washington.edu dbname=cactools user=$user");
	}
	pg_query($db, "set DateStyle to SQL");
	return($db);
}

function getEditors(){
	$valid = array("jadegar"=>"all", "ncrohde"=>"all", "hayden32"=>"all", "allisw4"=>"all", "hbrough"=>"all", "mawaskom"=>"all", "rebecar"=>"all", "susl"=>"all", "pasnyder"=>"all", "bweinst"=>"some", "ldugan"=>"some", "llfinch"=>"some");
	return ($valid);
}

function getAddress($a){
	global $DEV_MODE;

	if ($DEV_MODE){
		return("jadegar@uw.edu");
	} else {
		return("$a");
	}
}

############## Global functions ##############

function getID($db, $netid){
 $query = "SELECT id from contacts where netid='$netid'";
 $result = runQuery($db, $query);
 $num = pg_numrows($result);
 if($num<1){
	echo "<h3>UW NetID error.  Unable to select the correct record.</h3>";
	exit;
 }else{
   $a = pg_fetch_array($result, 0);
   return($a[0]);
 }
}


function runQuery($db, $query){
 $result = pg_query($db, $query) or die("Could not execute query");
 return($result);
}

function sanitize($string)
{//see www.phpbuilder.com/columns/sanitize_inc_php.txt
  $pattern[0] = '/(\\\\)/';
  $pattern[1] = "/\"/";
  $pattern[2] = "/'/";
  $replacement[0] = '\\\\\\';
  $replacement[1] = '\"';
  $replacement[2] = "\\'";
  return preg_replace($pattern, $replacement, $string);
}
?>
