<?php

namespace App\Domains\Admin\Filament\Resources\StaticPage;

use App\Domains\StaticPage\Models\StaticPage;
use App\Domains\StaticPage\Services\StaticPageService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class StaticPageResource extends Resource
{
    protected static ?string $model = StaticPage::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function getNavigationGroup(): ?string
    {
        return __('admin::static.navigation.group');
    }

    public static function getModelLabel(): string
    {
        return __('admin::static.resource.label');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin::static.navigation.pages');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('admin::static.resource.label'))
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label(__('admin::static.fields.title'))
                            ->required()
                            ->maxLength(200)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                        Forms\Components\TextInput::make('slug')
                            ->label(__('admin::static.fields.slug'))
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\Textarea::make('summary')
                            ->label(__('admin::static.fields.summary'))
                            ->rows(3),
                        Forms\Components\RichEditor::make('content')
                            ->label(__('admin::static.fields.content'))
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'bold','italic','strike','underline','bulletList','orderedList','h2','h3','blockquote','link','undo','redo'
                            ])
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
                            ->visible(fn (callable $get) => filled($get('header_image_path')) && empty($get('header_image')) && !$get('remove_header_image'))
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('header_image')
                            ->label(__('admin::static.fields.header_image'))
                            ->image()
                            ->disk('public')
                            ->directory('tmp/static-pages')
                            ->openable()
                            ->downloadable(false)
                            ->helperText(__('admin::static.help.header_image')),
                        Forms\Components\Toggle::make('remove_header_image')
                            ->label(__('admin::static.actions.remove_header_image'))
                            ->reactive()
                            ->visible(fn (callable $get) => filled($get('header_image_path')) && empty($get('header_image'))),
                    ]),
                Forms\Components\Section::make(__('admin::static.fields.status'))
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label(__('admin::static.fields.status'))
                            ->required()
                            ->options([
                                'draft' => __('admin::static.status.draft'),
                                'published' => __('admin::static.status.published'),
                            ])->native(false)
                            ->default('draft'),
                        Forms\Components\DateTimePicker::make('published_at')
                            ->label(__('admin::static.fields.published_at'))
                            ->seconds(false)
                            ->native(false),
                        Forms\Components\TextInput::make('meta_description')
                            ->label(__('admin::static.fields.meta_description'))
                            ->maxLength(160),
                    ])->columns(2),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label(__('admin::static.fields.title'))
                    ->searchable()->sortable(),
                Tables\Columns\TextColumn::make('status')->label(__('admin::static.fields.status'))->badge()->colors([
                    'warning' => 'draft',
                    'success' => 'published',
                ])->sortable(),
                Tables\Columns\TextColumn::make('published_at')->label(__('admin::static.fields.published_at'))->dateTime()->sortable(),
            ])
            ->defaultSort('title')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'draft' => __('admin::static.status.draft'),
                    'published' => __('admin::static.status.published'),
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->extraAttributes(['style' => '--c-300:#93c5fd;--c-400:#60a5fa;--c-500:#3b82f6;--c-600:#2563eb'])
                    ->iconButton()
                    ->tooltip(__('Open'))
                    ->url(fn(StaticPage $record) => url('/' . $record->slug), shouldOpenInNewTab: true),
                Tables\Actions\EditAction::make()->iconButton()->label('')->tooltip(__('Edit')),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->iconButton()
                    ->label('')
                    ->tooltip(__('Delete')),
                Tables\Actions\Action::make('publish')
                    ->label(__('admin::static.actions.publish'))
                    ->visible(fn(StaticPage $record) => $record->status !== 'published')
                    ->requiresConfirmation()
                    ->action(function(StaticPage $record) {
                        app(StaticPageService::class)->publish($record);
                    }),
                Tables\Actions\Action::make('unpublish')
                    ->label(__('admin::static.actions.unpublish'))
                    ->visible(fn(StaticPage $record) => $record->status === 'published')
                    ->requiresConfirmation()
                    ->action(function(StaticPage $record) {
                        app(StaticPageService::class)->unpublish($record);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_publish')
                        ->label(__('admin::static.actions.publish'))
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $service = app(StaticPageService::class);
                            foreach ($records as $record) {
                                if ($record->status !== 'published') {
                                    $service->publish($record);
                                }
                            }
                        }),
                    Tables\Actions\BulkAction::make('bulk_unpublish')
                        ->label(__('admin::static.actions.unpublish'))
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $service = app(StaticPageService::class);
                            foreach ($records as $record) {
                                if ($record->status === 'published') {
                                    $service->unpublish($record);
                                }
                            }
                        }),
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
            'index' => StaticPageResource\Pages\ListStaticPages::route('/'),
            'create' => StaticPageResource\Pages\CreateStaticPage::route('/create'),
            'edit' => StaticPageResource\Pages\EditStaticPage::route('/{record}/edit'),
        ];
    }
}
