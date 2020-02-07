<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware54\Converter;

use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\Converter\CurrencyConverter;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CurrencyDataSet;
use SwagMigrationAssistant\Profile\Shopware54\Shopware54Profile;

class Shopware54CurrencyConverter extends CurrencyConverter
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile()->getName() === Shopware54Profile::PROFILE_NAME
            && $migrationContext->getDataSet()::getEntity() === CurrencyDataSet::getEntity();
    }
}