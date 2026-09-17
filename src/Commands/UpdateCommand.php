<?php

namespace jeremykenedy\laravelexceptionnotifier\Commands;

class UpdateCommand extends InstallCommand
{
    protected $signature = 'exception-notifier:update
        {--framework= : Email layout: legacy, bootstrap5, or tailwind}
        {--theme= : Email color scheme: light, dark, or system}
        {--ui-kit : Use the installed Laravel UI Kit CSS framework and default theme}
        {--force : Replace the selected email view after saving a backup}';

    protected $description = 'Restore missing exception email files or explicitly switch the email layout';
}
