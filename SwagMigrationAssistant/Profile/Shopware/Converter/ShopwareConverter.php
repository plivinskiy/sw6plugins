<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use SwagMigrationAssistant\Migration\Converter\Converter;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

abstract class ShopwareConverter extends Converter
{
    protected const TYPE_STRING = 'string';
    protected const TYPE_BOOLEAN = 'bool';
    protected const TYPE_INVERT_BOOLEAN = 'invert_bool';
    protected const TYPE_INTEGER = 'int';
    protected const TYPE_FLOAT = 'float';
    protected const TYPE_DATETIME = 'datetime';

    /**
     * @var MigrationContextInterface
     */
    protected $migrationContext;

    public function getSourceIdentifier(array $data): string
    {
        return $data['id'];
    }

    protected function convertValue(
        array &$newData,
        string $newKey,
        array &$sourceData,
        string $sourceKey,
        string $castType = self::TYPE_STRING
    ): void {
        if ($sourceData[$sourceKey] !== null && $sourceData[$sourceKey] !== '') {
            switch ($castType) {
                case self::TYPE_BOOLEAN:
                    $sourceValue = (bool) $sourceData[$sourceKey];
                    break;
                case self::TYPE_INVERT_BOOLEAN:
                    $sourceValue = !(bool) $sourceData[$sourceKey];
                    break;
                case self::TYPE_INTEGER:
                    $sourceValue = (int) $sourceData[$sourceKey];
                    break;
                case self::TYPE_FLOAT:
                    $sourceValue = (float) $sourceData[$sourceKey];
                    break;
                case self::TYPE_DATETIME:
                    $sourceValue = $sourceData[$sourceKey];
                    if (!$this->validDate($sourceValue)) {
                        return;
                    }
                    break;
                default:
                    $sourceValue = (string) $sourceData[$sourceKey];
            }
            $newData[$newKey] = $sourceValue;
        }
        unset($sourceData[$sourceKey]);
    }

    /**
     * @param string[] $requiredDataFieldKeys
     *
     * @return string[]
     */
    protected function checkForEmptyRequiredDataFields(array $rawData, array $requiredDataFieldKeys): array
    {
        $emptyFields = [];
        foreach ($requiredDataFieldKeys as $requiredDataFieldKey) {
            if (!isset($rawData[$requiredDataFieldKey]) || $rawData[$requiredDataFieldKey] === '') {
                $emptyFields[] = $requiredDataFieldKey;
            }
        }

        return $emptyFields;
    }

    /**
     * @param string[] $requiredDataFields
     *
     * @return string[]
     */
    protected function checkForEmptyRequiredConvertedFields(array $converted, array $requiredDataFields): array
    {
        $emptyFields = [];
        foreach ($requiredDataFields as $requiredDataFieldKey => $requiredDataFieldValue) {
            if (!isset($converted[$requiredDataFieldKey]) || $converted[$requiredDataFieldKey] === '') {
                $emptyFields[] = $requiredDataFieldValue;
            }
        }

        return $emptyFields;
    }

    protected function validDate(string $value): bool
    {
        try {
            new \DateTime($value);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function getAttributes(array $attributes, string $entityName, string $connectionName, array $blacklist = []): array
    {
        $result = [];
        // remove unwanted characters from connection name
        $connectionName = str_replace(' ', '', $connectionName);
        $connectionName = preg_replace('/[^A-Za-z0-9\-]/', '', $connectionName);

        foreach ($attributes as $attribute => $value) {
            if (in_array($attribute, $blacklist, true)) {
                continue;
            }
            $result['migration_' . $connectionName . '_' . $entityName . '_' . $attribute] = $value;
        }

        return $result;
    }
}
