<?php

namespace App\Domains\Admin\Filament\Resources\News;

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\News\Private\Models\News;
use App\Domains\News\Private\Services\NewsService;
use App\Domains\Shared\Support\HtmlLinkUtils;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class NewsResource extends Resource
{
    protected static ?string $model = News::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    public static function getNavigationGroup(): ?string
    {
        return __('admin::news.navigation.group');
    }

    public static function getModelLabel(): string
    {
        return __('admin::news.resource.label');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin::news.navigation.news');
    }

    public static function canAccess(): bool
    {
        /** @var \App\Domains\Auth\Private\Models\User|null $user */
        $user = \Illuminate\Support\Facades\Auth::user();
        return $user?->hasRole([Roles::ADMIN, Roles::TECH_ADMIN]) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('admin::news.resource.label'))
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label(__('admin::news.fields.title'))
                            ->required()
                            ->maxLength(200)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn($state, callable $set) => $set('slug', Str::slug($state))),
                        Forms\Components\TextInput::make('slug')
                            ->label(__('admin::news.fields.slug'))
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\Textarea::make('summary')
                            ->label(__('admin::news.fields.summary'))
                            ->required()
                            ->rows(3),
                        Forms\Components\RichEditor::make('content')
                            ->label(__('admin::news.fields.content'))
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'bold', 'italic', 'strike', 'underline', 'bulletList', 'orderedList', 'h2', 'h3', 'blockquote', 'link', 'undo', 'redo'
                            ])
                            ->dehydrateStateUsing(fn(?string $state) => HtmlLinkUtils::addTargetBlankToExternalLinks($state))
                            ->required(),
                    ])->columns(2),
                Forms\Components\Section::make('Media')
                    ->schema([
                        Forms\Components\Placeholder::make('header_image_preview')
                            ->label('')
                            ->content(function (callable $get) {
                                $path = $get('header_image_path');
                                if (!$path) return '';
                                $url = asset('storage/' . $path);
                                return new \Illuminate\Support\HtmlString(
                                    '<div class="mb-2 text-center"><img src="' . e($url) . '" alt="Header image" style="max-width:200px; height: auto; border-radius: .5rem; display:block; margin:0 auto;"/></div>'
                                );
                            })
                            ->reactive()
                            ->visible(fn(callable $get) => filled($get('header_image_path')) && empty($get('header_image')) && !$get('remove_header_image'))
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('header_image')
                            ->label(__('admin::news.fields.header_image'))
                            ->image()
                            ->disk('public')
                            ->directory('tmp/news')
                            ->openable()
                            ->downloadable(false)
                            ->helperText(__('admin::news.help.header_image')),
                        Forms\Components\Toggle::make('remove_header_image')
                            ->label(__('admin::news.actions.remove_header_image'))
                            ->reactive()
                            ->visible(fn(callable $get) => filled($get('header_image_path')) && empty($get('header_image'))),
                    ]),
                Forms\Components\Section::make(__('admin::news.fields.status'))
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label(__('admin::news.fields.status'))
                            ->required()
                            ->options([
                                'draft' => __('admin::news.status.draft'),
                                'published' => __('admin::news.status.published'),
                            ])->native(false)
                            ->default('draft'),
                        Forms\Components\TextInput::make('meta_description')
                            ->label(__('admin::news.fields.meta_description'))
                            ->maxLength(160),
                        Forms\Components\Toggle::make('is_pinned')->label(__('admin::news.fields.is_pinned')),
                        Forms\Components\TextInput::make('display_order')
                            ->label(__('admin::news.fields.display_order'))
                            ->disabled()
                            ->numeric()
                            ->minValue(1)
                            ->helperText(__('admin::news.help.display_order')),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label(__('admin::news.fields.title'))
                    ->searchable()->sortable(),
                Tables\Columns\TextColumn::make('status')->label(__('admin::news.fields.status'))->badge()->colors([
                    'warning' => 'draft',
                    'success' => 'published',
                ])->sortable(),
                Tables\Columns\IconColumn::make('is_pinned')->boolean()->label(__('admin::news.fields.is_pinned'))->sortable(),
                Tables\Columns\TextColumn::make('published_at')->label(__('admin::news.fields.published_at'))->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'draft' => __('admin::news.status.draft'),
                    'published' => __('admin::news.status.published'),
                ]),
                Tables\Filters\TernaryFilter::make('is_pinned')
                    ->placeholder(__('admin::news.filters.all'))
                    ->trueLabel(__('admin::news.fields.is_pinned'))
                    ->falseLabel(__('admin::news.filters.not_pinned'))
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->iconButton()
                    ->tooltip(__('Open'))
                    ->url(fn(News $record) => route('news.show', ['slug' => $record->slug]), shouldOpenInNewTab: true),
                Tables\Actions\EditAction::make()->iconButton()->label('')->tooltip(__('Edit')),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->iconButton()
                    ->label('')
                    ->tooltip(__('Delete')),
                Tables\Actions\Action::make('publish')
                    ->visible(fn(News $record) => $record->status !== 'published')
                    ->label(__('admin::news.actions.publish'))
                    ->requiresConfirmation()
                    ->action(function (News $record) {
                        app(NewsService::class)->publish($record);
                    }),
                Tables\Actions\Action::make('unpublish')
                    ->visible(fn(News $record) => $record->status === 'published')
                    ->requiresConfirmation()
                    ->label(__('admin::news.actions.unpublish'))
                    ->action(function (News $record) {
                        app(NewsService::class)->unpublish($record);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => NewsResource\Pages\ListNews::route('/'),
            'create' => NewsResource\Pages\CreateNews::route('/create'),
            'edit' => NewsResource\Pages\EditNews::route('/{record}/edit'),
        ];
    }
}
