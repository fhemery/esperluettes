<?php

namespace App\Domains\Admin\Filament\Resources\Shared;

use App\Domains\Admin\Filament\Resources\Shared\DomainEventResource\Pages;
use App\Domains\Shared\Models\DomainEvent;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;

class DomainEventResource extends Resource
{
    protected static ?string $model = DomainEvent::class;

    protected static ?string $navigationIcon = 'heroicon-o-bolt';
    protected static ?int $navigationSort = 99;

    public static function getNavigationLabel(): string
    {
        return __('admin.domain_events.navigation_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.domain_events.plural_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.domain_events.model_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.domain_events.navigation_group');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('occurred_at')
                    ->label(__('admin.domain_events.columns.occurred_at'))
                    ->dateTime(format: 'Y/m/d H:i:s')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('admin.domain_events.columns.event'))
                    ->searchable()
                    ->wrap()
                    ->formatStateUsing(fn (string $state): string => last(explode('\\', $state))),
                Tables\Columns\TextColumn::make('summary')
                    ->label(__('admin.domain_events.columns.summary'))
                    ->wrap()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('triggered_by_user_id')
                    ->label(__('admin.domain_events.columns.user_id'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('context_url')
                    ->label(__('admin.domain_events.columns.url'))
                    ->limit(60)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('context_ip')
                    ->label(__('admin.domain_events.columns.ip'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('payload')
                    ->label(__('admin.domain_events.columns.payload'))
                    ->formatStateUsing(fn ($state) => is_array($state) ? substr(json_encode($state), 0, 1000) : (string) $state)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('occurred_at', 'desc')
            ->filters([
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                TextEntry::make('id')
                    ->label(__('admin.domain_events.columns.id')),
                TextEntry::make('occurred_at')
                    ->label(__('admin.domain_events.columns.occurred_at'))
                    ->dateTime('Y/m/d H:i:s'),
                TextEntry::make('name')
                    ->label(__('admin.domain_events.columns.event')),
                TextEntry::make('summary')
                    ->label(__('admin.domain_events.columns.summary')),
                TextEntry::make('triggered_by_user_id')
                    ->label(__('admin.domain_events.columns.user_id')),
                TextEntry::make('context_url')
                    ->label(__('admin.domain_events.columns.url')),
                TextEntry::make('context_ip')
                    ->label(__('admin.domain_events.columns.ip')),
                TextEntry::make('context_user_agent')
                    ->label(__('admin.domain_events.columns.user_agent'))
                    ->columnSpanFull(),
                TextEntry::make('payload')
                    ->label(__('admin.domain_events.columns.payload'))
                    ->formatStateUsing(fn ($state) => is_array($state)
                        ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                        : (string) $state)
                    ->columnSpanFull()
                    ->copyable(),
                TextEntry::make('meta')
                    ->label(__('admin.domain_events.columns.meta'))
                    ->formatStateUsing(fn ($state) => is_array($state)
                        ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                        : (string) $state)
                    ->columnSpanFull()
                    ->copyable(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDomainEvents::route('/'),
            'view' => Pages\ViewDomainEvent::route('/{record}'),
        ];
    }
}
