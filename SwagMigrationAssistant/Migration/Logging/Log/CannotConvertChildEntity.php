<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging\Log;

class CannotConvertChildEntity extends BaseRunLogEntry
{
    /**
     * @var string
     */
    private $parentEntity;

    /**
     * @var string
     */
    private $parentSourceId;

    public function __construct(string $runId, string $entity, string $parentEntity, string $parentSourceId)
    {
        parent::__construct($runId, $entity, null);
        $this->parentEntity = $parentEntity;
        $this->parentSourceId = $parentSourceId;
    }

    public function getLevel(): string
    {
        return self::LOG_LEVEL_WARNING;
    }

    public function getCode(): string
    {
        return sprintf('SWAG_MIGRATION_CANNOT_CONVERT_CHILD_%s_ENTITY', mb_strtoupper($this->getEntity()));
    }

    public function getTitle(): string
    {
        return sprintf('The %s child entity could not be converted', $this->getEntity());
    }

    public function getParameters(): array
    {
        return [
            'entity' => $this->getEntity(),
            'parentEntity' => $this->parentEntity,
            'parentSourceId' => $this->parentSourceId,
        ];
    }

    public function getDescription(): string
    {
        $args = $this->getParameters();

        return sprintf(
            'The %s child entity from the %s parent entity with the id "%s" could not be converted.',
            $args['entity'],
            $args['parentEntity'],
            $args['parentSourceId']
        );
    }

    public function getTitleSnippet(): string
    {
        return sprintf('%s.%s.title', $this->getSnippetRoot(), 'SWAG_MIGRATION_CANNOT_CONVERT_CHILD_ENTITY');
    }

    public function getDescriptionSnippet(): string
    {
        return sprintf('%s.%s.description', $this->getSnippetRoot(), 'SWAG_MIGRATION_CANNOT_CONVERT_CHILD_ENTITY');
    }
}
