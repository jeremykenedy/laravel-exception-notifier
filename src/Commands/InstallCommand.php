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

        [$layout, $theme] = $selection;
        $viewPath = resource_path('views/emails/exception.blade.php');
        if ($layout !== null && $files->exists($viewPath) && ! $this->option('force')) {
            $this->error('The email view already exists. Use --force to replace it with a backup.');

            return self::FAILURE;
        }

        if (! $this->publishMissingFiles($files) || ! $this->publishView($files, $viewPath, $layout, $theme)) {
            return self::FAILURE;
        }

        $this->info('Configure your mail recipients and exception reporting callback as described in the README.');
        $this->line('Custom exceptions.emailExceptionView settings are preserved. Clear cached views after changing templates.');

        return self::SUCCESS;
    }

    private function selectLayout(): ?array
    {
        $layout = $this->option('layout');
        $theme = $this->option('theme');

        if ($layout === null && $this->input->isInteractive()) {
            $layout = $this->choice('Email layout', ['keep', 'legacy', 'modern'], 'keep');
            $layout = $layout === 'keep' ? null : $layout;
        }

        if ($layout !== null && ! in_array($layout, ['legacy', 'modern'], true)) {
            $this->error('Choose legacy or modern.');

            return null;
        }

        if ($theme !== null && ! in_array($theme, ['light', 'dark', 'system'], true)) {
            $this->error('Choose light, dark, or system.');

            return null;
        }

        if ($theme !== null && $layout === null) {
            $this->error('Specify --layout when selecting a theme.');

            return null;
        }

        if ($layout !== null && $theme === null && $this->input->isInteractive()) {
            $theme = $this->choice('Email color scheme', ['light', 'dark', 'system'], 'light');
        }

        return [$layout, $theme];
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

        if ($files->exists($path)) {
            $backup = $path.'.'.date('YmdHis').'.'.bin2hex(random_bytes(4)).'.bak';
            if (! $files->copy($path, $backup)) {
                $this->error('Unable to back up the email view. No view changes were made.');

                return false;
            }
            $this->info('Backup saved to '.$backup);
        }

        $theme = $theme ?? 'light';
        $view = $layout === null || $layout === 'legacy' ? 'exception' : $layout;
        $contents = $layout === null
            ? $files->get(dirname(__DIR__).'/resources/views/emails/exception.blade.php')
            : "@include('laravelexceptionnotifier::emails.{$view}', ['theme' => '{$theme}'])\n";
        $files->ensureDirectoryExists(dirname($path));
        $files->replace($path, $contents);
        $this->info('Installed '.($layout ?? 'legacy').' email view.');

        return true;
    }
}
