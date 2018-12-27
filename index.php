<?php //SuperADD Web Service PHP
if( //Verify POST Values are set.
	isset($_POST['domain']) && !empty($_POST['domain']) && 
	isset($_POST['basedn']) && !empty($_POST['basedn']) &&
	isset($_POST['username']) && !empty($_POST['username']) &&
	isset($_POST['password']) && !empty($_POST['password']) &&
	isset($_POST['function']) && !empty($_POST['function'])
) {
	$ldap = ldap_connect($_POST['domain']); //LDAP Connect.
	if(ldap_bind($ldap, $_POST['username'].'@'.$_POST['domain'], $_POST['password'])) { //Check if LDAP is connected.
		if($_POST['function'] == 'list' && isset($_POST['filter']) && !empty($_POST['filter'])) { //List a specified OU.
			$computers = array();
			$result = ldap_list($ldap, $_POST['basedn'], $_POST['filter'], array('CN','description')); //Run the list search.
			foreach(ldap_get_entries($ldap, $result) as $v) { //Clean raw ldap_list output into an array.
				$desc = '';
				if(isset($v['description']) && $v['description']['count'] == 1) {
					$desc = $v['description'][0];
				}
				if(isset($v['cn']) && $v['cn']['count'] == 1) {
					array_push($computers, array('cn' => $v['cn'][0], 'description' => $desc));
				}
			}
			echo json_encode($computers); //Convert result array to JSON then output.
		}
		if( //Create / modify a computer object.
			$_POST['function'] == 'update' &&
			isset($_POST['cn']) && !empty($_POST['cn']) &&
			isset($_POST['description'])
		) {
			$cn_escaped = ldap_escape($_POST['cn'], null, LDAP_ESCAPE_DN);
			$exists = @ldap_read($ldap, 'CN='.$cn_escaped.','.$_POST['basedn'], 'objectClass=computer');
			if(empty($_POST['description'])) {
				$_POST['description'] = array();
			}
			if($exists && isset($_POST['confirm'])) { //If computer exists, create only if confirm is sent.
				ldap_mod_replace($ldap, 'CN='.$cn_escaped.','.$_POST['basedn'], array( //Modify the computer.
					'description' => $_POST['description']
				));
			} elseif($exists) { //If computer exists, but no confirm, send error.
				echo 'This object already exists.';
			} else {
				$attributes = array( //Create the computer.
					'cn' => $cn_escaped,
					'objectClass' => 'computer',
					'description' => $_POST['description']
				);
				if(empty($_POST['description'])) {
					unset($attributes['description']);
				}
				ldap_add($ldap, 'CN='.$cn_escaped.','.$_POST['basedn'], $attributes);
			}
		}
	} else {
		echo 'Could not connect to LDAP server.';
	}
	ldap_close($ldap);
} else {
	echo file_get_contents('documentation.html');
}
?>