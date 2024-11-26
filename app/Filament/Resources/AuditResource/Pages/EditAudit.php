<?php

namespace App\Filament\Resources\AuditResource\Pages;

use App\Filament\Resources\AuditResource;
use App\Models\Standard;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;

class EditAudit extends EditRecord
{
    protected static string $resource = AuditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Edit Audit Details')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->hint('Give the audit a distinctive title.')
                            ->default('My Title Here - DELETE ME')
                            ->required()
                            ->columns(1)
                            ->placeholder('2023 SOC 2 Type II Audit')
                            ->maxLength(255),
                        Select::make('manager_id')
                            ->label('Audit Manager')
                            ->hint('Who will be managing this audit?')
                            ->options(Standard::query()->where('status', 'In Scope')->pluck('name', 'id')->toArray())
                            ->columns(1)
                            ->searchable(),
                        Textarea::make('description')
                            ->columnSpanFull(),
                        DatePicker::make('start_date')
                            ->default(now())
                            ->required(),
                        DatePicker::make('end_date')
                            ->default(now()->addDays(30))
                            ->required(),
                    ]),
            ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', [$this->record]);
    }
}
