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
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            
            // Firmeninformationen
            $table->string('company_name')->default('SunnyBill');
            $table->string('company_legal_form')->nullable(); // GmbH, AG, etc.
            $table->text('company_address')->nullable();
            $table->string('company_postal_code')->nullable();
            $table->string('company_city')->nullable();
            $table->string('company_country')->default('Deutschland');
            
            // Kontaktdaten
            $table->string('phone')->nullable();
            $table->string('fax')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            
            // Rechtliche Informationen
            $table->string('tax_number')->nullable();
            $table->string('vat_id')->nullable();
            $table->string('commercial_register')->nullable(); // Handelsregister
            $table->string('commercial_register_number')->nullable();
            $table->string('management')->nullable(); // Geschäftsführung
            
            // Bankdaten
            $table->string('bank_name')->nullable();
            $table->string('iban')->nullable();
            $table->string('bic')->nullable();
            
            // Logo-Einstellungen
            $table->string('logo_path')->nullable();
            $table->integer('logo_width')->default(200); // in px
            $table->integer('logo_height')->default(60); // in px
            $table->integer('logo_margin_top')->default(0); // in px
            $table->integer('logo_margin_right')->default(0); // in px
            $table->integer('logo_margin_bottom')->default(30); // in px
            $table->integer('logo_margin_left')->default(0); // in px
            
            // Zahlungsbedingungen
            $table->integer('default_payment_days')->default(14);
            $table->text('payment_terms')->nullable();
            
            // PDF-Layout Einstellungen
            $table->decimal('pdf_margin_top', 5, 2)->default(1.00); // in cm
            $table->decimal('pdf_margin_right', 5, 2)->default(2.00); // in cm
            $table->decimal('pdf_margin_bottom', 5, 2)->default(2.50); // in cm
            $table->decimal('pdf_margin_left', 5, 2)->default(2.00); // in cm
            
            $table->timestamps();
        });
        
        // Erstelle Standard-Einstellungen
        DB::table('company_settings')->insert([
            'company_name' => 'SunnyBill',
            'company_legal_form' => 'GmbH',
            'company_address' => 'Musterstraße 123',
            'company_postal_code' => '12345',
            'company_city' => 'Musterstadt',
            'company_country' => 'Deutschland',
            'phone' => '+49 123 456789',
            'email' => 'info@sunnybill.de',
            'website' => 'www.sunnybill.de',
            'tax_number' => 'DE123456789',
            'vat_id' => 'DE123456789',
            'commercial_register' => 'Amtsgericht Musterstadt',
            'commercial_register_number' => 'HRB 12345',
            'management' => 'Max Mustermann',
            'bank_name' => 'Musterbank',
            'iban' => 'DE12 3456 7890 1234 5678 90',
            'bic' => 'ABCDEFGH',
            'default_payment_days' => 14,
            'payment_terms' => 'Zahlung innerhalb von 14 Tagen ohne Abzug.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_settings');
    }
};
