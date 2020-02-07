<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Local;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Currency\CurrencyEntity;
use SwagMigrationAssistant\Migration\EnvironmentInformation;
use SwagMigrationAssistant\Migration\Gateway\Reader\EnvironmentReaderInterface;
use SwagMigrationAssistant\Migration\Gateway\Reader\ReaderRegistry;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\RequestStatusStruct;
use SwagMigrationAssistant\Profile\Shopware\Exception\DatabaseConnectionException;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactoryInterface;
use SwagMigrationAssistant\Profile\Shopware\Gateway\ShopwareGatewayInterface;
use SwagMigrationAssistant\Profile\Shopware\Gateway\TableReaderInterface;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

class ShopwareLocalGateway implements ShopwareGatewayInterface
{
    public const GATEWAY_NAME = 'local';

    /**
     * @var ReaderRegistry
     */
    private $readerRegistry;

    /**
     * @var EnvironmentReaderInterface
     */
    private $localEnvironmentReader;

    /**
     * @var TableReaderInterface
     */
    private $localTableReader;

    /**
     * @var ConnectionFactoryInterface
     */
    private $connectionFactory;

    /**
     * @var EntityRepositoryInterface
     */
    private $currencyRepository;

    public function __construct(
        ReaderRegistry $readerRegistry,
        EnvironmentReaderInterface $localEnvironmentReader,
        TableReaderInterface $localTableReader,
        ConnectionFactoryInterface $connectionFactory,
        EntityRepositoryInterface $currencyRepository
    ) {
        $this->readerRegistry = $readerRegistry;
        $this->localEnvironmentReader = $localEnvironmentReader;
        $this->localTableReader = $localTableReader;
        $this->connectionFactory = $connectionFactory;
        $this->currencyRepository = $currencyRepository;
    }

    public function getName(): string
    {
        return self::GATEWAY_NAME;
    }

    public function getSnippetName(): string
    {
        return 'swag-migration.wizard.pages.connectionCreate.gateways.shopwareLocal';
    }

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface;
    }

    public function read(MigrationContextInterface $migrationContext): array
    {
        $reader = $this->readerRegistry->getReader($migrationContext);

        return $reader->read($migrationContext);
    }

    public function readEnvironmentInformation(MigrationContextInterface $migrationContext, Context $context): EnvironmentInformation
    {
        $connection = $this->connectionFactory->createDatabaseConnection($migrationContext);
        $profile = $migrationContext->getProfile();

        try {
            $connection->connect();
        } catch (\Exception $e) {
            $error = new DatabaseConnectionException();

            return new EnvironmentInformation(
                $profile->getSourceSystemName(),
                $profile->getVersion(),
                '-',
                [],
                [],
                new RequestStatusStruct($error->getErrorCode(), $error->getMessage())
            );
        }
        $connection->close();
        $environmentData = $this->localEnvironmentReader->read($migrationContext);

        /** @var CurrencyEntity $targetSystemCurrency */
        $targetSystemCurrency = $this->currencyRepository->search(new Criteria([Defaults::CURRENCY]), $context)->get(Defaults::CURRENCY);
        if (!isset($environmentData['defaultCurrency'])) {
            $environmentData['defaultCurrency'] = $targetSystemCurrency->getIsoCode();
        }

        $totals = $this->readTotals($migrationContext, $context);

        return new EnvironmentInformation(
            $profile->getSourceSystemName(),
            $profile->getVersion(),
            $environmentData['host'],
            $totals,
            $environmentData['additionalData'],
            new RequestStatusStruct(),
            false,
            [],
            $targetSystemCurrency->getIsoCode(),
            $environmentData['defaultCurrency']
        );
    }

    public function readTotals(MigrationContextInterface $migrationContext, Context $context): array
    {
        $readers = $this->readerRegistry->getReaderForTotal($migrationContext);

        $totals = [];
        foreach ($readers as $reader) {
            $total = $reader->readTotal($migrationContext);
            $totals[$total->getEntityName()] = $total;
        }

        return $totals;
    }

    public function readTable(MigrationContextInterface $migrationContext, string $tableName, array $filter = []): array
    {
        return $this->localTableReader->read($migrationContext, $tableName, $filter);
    }
}
