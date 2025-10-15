<?php

namespace App\Domains\Admin\Filament\Resources\Auth;

use App\Domains\Admin\Filament\Resources\Auth\ActivationCodeResource\Pages;
use App\Domains\Auth\Private\Models\ActivationCode;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Enums\FiltersLayout;

class ActivationCodeResource extends Resource
{
    protected static ?string $model = ActivationCode::class;

    /**
     * Simple in-request cache for profile display names to avoid repeated lookups.
     * @var array<int, string|null>
     */
    protected static array $profileLabelCache = [];

    public static function getNavigationGroup(): ?string {
        return __('admin::auth.user_management');
    }

    public static function getModelLabel(): string
    {
        return __('admin::auth.activation_codes.model_label');
    }
    public static function getPluralModelLabel(): string
    {
        return __('admin::auth.activation_codes.plural_label');
    }
    public static function getNavigationLabel(): string
    {
        return __('admin::auth.activation_codes.navigation_label');
    }

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        /** @var \App\Domains\Auth\Private\Models\User|null $user */
        $user = \Illuminate\Support\Facades\Auth::user();
        return $user?->hasRole([Roles::ADMIN, Roles::TECH_ADMIN]) ?? false;
    }

    /**
     * Resolve the shared ProfilePublicApi once for use in static callbacks.
     */
    protected static function profileApi(): ProfilePublicApi
    {
        /** @var ProfilePublicApi $api */
        $api = app(ProfilePublicApi::class);
        return $api;
    }

    protected static function profileLabel(?int $userId): ?string
    {
        if (empty($userId)) {
            return null;
        }

        if (array_key_exists($userId, static::$profileLabelCache)) {
            return static::$profileLabelCache[$userId];
        }

        $dto = static::profileApi()->getPublicProfile((int) $userId);
        return static::$profileLabelCache[$userId] = $dto?->display_name;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('sponsor_user_id')
                    ->label(__('admin::auth.activation_codes.sponsor_user_label'))
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => static::profileApi()->searchDisplayNames($search, 50))
                    ->getOptionLabelUsing(fn ($value): ?string => empty($value)
                        ? null
                        : static::profileApi()->getPublicProfile((int) $value)?->display_name)
                    ->nullable()
                    ->helperText(__('admin::auth.activation_codes.sponsor_user_helper')),

                Textarea::make('comment')
                    ->label(__('admin::auth.activation_codes.comment_label'))
                    ->rows(3)
                    ->placeholder(__('admin::auth.activation_codes.comment_placeholder')),

                DateTimePicker::make('expires_at')
                    ->label(__('admin::auth.activation_codes.expires_at_label'))
                    ->helperText(__('admin::auth.activation_codes.expires_at_helper'))
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label(__('admin::auth.activation_codes.code_header'))
                    ->copyable()
                    ->sortable(),

                TextColumn::make('sponsor_user_id')
                    ->label(__('admin::auth.activation_codes.sponsor_header'))
                    ->placeholder(__('admin::auth.activation_codes.placeholder.no_sponsor'))
                    ->formatStateUsing(function ($state, $record) {
                        $label = static::profileLabel((int) $state);
                        return $label ?? __('admin::auth.activation_codes.placeholder.deleted');
                    }),

                TextColumn::make('status')
                    ->label(__('admin::auth.activation_codes.status_header'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => __('admin::auth.activation_codes.status.active'),
                        'used' => __('admin::auth.activation_codes.status.used'),
                        'expired' => __('admin::auth.activation_codes.status.expired'),
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'expired' => 'warning',
                        'used' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('used_by_user_id')
                    ->label(__('admin::auth.activation_codes.used_by_header'))
                    ->placeholder(__('admin::auth.activation_codes.placeholder.not_used'))
                    ->formatStateUsing(function ($state) {
                        $label = static::profileLabel((int) $state);
                        return $label ?? __('admin::auth.activation_codes.placeholder.deleted');
                    }),

                TextColumn::make('used_at')
                    ->label(__('admin::auth.activation_codes.used_at_header'))
                    ->dateTime()
                    ->placeholder(__('admin::auth.activation_codes.placeholder.not_used'))
                    ->sortable(),

                TextColumn::make('expires_at')
                    ->label(__('admin::auth.activation_codes.expires_at_header'))
                    ->dateTime()
                    ->placeholder(__('admin::auth.activation_codes.placeholder.no_expiration'))
                    ->sortable(),

                TextColumn::make('comment')
                    ->label(__('admin::auth.activation_codes.comment_header'))
                    ->limit(50)
                    ->placeholder(__('admin::auth.activation_codes.placeholder.no_comment')),

                TextColumn::make('created_at')
                    ->label(__('admin::shared.column.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filtersLayout(FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->filters([
                Filter::make('code')
                    ->label(__('admin::auth.activation_codes.code_header'))
                    ->form([
                        TextInput::make('value')
                            ->label(__('admin::auth.activation_codes.code_header'))
                            ->placeholder('...'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = trim((string)($data['value'] ?? ''));
                        if ($value === '') {
                            return $query;
                        }
                        return $query->where('code', 'like', '%' . $value . '%');
                    }),
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
                    ->label(__('admin::auth.activation_codes.sponsor_user_label'))
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => static::profileApi()->searchDisplayNames($search, 50))
                    ->getOptionLabelUsing(fn ($value): ?string => static::profileLabel((int) $value)),

                SelectFilter::make('used_by_user_id')
                    ->label(__('admin::auth.activation_codes.used_by_header'))
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => static::profileApi()->searchDisplayNames($search, 50))
                    ->getOptionLabelUsing(fn ($value): ?string => static::profileLabel((int) $value)),
            ])
            ->actions([
                DeleteAction::make()->iconButton()->label('')
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
