<?php

declare(strict_types = 1);

namespace CodelyTv\Shared\Infrastructure\Elasticsearch;

use CodelyTv\Shared\Domain\Utils;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\Missing404Exception;

final class ElasticsearchClientFactory
{
    public function __invoke(
        string $host,
        string $indexPrefix,
        string $schemasFolder,
        bool $environment
    ): ElasticsearchClient {
        $client = ClientBuilder::create()->setHosts([$host])->build();

        $this->generateIndexIfNotExists($client, $indexPrefix, $schemasFolder, $environment);

        return new ElasticsearchClient($client, $indexPrefix);
    }

    private function generateIndexIfNotExists(
        Client $client,
        string $indexPrefix,
        string $schemasFolder,
        $environment
    ): void {
        $indexes = Utils::filesIn($schemasFolder, '.json');

        foreach ($indexes as $index) {
            $indexName = sprintf('%s_%s', $indexPrefix, $index);

            if ('prod' !== $environment && !$this->indexExists($client, $indexName)) {
                $indexBody = Utils::jsonDecode(file_get_contents("$schemasFolder/$index"));

                $client->indices()->create(['index' => $indexName, 'body' => $indexBody]);
            }
        }
    }

    private function indexExists(Client $client, string $indexName): bool
    {
        try {
            $client->indices()->getSettings(['index' => $indexName]);

            return true;
        } catch (Missing404Exception $unused) {
            return false;
        }
    }
}
