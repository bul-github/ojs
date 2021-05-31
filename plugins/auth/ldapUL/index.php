<?php
/**
 * @defgroup plugins_auth_ldap_ul LDAP UL Authentication Plugin
 */

/**
 * @file plugins/auth/ldapUL/index.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Copyright (c) 2021 Université Laval
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_auth_ldap_ul
 * @brief Wrapper for loading the LDAP UL authentication plugin.
 *
 */

require_once('LDAPULAuthPlugin.inc.php');

return new LDAPULAuthPlugin();


