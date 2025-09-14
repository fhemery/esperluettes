<?php

namespace App\Domains\Admin\Filament\Resources\Auth;

use App\Domains\Admin\Filament\Resources\Auth\UserResource\Pages;
use App\Domains\Auth\Models\User;
use App\Domains\Auth\Models\Role;
use App\Domains\Auth\PublicApi\Roles;
use App\Domains\Auth\Services\RoleService;
use App\Domains\Auth\Services\UserActivationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class UserResource extends Resource
{
    protected static ?string $navigationLabel = null;

    public static function getNavigationLabel(): string
    {
        return __('admin::auth.users.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin::auth.users.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin::auth.users.plural_label');
    }
    public static function getNavigationGroup(): ?string {
        return __('admin::auth.user_management');
    }
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Read-only display of the Profile display name (joined alias), not persisted
                Forms\Components\TextInput::make('profile_display_name')
                    ->label(__('admin::auth.users.name_header'))
                    ->disabled()
                    ->dehydrated(false)
                    ->afterStateHydrated(function ($component, $record) {
                        if ($record) {
                            $name = $record->profile_display_name ?? null;
                            if ($name === null) {
                                $name = DB::table('users as u')
                                    ->leftJoin('profile_profiles as pp', 'pp.user_id', '=', 'u.id')
                                    ->where('u.id', $record->id)
                                    ->value('pp.display_name');
                            }
                            $component->state($name ?? '');
                        }
                    }),
                Forms\Components\TextInput::make('email')
                    ->label(__('admin::auth.users.email_header'))
                    ->email()
                    ->required()
                    ->maxLength(255),
                Select::make('roles')
                    ->label(__('admin::auth.users.roles_header'))
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->saveRelationshipsUsing(function (User $record, ?array $state) {
                        // $state is an array of role IDs selected in the form
                        $selectedIds = collect($state ?? [])->map(fn ($v) => (int) $v)->filter()->values();

                        $selectedSlugs = Role::query()->whereIn('id', $selectedIds)->pluck('slug')->all();
                        $currentSlugs = $record->roles()->pluck('slug')->all();

                        $toGrant = array_values(array_diff($selectedSlugs, $currentSlugs));
                        $toRevoke = array_values(array_diff($currentSlugs, $selectedSlugs));

                        /** @var RoleService $roles */
                        $roles = app(RoleService::class);
                        foreach ($toGrant as $slug) {
                            $roles->grant($record, $slug);
                        }
                        foreach ($toRevoke as $slug) {
                            $roles->revoke($record, $slug);
                        }
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query
                    ->leftJoin('profile_profiles as pp', 'pp.user_id', '=', 'users.id')
                    ->select('users.*')
                    ->selectRaw('pp.display_name as profile_display_name');
            })
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label(__('admin::shared.column.id'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('profile_display_name')
                    ->label(__('admin::auth.users.name_header'))
                    ->sortable()
                    ->searchable(query: function (Builder $query, string $search) {
                        $query->orWhere('pp.display_name', 'like', "%{$search}%");
                    }),
                Tables\Columns\TextColumn::make('email')
                    ->label(__('admin::auth.users.email_header'))
                    ->searchable(),
                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label(__('admin::auth.users.email_verified_at_header'))
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label(__('admin::auth.users.roles_header'))
                    ->badge()
                    ->color('primary')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('admin::auth.users.is_active_header'))
                    ->boolean()
                    ->sortable()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin::shared.column.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('admin::shared.column.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label(__('admin::auth.users.is_active_header'))
                    ->options([
                        1 => __('admin::auth.users.status.active'),
                        0 => __('admin::auth.users.status.inactive'),
                    ])
                    ->placeholder('Tous les statuts'),
            ])
            ->actions([
                Action::make('activate')
                    ->label(__('admin::auth.users.actions.activate'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (User $record): bool => !$record->isActive())
                    ->requiresConfirmation()
                    ->modalHeading(__('admin::auth.users.activation.confirm_title'))
                    ->modalDescription(__('admin::auth.users.activation.confirm_message'))
                    ->action(function (User $record, UserActivationService $service) {
                        $service->activateUser($record);
                        Notification::make()
                            ->title(__('admin::auth.users.activation.success'))
                            ->success()
                            ->send();
                    }),
                Action::make('promote')
                    ->label(__('admin::auth.users.promote.action_label'))
                    ->icon('heroicon-o-arrow-up-circle')
                    ->color('primary')
                    ->visible(fn (User $record): bool => $record->hasRole(Roles::USER))
                    ->requiresConfirmation()
                    ->modalHeading(__('admin::auth.users.promote.confirm_title'))
                    ->modalDescription(function () {
                        $from = Role::where('slug', Roles::USER)->value('name') ?? 'user';
                        $to = Role::where('slug', Roles::USER_CONFIRMED)->value('name') ?? Roles::USER_CONFIRMED;
                        return __('admin::auth.users.promote.confirm_message', ['from' => $from, 'to' => $to]);
                    })
                    ->action(function (User $record, RoleService $roles) {
                        if ($record->hasRole(Roles::USER)) {
                            $roles->revoke($record, Roles::USER);
                        }
                        if (!$record->hasRole(Roles::USER_CONFIRMED)) {
                            $roles->grant($record, Roles::USER_CONFIRMED);
                        }
                        Notification::make()
                            ->title(function () {
                                $role = Role::where('slug', Roles::USER_CONFIRMED)->value('name') ?? Roles::USER_CONFIRMED;
                                return __('admin::auth.users.promote.success', ['role' => $role]);
                            })
                            ->success()
                            ->send();
                    }),

                Action::make('deactivate')
                    ->label(__('admin::auth.users.actions.deactivate'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (User $record): bool => $record->isActive())
                    ->requiresConfirmation()
                    ->modalHeading(__('admin::auth.users.deactivation.confirm_title'))
                    ->modalDescription(__('admin::auth.users.deactivation.confirm_message'))
                    ->action(function (User $record, UserActivationService $service) {
                        $service->deactivateUser($record);
                        Notification::make()
                            ->title(__('admin::auth.users.deactivation.success'))
                            ->success()
                            ->send();
                    }),
                    Tables\Actions\EditAction::make()->iconButton()->label(''),
                    Tables\Actions\DeleteAction::make()->iconButton()->label('')
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListUsers::route('/'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
