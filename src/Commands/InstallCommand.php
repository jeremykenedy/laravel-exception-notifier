<?php

namespace jeremykenedy\laravelexceptionnotifier\Commands;

use Illuminate\Console\Command;
use jeremykenedy\laravelexceptionnotifier\Support\EmailFiles;
use RuntimeException;

class InstallCommand extends Command
{
    protected $signature = 'exception-notifier:install
        {--layout= : Email layout: legacy or modern}
        {--theme= : Email color scheme: light, dark, or system}
        {--force : Replace the selected email view after saving a backup}';

    protected $description = 'Install exception email files without overwriting application configuration or mailers';

    public function handle(EmailFiles $files): int
    {
        $selection = $this->selectLayout();
        if ($selection === null) {
            return self::FAILURE;
        }

        try {
            [$layout, $theme] = $selection;
            $messages = $files->install($layout, $theme, (bool) $this->option('force'));
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        foreach ($messages as $message) {
            $this->info($message);
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
}
