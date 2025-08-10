<?php

namespace App\Domains\Admin\Filament\Resources\Story;

use App\Domains\Story\Models\StoryRefCopyright;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CopyrightResource extends Resource
{
    protected static ?string $model = StoryRefCopyright::class;
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $slug = 'story/copyrights';

    public static function getNavigationGroup(): ?string
    {
        return __('admin::story.group');
    }

    public static function getModelLabel(): string
    {
        return __('admin::story.copyright.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin::story.copyright.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin::story.copyright.navigation_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->label(__('admin::story.shared.name'))->required()->maxLength(255),
                Forms\Components\TextInput::make('slug')->label(__('admin::story.shared.slug'))->helperText(__('admin::story.shared.slug_helper'))->maxLength(255),
                Forms\Components\Textarea::make('description')->label(__('admin::story.shared.description'))->rows(3),
                Forms\Components\Toggle::make('is_active')->label(__('admin::story.shared.active'))->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('order')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label(__('admin::story.shared.name'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('slug')->label(__('admin::story.shared.slug'))->toggleable(isToggledHiddenByDefault: true)->copyable(),
                Tables\Columns\TextColumn::make('description')->label(__('admin::story.shared.description'))->wrap()->limit(80),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label(__('admin::story.shared.active'))->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton()->label(''),
                Tables\Actions\DeleteAction::make()->iconButton()->label(''),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('order');
    }

    public static function getPages(): array
    {
        return [
            'index' => CopyrightResource\Pages\ListCopyrights::route('/'),
            'create' => CopyrightResource\Pages\CreateCopyright::route('/create'),
            'edit' => CopyrightResource\Pages\EditCopyright::route('/{record}/edit'),
        ];
    }
}
