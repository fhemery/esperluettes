<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Domains\Dashboard\Private\View\Components\WelcomeComponent;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Story\Public\Api\StoryPublicApi;
use App\Domains\Comment\Public\Api\CommentPublicApi;
use App\Domains\Shared\Dto\FullProfileDto;
use App\Domains\Auth\Public\Api\Dto\RoleDto;

uses(TestCase::class, RefreshDatabase::class);

describe('WelcomeComponent', function () {

    it('renders welcome panel with profile and counts', function () {
        $user = alice($this);
        $this->actingAs($user);

        // Mock dependencies
        $profiles = Mockery::mock(ProfilePublicApi::class);
        $stories  = Mockery::mock(StoryPublicApi::class);
        $comments = Mockery::mock(CommentPublicApi::class);

        // Prepare DTOs/returns
        $role = new RoleDto(id: 2, name: 'Membre confirmé', slug: 'user-confirmed', description: null);
        $full = new FullProfileDto(
            userId: $user->id,
            displayName: 'Alice',
            slug: 'alice',
            avatarUrl: '/images/a.png',
            joinDateIso: '2024-01-02',
            roles: [$role],
        );

        $profiles->shouldReceive('getFullProfile')
            ->once()
            ->with($user->id)
            ->andReturn($full);

        $stories->shouldReceive('countAuthoredStories')
            ->once()
            ->with($user->id)
            ->andReturn(3);

        $comments->shouldReceive('countRootCommentsByUser')
            ->once()
            ->with('chapter', $user->id)
            ->andReturn(7);

        app()->instance(ProfilePublicApi::class, $profiles);
        app()->instance(StoryPublicApi::class, $stories);
        app()->instance(CommentPublicApi::class, $comments);

        /** @var WelcomeComponent $component */
        $component = app(WelcomeComponent::class);
        $view = $component->render();
        $html = $view->render();

        // Expect translated strings and dynamic values
        $expectedWelcome = __('dashboard::welcome.welcome_message', ['name' => 'Alice']);
        $expectedMemberSince = __('dashboard::welcome.member_since', [
            'date' => '2024-01-02',
            'role' => 'Membre confirmé',
        ]);
        $expectedActivity = __('dashboard::welcome.activity_summary', [
            'stories' => 3,
            'comments' => 7,
        ]);

        expect($html)->toContain($expectedWelcome)
            ->and($html)->toContain('surface-read')
            ->and($html)->toContain($expectedMemberSince)
            ->and($html)->toContain($expectedActivity)
            ->and($html)->not->toContain(__('dashboard::welcome.errors.data_unavailable'));
    });

    it('shows error when user not authenticated', function () {
        // Ensure guest (no actingAs)
        $profiles = Mockery::mock(ProfilePublicApi::class);
        $stories  = Mockery::mock(StoryPublicApi::class);
        $comments = Mockery::mock(CommentPublicApi::class);
        app()->instance(ProfilePublicApi::class, $profiles);
        app()->instance(StoryPublicApi::class, $stories);
        app()->instance(CommentPublicApi::class, $comments);

        /** @var WelcomeComponent $component */
        $component = app(WelcomeComponent::class);
        $html = $component->render()->render();

        $expectedError = __('dashboard::welcome.errors.not_authenticated');
        expect($html)->toContain($expectedError)
            ->and($html)->toContain('surface-error');
    });

    it('shows inline error when profile data unavailable', function () {
        $user = alice($this);
        $this->actingAs($user);

        $profiles = Mockery::mock(ProfilePublicApi::class);
        $stories  = Mockery::mock(StoryPublicApi::class);
        $comments = Mockery::mock(CommentPublicApi::class);

        $profiles->shouldReceive('getFullProfile')
            ->once()
            ->with($user->id)
            ->andReturn(null);

        // Story/Comment should not be called when profile is null, but allow no expectations
        app()->instance(ProfilePublicApi::class, $profiles);
        app()->instance(StoryPublicApi::class, $stories);
        app()->instance(CommentPublicApi::class, $comments);

        /** @var WelcomeComponent $component */
        $component = app(WelcomeComponent::class);
        $html = $component->render()->render();

        $expectedError = __('dashboard::welcome.errors.data_unavailable');
        expect($html)->toContain($expectedError)
            ->and($html)->toContain('surface-error');
    });
});
