<?php

declare(strict_types=1);

namespace OCA\ClubSuiteCore\Controller;

use Exception;
use OCA\ClubSuiteCore\Service\MemberService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;
use OCP\IL10N;
use OCP\IUserSession;
use OCP\IGroupManager;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;

class MemberApiController extends Controller {

    public function __construct(
        string $appName,
        IRequest $request,
        private MemberService $service,
        private LoggerInterface $logger,
        private IL10N $l,
        private IUserSession $userSession,
        private IGroupManager $groupManager
    ) {
        parent::__construct($appName, $request);
    }

    private function checkPermissions(): void {
        $user = $this->userSession->getUser();
        if (!$user) {
            throw new Exception($this->l->t('Not logged in'), 401);
        }
        if (!$this->groupManager->isAdmin($user->getUID()) 
            && !$this->groupManager->isInGroup($user->getUID(), 'vorstand')
            && !$this->groupManager->isInGroup($user->getUID(), 'verwaltung')) {
            throw new Exception($this->l->t('Insufficient permissions'), 403);
        }
    }

    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function index(): JSONResponse {
        try {
            $this->checkPermissions();
            $this->logger->info(\sprintf('[%s] Listing members', $this->appName));
            return new JSONResponse($this->service->listMembers());
        } catch (\Throwable $e) {
            $this->logger->error(\sprintf('[%s] Error listing members: %s', $this->appName, $e->getMessage()));
            return new JSONResponse(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 500);
        }
    }

    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function show(int $id): DataResponse {
        try {
            $this->checkPermissions();
            return new DataResponse($this->service->getMember($id));
        } catch (\Throwable $e) {
            $this->logger->warning(\sprintf('[%s] Member not found: %d', $this->appName, $id));
            return new DataResponse(['error' => $this->l->t('Member not found')], 404);
        }
    }

    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function create(): DataResponse {
        try {
            $this->checkPermissions();
            $data = $this->request->getParams();
            $result = $this->service->createMember($data);
            $this->logger->info(\sprintf('[%s] Created member: %s', $this->appName, $data['email'] ?? 'unknown'));
            return new DataResponse($result);
        } catch (Exception $e) {
            $this->logger->error(\sprintf('[%s] Error creating member: %s', $this->appName, $e->getMessage()));
            return new DataResponse(['error' => $e->getMessage()], 400);
        }
    }

    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function update(int $id): DataResponse {
        try {
            $this->checkPermissions();
            $data = $this->request->getParams();
            $result = $this->service->updateMember($id, $data);
            $this->logger->info(\sprintf('[%s] Updated member: %d', $this->appName, $id));
            return new DataResponse($result);
        } catch (Exception $e) {
            $this->logger->error(\sprintf('[%s] Error updating member: %s', $this->appName, $e->getMessage()));
            return new DataResponse(['error' => $e->getMessage()], 400);
        }
    }

    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function destroy(int $id): DataResponse {
        try {
            $this->checkPermissions();
            $this->service->deleteMember($id);
            $this->logger->info(\sprintf('[%s] Deleted member: %d', $this->appName, $id));
            return new DataResponse(['status' => 'success']);
        } catch (Exception $e) {
            $this->logger->error(\sprintf('[%s] Error deleting member: %s', $this->appName, $e->getMessage()));
            return new DataResponse(['error' => $e->getMessage()], 400);
        }
    }
}
