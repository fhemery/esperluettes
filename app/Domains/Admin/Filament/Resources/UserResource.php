<?php

namespace App\Domains\Admin\Filament\Resources;

use App\Domains\Admin\Filament\Resources\UserResource\Pages;
use App\Domains\Auth\Models\User;
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
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $navigationLabel = null;

    public static function getNavigationLabel(): string
    {
        return __('admin.users.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.users.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.users.plural_label');
    }
    public static function getNavigationGroup(): ?string {
        return __('admin.user_management');
    }
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('admin.users.name_header'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->label(__('admin.users.email_header'))
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('password')
                    ->label(__('admin.users.password_header'))
                    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->maxLength(255),
                Select::make('roles')
                    ->label(__('admin.users.roles_header'))
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label(__('admin.column.id'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('admin.users.name_header'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label(__('admin.users.email_header'))
                    ->searchable(),
                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label(__('admin.users.email_verified_at_header'))
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label(__('admin.users.roles_header'))
                    ->badge()
                    ->color('primary')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('admin.users.is_active_header'))
                    ->boolean()
                    ->sortable()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin.column.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('admin.column.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label(__('admin.users.is_active_header'))
                    ->options([
                        1 => __('admin.users.status.active'),
                        0 => __('admin.users.status.inactive'),
                    ])
                    ->placeholder('Tous les statuts'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('activate')
                    ->label(__('admin.users.actions.activate'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (User $record): bool => !$record->isActive())
                    ->requiresConfirmation()
                    ->modalHeading(__('admin.users.activation.confirm_title'))
                    ->modalDescription(__('admin.users.activation.confirm_message'))
                    ->action(function (User $record, UserActivationService $service) {
                        $service->activateUser($record);
                        Notification::make()
                            ->title(__('admin.users.activation.success'))
                            ->success()
                            ->send();
                    }),
                Action::make('deactivate')
                    ->label(__('admin.users.actions.deactivate'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (User $record): bool => $record->isActive())
                    ->requiresConfirmation()
                    ->modalHeading(__('admin.users.deactivation.confirm_title'))
                    ->modalDescription(__('admin.users.deactivation.confirm_message'))
                    ->action(function (User $record, UserActivationService $service) {
                        $service->deactivateUser($record);
                        Notification::make()
                            ->title(__('admin.users.deactivation.success'))
                            ->success()
                            ->send();
                    }),
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
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
