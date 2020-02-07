<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\AssociationRequiredMissingLog;
use SwagMigrationAssistant\Migration\Logging\Log\EmptyNecessaryFieldRunLog;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

abstract class ProductReviewConverter extends ShopwareConverter
{
    protected $requiredDataFieldKeys = [
        '_locale',
        'articleID',
        'email',
    ];

    /**
     * @var string
     */
    private $connectionId;

    /**
     * @var string
     */
    private $mainLocale;

    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct
    {
        $fields = $this->checkForEmptyRequiredDataFields($data, $this->requiredDataFieldKeys);

        if (!empty($fields)) {
            $this->loggingService->addLogEntry(new EmptyNecessaryFieldRunLog(
                $migrationContext->getRunUuid(),
                DefaultEntities::PRODUCT_REVIEW,
                $data['id'],
                implode(',', $fields)
            ));

            return new ConvertStruct(null, $data);
        }
        $this->generateChecksum($data);
        $originalData = $data;
        $this->connectionId = $migrationContext->getConnection()->getId();
        $this->mainLocale = $data['_locale'];
        unset($data['_locale']);

        $converted = [];
        $this->mainMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PRODUCT_REVIEW,
            $data['id'],
            $context,
            $this->checksum
        );
        $converted['id'] = $this->mainMapping['entityUuid'];
        unset($data['id']);

        $mapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::PRODUCT_MAIN,
            $data['articleID'],
            $context
        );

        if ($mapping === null) {
            $this->loggingService->addLogEntry(
                new AssociationRequiredMissingLog(
                    $migrationContext->getRunUuid(),
                    DefaultEntities::PRODUCT,
                    $data['articleID'],
                    DefaultEntities::PRODUCT_REVIEW
                )
            );

            return new ConvertStruct(null, $originalData);
        }
        $converted['productId'] = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];
        unset($data['articleID']);

        $mapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::CUSTOMER,
            $data['email'],
            $context
        );

        if ($mapping === null) {
            $this->loggingService->addLogEntry(
                new AssociationRequiredMissingLog(
                    $migrationContext->getRunUuid(),
                    DefaultEntities::CUSTOMER,
                    $data['email'],
                    DefaultEntities::PRODUCT_REVIEW
                )
            );

            return new ConvertStruct(null, $originalData);
        }
        $converted['customerId'] = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];
        unset($data['email']);

        $shopId = $data['shop_id'] === null ? $data['mainShopId'] : $data['shop_id'];
        $mapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::SALES_CHANNEL,
            $shopId,
            $context
        );

        if ($mapping === null) {
            $this->loggingService->addLogEntry(
                new AssociationRequiredMissingLog(
                    $migrationContext->getRunUuid(),
                    DefaultEntities::SALES_CHANNEL,
                    $shopId,
                    DefaultEntities::PRODUCT_REVIEW
                )
            );

            return new ConvertStruct(null, $originalData);
        }
        $converted['salesChannelId'] = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];
        unset($data['shop_id'], $data['mainShopId']);

        $converted['languageId'] = $this->mappingService->getLanguageUuid(
            $this->connectionId,
            $this->mainLocale,
            $context
        );

        if ($converted['languageId'] === null) {
            $this->loggingService->addLogEntry(
                new AssociationRequiredMissingLog(
                    $migrationContext->getRunUuid(),
                    DefaultEntities::LANGUAGE,
                    $this->mainLocale,
                    DefaultEntities::PRODUCT_REVIEW
                )
            );

            return new ConvertStruct(null, $originalData);
        }

        $this->convertValue($converted, 'title', $data, 'headline');
        if (empty($converted['title'])) {
            $converted['title'] = mb_substr($data['comment'], 0, 30) . '...';
        }
        $this->convertValue($converted, 'content', $data, 'comment');
        $this->convertValue($converted, 'points', $data, 'points', self::TYPE_FLOAT);
        $this->convertValue($converted, 'status', $data, 'active', self::TYPE_BOOLEAN);
        $this->convertValue($converted, 'comment', $data, 'answer');

        $this->updateMainMapping($migrationContext, $context);

        return new ConvertStruct($converted, $data, $this->mainMapping['id']);
    }
}
