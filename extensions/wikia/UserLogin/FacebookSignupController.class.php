<?php

/**
 * Controller performing sign-up using Facebook Connect feature
 *
 * @author macbre
 */
class FacebookSignupController extends WikiaController {

	const SIGNUP_USERNAME_KEY = 'username';
	const SIGNUP_PASSWORD_KEY = 'password';

	/** @var \FacebookClientFactory */
	protected $fbClientFactory;

	public function __construct() {
		parent::__construct();

		$this->fbClientFactory = new \FacebookClientFactory();
	}

	/**
	 * This method is called when user successfully logins using FB credentials
	 *
	 * Facebook user ID is passed to our backend:
	 *  - if there's Wikia account connected, log the user in,
	 *  - if not, render sign up modal
	 */
	public function index() {
		$fbUserId = $this->getFacebookUserId();

		// try to get connected Wikia account
		$user = FacebookClient::getInstance()->getWikiaUser( $fbUserId );

		if ( ( $user instanceof User ) && ( $fbUserId !== 0 ) ) {
			$this->errorMsg = '';

			if ( $this->isAccountDisabled( $user ) ) {
				// User account was disabled, abort the login
				$this->loginAborted = true;
				$this->errorMsg = wfMessage( 'userlogin-error-edit-account-closed-flag' )->escaped();
			} elseif ( $this->isAccountUnconfirmed( $user ) ) {
				$this->unconfirmed = true;
				$this->userName = $user->getName();
				LoginForm::clearLoginToken();
				$userLoginHelper = new UserLoginHelper();
				$userLoginHelper->setNotConfirmedUserSession( $user->getId() );
				$userLoginHelper->clearPasswordThrottle( $this->userName );
			} elseif ( !wfRunHooks( 'FacebookUserLoginSuccess', [ $user, &$this->errorMsg ] ) ) {
				$this->loginAborted = true;
			} else {
				// account is connected - log the user in
				$user->setCookies();
				$this->loggedIn = true;
				$this->userName = $user->getName();

				// Retrieve user email from Facebook if missing
				$email = $user->getEmail();
				if ( empty( $email ) ) {
					$this->saveEmailAsynchronously( $user->getId() );
				}
			}
		} else {
			$modal = $this->sendRequest('FacebookSignup', 'modal')->__toString();
			$title = $this->sendRequest('FacebookSignup', 'modalHeader')->__toString();

			// no account connected - show FB sign up modal
			$this->htmlTitle = $title;
			$this->modal = !empty($modal) ? $modal : wfMessage('usersignup-facebook-problem')->escaped();
			$this->cancelMsg = wfMessage('cancel')->escaped();
		}
	}

	/**
	 * Kick off an asynch job to update user's email to be what's reported by Facebook
	 * @param $userId
	 */
	protected function saveEmailAsynchronously( $userId ) {
		$task = new \Wikia\Tasks\Tasks\FacebookTask();
		$task->dupCheck();
		$task->call( 'updateEmailFromFacebook', $userId );
		$task->queue();
	}

	/**
	 * Check if account is disabled
	 * @param  User $user User account
	 * @return boolean true if the account is disabled, false otherwise
	 */
	private function isAccountDisabled( User $user ) {
		return $user->getBoolOption( 'disabled' ) || (
			defined( 'CLOSED_ACCOUNT_FLAG' ) &&
			$user->getRealName() == CLOSED_ACCOUNT_FLAG
		);
	}

	/**
	 * Check if account is unconfirmed. User confirms via an email we sent them.
	 * @param  User $user User account
	 * @return boolean true if the account is unconfirmed, false otherwise
	 */
	private function isAccountUnconfirmed( User $user ) {
		return $user->getOption( UserLoginSpecialController::NOT_CONFIRMED_SIGNUP_OPTION_NAME );
	}

	/**
	 * Displays Facebook sign up modal (called by index method)
	 */
	public function modal() {
		$fbUserId = $this->getFacebookUserId();
		if ( empty( $fbUserId ) ) {
			$this->skipRendering();
			return false;
		}

		// get an email from Facebook API
		$userInfo = \FacebookClient::getInstance()->getUserInfo( $fbUserId );
		// BugId:24400
		if ( !$userInfo ) {
			$this->skipRendering();
			return false;
		}
		$this->fbEmail = $userInfo->getProperty( 'email' );

		$returnTo = $this->wg->request->getVal( 'returnto' );
		$returnToQuery = $this->wg->request->getVal( 'returntoquery' );

		// Temporary code until we switch fully to FacebookClient
		$returnToUrl = FacebookClient::getInstance()->getReturnToUrl( $returnTo, $returnToQuery );

		$returnToParams = 'returnto=' . $returnTo;
		if ( $returnToQuery ) {
			$returnToParams .= '&returntoquery=' . htmlspecialchars( $returnToQuery );
		}

		// query string is neaded for redirects after Special:FacebookConnect
		$this->queryString = $returnToParams;
		// return to url is needed for modal signup completion
		$this->returnToUrl = $returnToUrl;

		$this->loginToken = UserLoginHelper::getSignupToken();
	}

	public function modalHeader() {
		$this->response->setTemplateEngine( WikiaResponse::TEMPLATE_ENGINE_MUSTACHE );

		$this->signupMsg = wfMessage( 'usersignup-facebook-signup-header' )->escaped();
		$this->loginMsg = wfMessage( 'usersignup-facebook-login-header' )->escaped();
		$this->orMsg = wfMessage( 'usersignup-facebook-or-header' )->escaped();
	}

