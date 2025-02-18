<?php
  $subsys="incidents";

  require_once('db-open.php');
  include('local-dls.php');
  require_once('session.inc');
  require_once('functions.php');

  // Scroll incidents per page
  $scrollipp = 15;
  $filterscroll='';
  $filterdate='';
  $filtercalltype='';
  $start='';

  // Begin filter GET/POST parsing

  // If apply_filters was posted...
  if (isset($_POST['apply_filters'])) {

    if ($_POST['date'] != "") {
      $filterdate = $_POST['date'];
    }

    if ($_POST['calltype'] != "") {
      $filtercalltype = $_POST['calltype'];
    }

    if (isset($_POST['scroll'])) {
      $filterscroll = "yes";
    }
    else {
      $filterscroll = "no";
    }
  }

  // 'owever... if the remove filters button was posted, reset all filters
  elseif (isset($_POST['remove_filters'])) {
    $filterdate='';
    $filtercalltype='';
    $start='';
    $filterscroll = "yes";
  }

  // Process GETs
  else {
    if (isset($_GET['date']) && $_GET['date'] != "") {
      $filterdate = $_GET['date'];
    }

    if (isset($_GET['calltype']) && $_GET['calltype'] != "") {
      $filtercalltype = $_GET['calltype'];
    }

    if (isset($_GET['scroll'])) {
      if ($_GET['scroll'] == "yes") {
        $filterscroll = "yes";
      }
      elseif($_GET['scroll'] == "no") {
        $filterscroll = "no";
      }
      // TODO: could leave it unset if someone typed manually.  change elseif to else?
    }

    if (isset($_GET['start']) && $_GET['start'] != "") {
      $start = $_GET['start'];
    }
  }

  if (isset($_POST["incidents_hide_units_oos"])) {
    if ($_POST["incidents_hide_units_oos"] == "Hide Out of Service" &&
        (!isset($_COOKIE["incidents_hide_units_oos"]) || $_COOKIE["incidents_hide_units_oos"] == "no")) {
      setcookie("incidents_hide_units_oos", "yes");
    }
    elseif ($_POST["incidents_hide_units_oos"] == "Show All Units" &&
            (!isset($_COOKIE["incidents_hide_units_oos"]) || $_COOKIE["incidents_hide_units_oos"] == "yes")) {
      setcookie("incidents_hide_units_oos", "no");
    }
    header("Location: http://".$_SERVER['HTTP_HOST'].$_SERVER["PHP_SELF"]."?".
           "date=$filterdate&calltype=$filtercalltype&scroll=$filterscroll&start=$start");
    exit;
  } 

  if (isset($_POST["incidents_hide_staging"])) {
    if ($_POST["incidents_hide_staging"] == "Hide Staging Locations" &&
        (!isset($_COOKIE["incidents_hide_staging"]) || $_COOKIE["incidents_hide_staging"] == "no")) {
      setcookie("incidents_hide_staging", "yes");
    }
    elseif ($_POST["incidents_hide_staging"] == "Show Staging Locations" &&
            (!isset($_COOKIE["incidents_hide_staging"]) || $_COOKIE["incidents_hide_staging"] == "yes")) {
      setcookie("incidents_hide_staging", "no");
    }
    header("Location: http://".$_SERVER['HTTP_HOST'].$_SERVER["PHP_SELF"]."?".
           "date=$filterdate&calltype=$filtercalltype&scroll=$filterscroll&start=$start");
    exit;
  }

  header_html("Dispatch :: Incidents","",
              $_SERVER['PHP_SELF']."?"."date=$filterdate&calltype=$filtercalltype&scroll=$filterscroll&start=$start");
?>
<body vlink="blue" link="blue" alink="cyan">

<form name="myform" 
 action="incidents-frame.php?<?php echo "date=$filterdate&calltype=$filtercalltype&scroll=$filterscroll&start=$start";?>"
 method="post" style="margin: 0px;" target="incidents">

<!-- START Display Incidents -->
<table width="100%">
<tr><td bgcolor="#aaaaaa">
<?php

