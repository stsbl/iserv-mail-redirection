<?php

declare(strict_types=1);

namespace Stsbl\IServ\MailRedirection\Service;

use IServ\Library\IdmApiClient\Hydrator\ArrayHydrator;
use IServ\Library\IdmApiClient\Hydrator\RawHydrator;
use IServ\Library\IdmApiClient\IdmClientInterface;

final class IdmRecipientLookup
{
    private const INCLUDE_PRIVILEGE = '464e390f-5cea-4835-b40c-c3d3303ae234';

    public function __construct(private readonly IdmClientInterface $client)
    {
    }

    /** @return array<string, mixed> */
    public function users(string $query): array
    {
        $url = 'iserv/idm/api/v1/lookup/users?' . http_build_query([
            'query' => $query,
            'includePrivilege' => self::INCLUDE_PRIVILEGE,
            '_attributes' => 'hexUuid,user,firstname,lastname,auxInfo',
        ], encoding_type: PHP_QUERY_RFC3986);

        return $this->client->performRequest('GET', $url, new ArrayHydrator(new RawHydrator()));
    }

    /** @return array<string, mixed> */
    public function groups(string $query): array
    {
        $url = 'iserv/idm/api/v1/lookup/groups?' . http_build_query([
            'query' => $query,
            'includeFlag' => '62301b70-1b9b-43da-b56d-1d717d171e16',
            '_attributes' => 'hexUuid,group,name,deleted',
        ], encoding_type: PHP_QUERY_RFC3986);

        return $this->client->performRequest('GET', $url, new ArrayHydrator(new RawHydrator()));
    }
}
