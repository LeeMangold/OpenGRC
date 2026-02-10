<?php

namespace App\Filament\Resources;

use Aliziodev\LaravelTaxonomy\Models\Taxonomy;
use App\Filament\Exports\AssetExporter;
use App\Filament\Resources\AssetResource\Pages\CreateAsset;
use App\Filament\Resources\AssetResource\Pages\EditAsset;
use App\Filament\Resources\AssetResource\Pages\ListAssets;
use App\Filament\Resources\AssetResource\Pages\ViewAsset;
use App\Filament\Resources\AssetResource\RelationManagers\ImplementationsRelationManager;
use App\Models\Asset;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\IconSize;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AssetResource extends Resource
{
    protected static ?string $model = Asset::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-computer-desktop';

    protected static ?string $navigationLabel = 'Assets';

    protected static string|\UnitEnum|null $navigationGroup = 'Entities';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Core Identification Section
                Section::make('Core Identification')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('asset_tag')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->label('Asset Tag')
                            ->placeholder('e.g., AST-12345'),

                        TextInput::make('serial_number')
                            ->maxLength(255)
                            ->label('Serial Number'),

                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Asset Name'),

                        Select::make('asset_type_id')
                            ->label('Asset Type')
                            ->options(fn () => Taxonomy::where('slug', 'asset-type')->first()?->children()->pluck('name', 'id') ?? collect())
                            ->searchable()
                            ->required(),

                        Select::make('status_id')
                            ->label('Status')
                            ->options(fn () => Taxonomy::where('slug', 'asset-status')->first()?->children()->pluck('name', 'id') ?? collect())
                            ->searchable()
                            ->required(),
                    ])
                    ->columns(2),

                // Hardware Specifications Section
                Section::make('Hardware Specifications')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('manufacturer')
                            ->maxLength(255),

                        TextInput::make('model')
                            ->maxLength(255),

                        TextInput::make('processor')
                            ->maxLength(255)
                            ->label('Processor/CPU'),

                        TextInput::make('ram_gb')
                            ->numeric()
                            ->suffix('GB')
                            ->label('RAM'),

                        TextInput::make('storage_type')
                            ->maxLength(255)
                            ->placeholder('HDD, SSD, NVMe')
                            ->label('Storage Type'),

                        TextInput::make('storage_capacity_gb')
                            ->numeric()
                            ->suffix('GB')
                            ->label('Storage Capacity'),

                        TextInput::make('graphics_card')
                            ->maxLength(255)
                            ->label('Graphics Card'),

                        TextInput::make('screen_size')
                            ->numeric()
                            ->suffix('"')
                            ->step(0.1)
                            ->label('Screen Size'),

                        TextInput::make('mac_address')
                            ->maxLength(255)
                            ->label('MAC Address')
                            ->placeholder('XX:XX:XX:XX:XX:XX'),

                        TextInput::make('ip_address')
                            ->maxLength(255)
                            ->label('IP Address')
                            ->placeholder('192.168.1.100'),

                        TextInput::make('hostname')
                            ->maxLength(255),

                        TextInput::make('operating_system')
                            ->maxLength(255)
                            ->label('Operating System'),

                        TextInput::make('os_version')
                            ->maxLength(255)
                            ->label('OS Version'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                // Assignment & Location Section
                Section::make('Assignment & Location')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('assigned_to_user_id')
                            ->label('Assigned To')
                            ->options(fn (string $operation): array => $operation === 'create' ? User::activeOptions() : User::optionsWithDeactivated())
                            ->searchable(),

                        DatePicker::make('assigned_at')
                            ->label('Assigned Date'),

                        TextInput::make('building')
                            ->maxLength(255),

                        TextInput::make('floor')
                            ->maxLength(255),

                        TextInput::make('room')
                            ->maxLength(255),

                        TextInput::make('cloud_provider')
                            ->maxLength(255)
                            ->label('Cloud Provider'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                // Financial Information Section
                Section::make('Financial Information')
                    ->columnSpanFull()
                    ->schema([
                        DatePicker::make('purchase_date')
                            ->label('Purchase Date'),

                        TextInput::make('purchase_price')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->label('Purchase Price'),

                        TextInput::make('purchase_order_number')
                            ->maxLength(255)
                            ->label('PO Number'),

                        TextInput::make('invoice_number')
                            ->maxLength(255),

                        TextInput::make('current_value')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->label('Current Value'),

                        TextInput::make('depreciation_method')
                            ->maxLength(255)
                            ->placeholder('Straight-line, Declining balance'),

                        TextInput::make('depreciation_rate')
                            ->numeric()
                            ->suffix('%')
                            ->step(0.01)
                            ->label('Depreciation Rate'),

                        TextInput::make('residual_value')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->label('Residual Value'),

                        TextInput::make('cost_per_hour')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->label('Cost Per Hour'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                // Warranty & Support Section
                Section::make('Warranty & Support')
                    ->columnSpanFull()
                    ->schema([
                        DatePicker::make('warranty_start_date')
                            ->label('Warranty Start'),

                        DatePicker::make('warranty_end_date')
                            ->label('Warranty End')
                            ->after('warranty_start_date'),

                        TextInput::make('warranty_type')
                            ->maxLength(255)
                            ->placeholder('Manufacturer, Extended'),

                        TextInput::make('warranty_provider')
                            ->maxLength(255),

                        TextInput::make('support_contract_number')
                            ->maxLength(255)
                            ->label('Support Contract #'),

                        DatePicker::make('support_expiry_date')
                            ->label('Support Expiry'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                // Lifecycle Management Section
                Section::make('Lifecycle Management')
                    ->columnSpanFull()
                    ->schema([
                        DatePicker::make('received_date')
                            ->label('Received Date'),

                        DatePicker::make('deployment_date')
                            ->label('Deployment Date'),

                        DatePicker::make('last_audit_date')
                            ->label('Last Audit'),

                        DatePicker::make('next_audit_date')
                            ->label('Next Audit'),

                        DatePicker::make('retirement_date')
                            ->label('Retirement Date'),

                        DatePicker::make('disposal_date')
                            ->label('Disposal Date'),

                        TextInput::make('disposal_method')
                            ->maxLength(255)
                            ->placeholder('Recycled, Donated, Destroyed'),

                        TextInput::make('expected_life_years')
                            ->numeric()
                            ->suffix('years')
                            ->label('Expected Life'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                // Maintenance & Service Section
                Section::make('Maintenance & Service')
                    ->columnSpanFull()
                    ->schema([
                        DateTimePicker::make('last_maintenance_date')
                            ->label('Last Maintenance'),

                        DateTimePicker::make('next_maintenance_date')
                            ->label('Next Maintenance'),

                        Select::make('condition_id')
                            ->label('Condition')
                            ->options(fn () => Taxonomy::where('slug', 'asset-condition')->first()?->children()->pluck('name', 'id') ?? collect())
                            ->searchable(),

                        Textarea::make('maintenance_notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),

                // Software & Licensing Section
                Section::make('Software & Licensing')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('license_key')
                            ->label('License Key')
                            ->password()
                            ->revealable()
                            ->columnSpanFull(),

                        TextInput::make('license_type')
                            ->maxLength(255)
                            ->placeholder('Per-device, Per-user, Enterprise'),

                        TextInput::make('license_seats')
                            ->numeric()
                            ->label('Number of Seats'),

                        DatePicker::make('license_expiry_date')
                            ->label('License Expiry'),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->visible(fn (Get $get) => Taxonomy::find($get('asset_type_id'))?->name === 'Software License'),

                // Security & Compliance Section
                Section::make('Security & Compliance')
                    ->columnSpanFull()
                    ->schema([
                        Toggle::make('encryption_enabled')
                            ->label('Encryption Enabled'),

                        Toggle::make('antivirus_installed')
                            ->label('Antivirus Installed'),

                        DateTimePicker::make('last_security_scan')
                            ->label('Last Security Scan'),

                        Select::make('compliance_status_id')
                            ->label('Compliance Status')
                            ->options(fn () => Taxonomy::where('slug', 'compliance-status')->first()?->children()->pluck('name', 'id') ?? collect())
                            ->searchable(),

                        Select::make('data_classification_id')
                            ->label('Data Classification')
                            ->options(fn () => Taxonomy::where('slug', 'data-classification')->first()?->children()->pluck('name', 'id') ?? collect())
                            ->searchable(),

                        Select::make('asset_exposure_id')
                            ->label('Asset Exposure')
                            ->options(fn () => Taxonomy::where('slug', 'asset-exposure')->first()?->children()->pluck('name', 'id') ?? collect())
                            ->searchable(),

                        Select::make('asset_criticality_id')
                            ->label('Asset Criticality')
                            ->options(fn () => Taxonomy::where('slug', 'asset-criticality')->first()?->children()->pluck('name', 'id') ?? collect())
                            ->searchable(),

                        TextInput::make('endpoint_agent_id')
                            ->maxLength(255)
                            ->label('Endpoint Agent ID'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                // Additional Metadata Section
                Section::make('Additional Information')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('parent_asset_id')
                            ->label('Parent Asset')
                            ->options(fn () => Asset::pluck('name', 'id'))
                            ->searchable(),

                        TextInput::make('alternative_name')
                            ->maxLength(255)
                            ->label('Alternative Name'),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),

                        Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),

                        TagsInput::make('tags')
                            ->columnSpanFull(),

                        FileUpload::make('image_url')
                            ->image()
                            ->label('Asset Image')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->columns([
                TextColumn::make('asset_tag')
                    ->searchable()
                    ->sortable()
                    ->label('Asset Tag'),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                TextColumn::make('assetType.name')
                    ->label('Type')
                    ->badge()
                    ->sortable(),

                TextColumn::make('status.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Available' => 'success',
                        'In Use' => 'info',
                        'In Repair' => 'warning',
                        'Retired', 'Lost', 'Stolen', 'Disposed' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('assignedToUser.name')
                    ->label('Assigned To')
                    ->searchable()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active')
                    ->sortable(),

                TextColumn::make('manufacturer')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('model')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('serial_number')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('purchase_date')
                    ->date('m/d/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('current_value')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Hardware Specifications
                TextColumn::make('processor')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('ram_gb')
                    ->label('RAM (GB)')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('storage_type')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('storage_capacity_gb')
                    ->label('Storage (GB)')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('graphics_card')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('screen_size')
                    ->label('Screen Size')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('mac_address')
                    ->label('MAC Address')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('hostname')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('operating_system')
                    ->label('OS')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('os_version')
                    ->label('OS Version')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Location Details
                TextColumn::make('building')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('floor')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('room')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('cloud_provider')
                    ->label('Cloud Provider')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('assigned_at')
                    ->label('Assigned Date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Financial Information
                TextColumn::make('purchase_price')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('purchase_order_number')
                    ->label('PO Number')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('invoice_number')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('depreciation_method')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('depreciation_rate')
                    ->suffix('%')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('residual_value')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('cost_per_hour')
                    ->label('Cost Per Hour')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Warranty & Support
                TextColumn::make('warranty_start_date')
                    ->date('m/d/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('warranty_end_date')
                    ->date('m/d/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('warranty_type')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('warranty_provider')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('support_contract_number')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('support_expiry_date')
                    ->date('m/d/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Lifecycle Management
                TextColumn::make('received_date')
                    ->date('m/d/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deployment_date')
                    ->date('m/d/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('last_audit_date')
                    ->date('m/d/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('next_audit_date')
                    ->date('m/d/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('retirement_date')
                    ->date('m/d/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('disposal_date')
                    ->date('m/d/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('disposal_method')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('expected_life_years')
                    ->suffix(' years')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Maintenance & Service
                TextColumn::make('condition.name')
                    ->label('Condition')
                    ->badge()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('last_maintenance_date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('next_maintenance_date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Software & Licensing
                TextColumn::make('license_type')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('license_seats')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('license_expiry_date')
                    ->date('m/d/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Security & Compliance
                IconColumn::make('encryption_enabled')
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('antivirus_installed')
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('last_security_scan')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('complianceStatus.name')
                    ->label('Compliance')
                    ->badge()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('dataClassification.name')
                    ->label('Data Classification')
                    ->badge()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('assetExposure.name')
                    ->label('Exposure')
                    ->badge()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('assetCriticality.name')
                    ->label('Criticality')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Low' => 'success',
                        'Medium' => 'warning',
                        'High' => 'danger',
                        'Critical' => 'danger',
                        default => 'gray',
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('endpoint_agent_id')
                    ->label('Endpoint Agent ID')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Relationships
                TextColumn::make('alternative_name')
                    ->label('Alternative Name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('parentAsset.name')
                    ->label('Parent Asset')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Metadata
                TextColumn::make('creator.name')
                    ->label('Created By')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updater.name')
                    ->label('Updated By')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('asset_type_id')
                    ->label('Asset Type')
                    ->options(fn () => Taxonomy::where('slug', 'asset-type')->first()?->children()->pluck('name', 'id') ?? collect())
                    ->searchable(),

                SelectFilter::make('status_id')
                    ->label('Status')
                    ->options(fn () => Taxonomy::where('slug', 'asset-status')->first()?->children()->pluck('name', 'id') ?? collect())
                    ->searchable(),

                SelectFilter::make('assigned_to_user_id')
                    ->label('Assigned To')
                    ->options(fn () => User::optionsWithDeactivated())
                    ->searchable(),

                TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All assets')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

                Filter::make('manufacturer')
                    ->schema([
                        TextInput::make('manufacturer'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['manufacturer'],
                        fn (Builder $query, $manufacturer): Builder => $query->where('manufacturer', 'like', "%{$manufacturer}%")
                    )
                    ),

                Filter::make('model')
                    ->schema([
                        TextInput::make('model'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['model'],
                        fn (Builder $query, $model): Builder => $query->where('model', 'like', "%{$model}%")
                    )
                    ),

                Filter::make('serial_number')
                    ->schema([
                        TextInput::make('serial_number'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['serial_number'],
                        fn (Builder $query, $serial): Builder => $query->where('serial_number', 'like', "%{$serial}%")
                    )
                    ),

                Filter::make('purchase_date')
                    ->schema([
                        DatePicker::make('purchased_from')
                            ->label('Purchased From'),
                        DatePicker::make('purchased_until')
                            ->label('Purchased Until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['purchased_from'],
                            fn (Builder $query, $date): Builder => $query->whereDate('purchase_date', '>=', $date),
                        )
                        ->when(
                            $data['purchased_until'],
                            fn (Builder $query, $date): Builder => $query->whereDate('purchase_date', '<=', $date),
                        )
                    ),

                SelectFilter::make('condition_id')
                    ->label('Condition')
                    ->options(fn () => Taxonomy::where('slug', 'asset-condition')->first()?->children()->pluck('name', 'id') ?? collect())
                    ->searchable(),

                SelectFilter::make('compliance_status_id')
                    ->label('Compliance Status')
                    ->options(fn () => Taxonomy::where('slug', 'compliance-status')->first()?->children()->pluck('name', 'id') ?? collect())
                    ->searchable(),

                SelectFilter::make('data_classification_id')
                    ->label('Data Classification')
                    ->options(fn () => Taxonomy::where('slug', 'data-classification')->first()?->children()->pluck('name', 'id') ?? collect())
                    ->searchable(),

                TernaryFilter::make('encryption_enabled')
                    ->label('Encryption')
                    ->placeholder('All assets')
                    ->trueLabel('Encrypted')
                    ->falseLabel('Not encrypted'),

                TernaryFilter::make('antivirus_installed')
                    ->label('Antivirus')
                    ->placeholder('All assets')
                    ->trueLabel('Installed')
                    ->falseLabel('Not installed'),

                Filter::make('warranty_end_date')
                    ->schema([
                        DatePicker::make('warranty_from')
                            ->label('Warranty Ends From'),
                        DatePicker::make('warranty_until')
                            ->label('Warranty Ends Until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['warranty_from'],
                            fn (Builder $query, $date): Builder => $query->whereDate('warranty_end_date', '>=', $date),
                        )
                        ->when(
                            $data['warranty_until'],
                            fn (Builder $query, $date): Builder => $query->whereDate('warranty_end_date', '<=', $date),
                        )
                    ),

                Filter::make('next_audit_date')
                    ->schema([
                        DatePicker::make('audit_from')
                            ->label('Next Audit From'),
                        DatePicker::make('audit_until')
                            ->label('Next Audit Until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['audit_from'],
                            fn (Builder $query, $date): Builder => $query->whereDate('next_audit_date', '>=', $date),
                        )
                        ->when(
                            $data['audit_until'],
                            fn (Builder $query, $date): Builder => $query->whereDate('next_audit_date', '<=', $date),
                        )
                    ),

                SelectFilter::make('parent_asset_id')
                    ->label('Parent Asset')
                    ->options(fn () => Asset::pluck('name', 'id'))
                    ->searchable(),

                TrashedFilter::make(),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(AssetExporter::class)
                    ->icon('heroicon-o-arrow-down-tray'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exporter(AssetExporter::class)
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray'),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Header Section with Key Information
                Section::make('Asset Overview')
                    ->columnSpanFull()
                    ->schema([
                        Flex::make([
                            Grid::make(2)
                                ->schema([
                                    Group::make([
                                        TextEntry::make('asset_tag')
                                            ->label('Asset Tag')
                                            ->badge()
                                            ->color('primary')
                                            ->size(TextSize::Large),

                                        TextEntry::make('name')
                                            ->label('Asset Name')
                                            ->size(TextSize::Large)
                                            ->weight('bold'),
                                    ]),

                                    Group::make([
                                        TextEntry::make('assetType.name')
                                            ->label('Type')
                                            ->badge()
                                            ->color('info'),

                                        TextEntry::make('status.name')
                                            ->label('Status')
                                            ->badge()
                                            ->color(fn (string $state): string => match ($state) {
                                                'Available' => 'success',
                                                'In Use' => 'info',
                                                'In Repair' => 'warning',
                                                'Retired', 'Lost', 'Stolen', 'Disposed' => 'danger',
                                                default => 'gray',
                                            }),
                                    ]),
                                ]),

                            Group::make([
                                ImageEntry::make('image_url')
                                    ->hiddenLabel()
                                    ->height(150)
                                    ->grow(false)
                                    ->visible(fn ($state) => ! empty($state)),

                                IconEntry::make('assetType.name')
                                    ->hiddenLabel()
                                    ->size(IconSize::ExtraLarge)
                                    ->icon(fn (string $state): string => match ($state) {
                                        'Laptop' => 'heroicon-o-computer-desktop',
                                        'Desktop' => 'heroicon-o-computer-desktop',
                                        'Server' => 'heroicon-o-server',
                                        'Monitor' => 'heroicon-o-tv',
                                        'Phone' => 'heroicon-o-device-phone-mobile',
                                        'Tablet' => 'heroicon-o-device-tablet',
                                        'Network Equipment' => 'heroicon-o-signal',
                                        'Peripheral' => 'heroicon-o-printer',
                                        'Software License' => 'heroicon-o-key',
                                        default => 'heroicon-o-cube',
                                    })
                                    ->color(fn (string $state): string => match ($state) {
                                        'Laptop', 'Desktop' => 'info',
                                        'Server' => 'danger',
                                        'Monitor', 'Phone', 'Tablet' => 'success',
                                        'Network Equipment' => 'warning',
                                        'Software License' => 'primary',
                                        default => 'gray',
                                    })
                                    ->visible(fn ($record) => empty($record->image_url)),
                            ])
                                ->grow(false),
                        ])->from('md'),
                    ])
                    ->collapsible(),

                // Core Identification
                Section::make('Identification Details')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('serial_number')
                            ->label('Serial Number')
                            ->placeholder('Not provided'),

                        TextEntry::make('is_active')
                            ->label('Active Status')
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Inactive')
                            ->color(fn ($state) => $state ? 'success' : 'danger'),

                        TextEntry::make('parentAsset.name')
                            ->label('Parent Asset')
                            ->placeholder('None'),
                    ])
                    ->columns(3)
                    ->collapsible(),

                // Hardware Specifications
                Section::make('Hardware Specifications')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('manufacturer')
                            ->placeholder('Not specified'),

                        TextEntry::make('model')
                            ->placeholder('Not specified'),

                        TextEntry::make('processor')
                            ->label('Processor/CPU')
                            ->placeholder('Not specified'),

                        TextEntry::make('ram_gb')
                            ->label('RAM')
                            ->suffix(' GB')
                            ->placeholder('Not specified'),

                        TextEntry::make('storage_type')
                            ->label('Storage Type')
                            ->placeholder('Not specified'),

                        TextEntry::make('storage_capacity_gb')
                            ->label('Storage Capacity')
                            ->suffix(' GB')
                            ->placeholder('Not specified'),

                        TextEntry::make('graphics_card')
                            ->label('Graphics Card')
                            ->placeholder('Not specified')
                            ->columnSpan(2),

                        TextEntry::make('screen_size')
                            ->label('Screen Size')
                            ->suffix('"')
                            ->placeholder('Not specified'),

                        TextEntry::make('operating_system')
                            ->label('Operating System')
                            ->placeholder('Not specified'),

                        TextEntry::make('os_version')
                            ->label('OS Version')
                            ->placeholder('Not specified'),

                        TextEntry::make('mac_address')
                            ->label('MAC Address')
                            ->copyable()
                            ->placeholder('Not specified'),

                        TextEntry::make('ip_address')
                            ->label('IP Address')
                            ->copyable()
                            ->placeholder('Not specified'),

                        TextEntry::make('hostname')
                            ->label('Hostname')
                            ->copyable()
                            ->placeholder('Not specified'),
                    ])
                    ->columns(3)
                    ->collapsed(),

                // Assignment & Location
                Section::make('Assignment & Location')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('assignedToUser.name')
                            ->label('Assigned To')
                            ->placeholder('Not assigned')
                            ->icon('heroicon-o-user'),

                        TextEntry::make('assigned_at')
                            ->label('Assigned Date')
                            ->dateTime()
                            ->placeholder('Not assigned'),

                        TextEntry::make('building')
                            ->placeholder('Not specified'),

                        TextEntry::make('floor')
                            ->placeholder('Not specified'),

                        TextEntry::make('room')
                            ->placeholder('Not specified'),

                        TextEntry::make('cloud_provider')
                            ->label('Cloud Provider')
                            ->placeholder('Not specified'),
                    ])
                    ->columns(3)
                    ->collapsed(),

                // Financial Information
                Section::make('Financial Information')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('purchase_date')
                            ->label('Purchase Date')
                            ->date('M d, Y')
                            ->placeholder('Not specified'),

                        TextEntry::make('purchase_price')
                            ->label('Purchase Price')
                            ->money('USD')
                            ->placeholder('Not specified'),

                        TextEntry::make('current_value')
                            ->label('Current Value')
                            ->money('USD')
                            ->placeholder('Not calculated'),

                        TextEntry::make('depreciation_method')
                            ->label('Depreciation Method')
                            ->placeholder('Not specified'),

                        TextEntry::make('depreciation_rate')
                            ->label('Depreciation Rate')
                            ->suffix('%')
                            ->placeholder('Not specified'),

                        TextEntry::make('residual_value')
                            ->label('Residual Value')
                            ->money('USD')
                            ->placeholder('Not specified'),

                        TextEntry::make('purchase_order_number')
                            ->label('PO Number')
                            ->copyable()
                            ->placeholder('Not specified'),

                        TextEntry::make('invoice_number')
                            ->label('Invoice Number')
                            ->copyable()
                            ->placeholder('Not specified'),

                        TextEntry::make('cost_per_hour')
                            ->label('Cost Per Hour')
                            ->money('USD')
                            ->placeholder('Not specified'),
                    ])
                    ->columns(3)
                    ->collapsed(),

                // Warranty & Support
                Section::make('Warranty & Support')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('warranty_start_date')
                            ->label('Warranty Start')
                            ->date('M d, Y')
                            ->placeholder('Not specified'),

                        TextEntry::make('warranty_end_date')
                            ->label('Warranty End')
                            ->date('M d, Y')
                            ->badge()
                            ->color(fn ($state) => $state && $state->isFuture() ? 'success' : 'warning')
                            ->placeholder('Not specified'),

                        TextEntry::make('warranty_type')
                            ->label('Warranty Type')
                            ->placeholder('Not specified'),

                        TextEntry::make('warranty_provider')
                            ->label('Warranty Provider')
                            ->placeholder('Not specified'),

                        TextEntry::make('support_contract_number')
                            ->label('Support Contract')
                            ->copyable()
                            ->placeholder('Not specified'),

                        TextEntry::make('support_expiry_date')
                            ->label('Support Expiry')
                            ->date('M d, Y')
                            ->placeholder('Not specified'),
                    ])
                    ->columns(3)
                    ->collapsed(),

                // Lifecycle Management
                Section::make('Lifecycle Management')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('received_date')
                            ->date('M d, Y')
                            ->placeholder('Not specified'),

                        TextEntry::make('deployment_date')
                            ->date('M d, Y')
                            ->placeholder('Not specified'),

                        TextEntry::make('last_audit_date')
                            ->date('M d, Y')
                            ->placeholder('Not specified'),

                        TextEntry::make('next_audit_date')
                            ->date('M d, Y')
                            ->badge()
                            ->color(fn ($state) => $state && $state->isPast() ? 'danger' : 'success')
                            ->placeholder('Not scheduled'),

                        TextEntry::make('expected_life_years')
                            ->suffix(' years')
                            ->placeholder('Not specified'),

                        TextEntry::make('retirement_date')
                            ->date('M d, Y')
                            ->placeholder('Not specified'),

                        TextEntry::make('disposal_date')
                            ->date('M d, Y')
                            ->placeholder('Not specified'),

                        TextEntry::make('disposal_method')
                            ->placeholder('Not specified'),
                    ])
                    ->columns(3)
                    ->collapsed(),

                // Maintenance & Service
                Section::make('Maintenance & Service')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('condition.name')
                            ->label('Condition')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Excellent' => 'success',
                                'Good' => 'info',
                                'Fair' => 'warning',
                                'Poor', 'Damaged' => 'danger',
                                default => 'gray',
                            })
                            ->placeholder('Not assessed'),

                        TextEntry::make('last_maintenance_date')
                            ->dateTime('M d, Y H:i')
                            ->placeholder('Not recorded'),

                        TextEntry::make('next_maintenance_date')
                            ->dateTime('M d, Y H:i')
                            ->badge()
                            ->color(fn ($state) => $state && $state->isPast() ? 'danger' : 'success')
                            ->placeholder('Not scheduled'),

                        TextEntry::make('maintenance_notes')
                            ->columnSpanFull()
                            ->placeholder('No maintenance notes'),
                    ])
                    ->columns(3)
                    ->collapsed(),

                // Security & Compliance
                Section::make('Security & Compliance')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('encryption_enabled')
                            ->label('Encryption')
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state ? 'Enabled' : 'Disabled')
                            ->color(fn ($state) => $state ? 'success' : 'danger'),

                        TextEntry::make('antivirus_installed')
                            ->label('Antivirus')
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state ? 'Installed' : 'Not Installed')
                            ->color(fn ($state) => $state ? 'success' : 'warning'),

                        TextEntry::make('last_security_scan')
                            ->dateTime('M d, Y H:i')
                            ->placeholder('Never scanned'),

                        TextEntry::make('complianceStatus.name')
                            ->label('Compliance Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Compliant' => 'success',
                                'Non-Compliant' => 'danger',
                                'Exempt' => 'info',
                                'Pending' => 'warning',
                                default => 'gray',
                            })
                            ->placeholder('Not assessed'),

                        TextEntry::make('dataClassification.name')
                            ->label('Data Classification')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Public' => 'success',
                                'Internal' => 'info',
                                'Confidential' => 'warning',
                                'Restricted' => 'danger',
                                default => 'gray',
                            })
                            ->placeholder('Not classified'),

                        TextEntry::make('assetExposure.name')
                            ->label('Asset Exposure')
                            ->badge()
                            ->placeholder('Not specified'),

                        TextEntry::make('assetCriticality.name')
                            ->label('Asset Criticality')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Low' => 'success',
                                'Medium' => 'warning',
                                'High' => 'danger',
                                'Critical' => 'danger',
                                default => 'gray',
                            })
                            ->placeholder('Not specified'),

                        TextEntry::make('endpoint_agent_id')
                            ->label('Endpoint Agent ID')
                            ->copyable()
                            ->placeholder('Not specified'),
                    ])
                    ->columns(3)
                    ->collapsed(),

                // Additional Information
                Section::make('Additional Information')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('alternative_name')
                            ->label('Alternative Name')
                            ->placeholder('Not specified'),

                        TextEntry::make('notes')
                            ->columnSpanFull()
                            ->placeholder('No additional notes')
                            ->html(),

                        TextEntry::make('tags')
                            ->badge()
                            ->separator(',')
                            ->placeholder('No tags'),

                        TextEntry::make('creator.name')
                            ->label('Created By'),

                        TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime('M d, Y H:i'),

                        TextEntry::make('updater.name')
                            ->label('Last Updated By'),

                        TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime('M d, Y H:i')
                            ->since(),
                    ])
                    ->columns(3)
                    ->collapsed(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ImplementationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAssets::route('/'),
            'create' => CreateAsset::route('/create'),
            'view' => ViewAsset::route('/{record}'),
            'edit' => EditAsset::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->with([
                'assetType',
                'status',
                'assignedToUser',
                'condition',
                'complianceStatus',
                'dataClassification',
                'assetExposure',
                'assetCriticality',
                'parentAsset',
                'creator',
                'updater',
            ]);
    }

    public static function getGlobalSearchResultTitle($record): string
    {
        return $record->name.' ('.$record->asset_tag.')';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['asset_tag', 'name', 'serial_number', 'manufacturer', 'model'];
    }
}
