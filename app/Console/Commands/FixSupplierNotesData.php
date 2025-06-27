<?php

namespace App\Console\Commands;

use App\Models\SupplierNote;
use Illuminate\Console\Command;

class FixSupplierNotesData extends Command
{
    protected $signature = 'fix:supplier-notes';
    protected $description = 'Fix supplier notes data - set default values for is_favorite';

    public function handle()
    {
        $this->info('Checking supplier notes data...');
        
        $notes = SupplierNote::all();
        $this->info("Found {$notes->count()} notes");
        
        foreach ($notes as $note) {
            $this->line("Note: {$note->title} - is_favorite: " . ($note->is_favorite ? 'true' : 'false'));
        }
        
        // Set all existing notes to is_favorite = false if they are null
        $updated = SupplierNote::whereNull('is_favorite')->update(['is_favorite' => false]);
        $this->info("Updated {$updated} notes to set is_favorite = false");
        
        // Also ensure sort_order is set
        $updatedSort = SupplierNote::whereNull('sort_order')->update(['sort_order' => 0]);
        $this->info("Updated {$updatedSort} notes to set sort_order = 0");
        
        $this->info('Data fix completed!');
        
        return 0;
    }
}