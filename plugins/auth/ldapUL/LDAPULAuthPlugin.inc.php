<?php

/**
 * @file plugins/auth/ldapUL/LDAPULAuthPlugin.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Copyright (c) 2021 Université Laval
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class LDAPULAuthPlugin
 * @ingroup plugins_auth_ldap_ul
 *
 * @brief LDAP authentication plugin for UL.
 */

import('lib.pkp.classes.plugins.AuthPlugin');

include('config.inc');

/**
 * Class LDAPAuthULPlugin
 */
class LDAPULAuthPlugin extends AuthPlugin {
	/**
	 * @copydoc Plugin::register()
	 * @param $category
	 * @param $path
	 * @param $mainContextId
	 * @return bool
	 */
	function register($category, $path, $mainContextId = null) {
		if (parent::register($category, $path, $mainContextId)) {
			$this->addLocaleData();
			if ($this->getEnabled()) {
				HookRegistry::register('Validation::login', array(&$this, 'login'));
			}
			return true;
		}
		return false;
	}

	/**
	 * @var resource the LDAP connection
	 */
	var $conn;

	/**
	 * Return the name of this plugin.
	 * @return string
	 */
	function getName() {
		return 'ldapUL';
	}

	/**
	 * Return the localized name of this plugin.
	 * @return string
	 */
	function getDisplayName() {
		return __('plugins.auth.ldap.ul.displayName');
	}

	/**
	 * Return the localized description of this plugin.
	 * @return string
	 */
	function getDescription() {
		return __('plugins.auth.ldap.ul.description');
	}

	/**
	 * Callback used to log the user via LDAP.
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 */
	function login($hookName, $args) {
		$username = $args[0];
		$password = $args[1];
		$user =& $args[2];
		$reason =& $args[3];

		if (!$this->authenticate($username, $password)) {
			return false;
		}

		$userDao = DAORegistry::getDAO('UserDAO');

		$user = $userDao->getUserByLdap($username);

		if ($user) {
			return true;
		}

		$userInfo = $this->getUserInfo($username);

		$proxies = array_filter($userInfo['proxyAddresses'], function($v, $k) {
			return strpos($v, '@ulaval.ca') !== false;
		}, ARRAY_FILTER_USE_BOTH);

		$email = null;
		foreach ($proxies as $proxy) {
			$email = str_ireplace('smtp:','', $proxy);

			if ($email) {
				// Stop as soon as we find a valid email address.
				break;
			}
		}

		if (!$email) {
			$reason = 'plugins.auth.ldap.ul.user.no.valid.email';
			return false;
		}

		$user = $userDao->getUserByEmail($email);

		if ($user) {
			if ($user->getLdap()) {
				$reason = 'plugins.auth.ldap.ul.user.already.binded';
				return false;
			}

			$user->setLdap($username);
			$userDao->updateObject($user);

			return true;
		}

		if ($userDao->userExistsByUsername($username)) {
			// Don't create the user as it already exists in the database.
			$reason = 'plugins.auth.ldap.ul.user.username.already.exists';
			return false;
		}

		$givenName = isset($userInfo['givenName']) ? $userInfo['givenName'][0] : null;
		$familyName = isset($userInfo['sn']) ? $userInfo['sn'][0] : null;
		$affiliation = 'Université Laval'; //$userInfo['department'][0];
		$phone = isset($userInfo['telephoneNumber']) ? $userInfo['telephoneNumber'][0] : null;
		$country = 'CA';

		if (!$password) {
			$password = Validation::generatePassword(20);
		}

		// The multilingual user data (givenName, familyName and affiliation) will be saved
		// in the current UI locale and copied in the site's primary locale too.
		$site = $this->getRequest()->getSite();
		$sitePrimaryLocale = $site->getPrimaryLocale();
		$currentLocale = AppLocale::getLocale();

		$userDao = DAORegistry::getDAO('UserDAO');

		$user = $userDao->newDataObject();
		$user->setUsername($username);
		$user->setGivenName($givenName, $currentLocale);
		$user->setFamilyName($familyName, $currentLocale);
		$user->setEmail($email);
		$user->setCountry($country);
		$user->setAffiliation($affiliation, $currentLocale);
		$user->setPhone($phone);
		$user->setLdap($username);

		if ($sitePrimaryLocale != $currentLocale) {
			$user->setGivenName($givenName, $sitePrimaryLocale);
			$user->setFamilyName($familyName, $sitePrimaryLocale);
			$user->setAffiliation($affiliation, $sitePrimaryLocale);
		}

		$user->setPassword(Validation::encryptCredentials($username, $password));
		$user->setDateRegistered(Core::getCurrentDate());
		$user->setInlineHelp(1);

		$userDao->insertObject($user);

		$user = !$user->getId() ? null : $user;

		if ($user) {
			// Add a markup to know when we need to add a default role for the user.
			$user->{"isFromLdap"} = true;

			return true;
		}

		return false;
	}

