<?php

namespace App\Filament\Exports;

use App\Models\Asset;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class AssetExporter extends Exporter
{
    protected static ?string $model = Asset::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('asset_tag')
                ->label('Asset Tag'),
            ExportColumn::make('serial_number')
                ->label('Serial Number'),
            ExportColumn::make('name')
                ->label('Name'),
            ExportColumn::make('assetType.name')
                ->label('Asset Type'),
            ExportColumn::make('status.name')
                ->label('Status'),
            ExportColumn::make('condition.name')
                ->label('Condition'),
            ExportColumn::make('category.name')
                ->label('Category'),
            ExportColumn::make('location.name')
                ->label('Location'),
            ExportColumn::make('department.name')
                ->label('Department'),
            ExportColumn::make('supplier.name')
                ->label('Supplier'),
            ExportColumn::make('manufacturer')
                ->label('Manufacturer'),
            ExportColumn::make('model')
                ->label('Model'),
            ExportColumn::make('assignedToUser.name')
                ->label('Assigned To'),
            ExportColumn::make('building')
                ->label('Building'),
            ExportColumn::make('floor')
                ->label('Floor'),
            ExportColumn::make('room')
                ->label('Room'),
            ExportColumn::make('purchase_date')
                ->label('Purchase Date'),
            ExportColumn::make('purchase_price')
                ->label('Purchase Price'),
            ExportColumn::make('warranty_end_date')
                ->label('Warranty End Date'),
            ExportColumn::make('hostname')
                ->label('Hostname'),
            ExportColumn::make('ip_address')
                ->label('IP Address'),
            ExportColumn::make('mac_address')
                ->label('MAC Address'),
            ExportColumn::make('operating_system')
                ->label('Operating System'),
            ExportColumn::make('is_active')
                ->label('Active'),
            ExportColumn::make('notes')
                ->label('Notes'),
            ExportColumn::make('created_at')
                ->label('Created At'),
            ExportColumn::make('updated_at')
                ->label('Updated At'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your asset export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }

    public function getFileDisk(): string
    {
        return setting('storage.driver', 'private');
    }
}
