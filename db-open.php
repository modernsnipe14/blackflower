<?php


  if (!@include('cad.conf')) {
    print "Critical error: CAD configuration file is missing or unreadable.  Contact your CAD system administrator.";
    exit;
  }

  $link = new mysqli($DB_HOST, $DB_USER, $DB_PASS) or die("Could not connect : " . $link->error);
  $link->select_db($DB_NAME) or die("Could not select database");

  function MysqlQuery ($sqlquery) {
    global $link;
    $return = $link->query($sqlquery) or die("CRITICAL ERROR\nIn query: $sqlquery<br>\nError: ".$link->error);
    return $return;
  }


  function MysqlGrabData ($sqlquery) {
    $return = MysqlQuery($sqlquery);
    $num_rows = mysqli_num_rows($return);
    if ($num_rows != 1) {
      print "Internal error, expected 1 row (got $num_rows) in query [$sqlquery]";
      syslog(LOG_CRIT, "MysqlGrabData: Internal error - saw $num_rows rows for [$sqlquery]");
    }
    $rval = mysqli_fetch_array($return, MYSQLI_NUM);
    mysqli_free_result($return);
    return $rval[0];
  }

  function MysqlClean ($array, $thing) {
    global $link;
    $input = $link -> real_escape_string ($array[$thing]);
    return $input;
  }
function InsertID()
{
    global $link;
    $rid = mysqli_insert_id($link);
    return $rid;
}
  function MYAFFROWS ()
  {
	  global $link;
	  $thing = mysqli_affected_rows ($link);
	  return $thing;
  }
  function MysqlUnClean ($input) {
    $input = htmlentities($input);
    return ($input);
  }

  header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
  header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
  header("Cache-Control: no-store, no-cache, must-revalidate");
  header("Cache-Control: post-check=0, pre-check=0", false);
  header("Pragma: no-cache");
?>
