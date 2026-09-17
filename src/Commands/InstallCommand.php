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
        $framework = $this->option('framework');
        $theme = $this->option('theme');
        $viewPath = resource_path('views/emails/exception.blade.php');

        if ($this->option('ui-kit')) {
            if ($framework !== null) {
                $this->error('Use either --ui-kit or --framework.');

                return self::FAILURE;
            }

            $framework = config('ui-kit.css_framework');
            if (! in_array($framework, ['bootstrap5', 'tailwind'], true)) {
                $this->error('Configure Laravel UI Kit with bootstrap5 or tailwind before using --ui-kit.');

                return self::FAILURE;
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

            return self::FAILURE;
        }

        if ($theme !== null && ! in_array($theme, ['light', 'dark', 'system'], true)) {
            $this->error('Choose light, dark, or system.');

            return self::FAILURE;
        }

        if ($theme !== null && $framework === null) {
            $this->error('Specify --framework when selecting a theme.');

            return self::FAILURE;
        }

        if ($framework !== null && $theme === null && $this->input->isInteractive()) {
            $theme = $this->choice('Email color scheme', ['light', 'dark', 'system'], 'light');
        }

        if ($framework !== null && $files->exists($viewPath) && ! $this->option('force')) {
            $this->error('The email view already exists. Use --force to replace it with a backup.');

            return self::FAILURE;
        }

        $source = dirname(__DIR__);
        foreach ([
            $source.'/App/Mail/ExceptionOccurred.php' => app_path('Mail/ExceptionOccurred.php'),
            $source.'/config/exceptions.php'          => config_path('exceptions.php'),
        ] as $from => $to) {
            if (! $files->exists($to)) {
                $files->ensureDirectoryExists(dirname($to));
                if (! $files->copy($from, $to)) {
                    $this->error('Unable to write '.$to);

                    return self::FAILURE;
                }
                $this->info('Created '.$to);
            } else {
                $this->line('Kept '.$to);
            }
        }

        if ($framework !== null || ! $files->exists($viewPath)) {
            if ($files->exists($viewPath)) {
                $backup = $viewPath.'.'.date('YmdHis').'.'.bin2hex(random_bytes(4)).'.bak';
                if (! $files->copy($viewPath, $backup)) {
                    $this->error('Unable to back up the email view. No view changes were made.');

                    return self::FAILURE;
                }
                $this->info('Backup saved to '.$backup);
            }

            $framework = $framework ?? 'legacy';
            $theme = $theme ?? 'light';
            $view = $framework === 'legacy' ? 'exception' : $framework;
            $contents = $framework === 'legacy' && $theme === 'light'
                ? $files->get($source.'/resources/views/emails/exception.blade.php')
                : "@include('laravelexceptionnotifier::emails.{$view}', ['theme' => '{$theme}'])\n";
            $files->ensureDirectoryExists(dirname($viewPath));
            $files->replace($viewPath, $contents);
            $this->info('Installed '.$framework.' email view ('.$theme.').');
        } else {
            $this->line('Kept '.$viewPath);
        }

        $this->info('Configure your mail recipients and exception reporting callback as described in the README.');
        $this->line('Custom exceptions.emailExceptionView settings are preserved. Clear cached views after changing templates.');

        return self::SUCCESS;
    }
}
