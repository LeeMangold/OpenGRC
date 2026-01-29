<?php

namespace App\Filament\Resources;

use App\Enums\SurveyStatus;
use App\Enums\SurveyTemplateStatus;
use App\Enums\VendorRiskRating;
use App\Enums\VendorStatus;
use App\Filament\Exports\VendorExporter;
use App\Filament\Resources\VendorResource\Pages\CreateVendor;
use App\Filament\Resources\VendorResource\Pages\EditVendor;
use App\Filament\Resources\VendorResource\Pages\ListVendors;
use App\Filament\Resources\VendorResource\Pages\ViewVendor;
use App\Filament\Resources\VendorResource\RelationManagers\ApplicationsRelationManager;
use App\Filament\Resources\VendorResource\RelationManagers\ImplementationsRelationManager;
use App\Filament\Resources\VendorResource\RelationManagers\SurveysRelationManager;
use App\Filament\Resources\VendorResource\RelationManagers\VendorDocumentsRelationManager;
use App\Filament\Resources\VendorResource\RelationManagers\VendorUsersRelationManager;
use App\Mail\SurveyInvitationMail;
use App\Models\Survey;
use App\Models\SurveyTemplate;
use App\Models\User;
use App\Models\Vendor;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class VendorResource extends Resource
{
    protected static ?string $model = Vendor::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    // Hide from navigation - access via Vendor Manager
    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationLabel(): string
    {
        return __('Vendors');
    }

    public static function getNavigationGroup(): string
    {
        return __('Entities');
    }

    public static function getModelLabel(): string
    {
        return __('Vendor');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Vendors');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Vendor Information'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('Name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('url')
                            ->label(__('Website URL'))
                            ->url()
                            ->maxLength(512),
                        Textarea::make('description')
                            ->label(__('Description'))
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make(__('Status & Risk'))
                    ->schema([
                        Select::make('status')
                            ->label(__('Status'))
                            ->enum(VendorStatus::class)
                            ->options(collect(VendorStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()]))
                            ->required(),
                        Select::make('risk_rating')
                            ->label(__('Organizational Risk Rating'))
                            ->helperText(__('The potential impact this vendor could have on your organization before any controls or mitigations are applied.'))
                            ->enum(VendorRiskRating::class)
                            ->options(collect(VendorRiskRating::cases())->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()]))
                            ->required(),
                    ])
                    ->columns(2),

                Section::make(__('Management'))
                    ->schema([
                        Select::make('vendor_manager_id')
                            ->label(__('Vendor Relationship Manager'))
                            ->helperText(__('Internal owner responsible for managing this vendor relationship'))
                            ->relationship('vendorManager', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->columns(1),

                Section::make(__('Vendor Contact'))
                    ->description(__('Primary contact at the vendor organization'))
                    ->schema([
                        TextInput::make('contact_name')
                            ->label(__('Contact Name'))
                            ->helperText(__('Main point of contact at the vendor'))
                            ->maxLength(255),
                        TextInput::make('contact_email')
                            ->label(__('Contact Email'))
                            ->email()
                            ->maxLength(255),
                        TextInput::make('contact_phone')
                            ->label(__('Contact Phone'))
                            ->tel()
                            ->maxLength(255),
                        Textarea::make('address')
                            ->label(__('Physical Address'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Section::make(__('Additional Information'))
                    ->schema([
                        Textarea::make('notes')
                            ->label(__('Notes'))
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        FileUpload::make('logo')
                            ->label(__('Logo'))
                            ->disk(config('filesystems.default'))
                            ->directory('vendor-logos')
                            ->storeFileNamesIn('logo')
                            ->visibility('private')
                            ->maxSize(1024)
                            ->deletable()
                            ->deleteUploadedFileUsing(function ($state) {
                                if ($state) {
                                    Storage::disk(config('filesystems.default'))->delete($state);
                                }
                            }),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('name')
                            ->hiddenLabel()
                            ->size(TextSize::Large)
                            ->weight('bold')
                            ->columnSpanFull(),
                        TextEntry::make('status')
                            ->label(__('Status'))
                            ->badge()
                            ->color(fn (Vendor $record) => $record->status->getColor()),
                        TextEntry::make('risk_rating')
                            ->label(__('Organizational Risk'))
                            ->badge()
                            ->color(fn (Vendor $record) => $record->risk_rating->getColor()),
                        TextEntry::make('risk_score')
                            ->label(__('Risk Score'))
                            ->formatStateUsing(fn (?int $state): string => $state !== null ? "{$state}/100" : '-')
                            ->badge()
                            ->color(fn (?int $state): string => match (true) {
                                $state === null => 'gray',
                                $state <= 20 => 'success',
                                $state <= 40 => 'info',
                                $state <= 60 => 'warning',
                                $state <= 80 => 'orange',
                                default => 'danger',
                            }),
                        TextEntry::make('vendorManager.name')
                            ->label(__('Relationship Manager'))
                            ->icon('heroicon-o-user'),
                    ])
                    ->columns(4),

                Section::make(__('Details'))
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('description')
                            ->label(__('Description'))
                            ->placeholder(__('No description provided'))
                            ->html()
                            ->columnSpanFull(),
                        TextEntry::make('url')
                            ->label(__('Website'))
                            ->url(fn (?Vendor $record) => $record?->url)
                            ->openUrlInNewTab()
                            ->icon('heroicon-o-globe-alt')
                            ->placeholder(__('No website')),
                        TextEntry::make('contact_name')
                            ->label(__('Vendor Primary Contact'))
                            ->icon('heroicon-o-user-circle')
                            ->placeholder(__('Not assigned')),
                        TextEntry::make('contact_email')
                            ->label(__('Contact Email'))
                            ->icon('heroicon-o-envelope')
                            ->url(fn (?Vendor $record) => $record?->contact_email ? "mailto:{$record->contact_email}" : null)
                            ->placeholder(__('-')),
                        TextEntry::make('contact_phone')
                            ->label(__('Contact Phone'))
                            ->icon('heroicon-o-phone')
                            ->url(fn (?Vendor $record) => $record?->contact_phone ? "tel:{$record->contact_phone}" : null)
                            ->placeholder(__('-')),
                        TextEntry::make('address')
                            ->label(__('Physical Address'))
                            ->html()
                            ->icon('heroicon-o-map-pin')
                            ->placeholder(__('-')),
                        TextEntry::make('risk_score_calculated_at')
                            ->label(__('Risk Score Updated'))
                            ->dateTime()
                            ->placeholder(__('Never')),
                        TextEntry::make('updated_at')
                            ->label(__('Last Modified'))
                            ->dateTime(),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make(__('Notes'))
                    ->schema([
                        TextEntry::make('notes')
                            ->hiddenLabel()
                            ->prose()
                            ->markdown()
                            ->placeholder(__('No notes')),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->hidden(fn (?Vendor $record) => empty($record?->notes)),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->columns([
                TextColumn::make('name')->label(__('Name'))->searchable(),
                TextColumn::make('vendorManager.name')
                    ->label(__('Vendor Manager'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('status')->label(__('Status'))->badge()->color(fn ($record) => $record->status->getColor()),
                TextColumn::make('risk_rating')->label(__('Risk Rating'))->badge()->color(fn ($record) => $record->risk_rating->getColor()),
                TextColumn::make('url')
                    ->label(__('URL'))
                    ->url(fn ($record) => $record->url, true)
                    ->wrap()
                    ->sortable()
                    ->searchable()
                    ->hidden()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('Created'))
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('Updated'))
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options(collect(VendorStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])),
                SelectFilter::make('risk_rating')
                    ->label(__('Risk Rating'))
                    ->options(collect(VendorRiskRating::cases())->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])),
                SelectFilter::make('vendor_manager_id')
                    ->label(__('Vendor Manager'))
                    ->options(User::pluck('name', 'id')),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(VendorExporter::class)
                    ->icon('heroicon-o-arrow-down-tray'),
            ])
            ->recordActions([
                Action::make('send_survey')
                    ->label(__('Send Survey'))
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->schema([
                        Select::make('survey_template_id')
                            ->label(__('Survey Template'))
                            ->options(SurveyTemplate::where('status', SurveyTemplateStatus::ACTIVE)->pluck('title', 'id'))
                            ->searchable()
                            ->required(),
                        TextInput::make('respondent_email')
                            ->label(__('Respondent Email'))
                            ->email()
                            ->required()
                            ->helperText(__('The email address to send the survey to')),
                        TextInput::make('respondent_name')
                            ->label(__('Respondent Name'))
                            ->helperText(__('Name of the person completing the survey')),
                        DatePicker::make('due_date')
                            ->label(__('Due Date'))
                            ->native(false),
                    ])
                    ->action(function (Vendor $record, array $data) {
                        $survey = Survey::create([
                            'survey_template_id' => $data['survey_template_id'],
                            'vendor_id' => $record->id,
                            'respondent_email' => $data['respondent_email'],
                            'respondent_name' => $data['respondent_name'] ?? null,
                            'due_date' => $data['due_date'] ?? null,
                            'status' => SurveyStatus::DRAFT,
                            'created_by_id' => auth()->id(),
                        ]);

                        try {
                            Mail::send(new SurveyInvitationMail($survey));
                            $survey->update(['status' => SurveyStatus::SENT]);

                            Notification::make()
                                ->title(__('Survey Sent'))
                                ->body(__('Survey invitation sent to :email', ['email' => $data['respondent_email']]))
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()
                                ->title(__('Failed to Send Survey'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exporter(VendorExporter::class)
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray'),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ApplicationsRelationManager::class,
            ImplementationsRelationManager::class,
            SurveysRelationManager::class,
            VendorUsersRelationManager::class,
            VendorDocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVendors::route('/'),
            'create' => CreateVendor::route('/create'),
            'view' => ViewVendor::route('/{record}'),
            'edit' => EditVendor::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['vendorManager']);
    }
}
