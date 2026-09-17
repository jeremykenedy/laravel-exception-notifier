<?php

namespace jeremykenedy\laravelexceptionnotifier\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class InstallCommand extends Command
{
    protected $signature = 'exception-notifier:install
        {--framework= : Email layout: legacy, bootstrap5, or tailwind}
        {--theme= : Email color scheme: light, dark, or system}
        {--ui-kit : Use the installed Laravel UI Kit CSS framework and default theme}
        {--force : Replace the selected email view after saving a backup}';

    protected $description = 'Install exception email files without overwriting application configuration or mailers';

    public function handle(Filesystem $files): int
    {
        $selection = $this->selectLayout();
        if ($selection === null) {
            return self::FAILURE;
        }

        [$framework, $theme] = $selection;
        $viewPath = resource_path('views/emails/exception.blade.php');
        if ($framework !== null && $files->exists($viewPath) && ! $this->option('force')) {
            $this->error('The email view already exists. Use --force to replace it with a backup.');

            return self::FAILURE;
        }

        if (! $this->publishMissingFiles($files) || ! $this->publishView($files, $viewPath, $framework, $theme)) {
            return self::FAILURE;
        }

        $this->info('Configure your mail recipients and exception reporting callback as described in the README.');
        $this->line('Custom exceptions.emailExceptionView settings are preserved. Clear cached views after changing templates.');

        return self::SUCCESS;
    }

    private function selectLayout(): ?array
    {
        $framework = $this->option('framework');
        $theme = $this->option('theme');

        if ($this->option('ui-kit')) {
            if ($framework !== null) {
                $this->error('Use either --ui-kit or --framework.');

                return null;
            }

            $framework = config('ui-kit.css_framework');
            if (! in_array($framework, ['bootstrap5', 'tailwind'], true)) {
                $this->error('Configure Laravel UI Kit with bootstrap5 or tailwind before using --ui-kit.');

                return null;
            }
            $theme = $theme ?? (config('ui-kit.dark_mode.enabled', true)
                ? config('ui-kit.dark_mode.default', 'system') : 'light');
        }

        if ($framework === null && $this->input->isInteractive()) {
            $framework = $this->choice('Email layout', ['keep', 'legacy', 'bootstrap5', 'tailwind'], 'keep');
            $framework = $framework === 'keep' ? null : $framework;
        }

        if ($framework !== null && ! in_array($framework, ['legacy', 'bootstrap5', 'tailwind'], true)) {
            $this->error('Choose legacy, bootstrap5, or tailwind.');

            return null;
        }

        if ($theme !== null && ! in_array($theme, ['light', 'dark', 'system'], true)) {
            $this->error('Choose light, dark, or system.');

            return null;
        }

        if ($theme !== null && $framework === null) {
            $this->error('Specify --framework when selecting a theme.');

            return null;
        }

        if ($framework !== null && $theme === null && $this->input->isInteractive()) {
            $theme = $this->choice('Email color scheme', ['light', 'dark', 'system'], 'light');
        }

        return [$framework, $theme];
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

    private function publishView(Filesystem $files, string $path, ?string $framework, ?string $theme): bool
    {
        if ($framework === null && $files->exists($path)) {
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
        $view = $framework === null || $framework === 'legacy' ? 'exception' : $framework;
        $contents = $framework === null
            ? $files->get(dirname(__DIR__).'/resources/views/emails/exception.blade.php')
            : "@include('laravelexceptionnotifier::emails.{$view}', ['theme' => '{$theme}'])\n";
        $files->ensureDirectoryExists(dirname($path));
        $files->replace($path, $contents);
        $this->info('Installed '.($framework ?? 'legacy').' email view.');

        return true;
    }
}
