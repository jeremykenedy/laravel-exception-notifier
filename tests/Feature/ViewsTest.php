<?php

namespace jeremykenedy\laravelexceptionnotifier\Test\Feature;

use App\Mail\ExceptionOccurred;
use Illuminate\Support\Facades\Mail;
use jeremykenedy\laravelexceptionnotifier\Test\TestCase;

class ViewsTest extends TestCase
{
    public function test_all_layouts_render_all_themes_and_escape_exception_data(): void
    {
        $content = $this->content();
        foreach (['message', 'file', 'url', 'ip', 'line'] as $key) {
            $content[$key] = '<script>alert("'.$key.'")</script>';
        }
        $content['trace'][0] = array_fill_keys(['class', 'function', 'file', 'type', 'line'], '<img src=x onerror=alert(1)>');
        foreach (['exception', 'modern'] as $layout) {
            foreach (['light', 'dark', 'system'] as $theme) {
                $html = view('laravelexceptionnotifier::emails.'.$layout, compact('content', 'theme'))->render();
                $this->assertStringContainsString('&lt;script&gt;', $html);
                $this->assertStringContainsString('&lt;img', $html);
                $this->assertStringNotContainsString('<script', $html);
                $this->assertStringNotContainsString('<img', $html);
                $this->assertStringNotContainsString('must-not-appear', $html);
                $this->assertStringContainsString('name="viewport"', $html);
                $this->assertStringContainsString('name="color-scheme" content="'.($theme === 'system' ? 'light dark' : $theme).'"', $html);
                $this->assertSame($theme === 'system', str_contains($html, 'prefers-color-scheme: dark'));
            }
        }
    }

    public function test_empty_content_and_internal_stack_frames_render_without_notices(): void
    {
        foreach (['exception', 'modern'] as $layout) {
            foreach ([[], ['trace' => [[], ['function' => 'call_user_func']]]] as $content) {
                $html = view('laravelexceptionnotifier::emails.'.$layout, compact('content'))->render();
                $this->assertStringContainsString('</html>', $html);
            }
        }
    }

    public function test_invalid_theme_falls_back_to_light(): void
    {
        foreach (['exception', 'modern'] as $layout) {
            $html = view('laravelexceptionnotifier::emails.'.$layout, ['content' => [], 'theme' => '"><script>'])->render();
            $this->assertStringContainsString('name="color-scheme" content="light"', $html);
            $this->assertStringNotContainsString('<script>', $html);
        }
    }

    public function test_environment_theme_is_used_when_no_theme_is_passed(): void
    {
        config()->set('exceptions.emailExceptionTheme', 'dark');
        foreach (['exception', 'modern'] as $layout) {
            $html = view('laravelexceptionnotifier::emails.'.$layout, ['content' => []])->render();
            $this->assertStringContainsString('name="color-scheme" content="dark"', $html);
        }
    }

    public function test_each_installed_layout_is_deliverable_without_a_frontend_build(): void
    {
        $this->configureMail();
        foreach (['legacy', 'modern'] as $layout) {
            $this->artisan('exception-notifier:install', ['--layout' => $layout, '--theme' => 'system', '--force' => true, '--no-interaction' => true])->assertExitCode(0);
            Mail::send(new ExceptionOccurred($this->content()));
            $html = Mail::mailer()->getSymfonyTransport()->messages()->last()->getOriginalMessage()->getHtmlBody();
            $this->assertStringContainsString('Unable to complete the request', $html);
            $this->assertStringContainsString('prefers-color-scheme: dark', $html);
            $this->assertStringNotContainsString('<script', $html);
            $this->assertStringNotContainsString('<link', $html);
        }
    }
}
