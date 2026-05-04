<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FccLicenseResource\Pages\CreateFccLicense;
use App\Filament\Resources\FccLicenseResource\Pages\EditFccLicense;
use App\Filament\Resources\FccLicenseResource\Pages\ListFccLicenses;
use App\Filament\Resources\FccLicenseResource\Pages\ViewFccLicense;
use App\Models\FccFacility;
use App\Models\FccLicense;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FccLicenseResource extends Resource
{
    protected static ?string $model = FccLicense::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationLabel = 'Licenses';

    protected static ?string $modelLabel = 'FCC License';

    protected static ?string $pluralModelLabel = 'FCC Licenses';

    protected static string|\UnitEnum|null $navigationGroup = 'FCC Compliance';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'call_sign';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('License Identification')
                ->columnSpanFull()
                ->schema([
                    TextInput::make('call_sign')
                        ->label('Call Sign')
                        ->required()
                        ->placeholder('e.g., KXYZ-FM, WABC-AM')
                        ->unique(ignoreRecord: true)
                        ->maxLength(20),
                    TextInput::make('frn')
                        ->label('FCC Registration Number (FRN)')
                        ->required()
                        ->placeholder('10-digit FRN')
                        ->maxLength(20),
                    TextInput::make('licensee')
                        ->label('Licensee (Legal Entity)')
                        ->required()
                        ->placeholder('e.g., Market Hall Broadcasting, LLC')
                        ->maxLength(255),
                    Select::make('service')
                        ->label('Service')
                        ->required()
                        ->options([
                            'AM' => 'AM (Standard Broadcast)',
                            'FM' => 'FM (Commercial)',
                            'TV' => 'Full-Power Television',
                            'LPFM' => 'Low Power FM',
                            'LPTV' => 'Low Power Television',
                            'FX' => 'FM Translator',
                            'TX' => 'TV Translator',
                            'DT' => 'Digital TV',
                            'DC' => 'Digital Class A',
                            'DD' => 'Digital LPTV',
                            'CA' => 'Class A TV',
                            'OTHER' => 'Other',
                        ])
                        ->default('FM'),
                    TextInput::make('channel_or_frequency')
                        ->label('Channel / Frequency')
                        ->placeholder('e.g., 98.7 MHz or Ch. 27'),
                ])->columns(2),

            Section::make('Authorization Dates')
                ->columnSpanFull()
                ->schema([
                    DatePicker::make('grant_date')->label('License Grant Date'),
                    DatePicker::make('last_renewal_date')->label('Last Renewal'),
                    DatePicker::make('expiration_date')->label('Expiration Date')->required(),
                ])->columns(3),

            Section::make('Compliance Status')
                ->columnSpanFull()
                ->schema([
                    Select::make('status')
                        ->required()
                        ->options([
                            'active' => 'Active',
                            'expiring_soon' => 'Expiring Soon',
                            'at_risk' => 'At Risk',
                            'non_compliant' => 'Non-Compliant',
                            'silent' => 'Silent (47 CFR 73.1740)',
                            'cancelled' => 'Cancelled',
                        ])->default('active'),
                    TextInput::make('compliance_score')
                        ->label('Compliance Score (%)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->step(0.1)
                        ->default(100),
                    Select::make('facility_id')
                        ->label('Facility')
                        ->relationship('facility', 'name')
                        ->searchable()
                        ->preload(),
                ])->columns(3),

            Section::make('Notes')
                ->columnSpanFull()
                ->schema([
                    Textarea::make('public_notes')
                        ->rows(4)
                        ->placeholder('Public-facing notes about this authorization'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('call_sign')
                    ->label('Call Sign')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('licensee')
                    ->searchable()
                    ->limit(35)
                    ->sortable(),
                TextColumn::make('service')
                    ->badge()
                    ->color('warning'),
                TextColumn::make('channel_or_frequency')->label('Freq/Ch'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'active' => 'success',
                        'expiring_soon', 'at_risk' => 'warning',
                        'non_compliant' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => str($state)->headline()),
                TextColumn::make('expiration_date')
                    ->label('Expiration')
                    ->date('M d, Y')
                    ->sortable(),
                TextColumn::make('compliance_score')
                    ->label('Compliance')
                    ->suffix('%')
                    ->sortable()
                    ->color(fn ($state) => $state >= 95 ? 'success' : ($state >= 80 ? 'warning' : 'danger')),
                TextColumn::make('frn')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('service')->options([
                    'AM' => 'AM', 'FM' => 'FM', 'TV' => 'TV', 'LPFM' => 'LPFM',
                    'LPTV' => 'LPTV', 'FX' => 'FM Translator',
                ]),
                SelectFilter::make('status')->options([
                    'active' => 'Active',
                    'expiring_soon' => 'Expiring Soon',
                    'at_risk' => 'At Risk',
                    'non_compliant' => 'Non-Compliant',
                ]),
                SelectFilter::make('state')
                    ->relationship('facility', 'state')
                    ->label('State')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('call_sign')
            ->striped()
            ->paginated([25, 50, 100, 250])
            ->persistFiltersInSession()
            ->persistSortInSession();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFccLicenses::route('/'),
            'create' => CreateFccLicense::route('/create'),
            'view' => ViewFccLicense::route('/{record}'),
            'edit' => EditFccLicense::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) FccLicense::query()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $atRisk = FccLicense::query()->whereIn('status', ['at_risk', 'non_compliant'])->count();

        return $atRisk > 0 ? 'danger' : 'success';
    }
}
