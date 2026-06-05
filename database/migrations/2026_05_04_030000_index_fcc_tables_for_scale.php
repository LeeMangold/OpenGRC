<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * After the CDBS bulk import, fcc_licenses can hit ~60K rows and
 * fcc_facilities ~90K. Several Filament navigation badges + the
 * dashboard widgets run COUNT(*) WHERE status IN (...) — without
 * these indexes those queries scan every row and the page hangs
 * on the splash logo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fcc_licenses', function (Blueprint $table) {
            // status already has an index from the original migration, but
            // add the call_sign and licensee covering indexes used by sort
            // / search on the License page list view.
            if (! $this->hasIndex('fcc_licenses', 'fcc_licenses_call_sign_index')) {
                $table->index('call_sign');
            }
            if (! $this->hasIndex('fcc_licenses', 'fcc_licenses_licensee_index')) {
                $table->index('licensee');
            }
            if (! $this->hasIndex('fcc_licenses', 'fcc_licenses_service_index')) {
                $table->index('service');
            }
        });

        Schema::table('fcc_facilities', function (Blueprint $table) {
            if (! $this->hasIndex('fcc_facilities', 'fcc_facilities_state_index')) {
                $table->index('state');
            }
            if (! $this->hasIndex('fcc_facilities', 'fcc_facilities_owner_index')) {
                $table->index('owner');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fcc_licenses', function (Blueprint $table) {
            $table->dropIndex(['call_sign']);
            $table->dropIndex(['licensee']);
            $table->dropIndex(['service']);
        });
        Schema::table('fcc_facilities', function (Blueprint $table) {
            $table->dropIndex(['state']);
            $table->dropIndex(['owner']);
        });
    }

    private function hasIndex(string $table, string $name): bool
    {
        $rows = \DB::select("PRAGMA index_list('{$table}')");
        foreach ($rows as $row) {
            if ($row->name === $name) return true;
        }
        return false;
    }
};
