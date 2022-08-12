<?php
  $subsys="admin";
  require_once('db-open.php');
  require_once('session.inc');
  require_once('functions.php');
  SessionErrorIfReadonly();

  $td = "<td bgcolor=#cccccc>";

  if (!CheckAuthByLevel('admin_cleardb', $_SESSION['access_level'])) {
    syslog(LOG_WARNING, "Database clearing attempted without permissions by user ". $_SESSION['username'] ." level ". $_SESSION['access_level']);
    echo "Access level insufficient for this operation.<br>\n";
    echo "User: " . $_SESSION['username'] . "<br>\n";
    echo "Level: " . $_SESSION['access_level'] . "<br>\n";
    exit;
  }
  elseif (isset($_POST["cleardb"]) && $_POST["cleardb"] == 3) {

  /* Define timestamp and get a lock on tables */
    MysqlQuery("LOCK TABLES deployment_history READ, archive_master WRITE");
    $comment = MysqlClean($_POST, "comment", 80);
    $ts = date("Ymd_His");
    sleep(1);
    syslog(LOG_WARNING, "Database was archive/cleared to archive tag [$ts] by user ". $_SESSION['username'] ." level ". $_SESSION['access_level']);

    $dbver = 'NULL';
    $codever = 'NULL';
    $dephist = MysqlQuery("SELECT * FROM deployment_history ORDER BY idx DESC LIMIT 1");
    if (mysqli_num_rows($dephist)) {
      $schemaver = mysqli_fetch_object($dephist);
      $dbver = "'" . $schemaver->database_version . "'";
      $codever = "'" . $schemaver->requires_code_ver . "'";
    }

  /* Note revision in master archive table */
    MysqlQuery("INSERT INTO archive_master VALUES ('$ts', NOW(), '$comment', $dbver, $codever)");
    if (MYAFFROWS() != 1) die("Error registering archive checkpoint [$ts] in archive_master table");

  /* Make backup copies of all relevant tables and data */
  
  //Unlock tables in order to create backup tables below
  
	MysqlQuery("UNLOCK TABLES");
  
	MysqlQuery("CREATE TABLE `cadarchives.messages_$ts` (
	  `oid` int(11) NOT NULL AUTO_INCREMENT,
	  `ts` datetime NOT NULL,
	  `unit` varchar(20) DEFAULT NULL,
	  `message` varchar(255) NOT NULL,
	  `deleted` tinyint(1) NOT NULL DEFAULT 0,
	  `creator` varchar(20) DEFAULT NULL,
	  `message_type` varchar(20) DEFAULT NULL,
	  PRIMARY KEY (`oid`),
	  KEY `deleted` (`deleted`),
	  KEY `unit` (`unit`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
	
	MysqlQuery("CREATE TABLE `cadarchives.incidents_$ts` (
	  `incident_id` int(11) NOT NULL AUTO_INCREMENT,
	  `call_number` varchar(40) DEFAULT NULL,
	  `call_type` varchar(40) DEFAULT NULL,
	  `call_details` varchar(80) DEFAULT NULL,
	  `ts_opened` datetime NOT NULL,
	  `ts_dispatch` datetime DEFAULT NULL,
	  `ts_arrival` datetime DEFAULT NULL,
	  `ts_complete` datetime DEFAULT NULL,
	  `location` varchar(80) DEFAULT NULL,
	  `location_num` varchar(15) DEFAULT NULL,
	  `reporting_pty` varchar(80) DEFAULT NULL,
	  `contact_at` varchar(80) DEFAULT NULL,
	  `disposition` varchar(80) DEFAULT NULL,
	  `primary_unit` varchar(20) DEFAULT NULL,
	  `updated` datetime NOT NULL,
	  `duplicate_of_incident_id` int(11) DEFAULT NULL,
	  `incident_status` enum('New','Open','Dispositioned','Closed') DEFAULT NULL,
	  PRIMARY KEY (`incident_id`),
	  KEY `incident_status` (`incident_status`),
	  KEY `ts_opened` (`ts_opened`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
	
	MysqlQuery("CREATE TABLE `cadarchives.incident_notes_$ts` (
	  `note_id` int(11) NOT NULL AUTO_INCREMENT,
	  `incident_id` int(11) NOT NULL,
	  `ts` datetime NOT NULL,
	  `unit` varchar(20) DEFAULT NULL,
	  `message` varchar(255) NOT NULL,
	  `deleted` tinyint(1) NOT NULL DEFAULT 0,
	  `creator` varchar(20) DEFAULT NULL,
	  PRIMARY KEY (`note_id`),
	  KEY `incident_id` (`incident_id`,`deleted`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
	
	MysqlQuery("CREATE TABLE `cadarchives.incident_units_$ts` (
	  `uid` int(11) NOT NULL AUTO_INCREMENT,
	  `incident_id` int(11) NOT NULL,
	  `unit` varchar(20) NOT NULL,
	  `dispatch_time` datetime DEFAULT NULL,
	  `arrival_time` datetime DEFAULT NULL,
	  `transport_time` datetime DEFAULT NULL,
	  `transportdone_time` datetime DEFAULT NULL,
	  `cleared_time` datetime DEFAULT NULL,
	  `is_primary` tinyint(1) DEFAULT NULL,
	  `is_generic` tinyint(1) DEFAULT NULL,
	  PRIMARY KEY (`uid`),
	  KEY `incident_id` (`incident_id`,`cleared_time`),
	  KEY `dispatch_time` (`dispatch_time`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
	
	MysqlQuery("CREATE TABLE `cadarchives.bulletins_$ts` (
	  `bulletin_id` int(11) NOT NULL AUTO_INCREMENT,
	  `bulletin_subject` varchar(160) DEFAULT NULL,
	  `bulletin_text` text DEFAULT NULL,
	  `updated` datetime DEFAULT NULL,
	  `updated_by` int(11) DEFAULT NULL,
	  `access_level` int(11) DEFAULT NULL,
	  `closed` tinyint(1) NOT NULL DEFAULT 0,
	  PRIMARY KEY (`bulletin_id`),
	  KEY `updated` (`updated`),
	  KEY `access_level` (`access_level`),
	  KEY `closed` (`closed`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
	
	MysqlQuery("CREATE TABLE `cadarchives.bulletin_views_$ts` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `bulletin_id` int(11) DEFAULT NULL,
	  `user_id` int(11) DEFAULT NULL,
	  `last_read` datetime DEFAULT NULL,
	  PRIMARY KEY (`id`),
	  KEY `user_id` (`user_id`,`bulletin_id`),
	  KEY `last_read` (`last_read`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
	
	MysqlQuery("CREATE TABLE `cadarchives.bulletin_history_$ts` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `bulletin_id` int(11) DEFAULT NULL,
	  `action` enum('Created','Edited','Closed','Reopened') DEFAULT NULL,
	  `updated` datetime DEFAULT NULL,
	  `updated_by` int(11) DEFAULT NULL,
	  PRIMARY KEY (`id`),
	  KEY `bulletin_id` (`bulletin_id`,`updated`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
	
	MysqlQuery("CREATE TABLE `cadarchives.units_$ts` (
	  `unit` varchar(20) NOT NULL,
	  `status` varchar(30) DEFAULT NULL,
	  `status_comment` varchar(255) DEFAULT NULL,
	  `update_ts` datetime DEFAULT NULL,
	  `role` varchar(20) DEFAULT NULL,
	  `type` set('Unit','Individual','Generic') DEFAULT NULL,
	  `personnel` varchar(100) DEFAULT NULL,
	  `assignment` varchar(20) DEFAULT NULL,
	  `personnel_ts` datetime DEFAULT NULL,
	  `location` varchar(255) DEFAULT NULL,
	  `location_ts` datetime DEFAULT NULL,
	  `notes` varchar(255) DEFAULT NULL,
	  `notes_ts` datetime DEFAULT NULL,
	  PRIMARY KEY (`unit`),
	  KEY `status` (`status`,`type`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
	
	MysqlQuery("CREATE TABLE `cadarchives.unit_incident_paging_$ts` (
	  `row_id` int(11) NOT NULL AUTO_INCREMENT,
	  `unit` varchar(20) NOT NULL,
	  `to_pager_id` int(11) NOT NULL,
	  `to_person_id` int(11) NOT NULL,
	  PRIMARY KEY (`row_id`),
	  KEY `unit` (`unit`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
	
	MysqlQuery("CREATE TABLE `cadarchives.deployment_history_$ts` (
	  `idx` int(11) NOT NULL AUTO_INCREMENT,
	  `schema_load_ts` datetime NOT NULL,
	  `update_ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
	  `database_version` varchar(20) NOT NULL,
	  `requires_code_ver` varchar(20) NOT NULL,
	  `mysql_user` varchar(255) DEFAULT NULL,
	  `host` varchar(255) DEFAULT NULL,
	  `uid` int(11) DEFAULT NULL,
	  `user` varchar(8) DEFAULT NULL,
	  `cwd` varchar(255) DEFAULT NULL,
	  PRIMARY KEY (`idx`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
	
	MysqlQuery("CREATE TABLE `cadarchives.users_$ts` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `username` varchar(20) NOT NULL,
	  `password` varchar(64) NOT NULL,
	  `name` varchar(40) DEFAULT NULL,
	  `access_level` int(11) NOT NULL DEFAULT 1,
	  `access_acl` varchar(20) DEFAULT NULL,
	  `timeout` int(11) NOT NULL DEFAULT 300,
	  `preferences` text DEFAULT NULL,
	  `change_password` tinyint(1) NOT NULL DEFAULT 0,
	  `locked_out` tinyint(1) NOT NULL DEFAULT 0,
	  `failed_login_count` int(11) NOT NULL DEFAULT 0,
	  `last_login_time` datetime DEFAULT NULL,
	  PRIMARY KEY (`id`),
	  KEY `username` (`username`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
	
	MysqlQuery("CREATE TABLE `cadarchives.channels_$ts` (
	  `channel_id` int(11) NOT NULL AUTO_INCREMENT,
	  `channel_name` varchar(40) NOT NULL,
	  `repeater` tinyint(1) NOT NULL DEFAULT 0,
	  `available` tinyint(1) NOT NULL DEFAULT 1,
	  `precedence` int(11) NOT NULL DEFAULT 50,
	  `incident_id` int(11) DEFAULT NULL,
	  `staging_id` int(11) DEFAULT NULL,
	  `notes` varchar(160) DEFAULT NULL,
	  PRIMARY KEY (`channel_id`),
	  KEY `precedence` (`precedence`,`channel_name`),
	  KEY `incident_id` (`incident_id`),
	  KEY `staging_id` (`staging_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    /*MysqlQuery("LOCK TABLES messages WRITE, incidents WRITE, incident_notes WRITE, 
                  incident_units WRITE, bulletins WRITE, bulletin_views WRITE,
                  bulletin_history WRITE, units WRITE, unit_incident_paging WRITE, deployment_history WRITE, users WRITE, channels WRITE");


    MysqlQuery("CREATE TABLE cadarchives.messages_$ts LIKE messages ");
    MysqlQuery("CREATE TABLE cadarchives.incidents_$ts LIKE incidents ");
    MysqlQuery("CREATE TABLE cadarchives.incident_notes_$ts LIKE incident_notes ");
    MysqlQuery("CREATE TABLE cadarchives.incident_units_$ts LIKE incident_units ");
    MysqlQuery("CREATE TABLE cadarchives.bulletins_$ts LIKE bulletins ");
    MysqlQuery("CREATE TABLE cadarchives.bulletin_views_$ts LIKE bulletin_views ");
    MysqlQuery("CREATE TABLE cadarchives.bulletin_history_$ts LIKE bulletin_history ");
    MysqlQuery("CREATE TABLE cadarchives.units_$ts LIKE units ");
    MysqlQuery("CREATE TABLE cadarchives.unit_incident_paging_$ts LIKE unit_incident_paging ");
    MysqlQuery("CREATE TABLE cadarchives.deployment_history_$ts LIKE deployment_history ");
    MysqlQuery("CREATE TABLE cadarchives.users_$ts LIKE users ");
    MysqlQuery("CREATE TABLE cadarchives.channels_$ts LIKE channels");*/

    MysqlQuery("LOCK TABLES messages WRITE, incidents WRITE, incident_notes WRITE, 
                  incident_units WRITE, bulletins WRITE, bulletin_views WRITE,
                  bulletin_history WRITE, units WRITE, unit_incident_paging WRITE,
                  `cadarchives.messages_$ts` WRITE, `cadarchives.incidents_$ts` WRITE, `cadarchives.incident_notes_$ts` WRITE, 
                  `cadarchives.incident_units_$ts` WRITE, `cadarchives.bulletins_$ts` WRITE, `cadarchives.bulletin_views_$ts` WRITE,
                  `cadarchives.bulletin_history_$ts` WRITE, `cadarchives.units_$ts` WRITE, `cadarchives.unit_incident_paging_$ts` WRITE,
                  `cadarchives.deployment_history_$ts` WRITE, deployment_history WRITE,
                  `cadarchives.users_$ts` WRITE, users WRITE,
                  `cadarchives.channels_$ts` WRITE, channels WRITE,
                  archive_master WRITE");

    MysqlQuery("INSERT INTO  `cadarchives.messages_$ts` SELECT * FROM messages");
    MysqlQuery("INSERT INTO  `cadarchives.incidents_$ts` SELECT * FROM incidents");
    MysqlQuery("INSERT INTO  `cadarchives.incident_notes_$ts` SELECT * FROM incident_notes");
    MysqlQuery("INSERT INTO  `cadarchives.incident_units_$ts` SELECT * FROM incident_units");
    MysqlQuery("INSERT INTO  `cadarchives.bulletins_$ts` SELECT * FROM bulletins");
    MysqlQuery("INSERT INTO  `cadarchives.bulletin_views_$ts` SELECT * FROM bulletin_views");
    MysqlQuery("INSERT INTO  `cadarchives.bulletin_history_$ts` SELECT * FROM bulletin_history");
    MysqlQuery("INSERT INTO  `cadarchives.units_$ts` SELECT * FROM units");
    MysqlQuery("INSERT INTO  `cadarchives.unit_incident_paging_$ts` SELECT * FROM unit_incident_paging");
    MysqlQuery("INSERT INTO  `cadarchives.deployment_history_$ts` SELECT * FROM deployment_history");
    MysqlQuery("INSERT INTO  `cadarchives.users_$ts` SELECT * FROM users");
    MysqlQuery("INSERT INTO  `cadarchives.channels_$ts` SELECT * FROM channels");

  /* Clear relevant tables and data */
    MysqlQuery("DELETE FROM messages");
    MysqlQuery("DELETE FROM incident_notes");
    MysqlQuery("DELETE FROM incident_units");
    MysqlQuery("DELETE FROM incidents");
    MysqlQuery("DELETE FROM bulletins");
    MysqlQuery("DELETE FROM bulletin_views");
    MysqlQuery("DELETE FROM bulletin_history");
    MysqlQuery("UPDATE units SET status=NULL, update_ts=NULL, status_comment=NULL, personnel_ts=NULL, location_ts=NULL, notes_ts=NULL, assignment='', location='', personnel='', notes=''");
    MysqlQuery("UPDATE channels SET available=1, incident_id=NULL");
    //MysqlQuery
    # TODO - clear unit locations, personnel, notes
    
  /* Finish */
    MysqlQuery("UNLOCK TABLES");
    MysqlQuery("TRUNCATE incidents");
    sleep(1);
    header("Location: admin.php");
    exit;
  }

  header_html("Dispatch :: Configuration");
?>
<body vlink="blue" link="blue" alink="cyan">
<?php include('include-title.php'); ?>
<center><b>CLEARING THE DATABASE</b></center>

<p>
<center><blink><font color=red><b>WARNING</b></font></blink></center>
<p>
<table width="100%">
<tr>
<td width="25%">
</td>
<td width="50%">
<center>
<font color=red>
Clearing the database will DELETE ALL LOG MESSAGES AND UNIT STATUS ENTRIES.
Do not do this unless you are really, <i>really</i>, <b>REALLY</b>
sure this is what you want to do!<p>
(Note: Unit definitions will not be deleted, you must manually delete them.)
</font>
</center>
</td>
<td width="25%">
</td>
</tr>
</table>
<p>
  <form name="myform" action="config-cleardb.php" method="post">
<table>
  <tr>
  <?php
    if (!isset($_POST["cleardb"]) || $_POST["cleardb"] == 0) {
      echo $td, "Are you SURE you want to do this?</td>\n";
      echo $td, "<input type=\"checkbox\" name=\"cleardb\" value=\"1\"> </td>\n";
      echo "<td><input type=\"submit\" value=\"Yes!\"> </td>";
    }
    elseif (isset($_POST["cleardb"]) && $_POST["cleardb"] == 1) {
      echo $td, "Are you SURE you want to do this?</td>\n";
      echo $td, "<input type=\"checkbox\" name=\"cleardb\" value=\"1\" disabled checked> </td>\n</tr>\n<tr>\n";
      echo $td, "Are you REALLY SURE you want to do this?</td>\n";
      echo $td, "<input type=\"checkbox\" name=\"cleardb\" value=\"2\"> </td>";
      echo "<td><input type=\"submit\" value=\"Yes!\"> </td>";
      echo "</tr><tr>\n";
    }
    elseif (isset($_POST["cleardb"]) && $_POST["cleardb"] == 2) {
      echo $td, "Are you SURE you want to do this?</td>\n";
      echo $td, "<input type=\"checkbox\" name=\"cleardb\" value=\"1\" disabled checked> </td>\n</tr>\n<tr>\n";
      echo $td, "Are you REALLY SURE you want to do this?</td>\n";
      echo $td, "<input type=\"checkbox\" name=\"cleardb\" value=\"2\" disabled checked> </td>\n</tr>\n<tr>\n";
      echo "<td bgcolor=#cccccc colspan=2> <input type=\"hidden\" name=\"cleardb\" value=\"3\"> \n";
      echo "Comment: <input type=\"text\" maxlength=\"80\" size=\"40\" name=\"comment\"></td>";
      echo "<td><input type=\"submit\" value=\"Archive and Clear Database\"> </td>";
    }
  ?>
  </tr>
  </table>

  </form>
</ul>
<p>
<hr>
<a href="admin.php">Abort and return to Admin page</a><br>
</body>
</html>


