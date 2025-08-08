<?php

namespace App\Domains\Admin\Filament\Resources;

use App\Domains\Admin\Filament\Resources\ActivationCodeResource\Pages;
use App\Domains\Auth\Models\ActivationCode;
use App\Domains\Auth\Models\User;
use App\Domains\Auth\Services\ActivationCodeService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Filters\SelectFilter;

class ActivationCodeResource extends Resource
{
    protected static ?string $model = ActivationCode::class;

    public static function getNavigationGroup(): ?string {
        return __('admin.user_management');
    }

    public static function getModelLabel(): string
    {
        return __('admin.activation_codes.model_label');
    }
    public static function getPluralModelLabel(): string
    {
        return __('admin.activation_codes.plural_label');
    }
    public static function getNavigationLabel(): string
    {
        return __('admin.activation_codes.navigation_label');
    }

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('sponsor_user_id')
                    ->label(__('admin.activation_codes.sponsor_user_label'))
                    ->options(User::all()->pluck('name', 'id'))
                    ->searchable()
                    ->nullable()
                    ->helperText(__('admin.activation_codes.sponsor_user_helper')),

                Textarea::make('comment')
                    ->label(__('admin.activation_codes.comment_label'))
                    ->rows(3)
                    ->placeholder(__('admin.activation_codes.comment_helper')),

                DateTimePicker::make('expires_at')
                    ->label(__('admin.activation_codes.expires_at_label'))
                    ->helperText(__('admin.activation_codes.expires_at_helper'))
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label(__('admin.activation_codes.code_header'))
                    ->copyable()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('sponsorUser.name')
                    ->label(__('admin.activation_codes.sponsor_header'))
                    ->placeholder(__('admin.activation_codes.placeholder.no_sponsor'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('admin.activation_codes.status_header'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => __('admin.activation_codes.status.active'),
                        'used' => __('admin.activation_codes.status.used'),
                        'expired' => __('admin.activation_codes.status.expired'),
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'expired' => 'warning',
                        'used' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('usedByUser.name')
                    ->label(__('admin.activation_codes.used_by_header'))
                    ->placeholder(__('admin.activation_codes.placeholder.not_used'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('used_at')
                    ->label(__('admin.activation_codes.used_at_header'))
                    ->dateTime()
                    ->placeholder(__('admin.activation_codes.placeholder.not_used'))
                    ->sortable(),

                TextColumn::make('expires_at')
                    ->label(__('admin.activation_codes.expires_at_header'))
                    ->dateTime()
                    ->placeholder(__('admin.activation_codes.placeholder.no_expiration'))
                    ->sortable(),

                TextColumn::make('comment')
                    ->label(__('admin.activation_codes.comment_header'))
                    ->limit(50)
                    ->placeholder(__('admin.activation_codes.placeholder.no_comment')),

                TextColumn::make('created_at')
                    ->label(__('admin.column.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'used' => 'Used',
                        'expired' => 'Expired',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'active' => $query->whereNull('used_at')
                                ->where(function ($q) {
                                    $q->whereNull('expires_at')
                                        ->orWhere('expires_at', '>', now());
                                }),
                            'used' => $query->whereNotNull('used_at'),
                            'expired' => $query->whereNull('used_at')
                                ->whereNotNull('expires_at')
                                ->where('expires_at', '<=', now()),
                            default => $query,
                        };
                    }),

                SelectFilter::make('sponsor_user_id')
                    ->label(__('admin.activation_codes.sponsor_user_label'))
                    ->options(User::all()->pluck('name', 'id'))
                    ->searchable(),
            ])
            ->actions([
                DeleteAction::make()
                    ->visible(fn (ActivationCode $record) => !$record->isUsed()),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivationCodes::route('/'),
            'create' => Pages\CreateActivationCode::route('/create'),
        ];
    }

    public static function canDelete($record): bool
    {
        // Only allow deletion of unused codes
        return !$record->isUsed();
    }
}
