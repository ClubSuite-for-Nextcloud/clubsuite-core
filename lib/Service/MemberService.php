<?php

declare(strict_types=1);

namespace OCA\ClubSuiteCore\Service;

use DateTimeImmutable;
use Exception;
use OCA\ClubSuiteCore\Db\Member;
use OCA\ClubSuiteCore\Db\MemberMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use Psr\Log\LoggerInterface;

class MemberService {

    public function __construct(
        private MemberMapper $mapper,
        private LoggerInterface $logger
    ) {}

    public function listMembers(): array {
        return $this->mapper->findAll();
    }

    /**
     * @throws DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function getMember(int $id): Member {
        return $this->mapper->findById($id);
    }

    /**
     * @throws Exception
     */
    public function createMember(array $data): Member {
        $this->validate($data);

        $this->logger->info('Creating new member', ['app' => 'clubsuite-core']);

        $member = new Member();
        $this->hydrate($member, $data);
        
        $now = new DateTimeImmutable();
        $member->setCreatedAt($now);
        $member->setUpdatedAt($now);

        return $this->mapper->insert($member);
    }

    /**
     * @throws DoesNotExistException
     * @throws Exception
     */
    public function updateMember(int $id, array $data): Member {
        $member = $this->mapper->findById($id);
        
        $this->hydrate($member, $data);
        $member->setUpdatedAt(new DateTimeImmutable());

        return $this->mapper->update($member);
    }

    /**
     * @throws DoesNotExistException
     */
    public function deleteMember(int $id): void {
        $member = $this->mapper->findById($id);
        $this->mapper->delete($member);
    }

    private function hydrate(Member $member, array $data): void {
        if (isset($data['firstname'])) $member->setFirstname($data['firstname']);
        if (isset($data['lastname'])) $member->setLastname($data['lastname']);
        if (isset($data['email'])) $member->setEmail($data['email']);
        if (isset($data['phone'])) $member->setPhone($data['phone']);
        if (isset($data['street'])) $member->setStreet($data['street']);
        if (isset($data['zip'])) $member->setZip($data['zip']);
        if (isset($data['city'])) $member->setCity($data['city']);
        if (isset($data['address'])) $member->setAddress($data['address']);
        if (isset($data['iban'])) $member->setIban($data['iban']);
        if (isset($data['status'])) $member->setStatus($data['status']);
        if (isset($data['userId'])) $member->setUserId($data['userId']);
        if (isset($data['mitgliedsnummer'])) $member->setMitgliedsnummer($data['mitgliedsnummer']);
        if (isset($data['eintrittsdatum'])) {
            $date = $data['eintrittsdatum'] ? new DateTimeImmutable($data['eintrittsdatum']) : null;
            $member->setEintrittsdatum($date);
        }
        if (isset($data['joinedAt'])) {
            $date = $data['joinedAt'] ? new DateTimeImmutable($data['joinedAt']) : null;
            $member->setJoinedAt($date);
        }
    }

    /**
     * @throws Exception
     */
    private function validate(array $data): void {
        if (empty($data['firstname']) || empty($data['lastname'])) {
            throw new Exception('Firstname and lastname are required');
        }
        // Accept both German and English status values
        $validStatuses = ['active', 'passive', 'honorary', 'aktiv', 'passiv', 'ehemalig', 'ehrenmitglied'];
        if (isset($data['status']) && !in_array($data['status'], $validStatuses)) {
            throw new Exception('Invalid status');
        }
        // Basic IBAN check (length + basic format)
        if (!empty($data['iban']) && !$this->validateIBAN($data['iban'])) {
            throw new Exception('Invalid IBAN format');
        }
    }

    private function validateIBAN(string $iban): bool {
        $iban = strtoupper(str_replace(' ', '', $iban));
        if (strlen($iban) < 15 || strlen($iban) > 34) {
            return false;
        }
        $pattern = '/^[A-Z]{2}[0-9]{2}[A-Z0-9]+$/';
        if (!preg_match($pattern, $iban)) {
            return false;
        }
        $checksum = substr($iban, 0, 4) . substr($iban, 4);
        $checksum = str_replace(
            range('A', 'Z'),
            array_map(strval, range(10, 35)),
            $checksum
        );
        return (bool)preg_match('/^[0-9]+$/', $checksum) && bcmod($checksum, '97') === '1';
    }
}
