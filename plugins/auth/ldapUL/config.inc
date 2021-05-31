<?php

// ldap://ldap.ulaval.ca -> 10.40.65.5
define('HOST', 'ldap.ulaval.ca');
define('PORT', 389);

define('SEARCH_CONTEXT', 'ou=IDUL,ou=Comptes,dc=ulaval,dc=ca');

define('USERNAME_KEY', 'sAMAccountName');
define('GROUP_KEY', 'memberOf');

define('USER', 'cn=OJS_LDAP,ou=SSA,ou=Groupe Systemes,dc=bibl,dc=ulaval,dc=ca');
define('PASSWORD', 'ABoGy;UUouKp#UnxtQ=WFs4QK');

define('GROUP_VALUE', 'cn=GD_BIBL_OJS_PERS_REVUES,ou=BIBL,ou=GD,ou=Institutionnel,dc=ulaval,dc=ca');

?>