// $profile_timer_start = microtime(true);
  
  // auxiliary query for incident_units: dynamically load them into array that the main display frame will reference:
  $query = "SELECT uid,incident_id,unit FROM incident_units WHERE cleared_time IS NULL ORDER BY incident_id,uid";
  $result = MysqlQuery ($query) or die ("In query: $query<br />\nError: ". mysql_error());
  while ($line = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
    // This is awkward.  Build a better data structure?
    $incident_id = $line["incident_id"];
    if (isset($unitcount[$incident_id]))
      $unitcount[$incident_id]++;
    else
      $unitcount[$incident_id] = 1;
    $unit[$incident_id][$unitcount[$incident_id]] = $line["unit"];
  }
  mysqli_free_result($result);
  
  // auxiliary query for dispatch and arrival times:
  $dispatch_times = array();
  $arrival_times = array();
  $query = "SELECT incident_id, MIN(iu.dispatch_time) as dispatch_time, MIN(iu.arrival_time) as arrival_time FROM incident_units iu GROUP BY iu.incident_id";
  $result = MysqlQuery ($query) or die ("In query: $query<br />\nError: ". mysql_error());
  while ($line = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
    $dispatch_times[$line["incident_id"]] = $line["dispatch_time"];
    $arrival_times[$line["incident_id"]] = $line["arrival_time"];
  }


  // Load incident lock info
  $locks = array();
  $query_locks = 'SELECT il.incident_id, il.user_id, il.takeover_timestamp, u.username, u.name FROM incident_locks il LEFT OUTER JOIN users u on il.user_id=u.id WHERE takeover_timestamp IS NULL';
  $locks_result = MysqlQuery($query_locks);
  while ($lock_row = mysqli_fetch_object($locks_result)) {
    $locks[(int)$lock_row->incident_id] = $lock_row;
    if ($DEBUG) {
      syslog(LOG_DEBUG, "Set lock flag for incident " . $lock_row->incident_id);
    }
  }

  $Channels = array();
  $channels = MysqlQuery("SELECT * FROM channels");
  if (mysqli_num_rows($channels)) {
    while ($channel = mysqli_fetch_object($channels)) {
      $Channels[$channel->channel_id] = array(
        'incident_id'       => $channel->incident_id, 
        'channel_name'      => $channel->channel_name, 
        'repeater'          => $channel->repeater);
    }
  }
// $profile_timer_end = microtime(true);

  //syslog(LOG_DEBUG, "aux queries: " . $profile_timer_end."-".$profile_timer_start."=".substr(($profile_timer_end - $profile_timer_start),0,5). " seconds");

// $profile_timer_start = microtime(true);
  // PREPARE MAIN QUERY
  $query_select = '
    SELECT i.*, 
    TIME_TO_SEC(TIMEDIFF(NOW(),updated)) as stale_secs, 
    TIME_TO_SEC(TIMEDIFF(NOW(),ts_opened)) as age_secs
    FROM incidents i';
  
  $query_where = ''; 
  $query_order = " ORDER BY i.incident_id DESC ";
  $query_limit = '';

  if (isset($_COOKIE["incidents_open_only"]) && $_COOKIE["incidents_open_only"]=="no") {
    // Set up to show a range of incidents depending on filter criteria and scroll window sizing
    $show_closed = 1;
    if (isset($filterdate) && $filterdate != '' && isset($filtercalltype) && $filtercalltype != '') {
      $query_where .= " WHERE DATE_FORMAT(i.ts_opened, '%Y-%m-%d') = '$filterdate' AND call_type = '$filtercalltype'";
    }
    elseif (isset($filterdate) && $filterdate != '') {
      $query_where .= " WHERE DATE_FORMAT(i.ts_opened, '%Y-%m-%d') = '$filterdate'";
    }
    elseif (isset($filtercalltype) && $filtercalltype != '') {
      $query_where .= " WHERE i.call_type = '$filtercalltype'";
    }

    if ($filterscroll == "yes") {
      if (isset($start) && $start > 0) $query_limit .= " LIMIT $start, $scrollipp";
      else                             $query_limit .= " LIMIT $scrollipp";
    }
  }
  else {
    $show_closed = 0;
    $query_where = " WHERE i.incident_status='Open'";
    if (isset($SUPERVISOR_INCIDENT_REVIEW) && $SUPERVISOR_INCIDENT_REVIEW && CheckAuthByLevel('review_incidents',$_SESSION['access_level'])) {
      $query_where .= " OR i.incident_status='Dispositioned'";
    }
  }

  $howmany = MysqlGrabData('SELECT COUNT(*) AS howmany FROM incidents i ' . $query_where);
  $query = "$query_select $query_where $query_order $query_limit";
  if ($DEBUG) {
    syslog(LOG_DEBUG, "Main incidents query: $query");
  }
  $result = MysqlQuery($query);

// $profile_timer_end = microtime(true);

// syslog(LOG_DEBUG, "main query: " . $profile_timer_end."-".$profile_timer_start."=".substr(($profile_timer_end - $profile_timer_start),0,5). " seconds");

  $td = "    <td class=\"message\" nowrap>";

  if ($filterdate != '' || $filtercalltype != '') {
    print "<b class=\"text\" style=\"color: #dd0000;\">Filters Applied</b><br />\n";
  }


