<?php

namespace CSUNMetaLab\Authentication\Factories;

/**
 * Factory class that generates and returns hashes to be used with LDAP
 * passwords.
 */
class PasswordFactoryLDAP
{
	/**
	 * Generates and returns a new password as a SSHA hash for use in LDAP. If
	 * the salt is not specified, one will be generated using the openssl
	 * extension with a length of four bytes.
	 *
	 * @param string $password The plaintext password to hash
	 * @param string $salt Optional salt for the algorithm
	 *
	 * @return string
	 */
	public static function SSHA($password, $salt=null) {
		if(empty($salt)) {
			// salts should be four bytes
			$salt = openssl_random_pseudo_bytes(4);
		}
		return "{SSHA}" . base64_encode(sha1($password . $salt, true) . $salt);
	}
}