<?php

namespace CSUNMetaLab\Authentication;

use Schema;
use CSUNMetaLab\Authentication\Interfaces\MetaAuthenticatableContract;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;

/**
 * Base class used with the META+Lab authentication mechanism. This can be
 * used on its own or subclassed for a specific application. If used on its
 * own it expects a table called "users" with a primary key of "user_id".
 */
class MetaUser extends Model implements AuthenticatableContract, MetaAuthenticatableContract {

	use Authenticatable;

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	const META_USER_TABLE = "users";

	/**
	 * The primary key used by the database table.
	 *
	 * @var string
	 */
	const META_USER_PRIMARY_KEY = "user_id";

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = self::META_USER_TABLE;

	/**
	 * The primary key used by the database table.
	 *
	 * @var string
	 */
	protected $primaryKey = self::META_USER_PRIMARY_KEY;

	/**
	 * The attributes returned by the search from the remote directory server.
	 *
	 * @var array
	 */
	public $searchAttributes = [];

	/**
	 * Returns whether this model supports having a remember_token attribute.
	 *
	 * @return boolean
	 */
	public function canHaveRememberToken() {
		return Schema::hasColumn($this->table, 'remember_token');
	}

	// implements MetaAuthenticatableContract#findForAuth
	public static function findForAuth($identifier) {
		return self::where(self::META_USER_PRIMARY_KEY, '=', $identifier)
			->first();
	}

	// implements MetaAuthenticatableContract#findForAuthToken
	public static function findForAuthToken($identifier, $token) {
		return self::where(self::META_USER_PRIMARY_KEY, '=', $identifier)
			->where('remember_token', '=', $token)
			->first();
	}

	/**
	 * Dynamic attribute that returns whether this record is valid within the
	 * database. If the column represented by the primary key is NULL then this
	 * method returns false as the record would not fundamentally exist.
	 *
	 * @example $user->is_valid
	 *
	 * @return boolean
	 */
	public function getIsValidAttribute() {
		return $this->exists;
	}

	/**
	 * Returns the currently-masquerading user instance. This is not the user
	 * the authenticated user is masquerading as. Returns null if there is no
	 * masquerade curently taking place.
	 *
	 * @return MetaUser|null 
	 */
	public function getMasqueradingUser() {
		if($this->isMasquerading()) {
			return session('masquerading_user');
		}

		return null;
	}

	/**
     * Returns whether this user instance is currently masquerading as another
     * user.
     *
     * @return boolean
     */
    public function isMasquerading() {
        return session('masquerading_user') != null;
    }

	/**
     * Allows the logged-in user to masquerade as the passed User. Returns true
     * on success or false otherwise.
     *
     * @param MetaUser $user The user instance to become
     * @return boolean
     */
    public function masqueradeAsUser($user) {
        // if this user is authenticated, then we can masquerade as the
        // user that has been passed as the parameter
        if(auth()->check()) {
            if(auth()->user()->user_id == $this->user_id) {
                // write the authenticated user into the session and then
                // authenticate as the passed user instance
                session(['masquerading_user' => auth()->user()]);
                auth()->logout();
                auth()->login($user);

                // success!
                return true;
            }
        }

        return false;
    }

    /**
     * Stops masquerading as another user if we are currently masquerading.
     * Returns true on success or false otherwise.
     *
     * @return boolean
     */
    public function stopMasquerading() {
        if($this->isMasquerading()) {
            // become the real user again
            auth()->logout();
            auth()->login(session('masquerading_user'));
            session(['masquerading_user' => null]);

            // success!
            return true;
        }

        return false;
    }
}