<?php

namespace Statamic\Ignition\Solutions;

use Facade\IgnitionContracts\Solution;

class EnableOAuth implements Solution
{
    public function getSolutionTitle(): string
    {
        return 'OAuth is disabled';
    }

    public function getSolutionDescription(): string
    {
        return 'Enable it by setting `STATAMIC_OAUTH_ENABLED=true` in your `.env` file.';
    }

    public function getDocumentationLinks(): array
    {
        return [
            'OAuth Guide' => 'https://docs.statamic.com/oauth',
        ];
    }
}
