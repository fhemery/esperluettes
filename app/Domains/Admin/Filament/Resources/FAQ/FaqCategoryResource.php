<?php

namespace App\Domains\Admin\Filament\Resources\FAQ;

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\FAQ\Private\Models\FaqCategory;
use App\Domains\FAQ\Public\Api\FaqPublicApi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class FaqCategoryResource extends Resource
{
    protected static ?string $model = FaqCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('admin::faq.navigation.group');
    }

    public static function getModelLabel(): string
    {
        return __('admin::faq.categories.resource.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin::faq.categories.resource.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin::faq.navigation.categories');
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
                Forms\Components\Section::make(__('admin::faq.categories.sections.details'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('admin::faq.categories.fields.name'))
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn($state, callable $set) => $set('slug', Str::slug($state))),
                        Forms\Components\TextInput::make('slug')
                            ->label(__('admin::faq.categories.fields.slug'))
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\Textarea::make('description')
                            ->label(__('admin::faq.categories.fields.description'))
                            ->rows(3)
                            ->maxLength(1000),
                        Forms\Components\Toggle::make('is_active')
                            ->label(__('admin::faq.categories.fields.is_active'))
                            ->default(true)
                            ->inline(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('admin::faq.categories.fields.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label(__('admin::faq.categories.fields.slug'))
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('questions_count')
                    ->label(__('admin::faq.categories.fields.questions'))
                    ->counts('questions')
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label(__('admin::faq.categories.fields.is_active'))
                    ->beforeStateUpdated(function ($record, $state) {
                        $api = app(FaqPublicApi::class);
                        if ($state) {
                            // Will be activated - no action needed, toggle will update
                        } else {
                            // Will be deactivated - no action needed, toggle will update
                        }
                    })
                    ->afterStateUpdated(function ($record, $state) {
                        // Filament already updated the model, we just trigger a refresh
                        // Since we're not using a separate activate/deactivate API for categories,
                        // the toggle column's default behavior (updating the model) is fine
                    }),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label(__('admin::faq.categories.fields.sort_order'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin::faq.categories.fields.created_at'))
                    ->dateTime()
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('admin::faq.categories.filters.active.label'))
                    ->placeholder(__('admin::faq.categories.filters.active.all'))
                    ->trueLabel(__('admin::faq.categories.filters.active.true'))
                    ->falseLabel(__('admin::faq.categories.filters.active.false')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => FaqCategoryResource\Pages\ListFaqCategories::route('/'),
            'create' => FaqCategoryResource\Pages\CreateFaqCategory::route('/create'),
            'edit' => FaqCategoryResource\Pages\EditFaqCategory::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('questions');
    }
}
