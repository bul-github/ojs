<?php

/**
 * @file controllers/grid/users/reviewer/ReviewerGridHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ReviewerGridHandler
 * @ingroup controllers_grid_users_reviewer
 *
 * @brief Handle reviewer grid requests.
 */

import('lib.pkp.classes.controllers.grid.users.reviewer.PKPReviewerGridHandler');

class ReviewerGridHandler extends PKPReviewerGridHandler {

	/**
	 * @copydoc PKPReviewerGridHandler::reviewRead()
	 */
	function reviewRead($args, $request) {
		// Retrieve review assignment.
		$reviewAssignment = $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT); /* @var $reviewAssignment ReviewAssignment */

		// Recommendation
		$newRecommendation = $request->getUserVar('recommendation');
		// If editor set or changed the recommendation
		if ($newRecommendation && $reviewAssignment->getRecommendation() != $newRecommendation) {
			$reviewAssignment->setRecommendation($newRecommendation);

			// Add log entry
			import('lib.pkp.classes.log.SubmissionLog');
			import('classes.log.SubmissionEventLogEntry');
			$submission = $this->getSubmission();
			$userDao = DAORegistry::getDAO('UserDAO'); /* @var $userDao UserDAO */
			$reviewer = $userDao->getById($reviewAssignment->getReviewerId());
			$user = $request->getUser();
			AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON, LOCALE_COMPONENT_APP_EDITOR);
			SubmissionLog::logEvent($request, $submission, SUBMISSION_LOG_REVIEW_RECOMMENDATION_BY_PROXY, 'log.review.reviewRecommendationSetByProxy', array('round' => $reviewAssignment->getRound(), 'submissionId' => $submission->getId(), 'editorName' => $user->getFullName(), 'reviewerName' => $reviewer->getFullName()));
		}
		return parent::reviewRead($args, $request);
	}

	/**
	 * Reset the reviewer's decision, notify the user and log the event.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage
	 */
	function resetReviewDecision($args, $request) {
		$reviewAssignment = $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT);
		$submission = $this->getSubmission();

		$reviewAssignment->setDeclined(false);
		$reviewAssignment->setDateConfirmed(null);

		$context = $request->getContext();
		$numWeeksPerResponse = $context->getData('numWeeksPerResponse');
		if ($numWeeksPerResponse === 0) {
			// It seems the true default number is 3 weeks.
			$numWeeksPerResponse = 3;
		}

		$numWeeksPerReview = $context->getData('numWeeksPerReview');
		if ($numWeeksPerReview === 0 || $numWeeksPerReview <= $numWeeksPerResponse) {
			// At least one week after the maximum response date.
			$numWeeksPerReview = $numWeeksPerResponse + 1;
		}

		$dateToResponse = date('Y/m/d', strtotime(sprintf('+%d week', $numWeeksPerResponse)));
		$dateToReview = date('Y/m/d', strtotime(sprintf('+%d week', $numWeeksPerReview)));

		$reviewAssignment->setDateResponseDue($dateToResponse);
		$reviewAssignment->setDateDue($dateToReview);

		$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewAssignmentDao->updateObject($reviewAssignment);

		$reviewerSubmissionDao = DAORegistry::getDAO('ReviewerSubmissionDAO');
		$reviewerSubmission = $reviewerSubmissionDao->getReviewerSubmission($reviewAssignment->getId());

		$reviewerSubmission->setStep(1);

		$reviewerSubmissionDao->updateObject($reviewerSubmission);

		$userDao = DAORegistry::getDAO('UserDAO');
		$reviewer = $userDao->getById($reviewAssignment->getReviewerId());
		$user = $request->getUser();

		$notificationMgr = new NotificationManager();
		$notificationMgr->createTrivialNotification(
			$user->getId(),
			NOTIFICATION_TYPE_SUCCESS,
			array('contents' => __('notification.type.resetReviewDecision'))
		);

		import('lib.pkp.classes.log.SubmissionLog');
		import('classes.log.SubmissionEventLogEntry');
		SubmissionLog::logEvent(
			$request,
			$submission,
			SUBMISSION_LOG_REVIEW_RESET_DECISION,
			'log.editor.resetReviewDecision',
			array(
				'reviewerName' => $reviewer->getFullName(),
				'editorName' => $user->getFullName(),
				'submissionId' => $submission->getId()
			)
		);

		return DAO::getDataChangedEvent($reviewAssignment->getId());
	}
}


