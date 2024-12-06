<?php

declare(strict_types=1);

namespace OCA\Outline\Service;

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use OCA\Outline\AppInfo\Application;
use OCP\Files\File;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IL10N;
use OCP\PreConditionNotMetException;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Service to make network requests
 */
class NetworkService {

	private IClient $client;

	public function __construct(
		private IConfig $config,
		IClientService $clientService,
		private LoggerInterface $logger,
		private SecretService $secretService,
		private IL10N $l10n
	) {
		$this->client = $clientService->newClient();
	}


	public function request(string $userId, string $endPoint, array $params = [], string $method = 'POST',
		bool $jsonResponse = true, bool $outlineApiRequest = true) {
		$outlineUrl = $this->config->getUserValue($userId, Application::APP_ID, 'url');
		$apiKey = $this->secretService->getEncryptedUserValue($userId, 'api_key');

		try {
			$url = rtrim($outlineUrl, '/') . '/api/' . $endPoint;
			$options = [
				'headers' => [
					'Authorization' => 'Bearer ' . $apiKey,
					'Content-Type' => 'application/json',
					'User-Agent' => Application::INTEGRATION_USER_AGENT,
					'Accept' => 'application/json',
				],
			];

			if (count($params) > 0) {
				if ($method === 'GET') {
					$url .= '?' . http_build_query($params);
				} else {
					$options['body'] = $params;
				}
			}

			if ($method === 'GET') {
				$response = $this->client->get($url, $options);
			} elseif ($method === 'POST') {
				$response = $this->client->post($url, $options);
			} elseif ($method === 'PUT') {
				$response = $this->client->put($url, $options);
			} elseif ($method === 'DELETE') {
				$response = $this->client->delete($url, $options);
			} else {
				return ['error' => $this->l10n->t('Bad HTTP method')];
			}
			$body = $response->getBody();
			$respCode = $response->getStatusCode();

			if ($respCode >= 400) {
				return ['error' => $this->l10n->t('Bad credentials')];
			}

			if ($jsonResponse) {
				return json_decode($body, true);
			}
			return $body;
		} catch (ServerException | ClientException $e) {
			$body = $e->getResponse()->getBody();
			$this->logger->warning('Outline API error : ' . $body, ['app' => Application::APP_ID]);
			return ['error' => $e->getMessage()];
		} catch (Exception | Throwable $e) {
			$this->logger->warning('Outline API error', ['exception' => $e, 'app' => Application::APP_ID]);
			return ['error' => $e->getMessage()];
		}
	}

	/**
	 * @param string $userId
	 * @param string $endPoint
	 * @param File $file
	 * @return array|mixed|resource|string|string[]
	 * @throws PreConditionNotMetException
	 */
	public function requestSendFile(string $userId, string $endPoint, File $file): array {
		$outlineUrl = $this->config->getUserValue($userId, Application::APP_ID, 'url');
		$email = $this->config->getUserValue($userId, Application::APP_ID, 'email');
		$apiKey = $this->secretService->getEncryptedUserValue($userId, 'api_key');

		try {
			$url = rtrim($outlineUrl, '/') . '/api/v1/' . $endPoint;
			$credentials = base64_encode($email . ':' . $apiKey);
			$options = [
				'headers' => [
					'Authorization' => 'Basic ' . $credentials,
					'User-Agent' => Application::INTEGRATION_USER_AGENT,
				],
				'multipart' => [
					[
						'name' => 'filename',
						'contents' => $file->getContent(),
						'filename' => $file->getName(),
					],
				],
			];

			$response = $this->client->post($url, $options);
			$body = $response->getBody();
			$respCode = $response->getStatusCode();

			if ($respCode >= 400) {
				return ['error' => $this->l10n->t('Bad credentials')];
			}
			return json_decode($body, true);
		} catch (ServerException | ClientException $e) {
			$body = $e->getResponse()->getBody();
			$this->logger->warning('Outline API error : ' . $body, ['app' => Application::APP_ID]);
			return ['error' => $e->getMessage()];
		} catch (Exception | Throwable $e) {
			$this->logger->warning('Outline API error', ['exception' => $e, 'app' => Application::APP_ID]);
			return ['error' => $e->getMessage()];
		}
	}

	/**
	 * @param string $userId
	 * @param string $avatarUrl
	 * @return array|mixed|resource|string|string[]
	 * @throws PreConditionNotMetException
	 */
	public function requestAvatar(string $userId, string $avatarUrl): mixed {
		$outlineUrl = $this->config->getUserValue($userId, Application::APP_ID, 'url');

		try {
			$url = rtrim($outlineUrl, '/') . $avatarUrl;
			$options = [
				'headers' => [
					'User-Agent' => Application::INTEGRATION_USER_AGENT,
				],
			];

			$response = $this->client->get($url, $options);

			return $response->getBody();
		} catch (ServerException | ClientException $e) {
			$body = $e->getResponse()->getBody();
			$this->logger->warning('Outline API error : ' . $body, ['app' => Application::APP_ID]);
			return ['error' => $e->getMessage()];
		} catch (Exception | Throwable $e) {
			$this->logger->warning('Outline API error', ['exception' => $e, 'app' => Application::APP_ID]);
			return ['error' => $e->getMessage()];
		}
	}
}
