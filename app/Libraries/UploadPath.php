<?php

namespace App\Libraries;

class UploadPath
{
    /**
     * Absolute base directory for uploaded files, with a trailing separator.
     * Configurable via .env (app.uploadPath) so it can point outside the
     * deployed application folder on hosts where deploys replace the whole
     * codebase - otherwise each deploy wipes uploaded files while their
     * database references survive, breaking existing download/image links.
     */
    public static function base(): string
    {
        $configured = trim((string) (config('App')->uploadPath ?? ''));

        return $configured !== ''
            ? rtrim($configured, '/\\') . DIRECTORY_SEPARATOR
            : WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR;
    }
}
