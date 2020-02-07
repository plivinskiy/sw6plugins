<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Gateway\Reader;

use SwagMigrationAssistant\Exception\ReaderNotFoundException;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

class ReaderRegistry implements ReaderRegistryInterface
{
    /**
     * @var ReaderInterface[]
     */
    private $readers;

    public function __construct(iterable $readers)
    {
        $this->readers = $readers;
    }

    /**
     * @throws ReaderNotFoundException
     */
    public function getReader(MigrationContextInterface $migrationContext): ReaderInterface
    {
        foreach ($this->readers as $reader) {
            if ($reader->supports($migrationContext)) {
                return $reader;
            }
        }

        throw new ReaderNotFoundException($migrationContext->getDataSet()::getEntity());
    }

    /**
     * @return ReaderInterface[]
     */
    public function getReaderForTotal(MigrationContextInterface $migrationContext): array
    {
        $readers = [];
        foreach ($this->readers as $reader) {
            if ($reader->supportsTotal($migrationContext)) {
                $readers[] = $reader;
            }
        }

        return $readers;
    }
}
