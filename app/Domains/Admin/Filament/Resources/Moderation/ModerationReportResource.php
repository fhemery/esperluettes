<?php

namespace App\Domains\Admin\Filament\Resources\Moderation;

use App\Domains\Admin\Filament\Resources\Moderation\ModerationReportResource\Pages\EditModerationReport;
use App\Domains\Admin\Filament\Resources\Moderation\ModerationReportResource\Pages\ListModerationReports;
use App\Domains\Moderation\Models\ModerationReason;
use App\Domains\Moderation\Models\ModerationReport;
use App\Domains\Moderation\Private\Services\ModerationService;
use App\Domains\Moderation\Public\Services\ModerationRegistry;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Form;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class ModerationReportResource extends Resource
{
    protected static ?string $model = ModerationReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';

    public static function getNavigationGroup(): ?string
    {
        return __('admin::moderation.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('admin::moderation.reports.label');
    }

     public static function getPluralModelLabel(): string
    {
        return __('admin::moderation.reports.plural_label');
    }


    public static function getNavigationLabel(): string
    {
        return __('admin::moderation.reports.navigation_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('topic_key')->label(__('admin::moderation.reports.fields.topic'))
                    ->formatStateUsing(function (string $state) {
                        $registry = app(ModerationRegistry::class);
                        $topic = $registry->getTopic($state);
                        return $topic['displayName'];
                    })    
                    ->disabled(),
                TextInput::make('entity_id')->label(__('admin::moderation.reports.fields.entity'))
                    ->disabled(),
                TextInput::make('reason_id')
                    ->label(__('admin::moderation.reports.fields.reason'))
                    ->formatStateUsing(function (int $state) {
                        $reason = ModerationReason::find($state);
                        return $reason?->label;
                    })
                    ->disabled(),
                Textarea::make('description')
                    ->label(__('admin::moderation.reports.fields.description'))
                    ->readOnly()
                    ->disabled(),
                
                Textarea::make('review_comment')
                    ->label(__('admin::moderation.reports.fields.review_comment'))
                    ->helperText(__('admin::moderation.reports.fields.review_comment_hint')),

                Placeholder::make('snapshot_render')
                    ->label(__('admin::moderation.reports.fields.snapshot'))
                    ->content(function ($record) {
                        if (! $record) {
                            return null;
                        }

                        /** @var ModerationRegistry $registry */
                        $registry = app(ModerationRegistry::class);
                        if (! $registry->hasFormatter($record->topic_key)) {
                            return null;
                        }

                        $snapshot = $record->content_snapshot ?? [];
                        if (empty($snapshot)) {
                            return null;
                        }

                        $formatter = $registry->getFormatter($record->topic_key);
                        $html = (string) $formatter->render($snapshot);
                        if (trim($html) === '') {
                            return null;
                        }

                        return new HtmlString($html);
                    })
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable()->label('ID'),
                Tables\Columns\TextColumn::make('topic_key')->label(__('admin::moderation.reports.fields.topic'))
                    ->formatStateUsing(function (string $state) {
                        $registry = app(ModerationRegistry::class);
                        $topic = $registry->getTopic($state);
                        return $topic['displayName'];
                    })    
                    ->badge()->sortable()->searchable(),
                Tables\Columns\TextColumn::make('entity_id')->label(__('admin::moderation.reports.fields.entity'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('reason.label')->label(__('admin::moderation.reports.fields.reason'))
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('description')->label(__('admin::moderation.reports.fields.description'))
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('review_comment')->label(__('admin::moderation.reports.fields.review_comment'))
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('reported_by_user_id')->label(__('admin::moderation.reports.fields.reported_by'))
                    ->formatStateUsing(function (int $state) {
                        $profileApi = app(ProfilePublicApi::class);
                        $profile = $profileApi->getPublicProfile($state);
                        return $profile?->display_name ?? __('admin::moderation.reports.anonymous');
                    })
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')->label(__('admin::moderation.reports.fields.status'))
                    ->badge()->colors([
                        'warning' => 'pending',
                        'success' => 'confirmed',
                        'danger' => 'dismissed',
                    ])->formatStateUsing(function (string $state) {
                        return __('admin::moderation.reports.status.' . $state);
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label(__('admin::moderation.reports.fields.created_at'))
                    ->dateTime()->since()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('topic_key')
                    ->label(__('admin::moderation.reports.fields.topic'))
                    ->options(fn() => ModerationReport::query()->distinct()->pluck('topic_key', 'topic_key')->toArray()),
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('admin::moderation.reports.fields.status'))
                    ->options([
                        'pending' => __('admin::moderation.reports.status.pending'),
                        'confirmed' => __('admin::moderation.reports.status.confirmed'),
                        'dismissed' => __('admin::moderation.reports.status.dismissed'),
                    ])
                    ->default('pending'),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->iconButton()
                    ->tooltip(__('admin::moderation.reports.actions.open'))
                    ->visible(fn(ModerationReport $record) => filled($record->content_url))
                    ->url(fn(ModerationReport $record) => $record->content_url, shouldOpenInNewTab: true),
                Tables\Actions\Action::make('approve')
                    ->label('')
                    ->icon('heroicon-o-check')
                    ->iconButton()
                    ->tooltip(__('admin::moderation.reports.actions.approve'))
                    ->visible(fn(ModerationReport $record) => $record->status === 'pending')
                    ->action(fn(ModerationReport $record) => app(ModerationService::class)->approveReport($record->id)),
                Tables\Actions\Action::make('dismiss')
                    ->label('')
                    ->icon('heroicon-o-x-mark')
                    ->iconButton()
                    ->tooltip(__('admin::moderation.reports.actions.dismiss'))
                    ->visible(fn(ModerationReport $record) => $record->status === 'pending')
                    ->action(fn(ModerationReport $record) => app(ModerationService::class)->dismissReport($record->id)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // no destructive bulk actions for now
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->latest('id');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListModerationReports::route('/'),
            'edit' => EditModerationReport::route('/{record}/edit'),
        ];
    }
}
