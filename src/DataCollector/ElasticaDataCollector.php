<?php

declare(strict_types=1);

namespace EMS\CommonBundle\DataCollector;

use EMS\CommonBundle\Elasticsearch\ElasticaLogger;
use EMS\CommonBundle\Service\ElasticaService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

class ElasticaDataCollector extends DataCollector
{
    private ElasticaLogger $logger;
    private ElasticaService $elasticaService;

    public function __construct(ElasticaLogger $logger, ElasticaService $elasticaService)
    {
        $this->logger = $logger;
        $this->elasticaService = $elasticaService;
    }

    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
        $this->data['nb_queries'] = $this->logger->getNbQueries();
        $this->data['queries'] = $this->logger->getQueries();
        $this->data['version'] = $this->elasticaService->getVersion();
        $this->data['health'] = $this->elasticaService->getHealthStatus();
    }

    public function getHealth(): string
    {
        return $this->data['health'];
    }

    public function getVersion(): string
    {
        return $this->data['version'];
    }

    /**
     * @return mixed
     */
    public function getQueryCount()
    {
        return $this->data['nb_queries'];
    }

    /**
     * @return mixed
     */
    public function getQueries()
    {
        return $this->data['queries'];
    }

    public function getTime(): int
    {
        $time = 0;
        foreach ($this->data['queries'] as $query) {
            $time += $query['engineMS'];
        }

        return $time;
    }

    public function getExecutionTime(): float
    {
        $time = 0;
        foreach ($this->data['queries'] as $query) {
            $time += $query['executionMS'];
        }

        return $time;
    }

    public function getName(): string
    {
        return 'elastica';
    }

    public function reset(): void
    {
        $this->logger->reset();
        $this->data = [];
    }
}
