<?php declare(strict_types=1);

namespace Egle\Resources\snippet\de_DE;

use Shopware\Core\System\Snippet\Files\SnippetFileInterface;

class SnippetFile_de_DE implements SnippetFileInterface
{
    public function getName(): string
    {
        return 'egle.de-DE';
    }
    
    public function getPath(): string
    {
        return __DIR__ . '/egle.de-DE.json';
    }
    
    public function getIso(): string
    {
        return 'de-DE';
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