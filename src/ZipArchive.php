<?php

namespace Laravel\Serverless;

/**
 * @method setExternalAttributesName(string $localName, int $opsys, mixed $value)
 */
class ZipArchive extends \ZipArchive
{
    public function addFolderUnix($folderPath)
    {
        /** @var \SplFileInfo $file */
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($folderPath)) as $file) {
            $localName = str_replace($folderPath, '', $file->getPathname());
            $localName = ltrim($localName, DIRECTORY_SEPARATOR);

            if ($file->isLink()) {
                $this->addFromString($localName, $file->getLinkTarget());
                $this->setExternalAttributesName($localName, $this::OPSYS_UNIX,
                    (0120000 | 0777) << 16
                );
            } elseif ($file->isFile()) {
                $this->addFile($file->getPathname(), $localName);
                $this->setExternalAttributesName($localName, $this::OPSYS_UNIX,
                    fileperms($file->getPathname()) << 16
                );
            }
        }
    }
}
