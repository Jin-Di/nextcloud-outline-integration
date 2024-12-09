<?php

declare(strict_types=1);

namespace OCA\Outline\Search;

use OCA\Outline\AppInfo\Application;
use OCA\Outline\Service\SecretService;
use OCA\Outline\Service\OutlineAPIService;
use OCP\App\IAppManager;
use OCP\IConfig;
use OCP\IDateTimeFormatter;
use OCP\IDateTimeZone;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;
use OCP\Search\SearchResultEntry;

class OutlineSearchProvider implements IProvider {

	public function __construct(
		private IAppManager $appManager,
		private IL10N $l10n,
		private IConfig $config,
		private IURLGenerator $urlGenerator,
		private IDateTimeFormatter $dateTimeFormatter,
		private IDateTimeZone $dateTimeZone,
		private SecretService $secretService,
		private OutlineAPIService $apiService
	) {
	}

    	public function getId(): string {
        	return 'outline-search-messages';
    	}

    	public function getName(): string {
        	return $this->l10n->t('Outline Knowledge Base');
    	}

    	public function getOrder(string $route, array $routeParameters): int {
        	return 20; // Adjust priority as needed
    	}

    	public function search(IUser $user, ISearchQuery $query): SearchResult {
        	if (!$this->appManager->isEnabledForUser(Application::APP_ID, $user)) {
            		return SearchResult::complete($this->getName(), []);
        	}

        	$limit = $query->getLimit();
		$term = $query->getTerm();
	        $offset = $query->getCursor();
		$offset = $offset ? intval($offset) : 0;

	        $url = $this->config->getUserValue($user->getUID(), Application::APP_ID, 'url');
	        $apiKey = $this->secretService->getEncryptedUserValue($user->getUID(), 'api_key');

	        if ($url === '' || $apiKey === '' ) {
	            	return SearchResult::paginated($this->getName(), [], 0);
	        }

	        // Call Outline API
	        $searchResult = $this->apiService->searchMessages($user->getUID(), $term, $offset, $limit);
		if (isset($searchResult['error'])) {
			return SearchResult::paginated($this->getName(), [], 0);
		}

	        $formattedResults = array_map(function (array $entry) use ($url): SearchResultEntry {
			$finalThumbnailUrl = $this->getThumbnailUrl($entry);
			return new SearchResultEntry(
				$finalThumbnailUrl,
				$this->getMainText($entry),
				$this->getSubline($entry),
				$this->getLinkToOutline($entry,$url),
				$finalThumbnailUrl === '' ? 'icon-outline-search-fallback' : '',
				true
			);
	       	}, $searchResult);

		return SearchResult::paginated(
			$this->getName(),
			$formattedResults,
			$offset + $limit
	    	);
	}

	protected function getMainText(array $entry): string {
		return strip_tags($entry['document']['title']);
	}

	protected function getSubline(array $entry): string {
		return $this->l10n->t('%s created by %s at %s', [$entry['document']['title'], $entry['createdBy']['name'], $this->getFormattedDate($entry['createdBy']['createdAt'])]);
	}

	protected function getFormattedDate(int $timestamp): string {
		return $this->dateTimeFormatter->formatDateTime($timestamp, 'long', 'short', $this->dateTimeZone->getTimeZone());
	}

	/**
	 * @param array $entry
	 * @param string $url
	 * @return string
	 */
	protected function getLinkToOutline(array $entry, string $url): string {
		$topic = str_replace('%', '.', rawurlencode($entry['subject']));
		return rtrim($url, '/') . $entry['document']['url'];
	}

	/**
	 * @param array $entry
	 * @return string
	 */
	protected function getThumbnailUrl(array $entry): string {
		return '';
	}
}
