<?php

namespace App\Domains\Admin\Filament\Resources\Moderation;

use App\Domains\Admin\Filament\Resources\Moderation\ModerationReasonResource\Pages\CreateModerationReason;
use App\Domains\Admin\Filament\Resources\Moderation\ModerationReasonResource\Pages\EditModerationReason;
use App\Domains\Admin\Filament\Resources\Moderation\ModerationReasonResource\Pages\ListModerationReasons;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Moderation\Private\Models\ModerationReason;
use App\Domains\Moderation\Public\Services\ModerationRegistry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class ModerationReasonResource extends Resource
{
    protected static ?string $model = ModerationReason::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'moderation/reasons';

    public static function getNavigationGroup(): ?string
    {
        return __('admin::moderation.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('admin::moderation.reason.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin::moderation.reason.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin::moderation.reason.navigation_label');
    }

    public static function canAccess(): bool
    {
        /** @var \App\Domains\Auth\Private\Models\User|null $user */
        $user = \Illuminate\Support\Facades\Auth::user();
        return $user?->hasRole([Roles::ADMIN, Roles::TECH_ADMIN, Roles::MODERATOR]) ?? false;
    }

    public static function form(Form $form): Form
    {
        $registry = app(ModerationRegistry::class);
        $topics = $registry->getTopics();
        
        // Build topic options for select
        $topicOptions = [];
        foreach ($topics as $key => $config) {
            $topicOptions[$key] = $config['displayName'];
        }

        return $form
            ->schema([
                Forms\Components\Select::make('topic_key')
                    ->label(__('admin::moderation.reason.topic_key'))
                    ->options($topicOptions)
                    ->required()
                    ->searchable(),
                
                Forms\Components\TextInput::make('label')
                    ->label(__('admin::moderation.reason.label_field'))
                    ->required()
                    ->maxLength(255)
                    ->helperText(__('admin::moderation.reason.label_helper')),
                
                Forms\Components\Toggle::make('is_active')
                    ->label(__('admin::moderation.reason.is_active'))
                    ->default(true)
                    ->helperText(__('admin::moderation.reason.is_active_helper')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label(__('admin::shared.column.id'))
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('topic_key')
                    ->label(__('admin::moderation.reason.topic_key'))
                    ->formatStateUsing(function (string $state) {
                        $registry = app(ModerationRegistry::class);
                        $topic = $registry->getTopic($state);
                        return $topic['displayName'];
                    })
                    ->badge()
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('label')
                    ->label(__('admin::moderation.reason.label_field'))
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                
                Tables\Columns\TextColumn::make('sort_order')
                    ->label(__('admin::moderation.reason.sort_order'))
                    ->sortable()
                    ->alignCenter(),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('admin::moderation.reason.is_active'))
                    ->boolean()
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
            ->defaultSort('sort_order', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('topic_key')
                    ->label(__('admin::moderation.reason.filter_topic'))
                    ->options(function () {
                        $registry = app(ModerationRegistry::class);
                        $topics = $registry->getTopics();
                        $options = [];
                        foreach ($topics as $key => $config) {
                            $options[$key] = $config['displayName'];
                        }
                        return $options;
                    }),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('admin::moderation.reason.filter_active'))
                    ->placeholder(__('admin::moderation.reason.filter_all'))
                    ->trueLabel(__('admin::moderation.reason.filter_active_only'))
                    ->falseLabel(__('admin::moderation.reason.filter_inactive_only')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton()->label(''),
                Tables\Actions\DeleteAction::make()->iconButton()->label('')
                    ->before(function (ModerationReason $record) {
                        // Check if reason is used in any reports
                        $reportsCount = DB::table('moderation_reports')
                            ->where('reason_id', $record->id)
                            ->count();
                        
                        if ($reportsCount > 0) {
                            throw new \Exception(
                                __('admin::moderation.reason.cannot_delete_in_use', ['count' => $reportsCount])
                            );
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            foreach ($records as $record) {
                                $reportsCount = DB::table('moderation_reports')
                                    ->where('reason_id', $record->id)
                                    ->count();
                                
                                if ($reportsCount > 0) {
                                    throw new \Exception(
                                        __('admin::moderation.reason.cannot_delete_in_use', ['count' => $reportsCount])
                                    );
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
            'index' => ListModerationReasons::route('/'),
            'create' => CreateModerationReason::route('/create'),
            'edit' => EditModerationReason::route('/{record}/edit'),
        ];
    }
}
