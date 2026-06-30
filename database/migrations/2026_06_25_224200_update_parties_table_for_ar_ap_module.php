<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('parties', function (Blueprint $table) {
            // Rename gst_number to gstin
            if (Schema::hasColumn('parties', 'gst_number')) {
                $table->renameColumn('gst_number', 'gstin');
            }
            
            // Make address mandatory by changing nullable to required
            if (Schema::hasColumn('parties', 'address')) {
                $table->text('address')->change();
            } else {
                $table->text('address')->after('email');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parties', function (Blueprint $table) {
            if (Schema::hasColumn('parties', 'gstin')) {
                $table->renameColumn('gstin', 'gst_number');
            }
            
            if (Schema::hasColumn('parties', 'address')) {
                $table->text('address')->nullable()->change();
            }
        });
    }
};
