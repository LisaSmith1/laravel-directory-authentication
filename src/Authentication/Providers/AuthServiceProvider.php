<?php namespace METALab\Auth\Provider;

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
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot() {
		// LDAP auth extension
		Auth::provider('ldap', function($app, array $config) {
			return new \METALab\Auth\Provider\UserProviderLDAP();
		});
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register() {
		//
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
