<?php

namespace jeremykenedy\laravelexceptionnotifier\Support;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

class EmailFiles
{
    public function __construct(private Filesystem $files)
    {
    }

    public function install(?string $layout, ?string $theme, bool $force): array
    {
        $path = resource_path('views/emails/exception.blade.php');
        $this->ensureReplacementAllowed($path, $layout, $force);
        $messages = $this->backupView($path, $layout);
        $messages = array_merge($messages, $this->publishMissingFiles());
        $messages[] = $this->publishView($path, $layout, $theme);

        return $messages;
    }

    private function ensureReplacementAllowed(string $path, ?string $layout, bool $force): void
    {
        if ($layout !== null && $this->files->exists($path) && ! $force) {
            throw new RuntimeException('The email view already exists. Use --force to replace it with a backup.');
        }
    }

    private function backupView(string $path, ?string $layout): array
    {
        if ($layout === null || ! $this->files->exists($path)) {
            return [];
        }

        $backup = $path.'.'.date('YmdHis').'.'.bin2hex(random_bytes(4)).'.bak';
        if (! $this->files->copy($path, $backup)) {
            throw new RuntimeException('Unable to back up the email view. No view changes were made.');
        }

        return ['Backup saved to '.$backup];
    }

    private function publishMissingFiles(): array
    {
        $source = dirname(__DIR__);
        $messages = [];
        foreach ([
            $source.'/App/Mail/ExceptionOccurred.php' => app_path('Mail/ExceptionOccurred.php'),
            $source.'/config/exceptions.php'          => config_path('exceptions.php'),
        ] as $from => $to) {
            if ($this->files->exists($to)) {
                $messages[] = 'Kept '.$to;

                continue;
            }

            $this->files->ensureDirectoryExists(dirname($to));
            if (! $this->files->copy($from, $to)) {
                throw new RuntimeException('Unable to write '.$to);
            }
            $messages[] = 'Created '.$to;
        }

        return $messages;
    }

    private function publishView(string $path, ?string $layout, ?string $theme): string
    {
        if ($layout === null && $this->files->exists($path)) {
            return 'Kept '.$path;
        }

        $contents = $this->viewContents($layout, $theme);
        $this->files->ensureDirectoryExists(dirname($path));
        $this->writeView($path, $contents);

        return 'Installed '.($layout ?? 'legacy').' email view.';
    }

    private function viewContents(?string $layout, ?string $theme): string
    {
        if ($layout === null) {
            return $this->files->get(dirname(__DIR__).'/resources/views/emails/exception.blade.php');
        }

        $theme = $theme ?? 'light';
        $view = $layout === 'legacy' ? 'exception' : $layout;

        return "@include('laravelexceptionnotifier::emails.{$view}', ['theme' => '{$theme}'])\n";
    }

    private function writeView(string $path, string $contents): void
    {
        $temporary = $path.'.'.bin2hex(random_bytes(8)).'.tmp';
        try {
            if ($this->files->put($temporary, $contents) !== strlen($contents)) {
                throw new RuntimeException('Unable to write the email view. The original view was preserved.');
            }

            if (! $this->files->move($temporary, $path)) {
                throw new RuntimeException('Unable to replace the email view. The original view was preserved.');
            }
        } finally {
            $this->files->delete($temporary);
        }
    }
}
