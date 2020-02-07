<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\MigrationMagento\Migration\Writer;

use Swag\MigrationMagento\Profile\Magento\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Writer\AbstractWriter;

class NotAssociatedWriter extends AbstractWriter
{
    public function supports(): string
    {
        return DefaultEntities::NOT_ASSOCIATED_MEDIA;
    }
}