	/**
	 * Handle sign up requests from modal
	 */
	public function signup() {

		// Check that Facebook account is not in use!
		$fbId = \FacebookClient::getInstance()->getUserId();
		if ( $this->fbClientFactory->isFacebookIdInUse( $fbId ) ) {
			$errorMessageKey = 'fbconnect-error-fb-account-in-use';
			$messageParams = [ $this->request->getVal( 'username' ) ];
			$this->setErrorResponse( $errorMessageKey, $messageParams );
			return;
		}

		$signupResponse = $this->app->sendRequest( 'FacebookSignup', 'createAccount' )->getData();

		switch ( $signupResponse['result'] ) {
			case 'ok':
				$this->result = 'ok';
				break;
			case 'unconfirm':
				$this->result = 'unconfirm';
				break;
			case 'error':
			default:
				// pass errors to the frontend form
				$this->response->setData( $signupResponse );
				break;
		}
	}

	/**
	 * Handle internal requests for creating Wikia accounts
	 */
	public function createAccount() {
		// Init session if necessary
		if ( session_id() == '' ) {
			wfSetupSession();
		}

		$signupForm = new UserLoginFacebookForm( $this->wg->request );
		$signupForm->load();
		$signupForm->addNewAccount();

		$result = ( $signupForm->msgType == 'error' ) ? 'error' : 'ok' ;
		if ( $result == 'ok' ) {
			\FacebookClientHelper::track( 'facebook-signup-join-wikia' );

			if ( !$signupForm->getHasConfirmedEmail() ) {
				$result = 'unconfirm';
			}
		}

		$this->response->setData( [
			'result' => $result,
			'msg' => $signupForm->msg,
			'errParam' => $signupForm->errParam,
		] );
	}

	/**
	 * Handler for Facebook Login (and connect) for users with a Wikia account
	 *
	 * @return null
	 */
	public function login() {
		$wg = $this->wg;

		$wikiaUserName = $wg->Request->getVal( self::SIGNUP_USERNAME_KEY );
		$wikiaPassword = $wg->Request->getVal( self::SIGNUP_PASSWORD_KEY );

		$user = $this->getValidWikiaUser( $wikiaUserName, $wikiaPassword );
		if ( !$user ) {
			return;
		}

		// Log the user in with existing wikia account
		// Create the fb/Wikia user mapping if not already created

		$fbUserId = $this->getValidFbUserId();
		if ( !$fbUserId ) {
			return;
		}

		$status = $this->fbClientFactory->connectToFacebook( $user->getId(), $fbUserId );
		if ( ! $status->isGood() ) {
			list( $message, $params ) = $this->fbClientFactory->getStatusError( $status );
			$this->setErrorResponse( $message, $params );
			return;
		}

		$this->setupUserSession( $user );

		\FacebookClientHelper::track( 'facebook-link-existing' );

		$this->response->setData( [
			'result' => 'ok',
			'msg' => 'success',
		] );
	}

	/**
	 * Retrieve and validate Facebook user id
	 *
	 * @return int|null
	 */
	protected function getValidFbUserId() {
		$fbUserId = FacebookClient::getInstance()->getUserId();
		if ( !$fbUserId ) {
			$this->setErrorResponse( 'userlogin-error-invalidfacebook' );
			return null;
		}

		return $fbUserId;
	}

	/**
	 * Retrieves and validates the User object matching given credentials
	 *
	 * @param string $wikiaUserName
	 * @param string $wikiaPassword
	 * @return null|User
	 */
	protected function getValidWikiaUser( $wikiaUserName, $wikiaPassword ) {
		if ( !$wikiaUserName ) {
			$messageCode = 'userlogin-error-noname';
			$errorParam = self::SIGNUP_USERNAME_KEY;
		} else if ( !$wikiaPassword ) {
			$messageCode = 'userlogin-error-wrongpasswordempty';
			$errorParam = self::SIGNUP_PASSWORD_KEY;
		} else {
			$user = \User::newFromName( $wikiaUserName );
			if ( !$user ) {
				$messageCode = 'userlogin-error-nosuchuser';
				$errorParam = self::SIGNUP_USERNAME_KEY;
			} else {
				if ( !$user->checkPassword( $wikiaPassword ) ) {
					$messageCode = 'userlogin-error-wrongpassword';
					$errorParam = self::SIGNUP_PASSWORD_KEY;
				}
			}
		}

		if ( $messageCode ) {
			$this->setErrorResponse( $messageCode, [], $errorParam );
			return null;
		}

		return $user;
	}

	/**
	 * Setup session for user to be logged in
	 *
	 * @param User $user
	 */
	protected function setupUserSession( \User $user ) {
		$wg = $this->wg;

		// Setup the session as is done when a request first starts
		if ( !$wg->SessionStarted ) {
			wfSetupSession();
		}

		$user->setCookies();

		// Store the user in the global user object
		$wg->User = $user;
	}

	/**
	 * Set a normalized error response meant for Ajax calls
	 *
	 * @param string $messageKey i18n error message key
	 * @param array $messageParams message parameters
	 * @param string|null $errorParam the error key
	 */
	protected function setErrorResponse( $messageKey, array $messageParams = [], $errorParam = null ) {
		$this->response->setData( [
			'result' => 'error',
			'msg' => wfMessage( $messageKey, $messageParams )->escaped(),
			'errParam' => $errorParam,
		] );
	}

	/**
	 * Get Facebook account ID of currently logged-in user.
	 *
	 * If no user is logged in, then an ID of 0 is returned.
	 */
	private function getFacebookUserId() {
		return FacebookClient::getInstance()->getUserId();
	}
}
