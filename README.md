# OpenLDAP-mailcow for Univention UCS 

Provision Univention UCS doamin controller accounts as local mailboxes, using LDAP password to permit user access. As it is not possible (as far as I know) to retrieve LDAP password from server, it is mandatory to manage users' password directly from mailcow. In each run, syncer looks for new mailboxes and if not presents, it creates one, together with user's credentials, whose login is email and password is randomly generated. For this reason the user needs to enter the account (also via admin) and change password: this operation will modify the LDAP one as well, syncing the two instances.

The whole repo is a fork of original [openldap-mailcow project](https://github.com/nextBOSS-Capabilities/openldap-mailcow) with some deep modifications to work with UCS's OpenLDAP, almost out of the box. The given project is, at his time, a fork of a more general LDAP project with Active Directory. All modified files in this project should be imported in the other one, in case one needs to use AD.

To recap, main modifications are about software changes inside mailcow in specific places of password management, beside LDAP connection.

* [How does it work](#how-does-it-work)
* [Usage](#usage)
  * [Prerequisites](#prerequisites)
  * [Setup](#setup)
  * [LDAP Fine-tuning](#ldap-fine-tuning)
* [Limitations](#limitations)
  * [WebUI and EAS authentication](#webui-and-eas-authentication)
  * [Two-way sync](#two-way-sync)
* [Customizations and Integration support](#customizations-and-integration-support)
* [Credits](#credits)

## How does it work

A python script periodically checks and creates new LDAP accounts and deactivates deleted and disabled ones with mailcow API. It also enables LDAP authentication in SOGo and dovecot. In order to permit also calendar and contacts sync, you need to create inside Mailcow the same password of LDAP

## Usage

### Prerequisites
Make sure that RDN identifier for user accounts in OpenLDAP is set to `uid`.

### Setup
1. Follow [Mailcow-dockerized instruction](https://github.com/mailcow/mailcow-dockerized) for docker install in order to retrieve and create mailcow system.
2. Run the "generate_config.sh" script, that will create the "mailcow.conf" file for the given setup
3. Clone this project inside folder (relative path) data/ldapsync. This means that all pulled file from repo should reside in "data/ldapsync" folder!!
4. Append "data/ldapsync/mailcow-dockerized/mailcow.conf.addon" file to mailcow.conf previously created
5. Start mailcow using the usual "docker compose pull" & "docker compose up -d" in order to generate the default mailcow system and log into mailcow dashboard
6. In "system->configuration" look for API (it's a folded parameter set), open it with the "+" and inside there, under read-write accesws, generate a new key, keep it for later
7. In eMail-> Configuration" create a new domain, with the same domain name of LDAP emails
8. In UCS mind to add the field "email" under each needed user, tab "samba"(it doesn't worj with "primaryemailaddress")
9. Add the snippet in data/docker-compose-override.yml inside the original docker-compose.yml in your root path, just at the end of all services, abd before "network" decalration
10. Modify the mailcow.conf added snippet using the following variable meaninigs:

    * `LDAP-MAILCOW_LDAP_URI` - LDAP (e.g., Active Directory) URI (must be reachable from within the container). The URIs are in syntax `protocol://host:port`. For example `ldap://localhost` or `ldaps://secure.domain.org`
    * `LDAP-MAILCOW_LDAP_BASE_DN` - base DN where user accounts can be found
    * `LDAP-MAILCOW_LDAP_BIND_DN` - bind DN of a special LDAP account that will be used to browse for users
    * `LDAP-MAILCOW_LDAP_BIND_DN_PASSWORD` - password for bind DN account
    * `LDAP-MAILCOW_API_HOST` - mailcow API url. Make sure it's enabled and accessible from within the container for both reads and writes
    * `LDAP-MAILCOW_API_KEY` - mailcow API key (read/write)
    * `LDAP-MAILCOW_SYNC_INTERVAL` - interval in seconds between LDAP synchronizations
    * `LDAP-MAILCOW_LDAP_FILTER` - LDAP filter to apply, defaults to `(&(objectClass=user)(objectCategory=person))`
    * `LDAP-MAILCOW_SOGO_LDAP_FILTER` - LDAP filter to apply for SOGo ([special syntax](https://sogo.nu/files/docs/SOGoInstallationGuide.html#_authentication_using_ldap)), defaults to `objectClass='user' AND objectCategory='person'`

11. Build additional container: `docker compose build ldap`
12. Start the new service: `docker compose up ldap -d`
13. Restart dovecot and SOGo if necessary `docker compose restart sogo-mailcow dovecot-mailcow`

## Limitations

### WebUI and EAS authentication

This tool enables authentication for Dovecot and SOGo, which means you will be able to log into POP3, SMTP, IMAP, and SOGo Web-Interface. **You will not be able to log into mailcow UI or EAS using your LDAP credentials by default.**

As a workaround, you can hook IMAP authentication directly to mailcow by adding the following code above [this line](https://github.com/mailcow/mailcow-dockerized/blob/48b74d77a0c39bcb3399ce6603e1ad424f01fc3e/data/web/inc/functions.inc.php#L608):

```php
    $mbox = imap_open ("{dovecot:993/imap/ssl/novalidate-cert}INBOX", $user, $pass);
    if ($mbox != false) {
        imap_close($mbox);
        return "user";
    }
```

As a side-effect, It will also allow logging into mailcow UI using mailcow app passwords (since they are valid for IMAP). **It is not a supported solution with mailcow and has to be done only at your own risk!**

### Two-way sync

Users from your LDAP directory will be added (and deactivated if disabled/not found) to your mailcow database. Not vice-versa, and this is by design.


## Credits
This is a fork of original [openldap-mailcow project](https://github.com/nextBOSS-Capabilities/openldap-mailcow) with slight modifications to work with UCS's OpenLDAP out of the box.
