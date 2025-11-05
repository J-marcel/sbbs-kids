<?php

namespace App\Http\Controllers\transaction;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionResource;
use App\Models\Subscription;
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
            $transactionId = $request->input('cpm_trans_id');

            if (!$transactionId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Transaction ID manquant'
                ], 400);
            }

            // Vérifier le statut auprès de CinetPay
            $status = $this->cinetPay->checkTransactionStatus($transactionId);

            Log::info('CinetPay Status Check', [
                'transaction_id' => $transactionId,
                'status' => $status
            ]);

            // Si le paiement est validé
            if ($status['code'] === '00') {
                $subscription = Subscription::where('transaction_id', $transactionId)->first();

                if ($subscription && $subscription->status === 'pending') {
                    // Récupérer les student_ids depuis cinetpay_data
                    $studentIds = $subscription->cinetpay_data['student_ids'] ?? [];
                    $subscription->activate($studentIds);

                    Log::info('Subscription Activated', [
                        'subscription_id' => $subscription->id,
                        'transaction_id' => $transactionId,
                        'students_count' => count($studentIds)
                    ]);
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Notification traitée'
            ]);

        } catch (\Exception $e) {
            Log::error('CinetPay Notification Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
