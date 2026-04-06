<?php
/**
 * ClubSuite API Authentication Service
 * Provides token-based API authentication for ClubSuite apps
 */

declare(strict_types=1);

namespace OCA\ClubSuiteCore\Service;

use DateTimeImmutable;
use OCP\IConfig;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\AppFramework\Db\DoesNotExistException;
use Psr\Log\LoggerInterface;

class ApiAuthService {
    private const TOKEN_LENGTH = 32;
    private const TOKEN_PREFIX = 'cs_';
    private const TOKEN_EXPIRY_DAYS = 90;

    private IConfig $config;
    private IUserManager $userManager;
    private IUserSession $userSession;
    private LoggerInterface $logger;
    private IL10N $l;
    private ?IGroupManager $groupManager = null;

    public function __construct(
        IConfig $config,
        IUserManager $userManager,
        IUserSession $userSession,
        LoggerInterface $logger,
        IL10N $l,
        ?IGroupManager $groupManager = null
    ) {
        $this->config = $config;
        $this->userManager = $userManager;
        $this->userSession = $userSession;
        $this->logger = $logger;
        $this->l = $l;
        $this->groupManager = $groupManager;
    }

    /**
     * Generate a new API token for a user
     */
    public function generateToken(?string $userId = null): array {
        $user = $userId 
            ? $this->userManager->get($userId) 
            : $this->userSession->getUser();

        if (!$user) {
            throw new \Exception($this->l->t('User not found'), 404);
        }

        $token = $this->generateSecureToken();
        $expires = (new DateTimeImmutable())->modify('+' . self::TOKEN_EXPIRY_DAYS . ' days');

        $this->storeToken($user->getUID(), $token, $expires);

        $this->logger->info('API token generated', [
            'user' => $user->getUID(),
            'app' => 'clubsuite-core'
        ]);

        return [
            'token' => self::TOKEN_PREFIX . $token,
            'expires' => $expires->format(DateTimeImmutable::ATOM),
            'user' => $user->getUID()
        ];
    }

    /**
     * Validate an API token
     */
    public function validateToken(string $token): ?string {
        if (!str_starts_with($token, self::TOKEN_PREFIX)) {
            return null;
        }

        $rawToken = substr($token, strlen(self::TOKEN_PREFIX));
        $userId = $this->findTokenUser($rawToken);

        if ($userId === null) {
            return null;
        }

        if (!$this->isTokenValid($userId, $rawToken)) {
            $this->logger->warning('Expired token used', [
                'user' => $userId,
                'app' => 'clubsuite-core'
            ]);
            return null;
        }

        return $userId;
    }

    /**
     * Revoke an API token
     */
    public function revokeToken(string $token): bool {
        if (!str_starts_with($token, self::TOKEN_PREFIX)) {
            return false;
        }

        $rawToken = substr($token, strlen(self::TOKEN_PREFIX));
        $userId = $this->findTokenUser($rawToken);

        if ($userId === null) {
            return false;
        }

        $this->removeToken($userId, $rawToken);

        $this->logger->info('API token revoked', [
            'user' => $userId,
            'app' => 'clubsuite-core'
        ]);

        return true;
    }

    /**
     * List active tokens for a user
     */
    public function listTokens(string $userId): array {
        $tokensJson = $this->config->getUserValue($userId, 'clubsuite-core', 'api_tokens', '[]');
        $tokens = json_decode($tokensJson, true) ?? [];

        $activeTokens = [];
        foreach ($tokens as $tokenData) {
            if (isset($tokenData['expires']) && new DateTimeImmutable($tokenData['expires']) > new DateTimeImmutable()) {
                $activeTokens[] = [
                    'created' => $tokenData['created'] ?? null,
                    'expires' => $tokenData['expires']
                ];
            }
        }

        return $activeTokens;
    }

    /**
     * Check if current user has required permission
     */
    public function checkPermission(string $requiredGroup = null): bool {
        $user = $this->userSession->getUser();
        if (!$user) {
            return false;
        }

        if ($requiredGroup === null || $requiredGroup === 'user') {
            return true;
        }

        if ($this->groupManager === null) {
            return false;
        }

        return $this->groupManager->isAdmin($user->getUID()) 
            || $this->groupManager->isInGroup($user->getUID(), $requiredGroup);
    }

    private function generateSecureToken(): string {
        return bin2hex(random_bytes(self::TOKEN_LENGTH / 2));
    }

    private function storeToken(string $userId, string $token, DateTimeImmutable $expires): void {
        $tokensJson = $this->config->getUserValue($userId, 'clubsuite-core', 'api_tokens', '[]');
        $tokens = json_decode($tokensJson, true) ?? [];

        $tokens[] = [
            'token' => password_hash($token, PASSWORD_DEFAULT),
            'created' => (new DateTimeImmutable())->format(DateTimeImmutable::ATOM),
            'expires' => $expires->format(DateTimeImmutable::ATOM)
        ];

        $this->config->setUserValue($userId, 'clubsuite-core', 'api_tokens', json_encode($tokens));
    }

    private function findTokenUser(string $rawToken): ?string {
        $users = $this->userManager->search('');

        foreach ($users as $user) {
            $userId = $user->getUID();
            $tokensJson = $this->config->getUserValue($userId, 'clubsuite-core', 'api_tokens', '[]');
            $tokens = json_decode($tokensJson, true) ?? [];

            foreach ($tokens as $tokenData) {
                if (isset($tokenData['token']) && password_verify($rawToken, $tokenData['token'])) {
                    return $userId;
                }
            }
        }

        return null;
    }

    private function isTokenValid(string $userId, string $rawToken): bool {
        $tokensJson = $this->config->getUserValue($userId, 'clubsuite-core', 'api_tokens', '[]');
        $tokens = json_decode($tokensJson, true) ?? [];

        foreach ($tokens as $tokenData) {
            if (isset($tokenData['token']) && password_verify($rawToken, $tokenData['token'])) {
                if (isset($tokenData['expires']) && new DateTimeImmutable($tokenData['expires']) > new DateTimeImmutable()) {
                    return true;
                }
            }
        }

        return false;
    }

    private function removeToken(string $userId, string $rawToken): void {
        $tokensJson = $this->config->getUserValue($userId, 'clubsuite-core', 'api_tokens', '[]');
        $tokens = json_decode($tokensJson, true) ?? [];

        $filtered = [];
        foreach ($tokens as $tokenData) {
            if (isset($tokenData['token']) && !password_verify($rawToken, $tokenData['token'])) {
                $filtered[] = $tokenData;
            }
        }

        $this->config->setUserValue($userId, 'clubsuite-core', 'api_tokens', json_encode($filtered));
    }
}