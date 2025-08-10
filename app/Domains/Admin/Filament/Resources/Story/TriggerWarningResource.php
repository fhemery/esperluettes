<?php

namespace App\Domains\Admin\Filament\Resources\Story;

use App\Domains\Story\Models\StoryRefTriggerWarning;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TriggerWarningResource extends Resource
{
    protected static ?string $model = StoryRefTriggerWarning::class;

    protected static ?string $navigationGroup = 'Story';
    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected static ?string $navigationLabel = 'Trigger Warnings';
    protected static ?string $slug = 'story/trigger-warnings';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                Forms\Components\TextInput::make('slug')->helperText('Leave empty to auto-generate from name')->maxLength(255),
                Forms\Components\Textarea::make('description')->rows(3),
                Forms\Components\Toggle::make('is_active')->label('Active')->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('order')
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('slug')->toggleable(isToggledHiddenByDefault: true)->copyable(),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label('Active')->sortable(),
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
            'index' => TriggerWarningResource\Pages\ListTriggerWarnings::route('/'),
            'create' => TriggerWarningResource\Pages\CreateTriggerWarning::route('/create'),
            'edit' => TriggerWarningResource\Pages\EditTriggerWarning::route('/{record}/edit'),
        ];
    }
}
