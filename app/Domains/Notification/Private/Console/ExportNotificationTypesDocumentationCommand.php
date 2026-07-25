<?php

namespace App\Domains\Notification\Private\Console;

use App\Domains\Notification\Public\Services\NotificationChannelRegistry;
use App\Domains\Notification\Public\Services\NotificationFactory;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ExportNotificationTypesDocumentationCommand extends Command
{
    protected $signature = 'notifications:export-types-doc
                            {--output=docs/notification-types.md : Path to the markdown file (relative to project root, or absolute)}
                            {--locale= : Locale used to resolve translation keys (defaults to the application locale)}';

    protected $description = 'Generate a markdown catalog of registered notification content types and external delivery channels';

    public function handle(NotificationFactory $factory, NotificationChannelRegistry $channels): int
    {
        $locale = $this->option('locale') ?: app()->getLocale();
        app()->setLocale($locale);

        $path = $this->resolveOutputPath((string) $this->option('output'));

        $directory = dirname($path);
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
                $this->error("Could not create directory: {$directory}");

                return self::FAILURE;
            }
        }

        $markdown = $this->buildMarkdown($factory, $channels, $locale);

        if (file_put_contents($path, $markdown) === false) {
            $this->error("Could not write file: {$path}");

            return self::FAILURE;
        }

        $this->info("Wrote {$path}");

        return self::SUCCESS;
    }

    private function resolveOutputPath(string $output): string
    {
        if ($output !== '' && str_starts_with($output, '/')) {
            return $output;
        }

        return base_path($output);
    }

    private function buildMarkdown(NotificationFactory $factory, NotificationChannelRegistry $channels, string $locale): string
    {
        $lines = [];
        $lines[] = '# Notification types catalog';
        $lines[] = '';
        $lines[] = '> **Auto-generated** by `php artisan notifications:export-types-doc`. Do not edit by hand.';
        $lines[] = '';
        $lines[] = 'Generated at: '.Carbon::now()->toIso8601String().' (`locale`: `'.$locale.'`)';
        $lines[] = '';

        foreach ($factory->getGroups() as $group) {
            $lines[] = '## Group: '.$this->markdownPlain(__($group->translationKey)).' (`'.$group->id.'`)';
            $lines[] = '';
            $lines[] = '| Type key | PHP class | User-facing label | Payload fields | Forced on website | Hidden in preferences UI |';
            $lines[] = '| --- | --- | --- | --- | --- | --- |';

            foreach ($factory->getTypesForGroup($group->id, true) as $def) {
                $lines[] = sprintf(
                    '| `%s` | `%s` | %s | %s | %s | %s |',
                    $def->type,
                    $def->class,
                    $this->markdownTableCell(__($def->nameKey)),
                    $this->payloadFields($def->class),
                    $def->forcedOnWebsite ? 'yes' : 'no',
                    $def->hideInSettings ? 'yes' : 'no',
                );
            }
            $lines[] = '';
        }

        $lines[] = '## Delivery channels';
        $lines[] = '';
        $lines[] = 'The built-in `website` channel is always available and is not part of the channel registry.';
        $lines[] = '';
        $lines[] = '| Channel id | User-facing label | Default for new users | Feature-gated |';
        $lines[] = '| --- | --- | --- | --- |';

        foreach ($channels->getAllChannels() as $channel) {
            $lines[] = sprintf(
                '| `%s` | %s | %s | %s |',
                $channel->id,
                $this->markdownTableCell(__($channel->nameTranslationKey)),
                $channel->defaultEnabled ? 'on' : 'off',
                $channel->featureCheck !== null ? 'yes' : 'no',
            );
        }
        $lines[] = '';
        $lines[] = '## Stored payload';
        $lines[] = '';
        $lines[] = 'Each notification row stores a `content_key` (the type key) and JSON from `NotificationContent::toData()`. '
            .'The "Payload fields" column above lists the keys each type stores; see `toData()` and `fromData()` on the PHP class for value types.';
        $lines[] = '';
        $lines[] = 'External delivery channels receive this payload **verbatim**. In particular, the Discord bot API '
            .'(`GET /api/discord/notifications/pending`) returns it unchanged as the `data` object, so the key names below '
            .'are part of that endpoint\'s contract — renaming one is a breaking change for the bot.';
        $lines[] = '';

        return implode("\n", $lines);
    }

    /**
     * Derive the stored payload keys for a notification type.
     *
     * Every content class reconstructs from a partial array (all fields null-coalesce),
     * so an empty `fromData([])` yields an instance whose `toData()` exposes the full key set.
     */
    private function payloadFields(string $class): string
    {
        try {
            $keys = array_keys($class::fromData([])->toData());
        } catch (\Throwable) {
            return '_(could not be determined)_';
        }

        if ($keys === []) {
            return '_(none)_';
        }

        return implode(', ', array_map(fn (string $key): string => '`'.$key.'`', $keys));
    }

    private function markdownPlain(string $value): string
    {
        return str_replace(['`'], ["'"], $value);
    }

    private function markdownTableCell(string $value): string
    {
        $escaped = str_replace(['|', "\n", "\r"], ['\\|', ' ', ' '], $value);

        return str_replace('`', "'", $escaped);
    }
}
