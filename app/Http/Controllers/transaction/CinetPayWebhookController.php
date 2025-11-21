<?php

namespace App\Http\Controllers\transaction;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionResource;
use App\Models\Subscription;
use App\Models\WorkshopPurchase;
use App\Services\CinetPayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CinetPayWebhookController extends Controller
{
    public function __construct(
        private CinetPayService $cinetPay
    ) {}

    /**
     * Notification de paiement CinetPay
     * POST /api/webhooks/cinetpay/notify
     */
   public function notify(Request $request): JsonResponse
{
    Log::info('CinetPay Notification', $request->all());

    try {
        $transactionId = $request->input('cpm_trans_id')
            ?? $request->input('transaction_id')
            ?? null;

        if (!$transactionId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Transaction ID manquant'
            ], 400);
        }

        $status = $this->cinetPay->checkTransactionStatus($transactionId);

        if ($status['code'] === '00') {
            // ✅ Vérifier si c'est un abonnement ou un achat de workshop
            if (str_starts_with($transactionId, 'WRK-')) {
                // Achat de workshop
                $purchases = WorkshopPurchase::where('transaction_id', $transactionId)
                    ->where('status', 'pending')
                    ->get();

                foreach ($purchases as $purchase) {
                    $purchase->complete();

                    Log::info('Workshop Purchase Completed', [
                        'purchase_id' => $purchase->id,
                        'student_id' => $purchase->student_id,
                        'workshop_id' => $purchase->workshop_id,
                        'transaction_id' => $transactionId
                    ]);
                }
            } else {
                // Abonnement (code existant)
                $subscriptions = Subscription::where('transaction_id', $transactionId)
                    ->where('status', 'pending')
                    ->get();

                foreach ($subscriptions as $subscription) {
                    $subscription->activate();
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Notification traitée'
        ]);

    } catch (\Exception $e) {
        Log::error('CinetPay Notification Error', [
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'status' => 'error',
            'message' => 'Erreur lors du traitement'
        ], 500);
    }
}

    public function return(Request $request): JsonResponse
    {
        $transactionId = $request->input('transaction_id');

        if (!$transactionId) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction ID manquant'
            ], 400);
        }

        $subscription = Subscription::where('transaction_id', $transactionId)
            ->with(['plan', 'students'])
            ->first();

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Abonnement introuvable'
            ], 404);
        }

        // Vérifier le statut
        $status = $this->cinetPay->checkTransactionStatus($transactionId);

        if ($status['code'] === '00') {
            if ($subscription->status === 'pending') {
                $studentIds = $subscription->cinetpay_data['student_ids'] ?? [];
                $subscription->activate($studentIds);
            }

            return response()->json([
                'success' => true,
                'message' => 'Paiement validé avec succès',
                'data' => new SubscriptionResource($subscription),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Paiement non validé',
            'status' => $status
        ], 400);
    }
}
