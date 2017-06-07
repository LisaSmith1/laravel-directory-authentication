<?php

namespace CSUNMetaLab\Authentication\Exceptions;

use Exception;

/**
 * This exception is thrown when the user model specified in config/auth.php
 * either does not exist or has an invalid configuration value.
 */
class InvalidUserModelException extends Exception
{
	/**
	 * Constructs a new InvalidUserModelException object.
	 */
	public function __construct($message="Invalid auth user model in configuration") {
		parent::__construct($message);
	}
}