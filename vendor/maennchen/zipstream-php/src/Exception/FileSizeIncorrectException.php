<?php

declare(strict_types=1);

namespace ZipStream\Exception;

use ZipStream\Exception;

/**
 * This Exception gets invoked if a file is not as large as it was specified.
 */
class FileSizeIncorrectException extends Exception
{
    /**
     * @internal
     */
    public function __construct(
        public int $expectedSize,
        public int $actualSize
    ) {
        parent::__construct("File is {$actualSize} instead of {$expectedSize} bytes large. Adjust `exactSize` parameter.");
    }
}
