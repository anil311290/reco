<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $existingColumns = Schema::getColumnListing('tax_rates');

        if (!in_array('tax_name', $existingColumns, true)) {
            Schema::table('tax_rates', function (Blueprint $table) {
                $table->string('tax_name')->default('')->after('name');
            });
        }

        if (!in_array('tax_code', $existingColumns, true)) {
            Schema::table('tax_rates', function (Blueprint $table) {
                $table->string('tax_code')->nullable()->after('code');
            });
        }

        if (!in_array('tax_rate', $existingColumns, true)) {
            Schema::table('tax_rates', function (Blueprint $table) {
                $table->decimal('tax_rate', 5, 2)->default(0)->after('rate');
            });
        }

        if (!in_array('tax_type', $existingColumns, true)) {
            Schema::table('tax_rates', function (Blueprint $table) {
                $table->enum('tax_type', ['addition', 'deduction'])->default('addition')->after('calculation_type');
            });
        }

        if (!in_array('tax_category', $existingColumns, true)) {
            Schema::table('tax_rates', function (Blueprint $table) {
                $table->string('tax_category')->nullable()->after('category');
            });
        }

        if (!in_array('status', $existingColumns, true)) {
            Schema::table('tax_rates', function (Blueprint $table) {
                $table->enum('status', ['active', 'inactive'])->default('active')->after('notes');
            });
        }

        if (!in_array('deleted_by', $existingColumns, true)) {
            Schema::table('tax_rates', function (Blueprint $table) {
                $table->string('deleted_by')->nullable()->after('updated_by_ip');
            });
        }

        if (!in_array('deleted_by_id', $existingColumns, true)) {
            Schema::table('tax_rates', function (Blueprint $table) {
                $table->unsignedBigInteger('deleted_by_id')->nullable()->after('deleted_by');
            });
        }

        if (!in_array('deleted_at', $existingColumns, true)) {
            Schema::table('tax_rates', function (Blueprint $table) {
                $table->softDeletes()->after('updated_at');
            });
        }

        DB::table('tax_rates')
            ->select('id', 'name', 'code', 'rate', 'type', 'category', 'calculation_type', 'is_active')
            ->orderBy('id')
            ->chunkById(100, function ($taxRates): void {
                foreach ($taxRates as $taxRate) {
                    DB::table('tax_rates')
                        ->where('id', $taxRate->id)
                        ->update([
                            'tax_name' => $taxRate->name,
                            'tax_code' => $taxRate->code,
                            'tax_rate' => $taxRate->rate,
                            'tax_type' => in_array($taxRate->calculation_type, ['addition', 'deduction'], true)
                                ? $taxRate->calculation_type
                                : 'addition',
                            'tax_category' => match (strtoupper((string) $taxRate->category)) {
                                'GST', 'CGST', 'SGST', 'IGST', 'TDS', 'TCS', 'CESS', 'OTHER' => strtoupper((string) $taxRate->category),
                                default => match ($taxRate->type) {
                                    'gst' => 'GST',
                                    'igst' => 'IGST',
                                    'cgst_sgst' => 'GST',
                                    'vat', 'exempt' => 'OTHER',
                                    default => 'OTHER',
                                },
                            },
                            'status' => $taxRate->is_active ? 'active' : 'inactive',
                        ]);
                }
            });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            $deletedByConstraintExists = DB::table('information_schema.KEY_COLUMN_USAGE')
                ->whereRaw('TABLE_SCHEMA = DATABASE()')
                ->where('TABLE_NAME', 'tax_rates')
                ->where('COLUMN_NAME', 'deleted_by_id')
                ->whereNotNull('REFERENCED_TABLE_NAME')
                ->exists();

            if (!$deletedByConstraintExists) {
                Schema::table('tax_rates', function (Blueprint $table) {
                    $table->foreign('deleted_by_id')->references('id')->on('users')->nullOnDelete();
                });
            }
        } else {
            if (Schema::hasColumn('tax_rates', 'deleted_by_id')) {
                // SQLite does not support information_schema queries; skip foreign key check.
            }
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            $statusIndexExists = collect(DB::select("SHOW INDEX FROM tax_rates WHERE Key_name = 'tax_rates_company_id_status_index'"))->isNotEmpty();

            if (!$statusIndexExists) {
                Schema::table('tax_rates', function (Blueprint $table) {
                    $table->index(['company_id', 'status']);
                });
            }
        } else {
            Schema::table('tax_rates', function (Blueprint $table) {
                $table->index(['company_id', 'status'], 'tax_rates_company_id_status_index');
            });
        }

        $columnsAfterBackfill = Schema::getColumnListing('tax_rates');
        $columnsToDrop = array_values(array_intersect([
            'name',
            'code',
            'rate',
            'calculation_type',
            'category',
            'type',
            'cgst_rate',
            'sgst_rate',
            'igst_rate',
            'is_inclusive',
            'is_active',
        ], $columnsAfterBackfill));

        if (Schema::getConnection()->getDriverName() === 'mysql' && $columnsToDrop !== []) {
            Schema::table('tax_rates', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            $statusIndexExists = collect(DB::select("SHOW INDEX FROM tax_rates WHERE Key_name = 'tax_rates_company_id_status_index'"))->isNotEmpty();

            if (!$statusIndexExists) {
                Schema::table('tax_rates', function (Blueprint $table) {
                    $table->index(['company_id', 'status']);
                });
            }
        }
    }

    public function down(): void
    {
        Schema::table('tax_rates', function (Blueprint $table) {
            $table->string('name')->default('')->after('company_id');
            $table->string('code')->nullable()->after('name');
            $table->decimal('rate', 5, 2)->default(0)->after('code');
            $table->enum('calculation_type', ['addition', 'deduction'])->default('addition')->after('rate');
            $table->string('category')->nullable()->after('type');
            $table->enum('type', ['gst', 'igst', 'cgst_sgst', 'vat', 'exempt'])->default('gst')->after('tax_rate');
            $table->decimal('cgst_rate', 5, 2)->default(0)->after('type');
            $table->decimal('sgst_rate', 5, 2)->default(0)->after('cgst_rate');
            $table->decimal('igst_rate', 5, 2)->default(0)->after('sgst_rate');
            $table->boolean('is_inclusive')->default(false)->after('igst_rate');
            $table->boolean('is_active')->default(true)->after('notes');
        });

        DB::table('tax_rates')
            ->select('id', 'tax_name', 'tax_code', 'tax_rate', 'tax_type', 'tax_category', 'status')
            ->orderBy('id')
            ->chunkById(100, function ($taxRates): void {
                foreach ($taxRates as $taxRate) {
                    DB::table('tax_rates')
                        ->where('id', $taxRate->id)
                        ->update([
                            'name' => $taxRate->tax_name,
                            'code' => $taxRate->tax_code,
                            'rate' => $taxRate->tax_rate,
                            'type' => match ($taxRate->tax_category) {
                                'IGST' => 'igst',
                                'GST', 'CGST', 'SGST' => 'gst',
                                default => 'exempt',
                            },
                            'is_active' => $taxRate->status === 'active',
                        ]);
                }
            });

        Schema::table('tax_rates', function (Blueprint $table) {
            $table->dropForeign(['deleted_by_id']);
            $table->dropIndex(['company_id', 'status']);
            $table->dropColumn([
                'tax_name',
                'tax_code',
                'tax_rate',
                'tax_type',
                'tax_category',
                'status',
                'deleted_by',
                'deleted_by_id',
                'deleted_at',
            ]);
        });
    }
};