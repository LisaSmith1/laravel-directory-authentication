<?php

namespace CSUNMetaLab\Authentication\Exceptions;

use Exception;

/**
 * This exception is thrown when the PHP ldap extension has not been loaded.
 */
class LdapExtensionNotLoadedException extends Exception
{
	/**
	 * Constructs a new LdapExtensionNotLoadedException object.
	 */
	public function __construct($message="The PHP ldap extension must be installed and enabled") {
		parent::__construct($message);
	}
}