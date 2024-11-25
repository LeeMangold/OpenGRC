<?php

namespace App\Filament\Resources\AuditResource\RelationManagers;

use App\Enums\WorkflowStatus;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';

    public function form(Form $form): Form
    {

        return $form
            ->schema([
                FileUpload::make('file_path')
                    ->label('File')
                    ->required()
                    ->multiple()
                    ->disk('private')
                    ->directory('attachments')
                    ->storeFileNamesIn('attachment_file_names'),
                TextInput::make('description')
                    ->label('Description')
                    ->required(),
                DateTimePicker::make('uploaded_at')
                    ->label('Uploaded At')
                    ->default(now())
                    ->required(),
                Select::make('status')
                    ->label('Status')
                    ->options([
                        'Pending' => 'Pending',
                        'Approved' => 'Approved',
                        'Rejected' => 'Rejected',
                    ])
                    ->default('Pending')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->description('Note: This table is not functional in the current release. This will be addressed in a future release. ')

            ->columns([
                Tables\Columns\TextColumn::make('file_name')->label("File Name")
                    ->getStateUsing(function ($record) {
                        return json_decode($record->attachment_file_names, true);
                    }),

                Tables\Columns\TextColumn::make('description')->html()->limit(100)->wrap(),

                Tables\Columns\TextColumn::make('created_at')->label('Uploaded At'),
                Tables\Columns\TextColumn::make('uploaded_by')
                    ->label('Uploaded By')
                    ->getStateUsing(function ($record) {
                        $user = User::find($record->uploaded_by);
                        return $user ? $user->name : 'Unknown';
                    }),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->disabled(function () {
                        return $this->getOwnerRecord()->status != WorkflowStatus::INPROGRESS;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

}
