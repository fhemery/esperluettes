<?php

namespace App\Domains\Admin\Filament\Resources;

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

    protected static ?string $navigationGroup = 'Content';

    protected static ?string $modelLabel = 'Announcement';

    protected static ?string $navigationLabel = 'Announcements';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Main')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(200)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\Textarea::make('summary')
                            ->required()
                            ->rows(3),
                        Forms\Components\RichEditor::make('content')
                            ->label('Content')
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'bold','italic','strike','underline','bulletList','orderedList','blockquote','link','undo','redo'
                            ])
                            ->required(),
                    ])->columns(2),
                Forms\Components\Section::make('Media')
                    ->schema([
                        Forms\Components\FileUpload::make('header_image')
                            ->label('Header image')
                            ->image()
                            ->disk('public')
                            ->directory('tmp/announcements')
                            ->openable()
                            ->downloadable(false)
                            ->helperText('Upload an image; responsive variants are generated on save.'),
                    ]),
                Forms\Components\Section::make('Publishing')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->required()
                            ->options([
                                'draft' => 'Draft',
                                'published' => 'Published',
                            ])->native(false)
                            ->default('draft'),
                        Forms\Components\DateTimePicker::make('published_at')
                            ->seconds(false)
                            ->native(false),
                        Forms\Components\TextInput::make('meta_description')
                            ->label('Meta description')
                            ->maxLength(160),
                        Forms\Components\Toggle::make('is_pinned')->label('Pinned'),
                        Forms\Components\TextInput::make('display_order')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Only used when pinned.'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
                Tables\Columns\BadgeColumn::make('status')->colors([
                    'warning' => 'draft',
                    'success' => 'published',
                ])->sortable(),
                Tables\Columns\IconColumn::make('is_pinned')->boolean()->label('Pinned')->sortable(),
                Tables\Columns\TextColumn::make('display_order')->sortable(),
                Tables\Columns\TextColumn::make('published_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'published' => 'Published',
                ]),
                Tables\Filters\TernaryFilter::make('is_pinned')->placeholder('All')->trueLabel('Pinned')->falseLabel('Not pinned')
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
