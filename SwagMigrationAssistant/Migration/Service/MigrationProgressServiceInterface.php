<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Service;

use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\Request;

interface MigrationProgressServiceInterface
{
    public function getProgress(Request $request, Context $context): ProgressState;
}
