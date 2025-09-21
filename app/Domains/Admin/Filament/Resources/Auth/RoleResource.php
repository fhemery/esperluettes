<?php

namespace App\Domains\Admin\Filament\Resources\Auth;

use App\Domains\Admin\Filament\Resources\Auth\RoleResource\Pages;
use App\Domains\Auth\Private\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RoleResource extends Resource
{
    public static function getNavigationGroup(): ?string {
        return __('admin::auth.user_management');
    }
    public static function getModelLabel(): string
    {
        return __('admin::auth.role.label');
    }
    public static function getPluralModelLabel(): string
    {
        return __('admin::auth.role.plural_label');
    }
    public static function getNavigationLabel(): string
    {
        return __('admin::auth.role.navigation_label');
    }
    protected static ?string $model = Role::class;
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?int $navigationSort = 2;
    protected static ?string $slug = 'roles';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('admin::auth.role.name_header'))
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\Textarea::make('description')
                    ->label(__('admin::auth.role.description_header'))
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label(__('admin::shared.column.id'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('admin::auth.role.name_header'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('users_count')
                    ->counts('users')
                    ->label(__('admin::auth.role.users_count_header'))
                    ->sortable(),
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
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton()->label(''),
                Tables\Actions\DeleteAction::make()->iconButton()->label('')
                    ->before(function (Role $record) {
                        // Prevent deleting roles that are in use
                        if ($record->users()->count() > 0) {
                            throw new \Exception('Cannot delete a role that is assigned to users.');
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            foreach ($records as $record) {
                                if ($record->users()->count() > 0) {
                                    throw new \Exception('Cannot delete a role that is assigned to users.');
                                }
                            }
                        }),
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
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
