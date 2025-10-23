<?php

namespace App\Domains\Admin\Filament\Resources\Calendar;

use App\Domains\Admin\Filament\Resources\Calendar\ActivitiesResource\Pages;
use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Calendar\Private\Models\Activity;
use App\Domains\Calendar\Public\Api\CalendarRegistry;
use App\Domains\Calendar\Public\Contracts\ActivityState;
use App\Domains\Shared\Support\HtmlLinkUtils;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ActivitiesResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    public static function getNavigationGroup(): ?string
    {
        return __('admin::calendar.navigation.group');
    }

    public static function getModelLabel(): string
    {
        return __('admin::calendar.resource.label');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin::calendar.navigation.activities');
    }

    public static function canAccess(): bool
    {
        /** @var \App\Domains\Auth\Private\Models\User|null $user */
        $user = Auth::user();
        return $user?->hasRole([Roles::ADMIN, Roles::TECH_ADMIN]) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('admin::calendar.resource.label'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('admin::calendar.fields.name'))
                            ->required()
                            ->maxLength(200),
                        Forms\Components\Select::make('activity_type')
                            ->label(__('admin::calendar.fields.activity_type'))
                            ->required()
                            ->options(function () {
                                /** @var CalendarRegistry $registry */
                                $registry = app(CalendarRegistry::class);
                                $keys = $registry->keys() ?? [];
                                $opts = [];
                                foreach ($keys as $key) {
                                    $label = __('calendar::activities.' . $key);
                                    if ($label === 'calendar::activities.' . $key) {
                                        $label = $key;
                                    }
                                    $opts[$key] = $label;
                                }
                                return $opts;
                            })
                            ->native(false),
                        Forms\Components\RichEditor::make('description')
                            ->label(__('admin::calendar.fields.description'))
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'bold','italic','strike','underline','bulletList','orderedList','h2','h3','blockquote','link','undo','redo'
                            ])
                            ->dehydrateStateUsing(fn(?string $state) => HtmlLinkUtils::addTargetBlankToExternalLinks($state)),
                    ])->columns(2),
                Forms\Components\Section::make(__('admin::calendar.sections.media'))
                    ->schema([
                        Forms\Components\Placeholder::make('image_preview')
                            ->label('')
                            ->content(function (callable $get) {
                                $path = $get('image_path');
                                if (!$path) return '';
                                $url = asset('storage/' . $path);
                                return new \Illuminate\Support\HtmlString(
                                    '<div class="mb-2 text-center"><img src="' . e($url) . '" alt="Image" style="max-width:200px; height: auto; border-radius: .5rem; display:block; margin:0 auto;"/></div>'
                                );
                            })
                            ->reactive()
                            ->visible(fn(callable $get) => filled($get('image_path')) && empty($get('image')) && !$get('remove_image'))
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('image')
                            ->label(__('admin::calendar.fields.image'))
                            ->image()
                            ->disk('public')
                            ->directory('tmp/activities')
                            ->openable()
                            ->downloadable(false),
                        Forms\Components\Toggle::make('remove_image')
                            ->label(__('admin::calendar.actions.remove_image'))
                            ->reactive()
                            ->visible(fn(callable $get) => filled($get('image_path')) && empty($get('image'))),
                    ]),
                Forms\Components\Section::make(__('admin::calendar.sections.restrictions'))
                    ->schema([
                        Forms\Components\Select::make('role_restrictions')
                            ->label(__('admin::calendar.fields.role_restrictions'))
                            ->multiple()
                            ->options(function () {
                                $roles = app(AuthPublicApi::class)->getAllRoles();
                                $opts = [];
                                foreach ($roles as $role) {
                                    /** @var \App\Domains\Auth\Public\Api\Dto\RoleDto $role */
                                    $opts[$role->slug] = $role->name;
                                }
                                return $opts;
                            })
                            ->searchable()
                            ->preload()
                            ->native(false),
                        Forms\Components\Toggle::make('requires_subscription')->label(__('admin::calendar.fields.requires_subscription')),
                        Forms\Components\TextInput::make('max_participants')->label(__('admin::calendar.fields.max_participants'))->numeric()->minValue(1)->maxValue(100000),
                    ])->columns(3),
                Forms\Components\Section::make(__('admin::calendar.sections.dates'))
                    ->schema([
                        Forms\Components\DateTimePicker::make('preview_starts_at')->label(__('admin::calendar.fields.preview_starts_at'))->seconds(false),
                        Forms\Components\DateTimePicker::make('active_starts_at')->label(__('admin::calendar.fields.active_starts_at'))->seconds(false),
                        Forms\Components\DateTimePicker::make('active_ends_at')->label(__('admin::calendar.fields.active_ends_at'))->seconds(false),
                        Forms\Components\DateTimePicker::make('archived_at')->label(__('admin::calendar.fields.archived_at'))->seconds(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label(__('admin::calendar.fields.name'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('activity_type')->label(__('admin::calendar.fields.activity_type'))->formatStateUsing(function ($state) {
                    $label = __('calendar::activities.' . $state);
                    return $label === 'calendar::activities.' . $state ? $state : $label;
                })->sortable(),
                Tables\Columns\TextColumn::make('state')->label(__('admin::calendar.fields.status'))->badge()->formatStateUsing(function ($state) {
                    return match ($state) {
                        ActivityState::DRAFT => __('admin::calendar.status.draft'),
                        ActivityState::PREVIEW => __('admin::calendar.status.preview'),
                        ActivityState::ACTIVE => __('admin::calendar.status.active'),
                        ActivityState::ENDED => __('admin::calendar.status.ended'),
                        ActivityState::ARCHIVED => __('admin::calendar.status.archived'),
                        default => (string) $state,
                    };
                })->sortable(),
                Tables\Columns\TextColumn::make('preview_starts_at')->label(__('admin::calendar.fields.preview_starts_at'))->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('active_starts_at')->label(__('admin::calendar.fields.active_starts_at'))->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('active_ends_at')->label(__('admin::calendar.fields.active_ends_at'))->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('archived_at')->label(__('admin::calendar.fields.archived_at'))->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('state')->label(__('admin::calendar.fields.status'))->options([
                    ActivityState::DRAFT => __('admin::calendar.status.draft'),
                    ActivityState::PREVIEW => __('admin::calendar.status.preview'),
                    ActivityState::ACTIVE => __('admin::calendar.status.active'),
                    ActivityState::ENDED => __('admin::calendar.status.ended'),
                    ActivityState::ARCHIVED => __('admin::calendar.status.archived'),
                ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton()->label('')->tooltip(__('admin::calendar.actions.edit')),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->iconButton()
                    ->label('')
                    ->tooltip(__('admin::calendar.actions.delete'))
                    ->using(function (Activity $record) {
                        app(\App\Domains\Calendar\Public\Api\CalendarPublicApi::class)->delete($record->id, (int) Auth::id());
                        return $record;
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label(__('admin::calendar.actions.bulk_delete')),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivities::route('/'),
            'create' => Pages\CreateActivity::route('/create'),
            'edit' => Pages\EditActivity::route('/{record}/edit'),
        ];
    }
}
