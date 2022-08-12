<?php
  $subsys = "admin";

  require_once('session.inc');
  require_once('functions.php');
  SessionErrorIfReadonly();

  header_html("Dispatch :: System Admin");

  print "<body vlink=\"blue\" link=\"blue\" alink=\"cyan\">\n";
  include('include-title.php'); 
  if (!CheckAuthByLevel('admin_general', $_SESSION['access_level'])) {
    print "Access level too low to access System Administration features.";
  }
  else {

 ?>
  <table>
  <tr><td></td></tr>
  <tr><td><b>System Administration</b></td></tr>
  <tr>
  <td align="left" width="400">

<table width="350" style="border: 3px ridge blue; padding: 5px; background-color: #dddddd">
  <tr><td><a href="config-users.php">Edit Users</a></td></tr>
  <tr><td><a href="config-cleardb.php">Archive and Clear Database</a></td></tr>
  <tr><td><a href='https://www.w2ort.com'>W2ORT - Home</a></td></tr>
</table>
</td>

<?php } ?>

</tr>
</table>

</body>
</html>
