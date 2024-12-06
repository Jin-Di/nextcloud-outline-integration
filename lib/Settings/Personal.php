<?php

declare(strict_types=1);

namespace OCA\Outline\Settings;

use OCA\Outline\AppInfo\Application;
use OCA\Outline\Service\SecretService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\Settings\ISettings;

class Personal implements ISettings {

    public function __construct(
        private IConfig $config,
        private IInitialState $initialStateService,
        private SecretService $secretService,
        private ?string $userId
    ) {
    }

    /**
     * Provide the form for managing the Outline API key in the Personal Settings
     *
     * @return TemplateResponse
     */
    public function getForm(): TemplateResponse {
        $url = $this ->config->getUserValue($this->userId, Application::APP_ID, 'url');
	// Retrieve the encrypted API key
        $apiKey = $this->secretService->getEncryptedUserValue($this->userId, 'api_key') ? 'dummyKey' : '';

        // Provide the initial state with just the API key
        $userConfig = [
		'url' => $url,
		'api_key' => $apiKey,
        ];
        $this->initialStateService->provideInitialState('user-config', $userConfig);

        // Return the template for personal settings
        return new TemplateResponse(Application::APP_ID, 'personalSettings');
    }

    /**
     * Define the section for Personal Settings
     *
     * @return string
     */
    public function getSection(): string {
        return 'connected-accounts';
    }

    /**
     * Set the priority of this section in the Personal Settings page
     *
     * @return int
     */
    public function getPriority(): int {
        return 10;
    }
}
