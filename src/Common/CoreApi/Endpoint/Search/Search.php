<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Common\CoreApi\Endpoint\Search;

use EMS\CommonBundle\Common\CoreApi\Client;
use EMS\CommonBundle\Common\CoreApi\Search\Scroll;
use EMS\CommonBundle\Contracts\CoreApi\Endpoint\Search\SearchInterface;
use EMS\CommonBundle\Elasticsearch\Document\Document;
use EMS\CommonBundle\Elasticsearch\Document\DocumentInterface;
use EMS\CommonBundle\Elasticsearch\Response\Response;
use EMS\CommonBundle\Elasticsearch\Response\ResponseInterface;
use EMS\CommonBundle\Search\Search as SearchObject;

class Search implements SearchInterface
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function search(SearchObject $search): ResponseInterface
    {
        return Response::fromArray($this->client->post('/api/search/search', ['search' => $search->serialize()])->getData());
    }

    public function count(SearchObject $search): int
    {
        $count = $this->client->post('/api/search/count', ['search' => $search->serialize()])->getData()['count'] ?? null;
        if (!\is_int($count)) {
            throw new \RuntimeException('Unexpected: count must be a string');
        }

        return $count;
    }

    public function scroll(SearchObject $search, int $scrollSize = 10, string $expireTime = '3m'): Scroll
    {
        $search->setSize($scrollSize);
        $search->setFrom(0);

        return new Scroll($this->client, $search, $expireTime);
    }

    public function version(): string
    {
        $version = $this->client->get('/api/search/version')->getData()['version'] ?? null;
        if (!\is_string($version)) {
            throw new \RuntimeException('Unexpected: search must be a string');
        }

        return $version;
    }

    public function healthStatus(): string
    {
        $status = $this->client->get('/api/search/health-status')->getData()['status'] ?? null;
        if (!\is_string($status)) {
            throw new \RuntimeException('Unexpected: status must be a string');
        }

        return $status;
    }

    public function refresh(?string $index = null): bool
    {
        $success = $this->client->post('/api/search/refresh', [
            'index' => $index,
        ])->getData()['success'] ?? null;
        if (!\is_bool($success)) {
            throw new \RuntimeException('Unexpected: search must be a boolean');
        }

        return $success;
    }

    /**
     * @return string[]
     */
    public function getIndicesFromAlias(string $alias): array
    {
        $indices = $this->client->post('/api/search/indices-from-alias', [
            'alias' => $alias,
        ])->getData()['indices'] ?? null;
        if (!\is_array($indices)) {
            throw new \RuntimeException('Unexpected: search must be an array');
        }

        return $indices;
    }

    /**
     * @return string[]
     */
    public function getAliasesFromIndex(string $index): array
    {
        $aliases = $this->client->post('/api/search/aliases-from-index', [
            'index' => $index,
        ])->getData()['aliases'] ?? null;
        if (!\is_array($aliases)) {
            throw new \RuntimeException('Unexpected: aliases must be an array');
        }

        return $aliases;
    }

    /**
     * @param string[] $sourceIncludes
     * @param string[] $sourcesExcludes
     */
    public function getDocument(string $index, ?string $contentType, string $id, array $sourceIncludes = [], array $sourcesExcludes = []): DocumentInterface
    {
        return Document::fromArray($this->client->post('/api/search/document', [
            'index' => $index,
            'content-type' => $contentType,
            'ouuid' => $id,
            'source-includes' => $sourceIncludes,
            'sources-excludes' => $sourcesExcludes,
        ])->getData());
    }
}
