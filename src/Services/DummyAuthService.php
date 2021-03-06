<?php

namespace InfamousQ\LManager\Services;


use Hybridauth\Exception\InvalidArgumentException;
use Hybridauth\Exception\UnexpectedValueException;
use InfamousQ\LManager\Util\DummyAdapter;
use InfamousQ\LManager\Util\RuntimeHybridauthStorage;

class DummyAuthService extends BaseAuthenticationService {

	public static $callback = '';
	public static $provider_config = array();
	public static $provider_name_storage;

	public function __construct(array $config) {
		$authentication_service_config = $config;
		self::$callback = $authentication_service_config['callback'];
		self::$provider_config = $authentication_service_config['providers'];
	}

	public function getProviderConfig($provider_name) {
		if (!is_array(self::$provider_config)) {
			return array();
		}
		if (array_key_exists($provider_name, self::$provider_config)) {
			$provider_config = self::$provider_config[$provider_name];
			if (empty($provider_config['enabled'] || empty($provider_config['keys']))) {
				throw new UnexpectedValueException("Provider '$provider_name' is not configured'");
			}
			return $provider_config;
		}
		throw new InvalidArgumentException("Provider '$provider_name' not found'");
	}

	public function getAvailableProviders() {
		$provider_data = [];
		foreach (self::$provider_config as $provider_config) {
			$provider_data[] = [
				'name' => $provider_config['name'],
				'code' => $provider_config['code'],
				'icon' => $provider_config['icon'],
				];
		}
		return $provider_data;
	}

	public function getConnectedProviders() {
		return array_keys(self::$provider_config);
	}

	public function authenticate($provider_name) {
		return new DummyAdapter(array(), null, new RuntimeHybridauthStorage());
	}

	public function disconnectAllAdapters() {
		return true;
	}

	public function getAdapter($provider_name) {
		return new DummyAdapter(array(), null, new RuntimeHybridauthStorage());
	}

	public function setProviderToStorage($provider_name) {
		self::$provider_name_storage = $provider_name;
	}

	public function getProviderFromStorage() {
		return self::$provider_name_storage;
	}
}