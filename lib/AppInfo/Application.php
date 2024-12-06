<?php

declare(strict_types=1);

namespace OCA\Outline\AppInfo;

use OCA\Outline\Search\OutlineSearchProvider;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

class Application extends App implements IBootstrap {
	public const APP_ID = 'outline';
	public const INTEGRATION_USER_AGENT = 'Nextcloud Outline Integration';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerSearchProvider(OutlineSearchProvider::class);
	}

	public function boot(IBootContext $context): void {
	}
}
