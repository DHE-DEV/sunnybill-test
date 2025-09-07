<?php

namespace App\Jobs;

use App\Models\SimCard;
use App\Services\OnceApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RefreshSimCardDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): array
    {
        Log::info('RefreshSimCardDataJob: Starting automatic SIM card data refresh');

        try {
            $onceService = new OnceApiService();

            // Test connection first
            if (!$onceService->testConnection()) {
                Log::warning('RefreshSimCardDataJob: Unable to connect to 1nce API');
                return [
                    'success' => false,
                    'message' => 'Unable to connect to 1nce API',
                    'updated' => 0,
                    'errors' => 1
                ];
            }

            // Get updated SIM card data from 1nce API
            $apiSimCards = $onceService->getSimCards();

            if (empty($apiSimCards)) {
                Log::warning('RefreshSimCardDataJob: No SIM card data received from 1nce API');
                return [
                    'success' => false,
                    'message' => 'No SIM card data received from 1nce API',
                    'updated' => 0,
                    'errors' => 0
                ];
            }

            $updated = 0;
            $errors = 0;

            DB::transaction(function () use ($apiSimCards, &$updated, &$errors) {
                foreach ($apiSimCards as $simData) {
                    try {
                        if (empty($simData['iccid'])) {
                            $errors++;
                            Log::warning('RefreshSimCardDataJob: SIM card without ICCID skipped', $simData);
                            continue;
                        }

                        // Find existing SIM card by ICCID
                        $existingSimCard = SimCard::where('iccid', $simData['iccid'])->first();

                        if ($existingSimCard) {
                            // Only update specific fields that change frequently
                            $updateData = [
                                'status' => $simData['status'],
                                'signal_strength' => $simData['signal_strength'],
                                'last_activity' => $simData['last_activity'],
                                'data_used_mb' => $simData['data_used_mb'],
                            ];

                            // Remove null values to avoid overwriting existing data with nulls
                            $updateData = array_filter($updateData, function ($value) {
                                return $value !== null;
                            });

                            if (!empty($updateData)) {
                                $existingSimCard->update($updateData);
                                $updated++;
                                Log::debug('RefreshSimCardDataJob: Updated SIM card', [
                                    'iccid' => $simData['iccid'],
                                    'updated_fields' => array_keys($updateData)
                                ]);
                            }
                        } else {
                            // This is a new SIM card from the API, create it
                            SimCard::create($simData);
                            $updated++;
                            Log::info('RefreshSimCardDataJob: Created new SIM card from API', ['iccid' => $simData['iccid']]);
                        }
                    } catch (\Exception $e) {
                        $errors++;
                        Log::error('RefreshSimCardDataJob: Error processing SIM card', [
                            'iccid' => $simData['iccid'] ?? 'unknown',
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            });

            if ($updated > 0) {
                Log::info("RefreshSimCardDataJob: Successfully updated {$updated} SIM cards" . 
                         ($errors > 0 ? " ({$errors} errors)" : ""));
            } else {
                Log::info("RefreshSimCardDataJob: No SIM cards needed updates" . 
                         ($errors > 0 ? " ({$errors} errors occurred)" : ""));
            }

            return [
                'success' => true,
                'message' => "Successfully processed SIM cards",
                'updated' => $updated,
                'errors' => $errors
            ];

        } catch (\Exception $e) {
            Log::error('RefreshSimCardDataJob: General error during refresh', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'updated' => 0,
                'errors' => 1
            ];
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('RefreshSimCardDataJob: Job failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
