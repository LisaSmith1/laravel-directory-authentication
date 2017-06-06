<?php

namespace CSUNMetaLab\Authentication\Providers;

use Illuminate\Support\ServiceProvider,
	Illuminate\Support\Facades\Auth,
	Illuminate\Support\Facades\App,
	Illuminate\Auth\Guard;

class AuthServiceProvider extends ServiceProvider
{
	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register() {
		// LDAP auth extension
		Auth::provider('ldap', function($app, array $config) {
			return new \CSUNMetaLab\Authentication\Providers\UserProviderLDAP();
		});
	}

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot() {
		$this->publishes([
        	__DIR__.'/../config/ldap.php' => config_path('ldap.php'),
    	]);
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides() {
		return array();
	}

}
