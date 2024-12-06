<?php

declare(strict_type=1);

namespace OCA\Outline\Controller;

use OCA\Outline\AppInfo\Application;
use OCA\Outline\Service\SecretService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUser;

class ConfigController extends Controller {
    public function __construct(
        string $appName,
        IRequest $request,
        private IConfig $config,
        private SecretService $secretService,
        private ?string $userId
    ) {
        parent::__construct($appName, $request);
    }


    #[NoAdminRequired]
    #[FrontpageRoute(verb: 'GET', url: '/is-connected')]
    public function isUserConnected(): DataResponse {
	$url = $this->config->getUserValue($this->userId, Application::APP_ID, 'url');
	$apiKey = $this->secretService->getEncryptedUserValue($this->userId, 'api_key');

	return new DataResponse([
		'connected' => ($url !== '' && $email !== '' && $apiKey !== ''),
	]);
    }

    #[NoAdminRequired]
    #[FrontpageRoute(verb: 'PUT', url: '/config')]
    public function setConfig(array $values): DataResponse {
        foreach ($values as $key => $value) {
            if ($key === 'api_key') {
                return new DataResponse([], Http::STATUS_BAD_REQUEST);
            }
            $this->config->setUserValue($this->userId, Application::APP_ID, $key, $value);
        }
        return new DataResponse([]);
    }

    #[NoAdminRequired]
    #[PasswordConfirmationRequired]
    #[FrontpageRoute(verb: 'PUT', url: '/sensitive-config')]
    public function setSensitiveConfig(array $values): DataResponse {
        foreach ($values as $key => $value) {
            if ($key === 'api_key') {
                $this->secretService->setEncryptedUserValue($this->userId, $key, $value);
            } else {
                $this->config->setUserValue($this->userId, Application::APP_ID, $key, $value);
            }
        }
        return new DataResponse([]);
    }
}
