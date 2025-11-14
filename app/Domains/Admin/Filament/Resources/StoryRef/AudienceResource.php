<?php

namespace App\Domains\Admin\Filament\Resources\StoryRef;

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\StoryRef\Private\Models\StoryRefAudience;
use App\Domains\StoryRef\Public\Api\StoryRefPublicApi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AudienceResource extends Resource
{
    protected static ?string $model = StoryRefAudience::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $slug = 'story/audiences';

    public static function canAccess(): bool
    {
        /** @var \App\Domains\Auth\Private\Models\User|null $user */
        $user = \Illuminate\Support\Facades\Auth::user();
        return $user?->hasRole([Roles::ADMIN, Roles::TECH_ADMIN]) ?? false;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin::story.group');
    }

    public static function getModelLabel(): string
    {
        return __('admin::story.audience.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin::story.audience.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin::story.audience.navigation_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('admin::story.shared.name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('slug')
                    ->label(__('admin::story.shared.slug'))
                    ->helperText(__('admin::story.shared.slug_helper'))
                    ->maxLength(255),
                Forms\Components\Toggle::make('is_active')
                    ->label(__('admin::story.shared.active'))
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('order')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label(__('admin::story.shared.name'))->searchable(),
                Tables\Columns\TextColumn::make('slug')->label(__('admin::story.shared.slug'))->toggleable(isToggledHiddenByDefault: true)->copyable(),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label(__('admin::story.shared.active')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton()->label(''),
                Tables\Actions\DeleteAction::make()->iconButton()->label('')
                    ->after(fn() => app(StoryRefPublicApi::class)->clearUiCache()),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->after(fn() => app(StoryRefPublicApi::class)->clearUiCache()),
            ])
            ->defaultSort('order');
    }

    public static function getPages(): array
    {
        return [
            'index' => AudienceResource\Pages\ListAudiences::route('/'),
            'create' => AudienceResource\Pages\CreateAudience::route('/create'),
            'edit' => AudienceResource\Pages\EditAudience::route('/{record}/edit'),
        ];
    }
}
