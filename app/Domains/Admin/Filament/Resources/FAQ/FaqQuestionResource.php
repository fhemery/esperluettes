<?php

namespace App\Domains\Admin\Filament\Resources\FAQ;

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\FAQ\Private\Models\FaqCategory;
use App\Domains\FAQ\Private\Models\FaqQuestion;
use App\Domains\FAQ\Public\Api\FaqPublicApi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class FaqQuestionResource extends Resource
{
    protected static ?string $model = FaqQuestion::class;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('admin::faq.navigation.group');
    }

    public static function getModelLabel(): string
    {
        return __('admin::faq.questions.resource.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin::faq.questions.resource.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin::faq.navigation.questions');
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
                Forms\Components\Section::make(__('admin::faq.questions.sections.details'))
                    ->schema([
                        Forms\Components\Select::make('faq_category_id')
                            ->label(__('admin::faq.questions.fields.category'))
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('description')
                                    ->rows(3),
                            ]),
                        Forms\Components\TextInput::make('question')
                            ->label(__('admin::faq.questions.fields.question'))
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn($state, callable $set) => $set('slug', Str::slug($state)))
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('slug')
                            ->label(__('admin::faq.questions.fields.slug'))
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('answer')
                            ->label(__('admin::faq.questions.fields.answer'))
                            ->required()
                            ->toolbarButtons([
                                'bold', 'italic', 'strike', 'underline', 
                                'bulletList', 'orderedList', 
                                'h2', 'h3', 'blockquote', 'link', 
                                'undo', 'redo'
                            ])
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('is_active')
                            ->label(__('admin::faq.questions.fields.is_active'))
                            ->default(true)
                            ->inline(false),
                    ])->columns(2),
                
                Forms\Components\Section::make(__('admin::faq.questions.sections.media'))
                    ->schema([
                        Forms\Components\Placeholder::make('image_preview')
                            ->label('')
                            ->content(function (callable $get) {
                                $path = $get('image_path');
                                if (!$path) return '';
                                $url = asset('storage/' . $path);
                                return new \Illuminate\Support\HtmlString(
                                    '<div class="mb-2 text-center"><img src="' . e($url) . '" alt="Question image" style="max-width:200px; height: auto; border-radius: .5rem; display:block; margin:0 auto;"/></div>'
                                );
                            })
                            ->reactive()
                            ->visible(fn(callable $get) => filled($get('image_path')) && empty($get('image')) && !$get('remove_image'))
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('image')
                            ->label(__('admin::faq.questions.fields.image'))
                            ->image()
                            ->disk('public')
                            ->directory('tmp/faq')
                            ->openable()
                            ->downloadable(false)
                            ->helperText(__('admin::faq.questions.help.image')),
                        Forms\Components\Toggle::make('remove_image')
                            ->label(__('admin::faq.questions.fields.remove_image'))
                            ->reactive()
                            ->visible(fn(callable $get) => filled($get('image_path')) && empty($get('image')))
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('image_alt_text')
                            ->label(__('admin::faq.questions.fields.image_alt_text'))
                            ->maxLength(255)
                            ->helperText(__('admin::faq.questions.fields.image_alt_text')),
                    ])->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('category.name')
                    ->label(__('admin::faq.questions.fields.category'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('question')
                    ->label(__('admin::faq.questions.fields.question'))
                    ->searchable()
                    ->limit(50)
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label(__('admin::faq.questions.fields.slug'))
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('image_path')
                    ->label(__('admin::faq.questions.fields.has_image'))
                    ->boolean()
                    ->trueIcon('heroicon-o-photo')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('gray'),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label(__('admin::faq.questions.fields.is_active'))
                    ->beforeStateUpdated(function ($record, $state) {
                        $api = app(FaqPublicApi::class);
                        // Optimistically toggle via API
                        if ($state) {
                            $api->activateQuestion($record->id);
                        } else {
                            $api->deactivateQuestion($record->id);
                        }
                    }),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label(__('admin::faq.questions.fields.sort_order'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin::faq.questions.fields.created_at'))
                    ->dateTime()
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('faq_category_id')
                    ->label(__('admin::faq.questions.filters.category.label'))
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('admin::faq.questions.filters.active.label'))
                    ->placeholder(__('admin::faq.questions.filters.active.all'))
                    ->trueLabel(__('admin::faq.questions.filters.active.true'))
                    ->falseLabel(__('admin::faq.questions.filters.active.false')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label(__('admin::faq.questions.bulk.activate_selected'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $api = app(FaqPublicApi::class);
                            foreach ($records as $record) {
                                $api->activateQuestion($record->id);
                            }
                        }),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label(__('admin::faq.questions.bulk.deactivate_selected'))
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $api = app(FaqPublicApi::class);
                            foreach ($records as $record) {
                                $api->deactivateQuestion($record->id);
                            }
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => FaqQuestionResource\Pages\ListFaqQuestions::route('/'),
            'create' => FaqQuestionResource\Pages\CreateFaqQuestion::route('/create'),
            'edit' => FaqQuestionResource\Pages\EditFaqQuestion::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('category');
    }
}
