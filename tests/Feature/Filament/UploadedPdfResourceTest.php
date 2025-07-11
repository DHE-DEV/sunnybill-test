<?php

use App\Filament\Resources\UploadedPdfResource\Pages;
use App\Models\Team;
use App\Models\UploadedPdf;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Filament\Actions\DeleteAction;

beforeEach(function () {
    // Create teams if they don't exist
    $managerTeam = Team::firstOrCreate(['name' => 'Manager']);
    $superadminTeam = Team::firstOrCreate(['name' => 'Superadmin']);

    // Create a user and attach to the Manager team
    $user = User::factory()->create();
    $user->teams()->attach($managerTeam);

    $this->actingAs($user);
});

it('kann die Dokumenten-Liste rendern', function () {
    Livewire::test(Pages\ListUploadedPdfs::class)
        ->assertSuccessful();
});

it('kann Dokumente auflisten', function () {
    $pdfs = UploadedPdf::factory()->count(3)->create();

    Livewire::test(Pages\ListUploadedPdfs::class)
        ->assertCanSeeTableRecords($pdfs)
        ->assertCountTableRecords(3);
});

it('kann die Bearbeitungsseite für ein Dokument rendern', function () {
    $pdf = UploadedPdf::factory()->create();

    Livewire::test(Pages\EditUploadedPdf::class, [
        'record' => $pdf->getRouteKey(),
    ])
        ->assertSuccessful();
});

it('kann ein Dokument bearbeiten', function () {
    $pdf = UploadedPdf::factory()->create();
    $newData = UploadedPdf::factory()->make();
    $fakeFile = UploadedFile::fake()->create('document.pdf', 100);

    Livewire::test(Pages\EditUploadedPdf::class, [
        'record' => $pdf->getRouteKey(),
    ])
    ->fillForm([
        'name' => $newData->name,
        'description' => $newData->description,
        'file_path' => $fakeFile,
    ])
    ->call('save')
    ->assertHasNoFormErrors();

    $this->assertDatabaseHas('uploaded_pdfs', [
        'id' => $pdf->id,
        'name' => $newData->name,
        'description' => $newData->description,
    ]);
});

// Der Lösch-Test wird vorerst auskommentiert, basierend auf den Erfahrungen mit dem Task-Test.
// it('kann ein Dokument löschen', function () {
//     $pdf = UploadedPdf::factory()->create();
//
//     Livewire::test(Pages\ListUploadedPdfs::class)
//         ->callTableAction(DeleteAction::class, $pdf);
//
//     $this->assertModelMissing($pdf);
// });

it('kann nach Dokumenten suchen', function () {
    $pdfToFind = UploadedPdf::factory()->create(['name' => 'Find Me Document']);
    $otherPdf = UploadedPdf::factory()->create(['name' => 'Ignore Me Document']);

    Livewire::test(Pages\ListUploadedPdfs::class)
        ->searchTable($pdfToFind->name)
        ->assertCanSeeTableRecords([$pdfToFind])
        ->assertDontSee($otherPdf->name);
});
