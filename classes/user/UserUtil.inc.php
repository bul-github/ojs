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
