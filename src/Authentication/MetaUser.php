<?php namespace METALab\Auth;

use Schema;
use METALab\Auth\Interfaces\MetaAuthenticatableContract;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;

/**
 * Base class used with the META+Lab authentication mechanism. This can be
 * used on its own or subclassed for a specific application. If used on its
 * own it expects a table called "users" with a primary key of "user_id". The
 * constructor can also be used to provide an instance with modified table
 * and primary key names as well.
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
}