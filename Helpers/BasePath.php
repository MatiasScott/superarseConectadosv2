<?php
// app/Helpers/BasePath.php

class BasePath
{
    public static function detect(): string
    {
        if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'superarse.ec') !== false) {
            return '';
        }

        $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
        return rtrim($scriptDir, '/');
    }
}
