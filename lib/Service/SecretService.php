<?php

declare(strict_types=1);

namespace OCA\Outline\Service;

use OCA\Outline\AppInfo\Application;
use OCP\Security\ICrypto;
use OCP\IConfig;

class SecretService {

    private ICrypto $crypto;
    private IConfig $config;

    public function __construct(ICrypto $crypto, IConfig $config) {
        $this->crypto = $crypto;
        $this->config = $config;
    }

    /**
     * Encrypt and save a user-specific value securely
     */
    public function setEncryptedUserValue(string $userId, string $key, string $value): void {
		if ($value === '') {
			$this->config->setUserValue($userId, Application::APP_ID, $key, '');
			return;
		}
		$encryptedValue = $this->crypto->encrypt($value);
		$this->config->setUserValue($userId, Application::APP_ID, $key, $encryptedValue);
    }

    /**
     * Retrieve and decrypt a user-specific value
     */
    public function getEncryptedUserValue(string $userId, string $key): ?string {
        $storedValue = $this->config->getUserValue($userId, Application::APP_ID, $key);
        if ($storedValue === '') {
            return '';
        }
        return $this->crypto->decrypt($storedValue);
    }
}
