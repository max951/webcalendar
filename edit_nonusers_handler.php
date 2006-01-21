<?php
include_once 'includes/init.php';
load_user_layers ();

if ( ! $is_admin ) {
  echo "<h2>" . translate("Error") .
    "</h2>" . translate("You are not authorized") . ".\n";
  echo "</body>\n</html>";
  exit;
}
$error = "";

$delete = getPostValue ( 'delete' );
if ( ! empty ( $delete ) ) {
  // delete this nonuser calendar
  $user = $nid;

  // Get event ids for all events this user is a participant
  $events = array ();
  $res = dbi_execute ( "SELECT webcal_entry.cal_id " .
    "FROM webcal_entry, webcal_entry_user " .
    "WHERE webcal_entry.cal_id = webcal_entry_user.cal_id " .
    "AND webcal_entry_user.cal_login = ?", array( $user ) );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $events[] = $row[0];
    }
  }

  // Now count number of participants in each event...
  // If just 1, then save id to be deleted
  $delete_em = array ();
  for ( $i = 0; $i < count ( $events ); $i++ ) {
    $res = dbi_execute ( "SELECT COUNT(*) FROM webcal_entry_user " .
      "WHERE cal_id = ?", array( $events[$i] ) );
    if ( $res ) {
      if ( $row = dbi_fetch_row ( $res ) ) {
        if ( $row[0] == 1 )
   $delete_em[] = $events[$i];
      }
      dbi_free_result ( $res );
    }
  }
  // Now delete events that were just for this user
  for ( $i = 0; $i < count ( $delete_em ); $i++ ) {
    dbi_execute ( "DELETE FROM webcal_entry_repeats WHERE cal_id = ?", array( $delete_em[$i] ) );
    dbi_execute ( "DELETE FROM webcal_entry_repeats_not WHERE cal_id = ?", array( $delete_em[$i] ) );
    dbi_execute ( "DELETE FROM webcal_entry_log WHERE cal_entry_id = ?", array( $delete_em[$i] ) );
    dbi_execute ( "DELETE FROM webcal_import_data WHERE cal_id = ?", array( $delete_em[$i] ) );
    dbi_execute ( "DELETE FROM webcal_site_extras WHERE cal_id = ?", array( $delete_em[$i] ) );
    dbi_execute ( "DELETE FROM webcal_entry_ext_user WHERE cal_id = ?", array( $delete_em[$i] ) );
    dbi_execute ( "DELETE FROM webcal_reminder_log WHERE cal_id =? ", array( $delete_em[$i] ) );
    dbi_execute ( "DELETE FROM webcal_blob WHERE cal_id = ?", array( $delete_em[$i] ) );
    dbi_execute ( "DELETE FROM webcal_entry WHERE cal_id = ?", array( $delete_em[$i] ) );
  }

  // Delete user participation from events
  dbi_execute ( "DELETE FROM webcal_entry_user WHERE cal_login = ?", array( $user ) );

  // Delete any layers other users may have that point to this user.
  dbi_execute ( "DELETE FROM webcal_user_layers WHERE cal_layeruser = ?", array( $user ) );

  // Delete user
  if ( ! dbi_execute ( "DELETE FROM webcal_nonuser_cals WHERE cal_login = ?", array( $user ) ) )
     $error = translate ("Database error") . ": " . dbi_error();

} else {
  if ( $action == "Save" || $action == translate ("Save") ) {
  // Updating
    $query_params = array();
	$sql = "UPDATE webcal_nonuser_cals SET ";
    if ($nlastname) { 
	  $sql .= " cal_lastname = ?, ";
      $query_params[] = $nlastname;
	}
    if ($nfirstname) {
	  $sql .= " cal_firstname = ?, ";
      $query_params[] = $nfirstname;
	}
    if ( $ispublic ) {
	  $sql .= " cal_is_public = ?, ";
      $query_params[] = $ispublic;
	}
    
	$sql .= "cal_admin = ? WHERE cal_login = ?";
    $query_params[] = $nadmin;
	$query_params[] = $nid;

    if ( ! dbi_execute ( $sql, $query_params ) ) {
      $error = translate ("Database error") . ": " . dbi_error();
    }
  } else {
  // Adding
    if (preg_match( "/^[\w]+$/", $nid )) {
      $nid = $NONUSER_PREFIX.$nid;
      $sql = "INSERT INTO webcal_nonuser_cals " .
      "( cal_login, cal_firstname, cal_lastname, cal_admin, cal_is_public ) " .
      "VALUES ( ?, ?, ?, ?, ? )";
      if ( ! dbi_execute ( $sql, array( $nid, $nfirstname, $nlastname, $nadmin, $ispublic ) ) ) {
        $error = translate ("Database error") . ": " . dbi_error();
      }
    } else {
      $error = translate ("Calendar ID")." ".translate ("word characters only").".";
    }
  }
}

if ( ! empty ( $error ) ) {
  print_header( '', '', '', true );
?>

<h2><?php etranslate("Error")?></h2>

<blockquote>
<?php
echo $error;
//if ( $sql != "" )
//  echo "<br /><br /><b>SQL:</b> $sql";
//?>
</blockquote>
</body>
</html>
<?php } else if ( empty ( $error ) ) {
?><html><head></head>
<body onLoad="alert('<?php etranslate("Changes successfully saved", true);?>'); window.parent.location.href='users.php';">
</body></html>
<?php } ?>
