@php
    use App\Models\SupplierContractBilling;
    use Filament\Tables;
    use Filament\Tables\Table;
    use Illuminate\Database\Eloquent\Builder;
    
    // Hole die Abrechnungen für diesen Vertrag
    $billings = $contract->billings()
        ->orderByRaw('billing_year DESC, billing_month DESC')
        ->get();
@endphp

<div class="space-y-4">
    @if($billings->isEmpty())
        <div class="text-center py-8 text-gray-500">
            <div class="text-lg font-medium">Keine Abrechnungen vorhanden</div>
            <div class="text-sm">Für diesen Vertrag wurden noch keine Abrechnungen erstellt.</div>
        </div>
    @else
        {{-- Übersicht --}}
        @php
            $totalAmount = $billings->sum('total_amount');
            $paidAmount = $billings->where('status', 'paid')->sum('total_amount');
            $pendingAmount = $billings->whereIn('status', ['pending', 'approved'])->sum('total_amount');
        @endphp
        
        <div class="bg-gray-50 p-4 rounded-lg">
            <h3 class="font-semibold text-gray-900 mb-3">Übersicht</h3>
            <div class="grid grid-cols-4 gap-4 text-sm">
                <div>
                    <span class="text-gray-600">Gesamt:</span><br>
                    <span class="font-semibold text-lg">€{{ number_format($totalAmount, 2, ',', '.') }}</span>
                </div>
                <div>
                    <span class="text-gray-600">Bezahlt:</span><br>
                    <span class="font-semibold text-lg text-green-600">€{{ number_format($paidAmount, 2, ',', '.') }}</span>
                </div>
                <div>
                    <span class="text-gray-600">Ausstehend:</span><br>
                    <span class="font-semibold text-lg text-orange-600">€{{ number_format($pendingAmount, 2, ',', '.') }}</span>
                </div>
                <div>
                    <span class="text-gray-600">Anzahl:</span><br>
                    <span class="font-semibold text-lg">{{ $billings->count() }}</span>
                </div>
            </div>
        </div>

        {{-- Abrechnungen-Tabelle --}}
        <div>
            <h3 class="font-semibold text-gray-900 mb-3">Abrechnungen (sortiert nach Periode)</h3>
            
            <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Abrechnungsnummer
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Periode
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Titel
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Datum
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Betrag
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Aktionen
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($billings as $billing)
                            @php
                                $statusColor = match($billing->status) {
                                    'paid' => 'bg-green-100 text-green-800',
                                    'pending' => 'bg-orange-100 text-orange-800',
                                    'approved' => 'bg-blue-100 text-blue-800',
                                    'cancelled' => 'bg-red-100 text-red-800',
                                    default => 'bg-gray-100 text-gray-800'
                                };
                                
                                $statusText = match($billing->status) {
                                    'draft' => 'Entwurf',
                                    'pending' => 'Ausstehend',
                                    'approved' => 'Genehmigt',
                                    'paid' => 'Bezahlt',
                                    'cancelled' => 'Storniert',
                                    default => $billing->status
                                };
                                
                                $period = $billing->billing_period ?? ($billing->billing_year . '-' . str_pad($billing->billing_month, 2, '0', STR_PAD_LEFT));
                            @endphp
                            
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $billing->billing_number ?? 'Keine Nummer' }}
                                    </div>
                                    @if($billing->supplier_invoice_number)
                                        <div class="text-xs text-gray-500">
                                            Rechnung: {{ $billing->supplier_invoice_number }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ $period }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">{{ $billing->title }}</div>
                                    @if($billing->description)
                                        <div class="text-xs text-gray-500 mt-1">
                                            {{ Str::limit($billing->description, 60) }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @if($billing->billing_date)
                                        <div>{{ $billing->billing_date->format('d.m.Y') }}</div>
                                        @if($billing->due_date)
                                            <div class="text-xs text-gray-500">
                                                Fällig: {{ $billing->due_date->format('d.m.Y') }}
                                            </div>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900">
                                        €{{ number_format($billing->total_amount, 2, ',', '.') }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColor }}">
                                        {{ $statusText }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="{{ route('filament.admin.resources.supplier-contract-billings.view', $billing) }}" 
                                           target="_blank"
                                           class="text-indigo-600 hover:text-indigo-900">
                                            Anzeigen
                                        </a>
                                        
                                        @php
                                            $pdfDocument = $billing->documents()
                                                ->where(function($query) {
                                                    $query->where('mime_type', 'application/pdf')
                                                          ->orWhere('path', 'like', '%.pdf')
                                                          ->orWhere('original_name', 'like', '%.pdf');
                                                })
                                                ->first();
                                        @endphp
                                        
                                        @if($pdfDocument)
                                            <a href="{{ route('documents.preview', $pdfDocument->id) }}" 
                                               target="_blank"
                                               class="text-blue-600 hover:text-blue-900">
                                                PDF
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>