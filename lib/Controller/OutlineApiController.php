<?php

declare(strict_types=1);

namespace OCA\Outline\Controller;

use OCA\Outline\Service\OutlineAPIService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\IRequest;

class OutlineApiController extends Controller {
    public function __construct(
        string $appName,
        IRequest $request,
        private OutlineAPIService $apiService,
        private ?string $userId) {
        parent::__construct($appName, $request);
    }

    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function searchDocuments(string $query, int $limit = 10, int $offset = 0): DataResponse {
        $results = $this->apiService->search($this->userId, $query, $limit, $offset);

        if (isset($results['error'])) {
            return new DataResponse(['error' => $results['error']], Http::STATUS_BAD_REQUEST);
        }

        return new DataResponse($results);
    }
}
