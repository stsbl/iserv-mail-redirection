<?php

declare(strict_types=1);

namespace Stsbl\MailAliasBundle\Tests\Unit\Service;

use IServ\Library\IdmApiClient\IdmClientInterface;
use PHPUnit\Framework\TestCase;
use Stsbl\MailAliasBundle\Service\IdmRecipientLookup;

final class IdmRecipientLookupTest extends TestCase
{
    public function testSearchesIdmWithoutGroupRestrictions(): void
    {
        $client = $this->createMock(IdmClientInterface::class);
        $client->expects($this->once())->method('performRequest')->with(
            'GET', 'iserv/idm/api/v1/lookup/users?query=max&includePrivilege=464e390f-5cea-4835-b40c-c3d3303ae234&_attributes=hexUuid%2Cuser%2Cfirstname%2Clastname%2CauxInfo',
        )->willReturn(['exact' => []]);

        self::assertSame(['exact' => []], (new IdmRecipientLookup($client))->users('max'));
    }

    public function testSearchesGroupsWithTheAutocompleteIncludeFlag(): void
    {
        $client = $this->createMock(IdmClientInterface::class);
        $client->expects($this->once())->method('performRequest')->with(
            'GET', 'iserv/idm/api/v1/lookup/groups?query=teachers&includeFlag=62301b70-1b9b-43da-b56d-1d717d171e16&_attributes=hexUuid%2Cgroup%2Cname%2Cdeleted',
        )->willReturn(['exact' => []]);

        self::assertSame(['exact' => []], (new IdmRecipientLookup($client))->groups('teachers'));
    }
}
