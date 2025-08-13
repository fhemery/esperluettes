<?php

namespace App\Domains\Admin\Filament\Resources\News;

use App\Domains\News\Models\News;
use App\Domains\Admin\Filament\Resources\News\PinnedNewsResource\Pages\ReorderPinnedNews;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;

class PinnedNewsResource extends Resource
{
    protected static ?string $model = News::class;

    protected static ?string $navigationIcon = 'heroicon-o-bars-3';

    public static function getNavigationGroup(): ?string
    {
        return __('admin::news.navigation.group');
    }

    public static function getModelLabel(): string
    {
        return __('admin::news.resource.pinned_order_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin::news.navigation.pinned_order');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('display_order')
            ->columns([
                Tables\Columns\TextColumn::make('title')->label(__('admin::news.fields.title')),
                Tables\Columns\TextColumn::make('display_order')->label(__('admin::news.fields.display_order')),
                Tables\Columns\TextColumn::make('status')->label(__('admin::news.fields.status'))->badge()->colors([
                    'warning' => 'draft',
                    'success' => 'published',
                ]),
                Tables\Columns\TextColumn::make('published_at')->label(__('admin::news.fields.published_at'))->dateTime()->since(),
            ])
            ->defaultSort('display_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => ReorderPinnedNews::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('is_pinned', true)
            ->orderBy('display_order');
    }
}
