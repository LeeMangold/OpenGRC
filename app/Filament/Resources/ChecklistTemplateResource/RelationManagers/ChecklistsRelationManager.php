<?php

namespace App\Filament\Resources\ChecklistTemplateResource\RelationManagers;

use App\Enums\SurveyStatus;
use App\Enums\SurveyType;
use App\Filament\Resources\ChecklistResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ChecklistsRelationManager extends RelationManager
{
    protected static string $relationship = 'surveys';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $title = 'Checklists';

    public function form(Schema $schema): Schema
    {
        return ChecklistResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->modifyQueryUsing(fn (Builder $query) => $query->where('type', SurveyType::INTERNAL_CHECKLIST))
            ->columns([
                TextColumn::make('display_title')
                    ->label(__('checklist.checklist.table.columns.title'))
                    ->sortable(['title']),
                TextColumn::make('assignedTo.name')
                    ->label(__('checklist.checklist.table.columns.assigned_to'))
                    ->wrap(),
                TextColumn::make('status')
                    ->label(__('checklist.checklist.table.columns.status'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('progress')
                    ->label(__('checklist.checklist.table.columns.progress'))
                    ->suffix('%')
                    ->sortable(),
                IconColumn::make('approved_at')
                    ->label(__('checklist.checklist.table.columns.approved'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
                TextColumn::make('due_date')
                    ->label(__('checklist.checklist.table.columns.due_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('checklist.checklist.table.columns.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(SurveyStatus::class),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        $data['created_by_id'] = auth()->id();
                        $data['type'] = SurveyType::INTERNAL_CHECKLIST;

                        return $data;
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn ($record) => ChecklistResource::getUrl('view', ['record' => $record])),
                EditAction::make()
                    ->url(fn ($record) => ChecklistResource::getUrl('edit', ['record' => $record])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
