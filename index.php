<?php //SuperADD Web Service PHP Custom
ob_start();
require('main.php');
$ob = ob_get_contents();
if( //Verify POST Values are set.
	isset($_POST['domain']) && !empty($_POST['domain']) && 
	isset($_POST['basedn']) && !empty($_POST['basedn']) &&
	isset($_POST['username']) && !empty($_POST['username']) &&
	isset($_POST['password']) && !empty($_POST['password']) &&
	isset($_POST['function']) && !empty($_POST['function']) &&
	empty($ob) //If no errors from original Web Service
) {
	$ldap = ldap_connect($_POST['domain']); //LDAP Connect.
	ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0); //LDAP Set options for root searching.
	ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
	if(ldap_bind($ldap, $_POST['username'].'@'.$_POST['domain'], $_POST['password'])) { //Check if LDAP is connected.
		if( //Create / modify a computer object.
			$_POST['function'] == 'update' &&
			isset($_POST['cn']) && !empty($_POST['cn'])
		) {
			$cn_escaped = ldap_escape($_POST['cn'], null, LDAP_ESCAPE_DN); //Filter API input.
			$filter_escaped = ldap_escape($_POST['cn'], null, LDAP_ESCAPE_FILTER);
			$computer_dn = 'CN='.$cn_escaped.','.$_POST['basedn']; //Get full DN.
			$group_mappings = json_decode(file_get_contents('group_mappings.json'));
			foreach($group_mappings as $prefix => $group_dn) { //Foreach group mapping.
				if(strpos(strtolower($cn_escaped), strtolower($prefix)) === 0) { //If CN starts with group_mappings prefix.
					@ldap_mod_add($ldap, $group_dn, array( //Add computer to group.
						'member' => $computer_dn
					));
				}
			}
		}
	} else {
		echo 'Could not connect to LDAP server.';
	}
	ldap_close($ldap);
}
?>