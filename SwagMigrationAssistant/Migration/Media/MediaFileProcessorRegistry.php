<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Media;

use SwagMigrationAssistant\Exception\ProcessorNotFoundException;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

class MediaFileProcessorRegistry implements MediaFileProcessorRegistryInterface
{
    /**
     * @var MediaFileProcessorInterface[]
     */
    private $processors;

    public function __construct(iterable $processors)
    {
        $this->processors = $processors;
    }

    /**
     * @throws ProcessorNotFoundException
     */
    public function getProcessor(MigrationContextInterface $migrationContext): MediaFileProcessorInterface
    {
        foreach ($this->processors as $processor) {
            if ($processor->supports($migrationContext)) {
                return $processor;
            }
        }

        throw new ProcessorNotFoundException($migrationContext->getConnection()->getProfileName(), $migrationContext->getConnection()->getGatewayName());
    }
}
