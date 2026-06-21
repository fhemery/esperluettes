<?php

use App\Domains\Config\Public\Contracts\FeatureToggle;
use App\Domains\Config\Public\Contracts\FeatureToggleAccess;
use App\Domains\Settings\Public\Contracts\SettingsParameterDefinition;
use App\Domains\Shared\Contracts\ParameterType;
use App\Domains\Shared\Providers\SharedServiceProvider;
use App\Domains\Shared\Support\FeatureToggles;
use App\Domains\Story\Private\Models\Chapter;
use App\Domains\Story\Private\Models\Story;
use App\Domains\StoryRef\Private\Models\StoryRefType;
use App\Domains\StoryRef\Private\Models\StoryRefAudience;
use App\Domains\StoryRef\Private\Models\StoryRefCopyright;
use App\Domains\StoryRef\Private\Models\StoryRefGenre;
use App\Domains\StoryRef\Private\Models\StoryRefStatus;
use App\Domains\StoryRef\Private\Models\StoryRefFeedback;
use App\Domains\StoryRef\Private\Models\StoryRefTriggerWarning;
use App\Domains\StoryRef\Private\Services\TypeService;
use App\Domains\StoryRef\Private\Services\AudienceService;
use App\Domains\StoryRef\Private\Services\CopyrightService;
use App\Domains\StoryRef\Private\Services\GenreService;
use App\Domains\StoryRef\Private\Services\StatusService;
use App\Domains\StoryRef\Private\Services\FeedbackService;
use App\Domains\StoryRef\Private\Services\TriggerWarningService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

function enableDarkThemeSettingForTesting(TestCase $t): void
{
    createFeatureToggle($t, new FeatureToggle(
        FeatureToggles::DARK_THEME,
        FeatureToggles::DOMAIN,
        access: FeatureToggleAccess::ON,
    ));

    $settingsApi = app(\App\Domains\Settings\Public\Api\SettingsPublicApi::class);
    if ($settingsApi->getParameter(SharedServiceProvider::TAB_GENERAL, SharedServiceProvider::KEY_APPEARANCE) !== null) {
        return;
    }

    registerSettingsParameter(new SettingsParameterDefinition(
        tabId: SharedServiceProvider::TAB_GENERAL,
        sectionId: SharedServiceProvider::SECTION_APPEARANCE,
        key: SharedServiceProvider::KEY_APPEARANCE,
        type: ParameterType::ENUM,
        default: 'light',
        order: 15,
        nameKey: 'shared::settings.params.appearance.name',
        descriptionKey: 'shared::settings.params.appearance.description',
        constraints: [
            'options' => [
                'light' => 'shared::settings.params.appearance.options.light',
                'dark' => 'shared::settings.params.appearance.options.dark',
            ],
        ],
    ));
}

function assertHasIconBadge(TestCase $t, string $badgeIcon, string $badgeText, string $content): TestCase
{
    $t->assertMatchesRegularExpression(
        '/comment\s*<\/span>\s*1/s',
        $content
    );
    return $t;
}

// -------------------------------------------------------------------------
// Breadcrumb test helpers
// -------------------------------------------------------------------------

/**
 * Parse the response and return an array of breadcrumb items.
 * Each item: [ 'text' => string, 'href' => ?string ]
 * Skips separator items ('/').
 *
 * @return array<int,array{text:string,href:?string}>
 */
function breadcrumb_items(TestResponse $response): array
{
    $response->assertElementExists("nav[data-test-id='breadcrumbs']");
    $liNodes = $response->getElements("nav[data-test-id='breadcrumbs'] li");
    foreach ($liNodes as $li) {
        $text = trim(preg_replace('/\s+/', ' ', $li->textContent ?? ''));
        // Skip separator-only li
        if ($text === '/') continue;

        $a = null;
        foreach ($li->childNodes as $child) {
            if ($child instanceof \DOMElement && strtolower($child->tagName) === 'a') {
                $a = $child; break;
            }
        }

        $href = $a?->getAttribute('href') ?: null;
        $items[] = [
            'text' => $text,
            'href' => $href ?: null,
        ];
    }
    return $items;
}