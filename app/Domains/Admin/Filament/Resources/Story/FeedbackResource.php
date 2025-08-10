<?php

namespace App\Domains\Admin\Filament\Resources\Story;

use App\Domains\Story\Models\StoryRefFeedback;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FeedbackResource extends Resource
{
    protected static ?string $model = StoryRefFeedback::class;
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $slug = 'story/feedbacks';

    public static function getNavigationGroup(): ?string
    {
        return __('admin::story.group');
    }

    public static function getModelLabel(): string
    {
        return __('admin::story.feedback.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin::story.feedback.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin::story.feedback.navigation_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->label(__('admin::story.shared.name'))->required()->maxLength(255),
                Forms\Components\TextInput::make('slug')->label(__('admin::story.shared.slug'))->helperText(__('admin::story.shared.slug_helper'))->maxLength(255),
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
            'index' => FeedbackResource\Pages\ListFeedbacks::route('/'),
            'create' => FeedbackResource\Pages\CreateFeedback::route('/create'),
            'edit' => FeedbackResource\Pages\EditFeedback::route('/{record}/edit'),
        ];
    }
}
