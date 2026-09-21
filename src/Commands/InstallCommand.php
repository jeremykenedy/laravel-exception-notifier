<?php

namespace jeremykenedy\laravelexceptionnotifier\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class InstallCommand extends Command
{
    protected $signature = 'exception-notifier:install
        {--layout= : Email layout: legacy or modern}
        {--theme= : Email color scheme: light, dark, or system}
        {--force : Replace the selected email view after saving a backup}';

    protected $description = 'Install exception email files without overwriting application configuration or mailers';

    public function handle(Filesystem $files): int
    {
        $selection = $this->selectLayout();
        if ($selection === null) {
            return self::FAILURE;
        }

        if (! $this->installFiles($files, ...$selection)) {
            return self::FAILURE;
        }

        $this->info('Configure your mail recipients and exception reporting callback as described in the README.');
        $this->line('Custom exceptions.emailExceptionView settings are preserved. Clear cached views after changing templates.');

        return self::SUCCESS;
    }

    private function selectLayout(): ?array
    {
        $layout = $this->chooseLayout();
        $theme = $this->input->getOption('theme');

        if (! $this->validSelection($layout, $theme)) {
            return null;
        }

        return [$layout, $this->chooseTheme($layout, $theme)];
    }

    private function chooseLayout(): ?string
    {
        $layout = $this->input->getOption('layout');
        if ($layout === null && $this->input->isInteractive()) {
            $layout = $this->choice('Email layout', ['keep', 'legacy', 'modern'], 'keep');

            return $layout === 'keep' ? null : $layout;
        }

        return $layout;
    }

    private function validSelection(?string $layout, ?string $theme): bool
    {
        if (! in_array($layout, [null, 'legacy', 'modern'], true)) {
            $this->error('Choose legacy or modern.');

            return false;
        }

        if (! in_array($theme, [null, 'light', 'dark', 'system'], true)) {
            $this->error('Choose light, dark, or system.');

            return false;
        }

        if ($theme !== null && $layout === null) {
            $this->error('Specify --layout when selecting a theme.');

            return false;
        }

        return true;
    }

    private function chooseTheme(?string $layout, ?string $theme): ?string
    {
        if ($layout !== null && $theme === null && $this->input->isInteractive()) {
            return $this->choice('Email color scheme', ['light', 'dark', 'system'], 'light');
        }

        return $theme;
    }

    private function installFiles(Filesystem $files, ?string $layout, ?string $theme): bool
    {
        $path = resource_path('views/emails/exception.blade.php');
        if (! $this->canPublishView($files, $path, $layout)) {
            return false;
        }

        if (! $this->backupView($files, $path, $layout)) {
            return false;
        }

        return $this->publishMissingFiles($files) && $this->publishView($files, $path, $layout, $theme);
    }

    private function canPublishView(Filesystem $files, string $path, ?string $layout): bool
    {
        if ($layout === null || ! $files->exists($path) || $this->option('force')) {
            return true;
        }

        $this->error('The email view already exists. Use --force to replace it with a backup.');

        return false;
    }

    private function backupView(Filesystem $files, string $path, ?string $layout): bool
    {
        if ($layout === null || ! $files->exists($path)) {
            return true;
        }

        $backup = $path.'.'.date('YmdHis').'.'.bin2hex(random_bytes(4)).'.bak';
        if (! $files->copy($path, $backup)) {
            $this->error('Unable to back up the email view. No view changes were made.');

            return false;
        }
        $this->info('Backup saved to '.$backup);

        return true;
    }

    private function publishMissingFiles(Filesystem $files): bool
    {
        $source = dirname(__DIR__);
        foreach ([
            $source.'/App/Mail/ExceptionOccurred.php' => app_path('Mail/ExceptionOccurred.php'),
            $source.'/config/exceptions.php'          => config_path('exceptions.php'),
        ] as $from => $to) {
            if ($files->exists($to)) {
                $this->line('Kept '.$to);

                continue;
            }

            $files->ensureDirectoryExists(dirname($to));
            if (! $files->copy($from, $to)) {
                $this->error('Unable to write '.$to);

                return false;
            }
            $this->info('Created '.$to);
        }

        return true;
    }

    private function publishView(Filesystem $files, string $path, ?string $layout, ?string $theme): bool
    {
        if ($layout === null && $files->exists($path)) {
            $this->line('Kept '.$path);

            return true;
        }

        $contents = $this->viewContents($files, $layout, $theme);
        $files->ensureDirectoryExists(dirname($path));
        if (! $this->writeView($files, $path, $contents)) {
            return false;
        }
        $this->info('Installed '.($layout ?? 'legacy').' email view.');

        return true;
    }

    private function viewContents(Filesystem $files, ?string $layout, ?string $theme): string
    {
        if ($layout === null) {
            return $files->get(dirname(__DIR__).'/resources/views/emails/exception.blade.php');
        }

        $theme = $theme ?? 'light';
        $view = $layout === 'legacy' ? 'exception' : $layout;

        return "@include('laravelexceptionnotifier::emails.{$view}', ['theme' => '{$theme}'])\n";
    }

    private function writeView(Filesystem $files, string $path, string $contents): bool
    {
        $temporary = $path.'.'.bin2hex(random_bytes(8)).'.tmp';
        try {
            if ($files->put($temporary, $contents) !== strlen($contents)) {
                $this->error('Unable to write the email view. The original view was preserved.');

                return false;
            }

            if (! $files->move($temporary, $path)) {
                $this->error('Unable to replace the email view. The original view was preserved.');

                return false;
            }

            return true;
        } finally {
            $files->delete($temporary);
        }
    }
}
