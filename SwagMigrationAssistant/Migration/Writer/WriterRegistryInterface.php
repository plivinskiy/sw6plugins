<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Writer;

use SwagMigrationAssistant\Exception\WriterNotFoundException;

interface WriterRegistryInterface
{
    /**
     * Returns the writer which supports the given entity
     *
     * @throws WriterNotFoundException
     */
    public function getWriter(string $entityName): WriterInterface;
}
