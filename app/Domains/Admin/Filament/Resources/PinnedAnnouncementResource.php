<?php

namespace App\Domains\Admin\Filament\Resources;

use App\Domains\Announcement\Models\Announcement;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;

class PinnedAnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    protected static ?string $navigationIcon = 'heroicon-o-bars-3';

    protected static ?string $navigationGroup = 'Content';

    protected static ?string $modelLabel = 'Pinned Announcement Order';

    protected static ?string $navigationLabel = 'Pinned Order';

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('display_order')
            ->columns([
                Tables\Columns\TextColumn::make('title'),
                Tables\Columns\TextColumn::make('display_order'),
                Tables\Columns\TextColumn::make('status')->badge()->colors([
                    'warning' => 'draft',
                    'success' => 'published',
                ]),
                Tables\Columns\TextColumn::make('published_at')->dateTime()->since(),
            ])
            ->defaultSort('display_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => PinnedAnnouncementResource\Pages\ReorderPinnedAnnouncements::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('is_pinned', true)
            ->orderBy('display_order');
    }
}
