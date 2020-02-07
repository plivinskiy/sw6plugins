<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Command;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\MessageQueue\IndexerMessageSender;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriter;
use Shopware\Core\Framework\Store\Services\StoreService;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Storefront\Theme\ThemeService;
use SwagMigrationAssistant\Command\MigrateDataCommand;
use SwagMigrationAssistant\Controller\MigrationController;
use SwagMigrationAssistant\Migration\Data\SwagMigrationDataDefinition;
use SwagMigrationAssistant\Migration\DataSelection\DataSelectionRegistry;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSetRegistry;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSetRegistryInterface;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Gateway\GatewayRegistry;
use SwagMigrationAssistant\Migration\Gateway\GatewayRegistryInterface;
use SwagMigrationAssistant\Migration\Gateway\Reader\ReaderRegistry;
use SwagMigrationAssistant\Migration\Logging\LoggingService;
use SwagMigrationAssistant\Migration\Mapping\MappingService;
use SwagMigrationAssistant\Migration\Media\MediaFileService;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\MigrationContextFactory;
use SwagMigrationAssistant\Migration\MigrationContextFactoryInterface;
use SwagMigrationAssistant\Migration\Profile\ProfileRegistry;
use SwagMigrationAssistant\Migration\Profile\ProfileRegistryInterface;
use SwagMigrationAssistant\Migration\Run\RunService;
use SwagMigrationAssistant\Migration\Service\MediaFileProcessorService;
use SwagMigrationAssistant\Migration\Service\MigrationDataWriter;
use SwagMigrationAssistant\Migration\Service\PremappingService;
use SwagMigrationAssistant\Migration\Service\SwagMigrationAccessTokenService;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\CustomerAndOrderDataSelection;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\ProductDataSelection;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\MigrationServicesTrait;
use SwagMigrationAssistant\Test\Mock\Migration\Media\DummyMediaFileService;
use SwagMigrationAssistant\Test\Mock\Repositories\GeneralSettingRepo;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class MigrateDataCommandTest extends TestCase
{
    use IntegrationTestBehaviour;
    use MigrationServicesTrait;

    /**
     * @var CommandTester
     */
    private $commandTester;

    /**
     * @var Command
     */
    private $command;

    /**
     * @var Application
     */
    private $application;

    /**
     * @var MigrationController
     */
    private $controller;

    /**
     * @var string
     */
    private $runUuid;

    /**
     * @var EntityRepositoryInterface
     */
    private $runRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $generalSettingRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $connectionRepo;

    /**
     * @var string
     */
    private $connectionId;

    /**
     * @var ProfileRegistryInterface
     */
    private $profileRegistry;

    /**
     * @var GatewayRegistryInterface
     */
    private $gatewayRegistry;

    /**
     * @var DataSetRegistryInterface
     */
    private $dataSetRegistry;

    /**
     * @var string
     */
    private $invalidRunUuid;

    /**
     * @var EntityRepositoryInterface
     */
    private $dataRepo;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var EntityRepositoryInterface
     */
    private $mediaFileRepo;

    /**
     * @var MigrationContextFactoryInterface
     */
    private $migrationContextFactory;

    protected function setUp(): void
    {
        $this->context = Context::createDefaultContext();
        $this->mediaFileRepo = $this->getContainer()->get('swag_migration_media_file.repository');
        $this->dataRepo = $this->getContainer()->get('swag_migration_data.repository');
        $this->connectionRepo = $this->getContainer()->get('swag_migration_connection.repository');
        $this->generalSettingRepo = $this->getContainer()->get('swag_migration_general_setting.repository');
        $this->runRepo = $this->getContainer()->get('swag_migration_run.repository');
        $this->profileRegistry = $this->getContainer()->get(ProfileRegistry::class);
        $this->gatewayRegistry = $this->getContainer()->get(GatewayRegistry::class);
        $this->dataSetRegistry = $this->getContainer()->get(DataSetRegistry::class);
        $this->migrationContextFactory = $this->getContainer()->get(MigrationContextFactory::class);
        $salesChannelRepo = $this->getContainer()->get('sales_channel.repository');
        $themeRepo = $this->getContainer()->get('theme.repository');
        $mappingService = $this->getContainer()->get(MappingService::class);
        $loggingRepo = $this->getContainer()->get('swag_migration_logging.repository');
        $languageRepo = $this->getContainer()->get('language.repository');

        $kernel = self::getKernel();
        $this->application = new Application($kernel);
        $this->context->scope(MigrationContext::SOURCE_CONTEXT, function (): void {
            $this->connectionId = Uuid::randomHex();
            $this->connectionRepo->create(
                [
                    [
                        'id' => $this->connectionId,
                        'name' => 'myConnection',
                        'credentialFields' => [
                            'endpoint' => 'testEndpoint',
                            'apiUser' => 'testUser',
                            'apiKey' => 'testKey',
                        ],
                        'profileName' => Shopware55Profile::PROFILE_NAME,
                        'gatewayName' => ShopwareLocalGateway::GATEWAY_NAME,
                    ],
                ],
                $this->context
            );
        });

        $generalSettingsRepo = $this->getContainer()->get('swag_migration_general_setting.repository');
        $this->context->scope(MigrationContext::SOURCE_CONTEXT, function () use ($generalSettingsRepo): void {
            $generalSettingsRepo->create(
                [
                    [
                        'id' => Uuid::randomHex(),
                        'selectedConnectionId' => $this->connectionId,
                    ],
                ],
                $this->context
            );
        });

        $languageUuid = $this->getLanguageUuid(
            $this->getContainer()->get('locale.repository'),
            $languageRepo,
            'de-DE',
            $this->context
        );

        $mappingService->getOrCreateMapping($this->connectionId, DefaultEntities::CURRENCY, 'EUR', Context::createDefaultContext(), null, [], Uuid::randomHex());
        $mappingService->getOrCreateMapping($this->connectionId, DefaultEntities::LANGUAGE, 'de-DE', Context::createDefaultContext(), null, [], $languageUuid);
        $mappingService->getOrCreateMapping($this->connectionId, DefaultEntities::LANGUAGE, 'en-GB', Context::createDefaultContext(), null, [], $languageUuid);
        $mappingService->getOrCreateMapping($this->connectionId, DefaultEntities::LANGUAGE, 'en-US', Context::createDefaultContext(), null, [], $languageUuid);
        $mappingService->getOrCreateMapping($this->connectionId, DefaultEntities::LANGUAGE, 'nl-NL', Context::createDefaultContext(), null, [], $languageUuid);
        $mappingService->getOrCreateMapping($this->connectionId, DefaultEntities::LANGUAGE, 'bn-IN', Context::createDefaultContext(), null, [], $languageUuid);

        $dataFetcher = $this->getMigrationDataFetcher(
            $this->getContainer()->get(EntityWriter::class),
            $mappingService,
            $this->getContainer()->get(MediaFileService::class),
            $this->getContainer()->get('swag_migration_logging.repository'),
            $this->getContainer()->get(SwagMigrationDataDefinition::class),
            $this->getContainer()->get(DataSetRegistry::class),
            $this->getContainer()->get('currency.repository'),
            $this->getContainer()->get(ReaderRegistry::class)
        );

        $this->application->add(new MigrateDataCommand(
            new GeneralSettingRepo($this->connectionId),
            $this->getContainer()->get('swag_migration_connection.repository'),
            $this->getContainer()->get('swag_migration_run.repository'),
            $this->getContainer()->get(DataSetRegistry::class),
            new RunService(
                $this->runRepo,
                $this->connectionRepo,
                $dataFetcher,
                new SwagMigrationAccessTokenService(
                    $this->runRepo
                ),
                new DataSelectionRegistry([
                    new ProductDataSelection(),
                    new CustomerAndOrderDataSelection(),
                ]),
                $this->dataRepo,
                $this->mediaFileRepo,
                $salesChannelRepo,
                $themeRepo,
                $this->getContainer()->get(IndexerMessageSender::class),
                $this->getContainer()->get(ThemeService::class),
                $mappingService,
                $this->getContainer()->get('cache.object'),
                $this->getContainer()->get(SwagMigrationDataDefinition::class),
                $this->getContainer()->get(Connection::class),
                new LoggingService($loggingRepo),
                $this->getContainer()->get(StoreService::class)
            ),
            $this->getContainer()->get(PremappingService::class),
            $dataFetcher,
            $this->getMigrationDataConverter(
                $this->getContainer()->get(EntityWriter::class),
                $mappingService,
                new DummyMediaFileService(),
                $loggingRepo,
                $this->getContainer()->get(SwagMigrationDataDefinition::class),
                $this->getContainer()->get('payment_method.repository'),
                $this->getContainer()->get('shipping_method.repository'),
                $this->getContainer()->get('country.repository'),
                $this->getContainer()->get('sales_channel.repository')
            ),
            $this->getContainer()->get(MigrationDataWriter::class),
            $this->getContainer()->get(MediaFileProcessorService::class),
            $this->getContainer()->get(MigrationContextFactory::class),
            'migration:migrate'
        ));
        $this->command = $this->application->find('migration:migrate');
        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecution(): void
    {
        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'dataSelections' => ['products'],
        ]);
        $result = $this->getContainer()->get('product.repository')->search(new Criteria(), $this->context)->count();
        static::assertSame(42, $result);
    }
}
