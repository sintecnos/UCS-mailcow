<?php 

function change_ldap_password($usr,$newPwd) {
	$server=getenv('LDAP_URI');
	$dn=getenv('LDAP_BASE_DN');

	$adminUid = getenv('LDAP_ADMIN_DN');
	$adminPwd = getenv('LDAP_ADMIN_PW');

	$parts = explode ('@', $usr);
	$uid= $parts[0];
	$user = "uid=" . $uid.','.$dn;

	$conn = ldap_connect($server);
	ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
	ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);

	$ldapbind = ldap_bind($conn,$adminUid,$adminPwd);

	$filter = "(uid=". $uid . ")";

	$search_result = ldap_search($conn, $dn, $filter);
	if (!$search_result) {
		return false;
	}


	$newPwd_enc = "{SHA}" . base64_encode(pack("H*", sha1($newPwd)));
	$entry = ["userPassword" => $newPwd_enc];


	// Perform the password change
	if (!(ldap_mod_replace($conn, $user, $entry))) {
		return false;
	}


	ldap_unbind($conn);
	return true;
}

