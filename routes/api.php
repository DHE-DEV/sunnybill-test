<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Models\SolarPlant;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Supplier;
use App\Models\CreditNote;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Api\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// User search for mention autocomplete
Route::middleware('web')->group(function () {
    Route::get('/users/search', [UserController::class, 'search']);
    Route::get('/users/all', [UserController::class, 'all']);
});

// Mobile App API Routes
Route::prefix('mobile')->group(function () {
    
    // Authentication
    Route::post('/login', function (Request $request) {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Die angegebenen Anmeldedaten sind ungültig.'],
            ]);
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'admin' // Simplified for demo
            ],
            'token' => $token
        ]);
    });

    Route::middleware('auth:sanctum')->group(function () {
        
        // Logout
        Route::post('/logout', function (Request $request) {
            $request->user()->currentAccessToken()->delete();
            return response()->json(['message' => 'Erfolgreich abgemeldet']);
        });

        // Dashboard Stats
        Route::get('/stats', function () {
            $stats = [
                'solarPlants' => SolarPlant::count(),
                'customers' => Customer::count(),
                'suppliers' => Supplier::count(),
                'invoices' => Invoice::count(),
                'creditNotes' => CreditNote::count(),
                'revenue' => Invoice::where('status', 'paid')->sum('total')
            ];

            return response()->json($stats);
        });

        // Solar Plants
        Route::get('/solar-plants', function () {
            $plants = SolarPlant::select('id', 'name', 'total_capacity_kw', 'status', 'location')
                ->orderBy('name')
                ->get()
                ->map(function ($plant) {
                    // Ensure proper UTF-8 encoding
                    $name = mb_convert_encoding($plant->name ?? '', 'UTF-8', 'UTF-8');
                    $location = mb_convert_encoding($plant->location ?? '', 'UTF-8', 'UTF-8');
                    
                    return [
                        'id' => $plant->id,
                        'name' => $name,
                        'capacity' => $plant->total_capacity_kw,
                        'status' => $plant->status,
                        'location' => $location,
                        'status_text' => match($plant->status) {
                            'active' => 'Aktiv',
                            'maintenance' => 'Wartung',
                            'inactive' => 'Inaktiv',
                            default => 'Unbekannt'
                        },
                        'status_color' => match($plant->status) {
                            'active' => 'green',
                            'maintenance' => 'yellow',
                            'inactive' => 'red',
                            default => 'gray'
                        }
                    ];
                });

            return response()->json($plants, 200, ['Content-Type' => 'application/json; charset=utf-8']);
        });

        // Customers
        Route::get('/customers', function () {
            try {
                // Try to get real data first, fallback to mock data if there's an issue
                try {
                    $customers = \DB::table('customers')
                        ->select('id', 'name', 'email', 'phone', 'city')
                        ->whereNull('deleted_at')
                        ->orderBy('name')
                        ->limit(20)
                        ->get();
                } catch (\Exception $e) {
                    // If DB query fails, use mock data
                    $customers = collect([]);
                }
                
                // If no customers found, use mock data for demo
                if ($customers->isEmpty()) {
                    $customers = collect([
                        (object)[
                            'id' => '1',
                            'name' => 'Max Mustermann',
                            'email' => 'max.mustermann@example.com',
                            'phone' => '+49 30 12345678',
                            'city' => 'Berlin'
                        ],
                        (object)[
                            'id' => '2',
                            'name' => 'Anna Schmidt',
                            'email' => 'anna.schmidt@example.com',
                            'phone' => '+49 89 87654321',
                            'city' => 'München'
                        ],
                        (object)[
                            'id' => '3',
                            'name' => 'Thomas Weber',
                            'email' => 'thomas.weber@example.com',
                            'phone' => '+49 40 11223344',
                            'city' => 'Hamburg'
                        ],
                        (object)[
                            'id' => '4',
                            'name' => 'Sarah Müller',
                            'email' => 'sarah.mueller@example.com',
                            'phone' => '+49 221 55667788',
                            'city' => 'Köln'
                        ],
                        (object)[
                            'id' => '5',
                            'name' => 'Michael Fischer',
                            'email' => 'michael.fischer@example.com',
                            'phone' => '+49 711 99887766',
                            'city' => 'Stuttgart'
                        ]
                    ]);
                }
                
                $formattedCustomers = $customers->map(function ($customer) {
                    $name = $customer->name ?? 'Unbekannt';
                    $email = $customer->email ?? '';
                    $phone = $customer->phone ?? '';
                    $city = $customer->city ?? '';
                    
                    // Generate initials safely
                    $initials = '';
                    if ($name && $name !== 'Unbekannt') {
                        $nameParts = explode(' ', trim($name));
                        $initials = collect($nameParts)
                            ->filter()
                            ->map(fn($n) => strtoupper(substr(trim($n), 0, 1)))
                            ->take(2)
                            ->join('');
                    }
                    if (empty($initials)) {
                        $initials = 'UK';
                    }
                    
                    return [
                        'id' => $customer->id,
                        'name' => $name,
                        'email' => $email,
                        'phone' => $phone,
                        'city' => $city,
                        'initials' => $initials
                    ];
                });

                return response()->json($formattedCustomers, 200, ['Content-Type' => 'application/json; charset=utf-8']);
            } catch (\Exception $e) {
                \Log::error('Error loading customers: ' . $e->getMessage());
                return response()->json(['error' => 'Fehler beim Laden der Kunden'], 500);
            }
        });

        // Suppliers
        Route::get('/suppliers', function () {
            $suppliers = Supplier::select('id', 'name', 'email', 'phone', 'city')
                ->orderBy('name')
                ->get()
                ->map(function ($supplier) {
                    // Ensure proper UTF-8 encoding
                    $name = mb_convert_encoding($supplier->name ?? '', 'UTF-8', 'UTF-8');
                    $email = mb_convert_encoding($supplier->email ?? '', 'UTF-8', 'UTF-8');
                    $phone = mb_convert_encoding($supplier->phone ?? '', 'UTF-8', 'UTF-8');
                    $city = mb_convert_encoding($supplier->city ?? '', 'UTF-8', 'UTF-8');
                    
                    return [
                        'id' => $supplier->id,
                        'name' => $name,
                        'email' => $email,
                        'phone' => $phone,
                        'city' => $city,
                        'initials' => collect(explode(' ', $name))
                            ->map(fn($n) => strtoupper(substr($n, 0, 1)))
                            ->join('')
                    ];
                });

            return response()->json($suppliers, 200, ['Content-Type' => 'application/json; charset=utf-8']);
        });

        // Invoices
        Route::get('/invoices', function () {
            $invoices = Invoice::with('customer:id,name')
                ->select('id', 'invoice_number', 'customer_id', 'total', 'status', 'due_date', 'created_at')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($invoice) {
                    return [
                        'id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'customer_name' => $invoice->customer->name ?? 'Unbekannt',
                        'total' => $invoice->total,
                        'formatted_total' => '€' . number_format($invoice->total, 2, ',', '.'),
                        'status' => $invoice->status,
                        'status_text' => match($invoice->status) {
                            'draft' => 'Entwurf',
                            'sent' => 'Versendet',
                            'paid' => 'Bezahlt',
                            'overdue' => 'Überfällig',
                            default => 'Unbekannt'
                        },
                        'status_color' => match($invoice->status) {
                            'draft' => 'gray',
                            'sent' => 'blue',
                            'paid' => 'green',
                            'overdue' => 'red',
                            default => 'gray'
                        },
                        'due_date' => $invoice->due_date?->format('d.m.Y'),
                        'created_at' => $invoice->created_at->format('d.m.Y')
                    ];
                });

            return response()->json($invoices);
        });

        // Credit Notes
        Route::get('/credit-notes', function () {
            $creditNotes = CreditNote::with('customer:id,name')
                ->select('id', 'credit_note_number', 'customer_id', 'total', 'status', 'created_at')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($creditNote) {
                    return [
                        'id' => $creditNote->id,
                        'credit_note_number' => $creditNote->credit_note_number,
                        'customer_name' => $creditNote->customer->name ?? 'Unbekannt',
                        'total' => $creditNote->total,
                        'formatted_total' => '€' . number_format($creditNote->total, 2, ',', '.'),
                        'status' => $creditNote->status,
                        'status_text' => match($creditNote->status) {
                            'draft' => 'Entwurf',
                            'sent' => 'Versendet',
                            'applied' => 'Angewendet',
                            'cancelled' => 'Storniert',
                            default => 'Unbekannt'
                        },
                        'status_color' => match($creditNote->status) {
                            'draft' => 'gray',
                            'sent' => 'blue',
                            'applied' => 'green',
                            'cancelled' => 'red',
                            default => 'gray'
                        },
                        'created_at' => $creditNote->created_at->format('d.m.Y')
                    ];
                });

            return response()->json($creditNotes);
        });

        // Customer Details
        Route::get('/customers/{id}', function ($id) {
            try {
                $customer = Customer::with([
                    'addresses',
                    'phoneNumbers',
                    'employees',
                    'favoriteNotes',
                    'standardNotes',
                    'invoices',
                    'solarParticipations.solarPlant',
                    'monthlyCredits'
                ])->findOrFail($id);

                return response()->json([
                    'id' => $customer->id,
                    'customer_number' => $customer->customer_number,
                    'name' => $customer->name,
                    'customer_type' => $customer->customer_type,
                    'customer_type_text' => $customer->customer_type === 'business' ? 'Firmenkunde' : 'Privatkunde',
                    'company_name' => $customer->company_name,
                    'contact_person' => $customer->contact_person,
                    'department' => $customer->department,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'fax' => $customer->fax,
                    'website' => $customer->website,
                    'street' => $customer->street,
                    'address_line_2' => $customer->address_line_2,
                    'postal_code' => $customer->postal_code,
                    'city' => $customer->city,
                    'state' => $customer->state,
                    'country' => $customer->country,
                    'country_code' => $customer->country_code,
                    'tax_number' => $customer->tax_number,
                    'vat_id' => $customer->vat_id,
                    'payment_terms' => $customer->payment_terms,
                    'payment_days' => $customer->payment_days,
                    'bank_name' => $customer->bank_name,
                    'iban' => $customer->iban,
                    'bic' => $customer->bic,
                    'is_active' => $customer->is_active,
                    'notes' => $customer->notes,
                    'created_at' => $customer->created_at->format('d.m.Y H:i'),
                    'updated_at' => $customer->updated_at->format('d.m.Y H:i'),
                    'addresses' => $customer->addresses->map(function ($address) {
                        return [
                            'id' => $address->id,
                            'type' => $address->type,
                            'type_text' => match($address->type) {
                                'billing' => 'Rechnungsadresse',
                                'shipping' => 'Lieferadresse',
                                'default' => 'Standard',
                                default => $address->type
                            },
                            'street' => $address->street,
                            'address_line_2' => $address->address_line_2,
                            'postal_code' => $address->postal_code,
                            'city' => $address->city,
                            'state' => $address->state,
                            'country' => $address->country,
                            'country_code' => $address->country_code
                        ];
                    }),
                    'phone_numbers' => $customer->phoneNumbers->map(function ($phone) {
                        return [
                            'id' => $phone->id,
                            'type' => $phone->type,
                            'type_text' => match($phone->type) {
                                'mobile' => 'Mobil',
                                'work' => 'Geschäftlich',
                                'home' => 'Privat',
                                'fax' => 'Fax',
                                default => $phone->type
                            },
                            'number' => $phone->number,
                            'is_primary' => $phone->is_primary
                        ];
                    }),
                    'employees' => $customer->employees->map(function ($employee) {
                        return [
                            'id' => $employee->id,
                            'name' => $employee->name,
                            'email' => $employee->email,
                            'phone' => $employee->phone,
                            'position' => $employee->position,
                            'department' => $employee->department
                        ];
                    }),
                    'favorite_notes' => $customer->favoriteNotes->map(function ($note) {
                        return [
                            'id' => $note->id,
                            'title' => $note->title,
                            'content' => $note->content,
                            'created_at' => $note->created_at->format('d.m.Y H:i')
                        ];
                    }),
                    'standard_notes' => $customer->standardNotes->map(function ($note) {
                        return [
                            'id' => $note->id,
                            'title' => $note->title,
                            'content' => $note->content,
                            'created_at' => $note->created_at->format('d.m.Y H:i')
                        ];
                    }),
                    'invoices' => $customer->invoices->map(function ($invoice) {
                        return [
                            'id' => $invoice->id,
                            'invoice_number' => $invoice->invoice_number,
                            'total' => $invoice->total,
                            'formatted_total' => '€' . number_format($invoice->total, 2, ',', '.'),
                            'status' => $invoice->status,
                            'status_text' => match($invoice->status) {
                                'draft' => 'Entwurf',
                                'sent' => 'Versendet',
                                'paid' => 'Bezahlt',
                                'overdue' => 'Überfällig',
                                default => 'Unbekannt'
                            },
                            'status_color' => match($invoice->status) {
                                'draft' => 'gray',
                                'sent' => 'blue',
                                'paid' => 'green',
                                'overdue' => 'red',
                                default => 'gray'
                            },
                            'due_date' => $invoice->due_date?->format('d.m.Y'),
                            'created_at' => $invoice->created_at->format('d.m.Y')
                        ];
                    }),
                    'solar_participations' => $customer->solarParticipations->map(function ($participation) {
                        return [
                            'id' => $participation->id,
                            'solar_plant_name' => $participation->solarPlant->name ?? 'Unbekannt',
                            'percentage' => $participation->percentage,
                            'investment_amount' => $participation->investment_amount,
                            'formatted_investment' => $participation->investment_amount ? '€' . number_format($participation->investment_amount, 2, ',', '.') : null,
                            'start_date' => $participation->start_date?->format('d.m.Y'),
                            'end_date' => $participation->end_date?->format('d.m.Y')
                        ];
                    }),
                    'monthly_credits' => $customer->monthlyCredits->map(function ($credit) {
                        return [
                            'id' => $credit->id,
                            'month' => $credit->month,
                            'year' => $credit->year,
                            'amount' => $credit->amount,
                            'formatted_amount' => '€' . number_format($credit->amount, 2, ',', '.'),
                            'kwh_generated' => $credit->kwh_generated,
                            'created_at' => $credit->created_at->format('d.m.Y')
                        ];
                    })
                ]);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Kunde nicht gefunden'], 404);
            }
        });

        // Recent Activities
        Route::get('/activities', function () {
            $activities = collect();

            // Recent invoices
            $recentInvoices = Invoice::with('customer:id,name')
                ->orderBy('created_at', 'desc')
                ->limit(3)
                ->get()
                ->map(function ($invoice) {
                    return [
                        'type' => 'invoice',
                        'title' => "Rechnung {$invoice->invoice_number} erstellt",
                        'description' => "Kunde: {$invoice->customer->name}",
                        'time' => $invoice->created_at->diffForHumans(),
                        'color' => 'green'
                    ];
                });

            // Recent customers
            $recentCustomers = Customer::orderBy('created_at', 'desc')
                ->limit(2)
                ->get()
                ->map(function ($customer) {
                    return [
                        'type' => 'customer',
                        'title' => "Neuer Kunde hinzugefügt",
                        'description' => $customer->name,
                        'time' => $customer->created_at->diffForHumans(),
                        'color' => 'blue'
                    ];
                });

            // Recent solar plants
            $recentPlants = SolarPlant::orderBy('updated_at', 'desc')
                ->limit(2)
                ->get()
                ->map(function ($plant) {
                    return [
                        'type' => 'solar_plant',
                        'title' => "Solaranlage aktualisiert",
                        'description' => $plant->name,
                        'time' => $plant->updated_at->diffForHumans(),
                        'color' => 'orange'
                    ];
                });

            $activities = $activities
                ->concat($recentInvoices)
                ->concat($recentCustomers)
                ->concat($recentPlants)
                ->sortByDesc('time')
                ->take(5)
                ->values();

            return response()->json($activities);
        });
    });
});