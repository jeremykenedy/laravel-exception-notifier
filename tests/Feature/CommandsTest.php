<?php

namespace jeremykenedy\laravelexceptionnotifier\Test\Feature;

use Illuminate\Filesystem\Filesystem;
use jeremykenedy\laravelexceptionnotifier\Test\TestCase;

class CommandsTest extends TestCase
{
    public function test_noninteractive_install_uses_legacy_view(): void
    {
        $this->artisan('exception-notifier:install', ['--no-interaction' => true])->assertExitCode(0);
        $this->assertSame(
            file_get_contents(dirname(__DIR__, 2).'/src/resources/views/emails/exception.blade.php'),
            file_get_contents(resource_path('views/emails/exception.blade.php'))
        );
        $this->assertFileExists(app_path('Mail/ExceptionOccurred.php'));
        $this->assertFileExists(config_path('exceptions.php'));
    }

    public function test_install_and_update_keep_all_existing_files_even_with_force_alone(): void
    {
        $this->artisan('exception-notifier:install', ['--no-interaction' => true])->assertExitCode(0);
        $paths = [app_path('Mail/ExceptionOccurred.php'), config_path('exceptions.php'), resource_path('views/emails/exception.blade.php')];
        foreach ($paths as $path) {
            file_put_contents($path, 'Application customization');
        }
        foreach (['exception-notifier:install', 'exception-notifier:update'] as $command) {
            $this->artisan($command, ['--no-interaction' => true, '--force' => true])->assertExitCode(0);
            foreach ($paths as $path) {
                $this->assertSame('Application customization', file_get_contents($path));
            }
        }
        $this->assertSame([], glob(resource_path('views/emails/*.bak')));
    }

    public function test_layout_switch_requires_force_and_backs_up_the_original(): void
    {
        $this->artisan('exception-notifier:install', ['--no-interaction' => true])->assertExitCode(0);
        $path = resource_path('views/emails/exception.blade.php');
        file_put_contents($path, 'Custom layout');
        $this->artisan('exception-notifier:update', ['--layout' => 'modern', '--no-interaction' => true])->assertExitCode(1);
        $this->assertSame('Custom layout', file_get_contents($path));
        $this->artisan('exception-notifier:update', [
            '--layout' => 'modern', '--theme' => 'dark', '--force' => true, '--no-interaction' => true,
        ])->assertExitCode(0);
        $backups = glob($path.'.*.bak');
        $this->assertCount(1, $backups);
        $this->assertSame('Custom layout', file_get_contents($backups[0]));
        $this->assertStringContainsString('emails.modern', file_get_contents($path));
        $this->assertStringContainsString("'theme' => 'dark'", file_get_contents($path));
        $html = view('emails.exception', ['content' => $this->content()])->render();
        $this->assertStringContainsString('data-theme="dark"', $html);
        $this->assertStringContainsString('Stack trace', $html);
    }

    public function test_invalid_options_do_not_write_any_files(): void
    {
        foreach ([['--layout' => 'invalid'], ['--theme' => 'invalid'], ['--theme' => 'dark']] as $options) {
            $this->artisan('exception-notifier:install', $options + ['--no-interaction' => true])->assertExitCode(1);
            $this->assertFileDoesNotExist(config_path('exceptions.php'));
            $this->assertFileDoesNotExist(app_path('Mail/ExceptionOccurred.php'));
            $this->assertFileDoesNotExist(resource_path('views/emails/exception.blade.php'));
        }
    }

    public function test_interactive_install_can_select_a_layout_and_theme(): void
    {
        $this->artisan('exception-notifier:install')
            ->expectsChoice('Email layout', 'modern', ['keep', 'legacy', 'modern'])
            ->expectsChoice('Email color scheme', 'system', ['light', 'dark', 'system'])
            ->assertExitCode(0);
        $html = view('emails.exception', ['content' => $this->content()])->render();
        $this->assertStringContainsString('data-theme="light"', $html);
        $this->assertStringContainsString('prefers-color-scheme: dark', $html);
    }