?>

  <table width="100%" cellpadding="1" cellspacing="1">
  <tr>
    <td class="th" >Call No.
<?php
  if (isset($USE_INCIDENT_LOCKING) && $USE_INCIDENT_LOCKING && 
    isset($LOCKING_SHOW_USERNAME) && $LOCKING_SHOW_USERNAME)  
      print '<i>/open by</i>';
?> </td>
    <td class="th">Incident Details</td>
    <td class="th">Channel/Location</td>
    <td class="th">Call Type</td>
    <td class="th" style="font-size: 9">Last<br>Updated?</td>
    <td class="th" >Call&nbsp;Time</td>
    <td class="th" >Dispatch</td>
    <td class="th" >Arrival</td>
<?php
  if ($show_closed) {
    print "<td class=\"th\" width=\"50\">Complete</td>\n";
  }
?>
    <td class="th">Unit(s) Assigned</td>
  </tr>
<?php
  // ------------------------------------------------------------------------
  // Incident Display Table

  if (mysqli_num_rows($result)) {
    // Loop through all the incidents that match the query
    while ($line = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
      echo "  <tr>\n";
      $incident_id = $line["incident_id"];

      if (isset($USE_INCIDENT_LOCKING) && $USE_INCIDENT_LOCKING)  {
        if (isset($locks[$incident_id]) && $locks[$incident_id]->user_id > 0) {
          if ($locks[$incident_id]->user_id == $_SESSION["id"]) 
            $td = "    <td class=\"message-iself\" nowrap>";
          else 
            $td = "    <td class=\"message-iother\" nowrap>";
        }
        else 
          $td = "    <td class=\"message\" nowrap>";
      }


      if ($line["incident_status"] == 'Dispositioned' || $line["incident_status"] == 'Closed') {
        $quality = "<span style='color: #666666;'>";
        if (isset($SUPERVISOR_INCIDENT_REVIEW) && $SUPERVISOR_INCIDENT_REVIEW && $line["incident_status"] == 'Dispositioned') {
            $td = "   <td class=\"message-review\" nowrap>";
        }
      }
      elseif (isset($line["ts_opened"]) && $line["ts_opened"] <> "" && $line["age_secs"] < 300)
        $quality="<span style='font-weight: bold;'>";
      else
        $quality="<span style='font-weight: normal;'>";

      $stale_secs = (int)$line["stale_secs"];
      if ($stale_secs < 900)  // don't display before 15 minutes
        $staleness = "";
      elseif ($stale_secs < 1800) // 15-30 minutes: gray
        $staleness = "<span style='color: grey'>".(int)($stale_secs/60)."&nbsp;min</span>";
      elseif ($stale_secs < 3600) // 30-60 minutes: black
        $staleness = "<span style='color: black'>".(int)($stale_secs/60)."&nbsp;min</span>";
      elseif ($stale_secs < 7200) // 60-120 minutes: red
        $staleness = "<span style='color: red'>".(int)($stale_secs/60)."&nbsp;min</span>";
      elseif ($stale_secs < 86400)  // blink red after 120 minutes
        $staleness = "<span style='color: red; text-decoration:blink'>".(int)($stale_secs/3600)."&nbsp;hr&nbsp;".(int)(($stale_secs/60)%60)."&nbsp;min</span>";
      else  
        $staleness = "<span style='color: red; text-decoration:blink'>&gt;1&nbsp;day</span>";

      $href = "<a href='edit-incident.php?incident_id=$incident_id' " .
              "onClick=\"return popup('edit-incident.php?incident_id=$incident_id','incident-$incident_id',600,1000)\" ".
              "TARGET=\"_blank\">";

      // First Column "Number"
      
      if ($line["call_number"] != '') {
        echo $td, $quality, $href, str_replace("-", "&#8209;", $line["call_number"]), "</span></a>";
        if (isset($USE_INCIDENT_LOCKING) && $USE_INCIDENT_LOCKING && 
            isset($locks[$incident_id]) && $locks[$incident_id]->user_id > 0 &&
            isset($LOCKING_SHOW_USERNAME) && $LOCKING_SHOW_USERNAME) {
          print '&nbsp;&nbsp;<span style="color: #666666; font-style: italic; font-size: smaller; text-decoration: underline " title="Call '.$line["call_number"]. " is open by ".$locks[$incident_id]->name.'">'.$locks[$incident_id]->username.'</span>';
        }
      }
      else {
        echo $td, $quality, $href, 'legacy incident_id ', $incident_id, "</span></a>";  # bug 75 conversion
      }
      if (isset($SUPERVISOR_INCIDENT_REVIEW) && $SUPERVISOR_INCIDENT_REVIEW && $line['incident_status'] == 'Dispositioned' && CheckAuthByLevel('review_incidents', $_SESSION['access_level'])) {
        echo "&nbsp;&nbsp;<span style='font-size: 8pt; font-weight: bold; background-color: yellow; border: 1px solid black'>READY FOR REVIEW</span>";
        $quality = "<span style='color: #666666; font-style: italic;'>";
      }
      elseif ($line["incident_status"] == 'Dispositioned' || $line["incident_status"] == 'Closed') {
        echo "&nbsp;&nbsp;<span style='font-size: 8pt;'>[completed]</span>";
        $quality = "<span style='color: #666666; font-style: italic;'>";
      }
      echo "</td>\n";

      $display_details = MysqlUnClean($line["call_details"]);
      $details_title="";
      $details_channel="";
      if (strlen($display_details) > 40) {
        $details_title=" title=\"$display_details\"";
        $display_details = str_replace(" ", "&nbsp;",  substr($display_details,0,40)) . "...<sup style=\"color:blue\">&laquo;</sup>";
      }
      echo $td, $quality, "&nbsp;", $href, "<span $details_title>$display_details</span></a></td>\n";

      $display_location = MysqlUnClean($line["location"]);
      $location_title="";
      if (strlen($display_location) > 30) {
        $location_title=" title=\"$display_location\"";
        $display_location = str_replace(" ", "&nbsp;",  substr($display_location,0,30)) . "...<sup style=\"color:blue\">&laquo;</sup>";
      }

      echo $td, $quality; 
      $chclass = 'channel chasg';
      foreach ($Channels as $channel) {
        if ($channel['incident_id'] == $incident_id) {
          if ($channel['repeater']) $chclass .= ' b';
          echo "<span class=\"$chclass\" style=\"float: left;\">".$channel['channel_name']."&nbsp;</span>";
        }
      }
      echo "<span style=\"display: inline; float: left\" $location_title>&nbsp;$display_location</span></td>\n";

      
      echo $td, $quality, str_replace(" ", "&nbsp;", $line["call_type"]), "</span></td>\n";
      if ($line["incident_status"] == 'Dispositioned' || $line["incident_status"] == 'Closed') 
        echo $td, $quality, "</td>\n";
      else
        echo $td, $quality, "$staleness</td>\n";
      echo $td, $quality, dls_utime($line["ts_opened"]), "</span></td>\n";

      if (!array_key_exists($incident_id, $dispatch_times)) {
        if ($line["incident_status"] == 'New' || $line["incident_status"] == 'Open')  
          print "<td class=\"message undispatched_s2\">Undispatched</td>\n";
        else 
          print "<td class=\"message\"></td>\n";
      }
      else {
        echo $td, $quality, dls_utime($dispatch_times[$incident_id]), "</span></td>\n";
      }
      echo $td, $quality;
      if (isset($arrival_times[$incident_id])) {
        print dls_utime($arrival_times[$incident_id]);
      }
      print "</span></td>\n";
      if ($show_closed) {
        echo $td, $quality, dls_utime($line["ts_complete"]), "</span></td>\n";
      }

      if (isset($unitcount[$incident_id])) {
         $count = $unitcount[$incident_id];

         if ($count == 1)
           $display = str_replace(" ", "&nbsp;", $unit[$incident_id][1]);
         else {
           $display = implode (", ", $unit[$incident_id]);
           if (strlen($display) > 30) {
              $fulldisplay = $display;
              $display = str_replace(" ", "&nbsp;", substr($display, 0, 25));
              $display = "<span style=\"background-color: #bbbbbb\" title=\"$fulldisplay\">$display&nbsp;...</span>&nbsp;<sup style=\"font-weight: normal; color: blue\">&laquo;&nbsp;$count&nbsp;units</sup>";
           }
           else 
             $display = str_replace(" ", "&nbsp;", $display );
         }

         echo $td, $quality, $display, "</span></td>\n";
       }
       else
         echo $td, $quality, "none&nbsp;assigned</span></td>\n";

       echo "  </tr>\n";
     }
   } else {
      echo "  <tr>\n";
      echo $td, "<center>-</center></td>\n";
      echo $td, "<center>-</center></td>\n";
      echo $td, "<span style='color: #666666; font-size: 9pt;'>No active incidents.</span></td>\n";
      echo $td, "<center>-</center></td>\n";
      echo $td, "<center>-</center></td>\n";
      echo $td, "<center>-</center></td>\n";
      echo $td, "<center>-</center></td>\n";
      if ($show_closed) {
        echo $td, "<center>-</center></td>\n";
      }
      echo $td, "<center>-</center></td>\n";
      echo $td, "<center>-</center></td>\n";
      echo "  </tr>\n";
   }

   echo "  </table>\n</td></tr>\n</table>\n";

   // Are we going to scroll the results?
   if ($filterscroll == "yes") {
     // Print page back / page forward links
     echo "<center class=\"text\" style=\"margin-top: 8px;\">";

     $prevpage = (int)$start - (int)$scrollipp;
     if ($scrollipp > 0 && $start >= $scrollipp) {
       echo "<a href=\"".$_SERVER["PHP_SELF"].
            "?date=$filterdate&calltype=$filtercalltype&scroll=$filterscroll".
            "&start=$prevpage".
            "\" TARGET=\"incidents\">&lt;&lt;</a> | ";
     }
     else {
       echo "&lt;&lt; | ";
     }

     echo "<a href=\"".$_SERVER["PHP_SELF"].
          "?date=$filterdate&calltype=$filtercalltype&scroll=$filterscroll".
          "\" TARGET=\"incidents\">First Page</a> | ";

     if ($scrollipp > 0) {
       $pages = ceil($howmany / $scrollipp);
     }
     else {
       $pages = 1;
     }
     echo "Current filter will display $howmany incident";
     if ($howmany != 1) echo "s";
     echo " on $pages page";
     if ($pages != 1) echo "s";
     echo " | ";

     $nextpage = (int)$start + (int)$scrollipp;
     if ($scrollipp > 0 && $nextpage < $howmany) {
       echo "<a href=\"".$_SERVER["PHP_SELF"].
            "?date=$filterdate&calltype=$filtercalltype&scroll=$filterscroll".
            "&start=$nextpage".
            "\" TARGET=\"incidents\">&gt;&gt;</a>";
     }
     else {
       echo "&gt;&gt;";
     }

     echo "</center>\n";
   }

   mysqli_free_result($result);
   echo "<!-- END Display Incidents -->\n\n";

   $staging_query = "SELECT * from staging_locations WHERE time_released IS NULL order by location ASC";
   $staging_result = MysqlQuery($staging_query);

   $staging_locations = array();
   $staging_assignments = array();
   $unitstagings = array();
   while ($staging_row = mysqli_fetch_object($staging_result)) {
     $staging_assignments[$staging_row->staging_id] = array();
     $staging_locations[$staging_row->staging_id] = $staging_row->location;
     
   }
   mysqli_free_result($staging_result);

   $staging_assignments_query = "SELECT * FROM unit_staging_assignments WHERE time_reassigned IS NULL";
   $staging_assignments_result=MysqlQuery($staging_assignments_query);
   while ($staging_assignments_row = mysqli_fetch_object($staging_assignments_result)) {
     array_push($staging_assignments[$staging_assignments_row->staged_at_location_id], $staging_assignments_row->unit_name);
     $unit_staged_at[$staging_assignments_row->unit_name] = $staging_assignments_row->staged_at_location_id;
     
     // Dynamically program this now on behalf of Unit Availability section to avoid redoing the query then.
     $tmp = array();
     if (isset($unitstagings[$staging_assignments_row->unit_name])) 
       $tmp = $unitstagings[$staging_assignments_row->unit_name];
     array_push($tmp, $staging_assignments_row->staged_at_location_id);
     $unitstagings[$staging_assignments_row->unit_name] = $tmp;
   }
