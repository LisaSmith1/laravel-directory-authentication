<?php

namespace CSUNMetaLab\Authentication\Factories;

use CSUNMetaLab\Authentication\Handlers\HandlerLDAP;

/**
 * Factory class that returns HandlerLDAP instances.
 */
class HandlerLDAPFactory
{
	/**
	 * Returns a HandlerLDAP instance based on the default configuration.
	 *
	 * @return HandlerLDAP
	 */
	public static function fromDefaults() {
		$handler = new HandlerLDAP(
			config('ldap.host'),
			config('ldap.basedn'),
			config('ldap.dn'),
			config('ldap.password'),
			config('ldap.search_user_id'),
			config('ldap.search_username'),
			config('ldap.search_user_mail'),
			config('ldap.search_user_mail_array')
		);
		$handler->setVersion(config('ldap.version'));
		return $handler;
	}
}