    public function test_interactive_default_keeps_a_custom_view(): void
    {
        $this->artisan('exception-notifier:install', ['--no-interaction' => true])->assertExitCode(0);
        file_put_contents(resource_path('views/emails/exception.blade.php'), 'Custom layout');
        $this->artisan('exception-notifier:update')
            ->expectsChoice('Email layout', 'keep', ['keep', 'legacy', 'modern'])
            ->assertExitCode(0);
        $this->assertSame('Custom layout', file_get_contents(resource_path('views/emails/exception.blade.php')));
    }

    public function test_switching_back_to_legacy_and_repeated_backups_preserve_each_version(): void
    {
        $path = resource_path('views/emails/exception.blade.php');
        $this->artisan('exception-notifier:install', ['--layout' => 'modern', '--no-interaction' => true])->assertExitCode(0);
        $light = file_get_contents($path);
        $this->artisan('exception-notifier:update', ['--layout' => 'modern', '--theme' => 'dark', '--force' => true, '--no-interaction' => true])->assertExitCode(0);
        $dark = file_get_contents($path);
        $this->artisan('exception-notifier:update', ['--layout' => 'legacy', '--force' => true, '--no-interaction' => true])->assertExitCode(0);
        $backups = array_map('file_get_contents', glob($path.'.*.bak'));
        $this->assertCount(2, $backups);
        $this->assertContains($light, $backups);
        $this->assertContains($dark, $backups);
        $this->assertStringContainsString('exception-summary', view('emails.exception', ['content' => []])->render());
    }

    public function test_switch_keeps_custom_config_and_mailer_unchanged(): void
    {
        $this->artisan('exception-notifier:install', ['--no-interaction' => true])->assertExitCode(0);
        file_put_contents(config_path('exceptions.php'), '<?php return ["emailExceptionView" => "custom"];');
        file_put_contents(app_path('Mail/ExceptionOccurred.php'), 'Custom mailer');
        $this->artisan('exception-notifier:update', ['--layout' => 'modern', '--force' => true, '--no-interaction' => true])->assertExitCode(0);
        $this->assertSame('<?php return ["emailExceptionView" => "custom"];', file_get_contents(config_path('exceptions.php')));
        $this->assertSame('Custom mailer', file_get_contents(app_path('Mail/ExceptionOccurred.php')));
    }

    public function test_failed_backup_leaves_the_existing_view_untouched(): void
    {
        $this->artisan('exception-notifier:install', ['--no-interaction' => true])->assertExitCode(0);
        $path = resource_path('views/emails/exception.blade.php');
        file_put_contents($path, 'Custom view');
        $files = \Mockery::mock(Filesystem::class)->makePartial();
        $files->shouldReceive('copy')->once()->withArgs(function ($source, $destination) use ($path) {
            return $source === $path && str_ends_with($destination, '.bak');
        })->andReturn(false);
        $this->app->instance(Filesystem::class, $files);
        $this->artisan('exception-notifier:update', ['--layout' => 'modern', '--force' => true, '--no-interaction' => true])->assertExitCode(1);
        $this->assertSame('Custom view', file_get_contents($path));
    }

    public function test_failed_copy_returns_failure_without_installing_a_view(): void
    {
        $files = \Mockery::mock(Filesystem::class)->makePartial();
        $files->shouldReceive('copy')->once()->andReturn(false);
        $this->app->instance(Filesystem::class, $files);
        $this->artisan('exception-notifier:install', ['--no-interaction' => true])->assertExitCode(1);
        $this->assertFileDoesNotExist(resource_path('views/emails/exception.blade.php'));
    }

    public function test_explicit_legacy_light_selection_overrides_configured_dark_mode(): void
    {
        config()->set('exceptions.emailExceptionTheme', 'dark');
        $this->artisan('exception-notifier:install', [
            '--layout' => 'legacy', '--theme' => 'light', '--no-interaction' => true,
        ])->assertExitCode(0);
        $html = view('emails.exception', ['content' => []])->render();
        $this->assertStringContainsString('name="color-scheme" content="light"', $html);
        $this->assertStringNotContainsString('background: #101827', $html);
    }

    public function test_default_legacy_installation_keeps_configuration_driven_theme(): void
    {
        config()->set('exceptions.emailExceptionTheme', 'dark');
        $this->artisan('exception-notifier:install', ['--no-interaction' => true])->assertExitCode(0);
        $html = view('emails.exception', ['content' => []])->render();
        $this->assertStringContainsString('name="color-scheme" content="dark"', $html);
    }
}