	//
	// Core Plugin Functions
	// (Must be implemented by every authentication plugin)
	//

	/**
	 * Returns an instance of the authentication plugin
	 * @param $settings array settings specific to this instance.
	 * @param $authId int identifier for this instance
	 * @return LDAPAuthULPlugin
	 */
	function getInstance($settings, $authId) {
		return new LDAPAuthULPlugin($settings, $authId);
	}

	/**
	 * Authenticate a username and password.
	 * @param $username string
	 * @param $password string
	 * @return boolean true if authentication is successful
	 */
	function authenticate($username, $password) {
		$valid = false;

		if ($password != null) {
			if ($this->open()) {
				if ($entry = $this->getUserEntry($username)) {
					$userdn = ldap_get_dn($this->conn, $entry);

					if ($this->bind($userdn, $password)) {
						$valid = true;
					}
				}

				$this->close();
			}
		}

		return $valid;
	}

	//
	// LDAP Functions
	//

	/**
	 * Open connection to the server.
	 */
	function open() {
		$connection = ldap_connect(HOST, PORT);

		$this->conn = $connection;

		ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
		//ldap_set_option($connection, LDAP_OPT_NETWORK_TIMEOUT, 10);

		return $connection;
	}

	/**
	 * Close connection.
	 */
	function close() {
		ldap_close($this->conn);

		$this->conn = null;
	}

	/**
	 * Bind to a directory.
	 * @param $binddn string directory to bind (optional)
	 * @param $password string (optional)
	 * @return resource
	 */
	function bind($binddn = null, $password = null) {
		return @ldap_bind($this->conn, $binddn, $password);
	}

	/**
	 * Lookup a user entry in the directory.
	 * @param $username string
	 * @return bool/resource
	 */
	function getUserEntry($username) {
		$entry = false;
		$connection = $this->conn;

		if ($this->bind(USER, PASSWORD)) {
			$filterFormat = '(%s=%s)';
			$filterUsername = sprintf($filterFormat, USERNAME_KEY, $username);
			// Restraining the valid users to the specific LDAP group.
			$filterGroup = sprintf($filterFormat, GROUP_KEY, GROUP_VALUE);
			$filter = sprintf('(&%s%s)', $filterUsername, $filterGroup);

			$result = ldap_search($connection, SEARCH_CONTEXT, $filter);

			if (ldap_count_entries($connection, $result) == 1) {
				$entry = ldap_first_entry($connection, $result);
			}
		}

		return $entry;
	}

	/**
	 * Retrieve user profile information from the LDAP server.
	 * @param $username string
	 * @return array
	 */
	function getUserInfo($username) {
		if ($this->open()) {
			if ($entry = $this->getUserEntry($username)) {
				return ldap_get_attributes($this->conn, $entry);
			}

			$this->close();
		}

		return null;
	}
}

?>
