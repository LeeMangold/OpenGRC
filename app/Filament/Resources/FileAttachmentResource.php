<?php

namespace App\Filament\Resources;

use App\Filament\Exports\FileAttachmentExporter;
use App\Filament\Resources\FileAttachmentResource\Pages\CreateFileAttachment;
use App\Filament\Resources\FileAttachmentResource\Pages\EditFileAttachment;
use App\Filament\Resources\FileAttachmentResource\Pages\ListFileAttachments;
use App\Models\FileAttachment;
use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class FileAttachmentResource extends Resource
{
    protected static ?string $model = FileAttachment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                RichEditor::make('description')
                    ->disableToolbarButtons([
                        'image',
                        'attachFiles',
                    ])
                    ->required()
                    ->columnSpanFull(),
                FileUpload::make('file_path')
                    ->label('File')
                    ->preserveFilenames()
                    ->disk(setting('storage.driver', 'private'))
                    ->directory(function () {
                        $rand = Carbon::now()->timestamp.'-'.Str::random(2);

                        return 'attachments/'.$rand;
                    })
                    ->downloadable()
                    ->visibility('private')
                    ->openable()
                    ->deletable()
                    ->reorderable()
                    ->columnSpanFull()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->columns([
                TextColumn::make('description')
                    ->searchable()
                    ->sortable()
                    ->html()
                    ->limit()
                    ->wrap(),
                TextColumn::make('file_path')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('file_size')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('uploaded_by')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(FileAttachmentExporter::class)
                    ->icon('heroicon-o-arrow-down-tray'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exporter(FileAttachmentExporter::class)
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray'),
                    DeleteBulkAction::make(),
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
            'index' => ListFileAttachments::route('/'),
            'create' => CreateFileAttachment::route('/create'),
            'edit' => EditFileAttachment::route('/{record}/edit'),
        ];
    }
}
