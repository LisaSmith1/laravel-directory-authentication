<?php namespace METALab\Auth\Interfaces;

interface MetaAuthenticatableContract {

	/**
	 * Returns the user with the given identifier. This is used primarily by
	 * custom authentication service providers.
	 *
	 * @param string $identifier The identifier to use for retrieval
	 * @return User
	 */
	public static function findForAuth($identifier);

	/**
	 * Returns the user with the given identifier and Remember Me token. This
	 * is used primarily by custom authentication service providers.
	 *
	 * @param string $identifier The identifier to use for retrieval
	 * @param string $token The token to use for retrieval
	 *
	 * @return User
	 */
	public static function findForAuthToken($identifier, $token);
}