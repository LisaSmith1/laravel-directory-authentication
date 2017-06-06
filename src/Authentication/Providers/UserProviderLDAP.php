<?php

namespace CSUNMetaLab\Authentication\Providers;

use Exception;

use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;

use CSUNMetaLab\Authentication\Handlers\HandlerLDAP;

/**
 * Service provider handler that provides LDAP authentication operations.
 */
class UserProviderLDAP implements UserProvider
{
	private $ldap;
	private $modelName;

	// values for searching
	private $search_user_id;
	private $search_username;
	private $search_user_mail;
	private $search_user_mail_array;
	private $search_db_user_id_prefix;

	/**
	 * Constructs a new UserProviderLDAP object.
	 */
	public function __construct() {
		// set the searching attributes in LDAP
		$this->search_user_id = config('ldap.search_user_id');
		$this->search_username = config('ldap.search_username');
		$this->search_user_mail = config('ldap.search_user_mail');
		$this->search_user_mail_array = config('ldap.search_user_mail_array');

		// create the LDAP handler
		$this->ldap = new HandlerLDAP(
			config('ldap.host'),
			config('ldap.basedn'),
			config('ldap.dn'),
			config('ldap.password'),
			$this->search_user_id,
			$this->search_username,
			$this->search_user_mail,
			$this->search_user_mail_array);

		// set the model name to use as the user model
		$this->modelName = config('auth.providers.users.model');

		// set whether blank passwords are allowed to be used for auth
		$this->ldap->setAllowNoPass(config('ldap.allow_no_pass'));

		// set the searching attributes in the database after LDAP
		$this->search_db_user_id_prefix = config('ldap.search_user_id_prefix');
	}

	/**
 	 * Retrieves the user with the specified credentials from LDAP. Returns null
     * if the user could not be found. If the optional $checkDatabaseModel flag has
     * been set to false, it will instead return true if the authentication was successful.
 	 *
 	 * @param array $credentials The credentials to use
 	 * @param boolean $checkDatabaseModel True to attempt retrieval of a matching DB model, false otherwise
 	 *
 	 * @return User|boolean|null
 	 */
    public function retrieveByCredentials(array $credentials, $checkDatabaseModel=true) {
    	$u = $credentials['username'];
    	$p = $credentials['password'];

    	$m = $this->modelName;

    	// attempt to auth with the credentials provided first
    	try
    	{
    		// attempt regular authentication
	    	if($this->testCredentials($u, $p)) {
	    		// the credentials are valid so let's do the full search with
	    		// the default DN provided through the constructor
	    		$this->ldap->connect();

	    		$result = $this->ldap->searchByAuth($u);

	    		$emplId = $this->ldap->getAttributeFromResults($result, $this->search_user_id);
	    		$uid = $this->ldap->getAttributeFromResults($result, $this->search_username);
	    		$firstName = $this->ldap->getAttributeFromResults($result, "givenName");
	    		$lastName = $this->ldap->getAttributeFromResults($result, "sn");
	    		$displayName = $this->ldap->getAttributeFromResults($result, "displayName");
	    		$email = $this->ldap->getAttributeFromResults($result, $this->search_user_mail);

	    		// grab the first user with the specified attributes; return a fake user instance
	    		// if the user does not exist in the database
	    		$user = $m::findForAuth($this->search_db_user_id_prefix . $emplId);
	    		if(empty($user)) {
	    			$user = new $m();
	    		}

	    		// if the user ID is empty, we have an invalid user and should
	    		// treat it like a regular invalid authentication attempt
	    		if(empty($emplId)) {
	    			return null;
	    		}

	    		// add the LDAP search attributes
	    		$user->searchAttributes = [
	    			'uid' => $uid,
	    			'user_id' => $emplId,
	    			'first_name' => $firstName,
	    			'last_name' => $lastName,
	    			'display_name' => $displayName,
	    			'email' => $email,
	    		];

	    		// the authentication was successful
	    		return $user;
	    	}
    	}
    	catch(Exception $e)
    	{
    		// LDAP connection failure
    		return null;
    	}

    	// invalid login attempt
    	return null;
    }

	/**
	 * Retrieves the user with the specified identifier from the model.
	 *
	 * @param string $identifier The desired identifier to use
	 * @return User
	 */
    public function retrieveById($identifier) {
    	$m = $this->modelName;
    	return $m::findForAuth($identifier);
    }

    /**
	 * Returns the user with the specified identifier and Remember Me token.
	 *
	 * @param string $identifier The identifier to use
	 * @param string $token The Remember Me token to use
	 * @return User
	 */
	public function retrieveByToken($identifier, $token) {
		$m = $this->modelName;
		return $m::findForAuthToken($identifier, $token);
	}

	/**
	 * Returns whether the credentials provided can be used to authenticate
	 * against the directory.
	 *
	 * @param string $username The username to check
	 * @param string $password The password to check
	 * @return boolean
	 */
	protected function testCredentials($username, $password) {
		// we have to do something different with the bind to
		// check the credentials
		$this->ldap->connect();

		$result = $this->ldap->searchByAuth($username);

		// now we have to retrieve the uid and use that as
		// the username
		$username = $this->ldap->getAttributeFromResults($result, $this->search_username);

		// perform the bind with the uid/password combination
		return $this->ldap->connect($username, $password);
	}

	/**
	 * Updates the Remember Me token for the specified identifier.
	 *
	 * @param UserInterface $user The user object whose token is being updated
	 * @param string $token The Remember Me token to update
	 */
    public function updateRememberToken(AuthenticatableContract $user, $token) {
	    if(!empty($user)) {
	    	// make sure there is a remember_token field available for
	    	// updating before trying to update; otherwise we run into
	    	// an uncatchable exception
	    	if($user->canHaveRememberToken()) {
	    		$user->remember_token = $token;
	    		$user->save();
	    	}
	    }
    }

    /**
 	 * Validates that the provided credentials match the provided user.
 	 *
 	 * @param UserInterface $user The provided user object
 	 * @param array $credentials The credentials against which to check
 	 * @return boolean
 	 */
    public function validateCredentials(AuthenticatableContract $user, array $credentials) {
    	// our external service, directory, etc. has already verified whether
    	// or not the credentials are valid so the point is moot here; instead,
    	// let's either "return true" to do a pass-through or do a check for
    	// whether the user is actually active and should be allowed to auth in.
    	return true;
		//return $user->isActive();
    }
}