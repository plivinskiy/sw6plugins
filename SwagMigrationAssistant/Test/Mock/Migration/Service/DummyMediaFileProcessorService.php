<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Mock\Migration\Service;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Service\MediaFileProcessorService;

class DummyMediaFileProcessorService extends MediaFileProcessorService
{
    public function processMediaFiles(
        MigrationContextInterface $migrationContext,
        Context $context,
        int $fileChunkByteSize
    ): void {
    }
}
