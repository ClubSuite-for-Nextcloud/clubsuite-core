<?php
/**
 * API Auth Controller for ClubSuite
 * Handles token generation, validation and management
 */

declare(strict_types=1);

namespace OCA\ClubSuiteCore\Controller;

use OCA\ClubSuiteCore\Service\ApiAuthService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\IRequest;
use Psr\Log\LoggerInterface;
use OCP\IL10N;

class ApiAuthController extends Controller {

    private ApiAuthService $authService;
    private LoggerInterface $logger;
    private IL10N $l;

    public function __construct(
        string $appName,
        IRequest $request,
        ApiAuthService $authService,
        LoggerInterface $logger,
        IL10N $l
    ) {
        parent::__construct($appName, $request);
        $this->authService = $authService;
        $this->logger = $logger;
        $this->l = $l;
    }

    /**
     * Generate a new API token
     * POST /api/auth/token
     */
    #[NoCSRFRequired]
    public function generateToken(): DataResponse {
        try {
            $userId = $this->request->getParam('userId');
            $result = $this->authService->generateToken($userId);
            return new DataResponse($result);
        } catch (\Throwable $e) {
            $this->logger->error('Token generation failed: ' . $e->getMessage());
            return new DataResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * List active tokens for current user
     * GET /api/auth/tokens
     */
    #[NoCSRFRequired]
    public function listTokens(): DataResponse {
        try {
            $user = \OCP\Server::get(\OCP\IUserSession::class)->getUser();
            if (!$user) {
                return new DataResponse(['error' => $this->l->t('Not logged in')], 401);
            }
            $tokens = $this->authService->listTokens($user->getUID());
            return new DataResponse(['tokens' => $tokens]);
        } catch (\Throwable $e) {
            $this->logger->error('Token list failed: ' . $e->getMessage());
            return new DataResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Revoke an API token
     * DELETE /api/auth/token
     */
    #[NoCSRFRequired]
    public function revokeToken(): DataResponse {
        try {
            $token = $this->request->getHeader('Authorization');
            if (!$token) {
                return new DataResponse(['error' => $this->l->t('Token required')], 400);
            }
            
            $token = str_replace('Bearer ', '', $token);
            $result = $this->authService->revokeToken($token);
            
            if ($result) {
                return new DataResponse(['status' => 'success']);
            }
            return new DataResponse(['error' => $this->l->t('Token not found')], 404);
        } catch (\Throwable $e) {
            $this->logger->error('Token revocation failed: ' . $e->getMessage());
            return new DataResponse(['error' => $e->getMessage()], 400);
        }
    }
}