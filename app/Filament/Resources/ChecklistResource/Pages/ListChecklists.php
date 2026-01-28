<?php

namespace App\Filament\Resources\ChecklistResource\Pages;

use App\Filament\Resources\ChecklistResource;
use App\Filament\Resources\ChecklistTemplateResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListChecklists extends ListRecords
{
    protected static string $resource = ChecklistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ActionGroup::make([
                Action::make('all_templates')
                    ->label(__('checklist.checklist.actions.all_templates'))
                    ->icon('heroicon-o-queue-list')
                    ->url(ChecklistTemplateResource::getUrl('index')),
                Action::make('create_template')
                    ->label(__('checklist.checklist.actions.create_template'))
                    ->icon('heroicon-o-plus')
                    ->url(ChecklistTemplateResource::getUrl('create')),
            ])
                ->label(__('checklist.checklist.actions.templates'))
                ->icon('heroicon-o-clipboard-document-list')
                ->color('gray')
                ->button(),
            Action::make('help')
                ->icon('heroicon-o-question-mark-circle')
                ->iconButton()
                ->color('gray')
                ->url('https://docs.opengrc.com/features/checklists/')
                ->openUrlInNewTab(),
        ];
    }
}
