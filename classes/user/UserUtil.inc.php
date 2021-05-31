<?php

/**
 * @file classes/user/UserUtil.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Copyright (c) 2021 Université Laval
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class UserUtil
 * @ingroup user
 * @see User
 *
 * @brief Utility methods used to manipulate the users.
 */

class UserUtil {

	/**
	 * Constructor.
	 */
	private function __construct() {}

	/**
	 * Create a new user.
	 * @param $request PKPRequest
	 * @param $args array
	 * @param $auth
	 * @param $requireValidation bool
	 * @return User
	 */
	static function createUser($request, $args, $auth = null, $requireValidation = false) {
		$email = self::getData($args, 'email');
		$username = self::getData($args, 'username');
		$password = self::getData($args, 'password');

		if (!$password) {
			$password = Validation::generatePassword(20);
		}

		// The multilingual user data (givenName, familyName and affiliation) will be saved
		// in the current UI locale and copied in the site's primary locale too.
		$site = $request->getSite();
		$sitePrimaryLocale = $site->getPrimaryLocale();
		$currentLocale = AppLocale::getLocale();

		$userDao = DAORegistry::getDAO('UserDAO');

		$user = $userDao->newDataObject();
		$user->setUsername($username);
		$user->setGivenName(self::getData($args, 'givenName'), $currentLocale);
		$user->setFamilyName(self::getData($args, 'familyName'), $currentLocale);
		$user->setEmail($email);
		$user->setCountry(self::getData($args, 'country'));
		$user->setAffiliation(self::getData($args, 'affiliation'), $currentLocale);
		$user->setPhone(self::getData($args, 'phone'));
		$user->setLdap(self::getData($args, 'ldap'));
		$user->setStatus(self::getData($args, 'status'));

		if ($sitePrimaryLocale != $currentLocale) {
			$user->setGivenName(self::getData($args, 'givenName'), $sitePrimaryLocale);
			$user->setFamilyName(self::getData($args, 'familyName'), $sitePrimaryLocale);
			$user->setAffiliation(self::getData($args, 'affiliation'), $sitePrimaryLocale);
		}

		if ($auth) {
			$user->setPassword($password);
			// FIXME Check result and handle failures
			$auth->doCreateUser($user);
			$user->setAuthId($auth->authId);
		}

		$user->setPassword(Validation::encryptCredentials($username, $password));
		$user->setDateRegistered(Core::getCurrentDate());
		$user->setInlineHelp(1);

		if ($requireValidation) {
			// The account should be created in a disabled state.
			$user->setDisabled(true);
			$user->setDisabledReason(__('user.login.accountNotValidated', array('email' => $email)));
		}

		$userDao->insertObject($user);

		return !$user->getId() ? null : $user;
	}

	/**
	 * Get the value of an array.
	 * @param $args array
	 * @param $key string
	 * @return mixed
	 */
	private static function getData($args, $key) {
		return isset($args[$key]) ? $args[$key] : null;
	}

	/**
	 * If the journal accepts auto-registration, assign automatically
	 * the author role to the signed in user.
	 * @param $request PKPRequest
	 * @param $user User
	 */
	static function assignAuthorRoleUser($request, $user) {
		$context = $request->getContext();

		if (is_null($context)) {
			return;
		}

		/*$contextDao = DAORegistry::getDAO('JournalDAO');
		$contexts = $contextDao->getAll();
		foreach ($contexts as $context) {
			// Do code below...
		}*/

		$disableUserReg = $context->getSetting('disableUserReg');

		if ($disableUserReg === true) {
			return;
		}

		$contextId = $context->getId();

		if (!$user->hasRole(array(ROLE_ID_AUTHOR), $contextId)) {
			$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
			$userGroups = $userGroupDao->getByRoleId($contextId, ROLE_ID_AUTHOR);
			$userId = $user->getId();

			while ($userGroup = $userGroups->next()) {
				if (!$userGroup->getPermitSelfRegistration()) {
					continue;
				}

				$groupId = $userGroup->getId();

				if (!$userGroupDao->userInGroup($userId, $groupId)) {
					$userGroupDao->assignUserToGroup($userId, $groupId, $contextId);
				}
			}
		}
	}
}

?>
