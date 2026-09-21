<?php

namespace jeremykenedy\laravelexceptionnotifier\Commands;

class UpdateCommand extends InstallCommand
{
    protected $signature = 'exception-notifier:update
        {--layout= : Email layout: legacy or modern}
        {--theme= : Email color scheme: light, dark, or system}
        {--force : Replace the selected email view after saving a backup}';

    protected $description = 'Restore missing exception email files or explicitly switch the email layout';
}
