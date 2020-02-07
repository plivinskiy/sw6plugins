<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging\Log;

class FieldReassignedRunLog extends BaseRunLogEntry
{
    /**
     * @var string
     */
    private $emptyField;

    /**
     * @var string
     */
    private $replacementField;

    public function __construct(string $runId, string $entity, string $sourceId, string $emptyField, string $replacementField)
    {
        parent::__construct($runId, $entity, $sourceId);
        $this->emptyField = $emptyField;
        $this->replacementField = $replacementField;
    }

    public function getLevel(): string
    {
        return self::LOG_LEVEL_INFO;
    }

    public function getCode(): string
    {
        return sprintf('SWAG_MIGRATION_%s_ENTITY_FIELD_REASSIGNED', mb_strtoupper($this->getEntity()));
    }

    public function getTitle(): string
    {
        return sprintf('The %s entity has a field that was reassigned', $this->getEntity());
    }

    public function getParameters(): array
    {
        return [
            'entity' => $this->getEntity(),
            'sourceId' => $this->getSourceId(),
            'emptyField' => $this->emptyField,
            'replacementField' => $this->replacementField,
        ];
    }

    public function getDescription(): string
    {
        $args = $this->getParameters();

        return sprintf(
            'The %s entity with the source id "%s" got the field %s replaced with %s.',
            $args['entity'],
            $args['sourceId'],
            $args['emptyField'],
            $args['replacementField']
        );
    }

    public function getTitleSnippet(): string
    {
        return sprintf('%s.%s.title', $this->getSnippetRoot(), 'SWAG_MIGRATION_ENTITY_FIELD_REASSIGNED');
    }

    public function getDescriptionSnippet(): string
    {
        return sprintf('%s.%s.description', $this->getSnippetRoot(), 'SWAG_MIGRATION_ENTITY_FIELD_REASSIGNED');
    }
}
