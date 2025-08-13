<?php

namespace App\Domains\Admin\Filament\Resources\Announcement;

use App\Domains\Announcement\Models\Announcement;
use App\Domains\Announcement\Services\AnnouncementService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    public static function getNavigationGroup(): ?string
    {
        return __('admin::announcement.navigation.group');
    }

    public static function getModelLabel(): string
    {
        return __('admin::announcement.resource.label');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin::announcement.navigation.announcements');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('admin::announcement.resource.label'))
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label(__('admin::announcement.fields.title'))
                            ->required()
                            ->maxLength(200)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                        Forms\Components\TextInput::make('slug')
                            ->label(__('admin::announcement.fields.slug'))
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\Textarea::make('summary')
                            ->label(__('admin::announcement.fields.summary'))
                            ->required()
                            ->rows(3),
                        Forms\Components\RichEditor::make('content')
                            ->label(__('admin::announcement.fields.content'))
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'bold','italic','strike','underline','bulletList','orderedList','blockquote','link','undo','redo'
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
                            ->label(__('admin::announcement.fields.header_image'))
                            ->image()
                            ->disk('public')
                            ->directory('tmp/announcements')
                            ->openable()
                            ->downloadable(false)
                            ->helperText(__('admin::announcement.help.header_image')),
                        Forms\Components\Toggle::make('remove_header_image')
                            ->label(__('admin::announcement.actions.remove_header_image'))
                            ->reactive()
                            ->visible(fn (callable $get) => filled($get('header_image_path')) && empty($get('header_image'))),
                    ]),
                Forms\Components\Section::make(__('admin::announcement.fields.status'))
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label(__('admin::announcement.fields.status'))
                            ->required()
                            ->options([
                                'draft' => __('admin::announcement.status.draft'),
                                'published' => __('admin::announcement.status.published'),
                            ])->native(false)
                            ->default('draft'),
                        Forms\Components\DateTimePicker::make('published_at')
                            ->label(__('admin::announcement.fields.published_at'))
                            ->seconds(false)
                            ->native(false),
                        Forms\Components\TextInput::make('meta_description')
                            ->label(__('admin::announcement.fields.meta_description'))
                            ->maxLength(160),
                        Forms\Components\Toggle::make('is_pinned')->label(__('admin::announcement.fields.is_pinned')),
                        Forms\Components\TextInput::make('display_order')
                            ->label(__('admin::announcement.fields.display_order'))
                            ->numeric()
                            ->minValue(1)
                            ->helperText(__('admin::announcement.help.display_order')),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label(__('admin::announcement.fields.title'))
                    ->searchable()->sortable(),
                Tables\Columns\TextColumn::make('status')->label(__('admin::announcement.fields.status'))->badge()->colors([
                    'warning' => 'draft',
                    'success' => 'published',
                ])->sortable(),
                Tables\Columns\IconColumn::make('is_pinned')->boolean()->label(__('admin::announcement.fields.is_pinned'))->sortable(),
                Tables\Columns\TextColumn::make('published_at')->label(__('admin::announcement.fields.published_at'))->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'draft' => __('admin::announcement.status.draft'),
                    'published' => __('admin::announcement.status.published'),
                ]),
                Tables\Filters\TernaryFilter::make('is_pinned')
                    ->placeholder(__('admin::announcement.filters.all'))
                    ->trueLabel(__('admin::announcement.fields.is_pinned'))
                    ->falseLabel(__('admin::announcement.filters.not_pinned'))
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton()->label('')->tooltip(__('Edit')),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->iconButton()
                    ->label('')
                    ->tooltip(__('Delete')),
                Tables\Actions\Action::make('publish')
                    ->visible(fn(Announcement $record) => $record->status !== 'published')
                    ->requiresConfirmation()
                    ->action(function(Announcement $record) {
                        app(AnnouncementService::class)->publish($record);
                    }),
                Tables\Actions\Action::make('unpublish')
                    ->visible(fn(Announcement $record) => $record->status === 'published')
                    ->requiresConfirmation()
                    ->action(function(Announcement $record) {
                        app(AnnouncementService::class)->unpublish($record);
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
            'index' => AnnouncementResource\Pages\ListAnnouncements::route('/'),
            'create' => AnnouncementResource\Pages\CreateAnnouncement::route('/create'),
            'edit' => AnnouncementResource\Pages\EditAnnouncement::route('/{record}/edit'),
        ];
    }
}
