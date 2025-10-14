<?php

namespace App\Domains\Admin\Filament\Resources\Config;

use App\Domains\Config\Private\Models\FeatureToggle as FeatureToggleModel;
use App\Domains\Config\Public\Contracts\ConfigPublicApi;
use App\Domains\Config\Public\Contracts\FeatureToggle as FeatureToggleContract;
use App\Domains\Config\Public\Contracts\FeatureToggleAccess;
use App\Domains\Config\Public\Contracts\FeatureToggleAdminVisibility;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class FeatureToggleResource extends Resource
{
    protected static ?string $model = FeatureToggleModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-vertical';

    protected static ?string $navigationGroup = null;

    public static function getNavigationLabel(): string
    {
        return __('admin::pages.feature_toggles.nav_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin::pages.groups.tech');
    }

    public static function table(Table $table): Table
    {
        $user = Auth::user();
        $isTech = $user?->hasRole('tech-admin') ?? false;

        return $table
            ->query(
                FeatureToggleModel::query()
                    ->when(!$isTech, fn ($q) => $q->where('admin_visibility', FeatureToggleAdminVisibility::ALL_ADMINS->value))
                    ->orderBy('domain')->orderBy('name')
            )
            ->paginated(false)
            ->columns([
                TextColumn::make('name')->label(__('admin::pages.feature_toggles.columns.name'))->searchable(),
                TextColumn::make('domain')->label(__('admin::pages.feature_toggles.columns.domain')),
                TextColumn::make('access')->label(__('admin::pages.feature_toggles.columns.access'))
                    ->colors([
                        'success' => fn ($state) => $state === 'on',
                        'danger' => fn ($state) => $state === 'off',
                        'warning' => fn ($state) => $state === 'role_based',
                    ])->badge()->formatStateUsing(fn ($state) => strtoupper($state)),
                TextColumn::make('admin_visibility')->label(__('admin::pages.feature_toggles.columns.admin_visibility'))
                    ->formatStateUsing(fn (string $state): string => str_replace('_',' ', $state)),
                TextColumn::make('roles')->label(__('admin::pages.feature_toggles.columns.roles'))
                    ->formatStateUsing(function ($state) {
                        if (empty($state)) return '—';
                        return implode(', ', (array) $state);
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('create')
                    ->label(__('admin::pages.feature_toggles.actions.create'))
                    ->icon('heroicon-o-plus')
                    ->visible(fn () => Auth::user()?->hasRole('tech-admin') ?? false)
                    ->form([
                        Forms\Components\TextInput::make('name')->label(__('admin::pages.feature_toggles.form.name'))->required()->maxLength(100),
                        Forms\Components\TextInput::make('domain')->label(__('admin::pages.feature_toggles.form.domain'))->default('config')->required()->maxLength(100),
                        Forms\Components\Select::make('admin_visibility')
                            ->label(__('admin::pages.feature_toggles.form.admin_visibility'))
                            ->options([
                                FeatureToggleAdminVisibility::TECH_ADMINS_ONLY->value => __('admin::pages.feature_toggles.admin_visibility.tech_admins_only'),
                                FeatureToggleAdminVisibility::ALL_ADMINS->value => __('admin::pages.feature_toggles.admin_visibility.all_admins'),
                            ])->default(FeatureToggleAdminVisibility::TECH_ADMINS_ONLY->value)->required(),
                        Forms\Components\Select::make('access')
                            ->label(__('admin::pages.feature_toggles.form.access'))
                            ->options([
                                FeatureToggleAccess::OFF->value => __('admin::pages.feature_toggles.access.off'),
                                FeatureToggleAccess::ON->value => __('admin::pages.feature_toggles.access.on'),
                                FeatureToggleAccess::ROLE_BASED->value => __('admin::pages.feature_toggles.access.role_based'),
                            ])->default(FeatureToggleAccess::OFF->value)->required(),
                        Forms\Components\Select::make('roles')
                            ->label(__('admin::pages.feature_toggles.form.roles'))
                            ->multiple()
                            ->searchable()
                            ->options(function () {
                                $roles = app(\App\Domains\Auth\Public\Api\AuthPublicApi::class)->getAllRoles();
                                $options = [];
                                foreach ($roles as $r) {
                                    /** @var \App\Domains\Auth\Public\Api\Dto\RoleDto $r */
                                    $options[$r->slug] = $r->name;
                                }
                                return $options;
                            })
                            ->helperText(__('admin::pages.feature_toggles.form.roles_helper')),
                    ])
                    ->action(function (array $data) {
                        /** @var ConfigPublicApi $api */
                        $api = app(ConfigPublicApi::class);
                        $feature = new FeatureToggleContract(
                            name: $data['name'],
                            domain: $data['domain'],
                            admin_visibility: FeatureToggleAdminVisibility::from($data['admin_visibility']),
                            access: FeatureToggleAccess::from($data['access']),
                            roles: $data['roles'] ?? [],
                        );
                        $api->addFeatureToggle($feature);
                        Notification::make()->title(__('admin::pages.feature_toggles.notifications.created'))->success()->send();
                    })
            ])
            ->actions([
                Tables\Actions\Action::make('on')->label(__('admin::pages.feature_toggles.actions.set_on'))->color('success')
                    ->action(function (FeatureToggleModel $record) {
                        app(ConfigPublicApi::class)->updateFeatureToggle($record->name, FeatureToggleAccess::ON, $record->domain);
                        Notification::make()->title(__('admin::pages.feature_toggles.notifications.updated'))->success()->send();
                    }),
                Tables\Actions\Action::make('off')->label(__('admin::pages.feature_toggles.actions.set_off'))->color('danger')
                    ->action(function (FeatureToggleModel $record) {
                        app(ConfigPublicApi::class)->updateFeatureToggle($record->name, FeatureToggleAccess::OFF, $record->domain);
                        Notification::make()->title(__('admin::pages.feature_toggles.notifications.updated'))->success()->send();
                    }),
                Tables\Actions\Action::make('role_based')->label(__('admin::pages.feature_toggles.actions.set_role_based'))->color('warning')
                    ->action(function (FeatureToggleModel $record) {
                        app(ConfigPublicApi::class)->updateFeatureToggle($record->name, FeatureToggleAccess::ROLE_BASED, $record->domain);
                        Notification::make()->title(__('admin::pages.feature_toggles.notifications.updated'))->success()->send();
                    }),
                Tables\Actions\Action::make('delete')->label(__('admin::pages.feature_toggles.actions.delete'))->icon('heroicon-o-trash')
                    ->visible(fn () => Auth::user()?->hasRole('tech-admin') ?? false)
                    ->requiresConfirmation()
                    ->action(function (FeatureToggleModel $record) {
                        app(ConfigPublicApi::class)->deleteFeatureToggle($record->name, $record->domain);
                        Notification::make()->title(__('admin::pages.feature_toggles.notifications.deleted'))->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Domains\Admin\Filament\Resources\Config\FeatureToggleResource\Pages\ListFeatureToggles::route('/'),
        ];
    }
}
