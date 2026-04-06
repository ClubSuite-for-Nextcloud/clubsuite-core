<?php

declare(strict_types=1);

namespace OCA\ClubSuiteCore\Tests\Unit\Service;

use OCA\ClubSuiteCore\Db\MemberMapper;
use OCA\ClubSuiteCore\Db\Member;
use OCA\ClubSuiteCore\Service\MemberService;
use OCP\AppFramework\Db\DoesNotExistException;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class MemberServiceTest extends TestCase {
    private MemberService $service;
    private MemberMapper|MockObject $mapper;
    private LoggerInterface|MockObject $logger;

    protected function setUp(): void {
        parent::setUp();
        $this->mapper = $this->createMock(MemberMapper::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new MemberService($this->mapper, $this->logger);
    }

    public function testListMembers(): void {
        $this->mapper->expects($this->once())
            ->method('findAll')
            ->willReturn([]);

        $result = $this->service->listMembers();
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetMember(): void {
        $member = new Member();
        $member->setId(1);
        $member->setFirstname('Max');
        $member->setLastname('Mustermann');

        $this->mapper->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($member);

        $result = $this->service->getMember(1);
        $this->assertEquals('Max', $result->getFirstname());
        $this->assertEquals('Mustermann', $result->getLastname());
    }

    public function testCreateMemberValidData(): void {
        $this->mapper->expects($this->once())
            ->method('insert')
            ->willReturnCallback(function ($member) {
                $member->setId(1);
                return $member;
            });

        $data = [
            'firstname' => 'Max',
            'lastname' => 'Mustermann',
            'email' => 'max@example.com',
            'status' => 'active'
        ];

        $result = $this->service->createMember($data);
        $this->assertEquals('Max', $result->getFirstname());
        $this->assertEquals('Mustermann', $result->getLastname());
    }

    public function testCreateMemberMissingFirstname(): void {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Firstname and lastname are required');

        $data = [
            'lastname' => 'Mustermann'
        ];

        $this->service->createMember($data);
    }

    public function testCreateMemberMissingLastname(): void {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Firstname and lastname are required');

        $data = [
            'firstname' => 'Max'
        ];

        $this->service->createMember($data);
    }

    public function testCreateMemberInvalidStatus(): void {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid status');

        $data = [
            'firstname' => 'Max',
            'lastname' => 'Mustermann',
            'status' => 'invalid_status'
        ];

        $this->service->createMember($data);
    }

    public function testCreateMemberValidGermanStatus(): void {
        $this->mapper->expects($this->once())
            ->method('insert')
            ->willReturnCallback(function ($member) {
                $member->setId(1);
                return $member;
            });

        $data = [
            'firstname' => 'Max',
            'lastname' => 'Mustermann',
            'status' => 'aktiv'
        ];

        $result = $this->service->createMember($data);
        $this->assertEquals('aktiv', $result->getStatus());
    }

    public function testCreateMemberInvalidIBAN(): void {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid IBAN format');

        $data = [
            'firstname' => 'Max',
            'lastname' => 'Mustermann',
            'iban' => 'DE' // Too short
        ];

        $this->service->createMember($data);
    }

    public function testCreateMemberValidIBAN(): void {
        $this->mapper->expects($this->once())
            ->method('insert')
            ->willReturnCallback(function ($member) {
                $member->setId(1);
                return $member;
            });

        $data = [
            'firstname' => 'Max',
            'lastname' => 'Mustermann',
            'iban' => 'DE89370400440532013000' // Valid German IBAN
        ];

        $result = $this->service->createMember($data);
        $this->assertEquals('DE89370400440532013000', $result->getIban());
    }

    public function testUpdateMember(): void {
        $existingMember = new Member();
        $existingMember->setId(1);
        $existingMember->setFirstname('Max');
        $existingMember->setLastname('Mustermann');

        $this->mapper->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($existingMember);

        $this->mapper->expects($this->once())
            ->method('update')
            ->willReturnCallback(function ($member) {
                return $member;
            });

        $data = ['firstname' => 'Maxi'};
        $result = $this->service->updateMember(1, $data);

        $this->assertEquals('Maxi', $result->getFirstname());
    }

    public function testDeleteMember(): void {
        $member = new Member();
        $member->setId(1);

        $this->mapper->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($member);

        $this->mapper->expects($this->once())
            ->method('delete')
            ->with($member);

        $this->service->deleteMember(1);
    }
}