?>
     <div style="width: auto; margin: 0px; margin-top: 8px; margin-bottom: 8px;">
     <span class="text" style="display:inline;"> <b>Staging Locations</b> </span>
 
<?php 
     if (isset($_COOKIE['incidents_hide_staging']) && $_COOKIE["incidents_hide_staging"] == "yes") {
       $hiddenstaging = 1;
       print "<span class=\"text\">(Currently hidden from view, toggle with button at right...)</span>\n";
       print "<span class=\"text\" style=\"display: inline; float: right\">\n";
       print "<button <button onclick=\"setTimeout(function(){window.parent.location.reload();}, 100);\" type=\"submit\" name=\"incidents_hide_staging\" id=\"incidents_hide_staging\" ";
       print "value=\"Show Staging Locations\" title=\"Show Staging Locations\">Show Staging Locations</button>\n";
       print "</span>\n";
     }
     else {
       $hiddenstaging = 0;
       print "<span class=\"text\" style=\"display: inline; float: right\">\n";

       print "<BUTTON TYPE=\"blank\" onClick=\"return popup('edit-staging.php?add_staging_location','edit-staging',400,600)\" 
              TARGET=\"_blank\" NAME=\"edit_staging\" ID=\"new_edit_staging\">Add Staging Location</button>";
	   
       print "<button <button onclick=\"setTimeout(function(){window.parent.location.reload();}, 100);\" type=\"submit\" name=\"incidents_hide_staging\" id=\"incidents_hide_staging\" ";
       print "value=\"Hide Staging Locations\" title=\"Hide Staging Locations\">Hide Staging Locations</button>\n";
       print "</span>\n";

   if (!count($staging_locations)) {
     print "<table><Tr><td class=text>No staging locations defined.</td></tr></table>";
   }
   else {

?>
     <table width="100%">
     <tr><td bgcolor="#aaaaaa">
     <table width="100%" cellpadding="1" cellspacing="1">
       <tr>
         <td class="th" >Location</td>
         <td class="th" >Units Staged</td>
       </tr>
<?php
     // TODO: Sort by location name
     foreach (array_keys($staging_locations) as $staging_location_id) {
       print "<tr><td class=message>
                    <a onClick=\"return popup('edit-staging.php?staging_id=$staging_location_id','staging-$staging_location_id',400,600)\"
                    href=\"edit-staging.php?staging_id=".$staging_location_id."\">".$staging_locations[$staging_location_id]."</a></td>\n";
       $count = count($staging_assignments[$staging_location_id]);
       if ($count <= 0) {
         print "<td class=message> <span style='color: #666666; font-size: 9pt;'>No staged units. </span></td></tr>\n";
       }
       else {
         foreach ($staging_assignments[$staging_location_id] as $staged_unit_name) {

           //print "<td class=message>$staged_unit_name </td></tr>";
           if ($count == 1)
             $display = str_replace(" ", "&nbsp;", $staging_assignments[$staging_location_id][0]);
           else {
             $display = implode (", ", $staging_assignments[$staging_location_id]);
             if (strlen($display) > 80) {
                $fulldisplay = $display;
                $display = str_replace(" ", "&nbsp;", substr($display, 0, 25));
                $display = "<span style=\"background-color: #bbbbbb\" title=\"$fulldisplay\">$display&nbsp;...</span>&nbsp;<sup style=\"font-weight: normal; color: blue\">&laquo;&nbsp;$count&nbsp;units</sup>";
             }
             else 
               $display = str_replace(" ", "&nbsp;", $display );
           }
         }
         print "<td class=message>$display</td></tr>\n";
       }
     }
?>
</table>
</td>
</tr>
</table>

<?php
     }
   }


   print "</div>\n";
   echo "<!-- END Display Staging -->\n\n";


   print "<div style=\"width: auto; margin: 0px; margin-top: 8px; margin-bottom: 8px;\">\n";
   print "<span class=\"text\" style=\"display:inline;\"> <b>Channel Availability</b> (repeaters&nbsp;in&nbsp;<b>Bold</b>)   </span>";

   $channels = MysqlQuery("SELECT * FROM channels c ORDER BY precedence,channel_name");
   if (mysqli_num_rows($channels)) {
     while ($channel = mysqli_fetch_object($channels)) {
       $chclass='channel';
       $chtitle='This channel is available for assignment to incidents.';
       if ($channel->repeater) { $chclass .= ' b'; $chtitle.='  This is a repeated channel. '; }
       if ($channel->incident_id) { $chclass .= ' chasgix'; $chtitle = "This channel is assigned to incident ". CallNumber($channel->incident_id) .".";}
       if (!$channel->available) { $chclass .= ' chunav'; $chtitle = 'This channel is marked unavailable for assignment to incidents, contact your system administrator to change.'; }
       print "<span class=\"$chclass\" style=\"display:inline\" title=\"$chtitle\">$channel->channel_name</span>\n";
     }
     mysqli_free_result($channels);
   }
   else {
     print "<span class=\"text\" style=\"display:inline;\"><i> No channels configured. </i></span>";
   }
   print "<span class=\"text\" style=\"display: inline; float: right\">\n";
   if (CheckAuthByLevel('edit_channels', $_SESSION['access_level']) && 
        (!isset($_SESSION['readonly']) ||!$_SESSION['readonly'])) {
         print "<BUTTON TYPE=\"blank\" onClick=\"return popup('edit-channels.php','edit-channels',600,1000)\" 
              TARGET=\"_blank\" NAME=\"edit_channels\" ID=\"edit_channels\">Edit Channels</button>";
   }
   print "</div>\n";


   // -----------------------------------------------------------------------
   // Unit display section:

   if ((isset($_COOKIE["incidents_show_units"]) && $_COOKIE["incidents_show_units"] == "yes")
    || !isset($_COOKIE["incidents_show_units"])) {
     $query = "SELECT role, color_html FROM unit_roles";

     $result = MysqlQuery($query) or die ("In query: $query\nError: ".mysql_error());
     while ($line = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
       $rolecolor[$line["role"]] = $line["color_html"];
     }
     mysqli_free_result($result);


     $query_select = 'SELECT * FROM units u LEFT OUTER JOIN unit_assignments a on u.assignment=a.assignment ';
     //LEFT OUTER JOIN incident_units iu on u.unit=iu.unit ';
     $query_where  = '';

     if (isset($_COOKIE['incidents_hide_units_oos']) && $_COOKIE["incidents_hide_units_oos"] == "yes") {
       $query_where = " WHERE status NOT IN ('Out Of Service', 'Off Comm', 'Off Duty')";
     }
     $result = MysqlQuery("$query_select $query_where");
     $unitnames = array();
     $unitarray = array();
     $unitincidents = array();
     while ($unitrow = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
       array_push($unitnames, $unitrow["unit"]);
       $unitarray[$unitrow["unit"]] = $unitrow;
     }
     natsort($unitnames);
     $attachments = MysqlQuery ("SELECT * FROM incident_units WHERE cleared_time IS NULL ORDER BY incident_id ASC");
     while ($iu = mysqli_fetch_array($attachments, MYSQLI_ASSOC)) {
       //syslog(LOG_DEBUG, "Unit availability query: saw unit ".$iu["unit"]." on incident ".$iu["incident_id"]);
       $tmp = array();
       if (isset($unitincidents[$iu["unit"]])) 
         $tmp = $unitincidents[$iu["unit"]];
       array_push($tmp, $iu["incident_id"]);
       $unitincidents[$iu["unit"]] = $tmp;
       //array_push($unitincidents[$iu["unit"]], $iu["incident_id"]);
     }

     // initialize table as 1 quarter-page-width column, dynamically program if configured to do so and if it's needed.
     $columns = 4;
     $columnwidthpct = 25;  
     if (count($unitnames) > 10) {
       $columns = $INCIDENT_UNIT_COLUMNS;
       if ($columns >= 3) {
         $columnwidthpct = (int)100/$columns;
       }
     }

     $displayunits = array();

     print "<div style=\"width: auto; margin: 0px; margin-top: 8px; margin-bottom: 8px;\">\n";
     print "<table width=\"100%\"><tr><td nowrap class=\"text\">\n";
     print "<b>Unit Availability</b> &nbsp; &nbsp; (Units shown in <b>Bold</b>, Generic Units shown in ".
           "<span style=\"border: 2px dotted gray; background-color: #cccccc\"><b>Dashed Bold</b></span>. ".
           " Icons indicate designated supervisory Assignment.)\n";

     print "</td><td class=\"text\" align=\"right\">\n";

     if (isset($_COOKIE['incidents_hide_units_oos']) && $_COOKIE["incidents_hide_units_oos"] == "yes") {
       $hiddenoosunits = 1;
       print "<button onclick=\"setTimeout(function(){window.parent.location.reload();}, 100);\" type=\"submit\" name=\"incidents_hide_units_oos\" id=\"incidents_hide_units_oos\" ";
       print "value=\"Show All Units\" title=\"Show All Units\">Show All Units</button>\n";
     }
     else {
       $hiddenoosunits = 0;
       print "<button onclick=\"setTimeout(function(){window.parent.location.reload();}, 100);\" type=\"submit\" name=\"incidents_hide_units_oos\" id=\"incidents_hide_units_oos\" ";
       print "value=\"Hide Out of Service\" title=\"Hide Out of Service Units\">Hide Out of Service</button>\n";
     }
     print "</td></tr></table>\n";
     print "</div>\n";

     print "<table width=\"100%\" border=0>\n";

     print "<tr>\n";
     print "<td valign=top width=\"$columnwidthpct%\" align=left>";
     if (mysqli_num_rows($result) == 0) {
       if ($hiddenoosunits) 
         print "<span class=\"text\" style=\"color: red\">There are no units in service.  Go to Units screen to create new units, or set one or more Out Of Service units into service.</span>";
       else
         print "<span class=\"text\" style=\"color: red\">There are no units in service.  Go to Units screen to create them.</span>";
     }
     print "<table cellpadding=\"1\" cellspacing=\"1\" bgcolor=\"#aaaaaa\" width=\"100%\">";

     $threshold = sizeof($unitnames) / $columns;
     $pos_counter = 0;
     foreach ($unitnames as $u_name) {
       $tdclass = "message";
       $pos_counter++;
       $unitrow = $unitarray[$u_name];

       $u_name_html = str_replace(" ", "&nbsp;", $u_name);
       $u_status_html = str_replace(" ", "&nbsp;", $unitrow["status"]);

       if ($unitrow["status"] == "Off Duty"  ||
           $unitrow["status"] == "Out of Service") {
         $u_name_html = "<span style='color: gray;'>$u_name_html</span>";
       }
     elseif ((isset($unitincidents[$u_name]) && count($unitincidents[$u_name]) > 0) ||
             (isset($unitstagings[$u_name]) && count($unitstagings[$u_name]) > 0))
       {
       //elseif ($unitrow["status"] == "Attached to Incident") {}
         $attached=0;
         $tdclass = "message-attached";
         $u_status_html = "<span>";
         if (isset($unitincidents[$u_name])) {
           foreach ($unitincidents[$u_name] as $incident_id) {
             $u_status_html .= "Attached&nbsp;to&nbsp;<a href=\"edit-incident.php?incident_id=$incident_id\" ".
                "onClick=\"return popup('edit-incident.php?incident_id=$incident_id','incident-$incident_id',600,1000)\" ".
                "TARGET=\"_blank\">Incident&nbsp;$incident_id</a><br>";
             }
           $attached=1;
         }

         if (isset($unitstagings[$u_name])) {
           foreach ($unitstagings[$u_name] as $staging_id) {
             $staging_location = $staging_locations[$staging_id];
             $u_status_html .= "Staged&nbsp;At&nbsp;<a href=\"edit-staging.php?staging_id=$staging_id\" ".
                "onClick=\"return popup('edit-staging.php?staging_id=$staging_id','staging-$staging_id',400,600)\" ".
                "TARGET=\"_blank\">$staging_location</a><br>";
           }
         }

         if ($attached) { // not sure this logic is perfect
           $u_name_html = "<span style='background-color: yellow; color:black'>$u_name_html</span>";
         }
       }
       elseif (((isset($_COOKIE["units_color"]) && $_COOKIE["units_color"] == "yes")
             || !isset($_COOKIE["units_color"]))
             &&  isset($rolecolor[$unitrow["role"]])) {
         $u_name_html_build = "<span style='";

         if ($unitrow["type"] == 'Unit') {
           $u_name_html_build .= "font-weight: Bold; ";
         }

         $u_name_html_build .= "color: ".$rolecolor[$unitrow["role"]].";'>$u_name_html</span>";
         $u_name_html = $u_name_html_build;
       }

       if ($unitrow["type"] == 'Generic') {
           $u_name_html = "<span style='background-color: #bbbbbb; border: 2px dotted gray; padding-left: 1px; padding-right: 1px; font-weight: bold; white-space: nowrap; color: ".$rolecolor[$unitrow["role"]].";'>$u_name</span>";
       }

       if ($unitrow["status"] == "Available on Pager") {
         $u_status_html = "<span style=\"font-size:9px\">Available&nbsp;on&nbsp;Pager</font>";
       }


       $icon = "";
       if (isset($unitrow["assignment"])) {
         $icon = "<span class=" . $unitrow["display_class"] . " title=\"" .
                 $unitrow["description"] . "\">" . $unitrow["assignment"] .
                 "</span>";
       }

       print '<tr><td class="message" style="vertical-align: top;">' .
         "<a style=\"font-size: 8pt;\" href=\"edit-unit.php?unit=$u_name\" " .
         'title="LOC: [' . $unitrow["location"] . ']' .
         ' &#10;PERS: [' . $unitrow["personnel"] . ']';
       if ($unitrow["notes"] != '') print ' &#10;NOTES: [' . $unitrow["notes"] . ']';
       print "\" onClick=\"return popup('edit-unit.php?unit=".$unitrow["unit"]."','unit-".str_replace(" ", "&nbsp;", $unitrow["unit"])."',500,700)\" TARGET=\"_blank\">"
             . $u_name_html."</a>&nbsp;&nbsp;$icon</td><td class=\"$tdclass\">$u_status_html</td></tr>\n";

       if ($pos_counter >= $threshold) {
         print "</table></td>\n\n";
         print "<td valign=top width=\"$columnwidthpct%\" align=left>\n";
         print "<table cellpadding=\"1\" cellspacing=\"1\" bgcolor=\"#aaaaaa\" width=\"100%\">\n";
         $pos_counter = 0;
       }

     }
     mysqli_free_result($result);
   }

   mysqli_close($link);
   
   

   
 ?>
 </table></td><td></td><td></td></tr></table>

</form>
</body>
</html>
