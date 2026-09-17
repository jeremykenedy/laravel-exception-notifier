@php
    $theme = $theme ?? config('exceptions.emailExceptionTheme', 'light');
    $theme = in_array($theme, ['light', 'dark', 'system'], true) ? $theme : 'light';
    $dark = $theme === 'dark';
    $bootstrap = ($framework ?? 'bootstrap5') === 'bootstrap5';
    $background = $dark ? '#101827' : '#f3f5f8';
    $surface = $dark ? '#1b2738' : '#ffffff';
    $foreground = $dark ? '#edf2f7' : '#182437';
    $muted = $dark ? '#b4c2d3' : '#526278';
    $border = $dark ? '#35455c' : '#dce3ec';
    $accent = $dark ? '#ffa6a0' : '#a82d35';
@endphp
<!DOCTYPE html>
<html lang="en" @if($bootstrap) data-bs-theme="{{ $dark ? 'dark' : 'light' }}" @else class="{{ $dark ? 'dark' : 'light' }}" @endif>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <meta name="color-scheme" content="{{ $theme === 'system' ? 'light dark' : $theme }}">
    <meta name="supported-color-schemes" content="{{ $theme === 'system' ? 'light dark' : $theme }}">
    <title>Exception notification</title>
    <style>
        body { margin: 0; }
        .exception-email { overflow-wrap: anywhere; word-break: break-word; }
        .exception-email * { box-sizing: border-box; }
        .exception-email .email-frame { width: 100%; max-width: 880px; margin: 0 auto; }
        .exception-email .email-card { border-radius: 12px; overflow: hidden; }
        .exception-email .email-path { font-family: Consolas, Monaco, monospace; font-size: 13px; line-height: 1.8; }
        @media (max-width: 600px) {
            .exception-email { padding: 20px 12px !important; }
            .exception-email .email-section { padding: 20px !important; }
            .exception-email h1 { font-size: 24px !important; }
        }
        @if($theme === 'system')
        @media (prefers-color-scheme: dark) {
            .exception-email { background: #101827 !important; color: #edf2f7 !important; }
            .exception-email .email-card { background: #1b2738 !important; border-color: #35455c !important; }
            .exception-email .email-muted { color: #b4c2d3 !important; }
            .exception-email .email-accent { color: #ffa6a0 !important; }
            .exception-email .email-divider { border-color: #35455c !important; }
        }
        @endif
    </style>
</head>
<body class="exception-email {{ $bootstrap ? 'bg-body-tertiary' : 'bg-slate-100 dark:bg-slate-900 text-slate-900 dark:text-slate-100' }}" style="background: {{ $background }}; color: {{ $foreground }}; font: 15px/1.6 -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; padding: 40px 20px;">
    <main class="email-frame {{ $bootstrap ? 'container' : 'mx-auto max-w-4xl' }}">
        <p class="email-muted {{ $bootstrap ? 'small text-uppercase' : 'text-xs uppercase tracking-widest' }}" style="color: {{ $muted }}; font-size: 12px; font-weight: 700; letter-spacing: 2px; margin: 0 0 20px;">{{ config('app.name', 'Laravel') }} / Exception notification</p>
        <section class="email-card {{ $bootstrap ? 'card shadow-sm' : 'rounded-xl border bg-white dark:bg-slate-800' }}" style="background: {{ $surface }}; border: 1px solid {{ $border }};">
            <header class="email-section {{ $bootstrap ? 'card-header' : 'px-8 py-6' }}" style="padding: 28px 32px; border-top: 4px solid {{ $accent }};">
                <p class="email-accent {{ $bootstrap ? 'small fw-semibold' : 'text-xs font-semibold' }}" style="color: {{ $accent }}; font-size: 12px; font-weight: 700; letter-spacing: 1px; margin: 0 0 12px;">APPLICATION ERROR</p>
                <h1 style="font-size: 28px; font-weight: 650; line-height: 1.3; margin: 0;">{{ $content['message'] ?? 'Exception reported' }}</h1>
                <p class="email-path email-muted" style="color: {{ $muted }}; margin: 16px 0 0;">{{ $content['file'] ?? '' }}<br>Line {{ $content['line'] ?? '' }}</p>
            </header>
            <section aria-label="Request details" class="email-section email-divider {{ $bootstrap ? 'card-body' : 'px-8 py-6 border-t' }}" style="padding: 24px 32px; border-top: 1px solid {{ $border }};">
                <h2 style="font-size: 16px; margin: 0 0 16px;">Request details</h2>
                <dl style="margin: 0;">
                    <dt class="email-muted" style="color: {{ $muted }}; font-size: 12px; font-weight: 700;">URL</dt>
                    <dd class="email-path" style="margin: 4px 0 16px;">{{ $content['url'] ?? '' }}</dd>
                    <dt class="email-muted" style="color: {{ $muted }}; font-size: 12px; font-weight: 700;">IP ADDRESS</dt>
                    <dd class="email-path" style="margin: 4px 0 0;">{{ $content['ip'] ?? '' }}</dd>
                </dl>
            </section>
            <section aria-label="Stack trace" class="email-divider" style="border-top: 1px solid {{ $border }};">
                <div class="email-section" style="padding: 24px 32px 16px;">
                    <h2 style="font-size: 16px; margin: 0;">Stack trace <span class="email-muted" style="color: {{ $muted }}; font-size: 13px; font-weight: 400;">({{ count($content['trace'] ?? []) }} frames)</span></h2>
                </div>
                @forelse(($content['trace'] ?? []) as $frame)
                    <div class="email-section email-divider" style="padding: 16px 32px; {{ $loop->first ? '' : 'border-top: 1px solid '.$border.';' }}">
                        <p class="email-path" style="margin: 0;"><span class="email-muted" style="color: {{ $muted }};">#{{ $loop->iteration }}</span> {{ $frame['class'] ?? '' }}{{ $frame['type'] ?? '' }}<strong>{{ $frame['function'] ?? '' }}</strong>()</p>
                        <p class="email-path email-muted" style="color: {{ $muted }}; margin: 4px 0 0;">{{ $frame['file'] ?? 'Internal call' }}@isset($frame['line']) : {{ $frame['line'] }}@endisset</p>
                    </div>
                @empty
                    <p class="email-section email-muted" style="color: {{ $muted }}; padding: 0 32px 24px; margin: 0;">No stack frames available.</p>
                @endforelse
            </section>
        </section>
        <p class="email-muted" style="color: {{ $muted }}; font-size: 12px; margin: 20px 0 0;">Laravel Exception Notifier</p>
    </main>
</body>
</html>
