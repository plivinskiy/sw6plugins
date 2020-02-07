<?php declare(strict_types=1);

namespace Egle\Resources\snippet\en_GB;

use Shopware\Core\System\Snippet\Files\SnippetFileInterface;

class SnippetFile_en_GB implements SnippetFileInterface
{
    public function getName(): string
    {
        return 'egle.en-GB';
    }
    
    public function getPath(): string
    {
        return __DIR__ . '/egle.en-GB.json';
    }
    
    public function getIso(): string
    {
        return 'en-GB';
    }
    
    public function getAuthor(): string
    {
        return 'Tagwork One';
    }
    
    public function isBase(): bool
    {
        return false;
    }
}