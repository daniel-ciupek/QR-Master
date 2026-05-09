<?php

declare(strict_types=1);

namespace App\Support;

use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Policy;
use Spatie\Csp\Preset;
use Spatie\Csp\Value;

final class CspPolicy implements Preset
{
    public function configure(Policy $policy): void
    {
        $policy
            ->add(Directive::DEFAULT, Keyword::SELF)
            ->add(Directive::SCRIPT, Keyword::SELF)
            ->add(Directive::STYLE, Keyword::SELF)
            ->add(Directive::IMG, [Keyword::SELF, 'data:', 'blob:'])
            ->add(Directive::FONT, Keyword::SELF)
            ->add(Directive::CONNECT, $this->connectSources())
            ->add(Directive::MANIFEST, Keyword::SELF)
            ->add(Directive::OBJECT, Keyword::NONE)
            ->add(Directive::BASE, Keyword::SELF)
            ->add(Directive::FORM_ACTION, Keyword::SELF)
            ->add(Directive::FRAME_ANCESTORS, Keyword::NONE)
            ->add(Directive::UPGRADE_INSECURE_REQUESTS, Value::NO_VALUE)
            ->addNonce(Directive::SCRIPT)
            ->addNonce(Directive::STYLE);

        // W trybie deweloperskim: pozwól Vite HMR serwować zasoby
        if (app()->isLocal()) {
            $policy
                ->add(Directive::SCRIPT, ['http://localhost:5173', Keyword::UNSAFE_EVAL])
                ->add(Directive::STYLE, 'http://localhost:5173');
        }
    }

    /** @return non-empty-list<Keyword|string> */
    private function connectSources(): array
    {
        $sources = [Keyword::SELF];

        if (app()->isLocal()) {
            $sources[] = 'ws://localhost:5173';
            $sources[] = 'http://localhost:5173';
            $sources[] = 'ws://localhost:8080';
        }

        return $sources;
    }
}
