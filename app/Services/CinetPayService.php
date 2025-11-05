<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CinetPayService
{
    private string $apiKey;
    private string $siteId;
    private string $apiUrl;
    private string $notifyUrl;
    private string $returnUrl;

    public function __construct()
    {
        $this->apiKey = config('services.cinetpay.api_key');
        $this->siteId = config('services.cinetpay.site_id');
        $this->apiUrl = config('services.cinetpay.api_url', 'https://api-checkout.cinetpay.com/v2/payment');
        $this->notifyUrl = url('/api/webhooks/cinetpay/notify');
        $this->returnUrl = url('/api/webhooks/cinetpay/return');
    }

    /**
     * Initier un paiement
     */
    public function initiatePayment(array $data): array
    {
        try {
            // ✅ S'assurer que le montant est un entier
            $amount = (int) $data['amount'];

            // ✅ Vérifier le montant minimum
            if ($amount < 100) {
                throw new Exception("Le montant doit être au moins 100 XOF (reçu: {$amount})");
            }

            $payload = [
                'apikey' => $this->apiKey,
                'site_id' => $this->siteId,
                'transaction_id' => $data['transaction_id'],
                'amount' => $amount, // ✅ Utiliser la variable vérifiée
                'currency' => 'XOF',
                'description' => $data['description'],
                'customer_name' => $data['customer_name'] ?? '',
                'customer_surname' => $data['customer_surname'] ?? '',
                'customer_email' => $data['customer_email'] ?? '',
                'customer_phone_number' => $data['customer_phone_number'] ?? '',
                'customer_address' => $data['customer_address'] ?? '',
                'customer_city' => $data['customer_city'] ?? 'Abidjan',
                'customer_country' => $data['customer_country'] ?? 'CI',
                'customer_state' => $data['customer_state'] ?? 'CI',
                'customer_zip_code' => $data['customer_zip_code'] ?? '00000',
                'notify_url' => $this->notifyUrl,
                'return_url' => $this->returnUrl,
                'channels' => 'ALL',
                'metadata' => is_string($data['metadata'] ?? null)
                    ? $data['metadata']
                    : json_encode($data['metadata'] ?? []),
            ];

            Log::info('CinetPay Request', [
                'url' => $this->apiUrl,
                'amount_original' => $data['amount'],
                'amount_converted' => $amount,
                'payload' => array_merge($payload, [
                    'apikey' => '***HIDDEN***'
                ])
            ]);

            $response = Http::timeout(30)->post($this->apiUrl, $payload);

            // ✅ Logger la réponse brute
            Log::info('CinetPay Response', [
                'status' => $response->status(),
                'body' => $response->body(),
                'json' => $response->json(),
            ]);

            if ($response->successful()) {
                $result = $response->json();

                // ✅ Vérifier la structure de la réponse
                if (!isset($result['code'])) {
                    Log::error('CinetPay Invalid Response Structure', ['result' => $result]);
                    throw new Exception('Réponse CinetPay invalide : code manquant');
                }

                if ($result['code'] === '201') {
                    return [
                        'success' => true,
                        'payment_url' => $result['data']['payment_url'] ?? null,
                        'payment_token' => $result['data']['payment_token'] ?? null,
                        'data' => $result['data'] ?? []
                    ];
                }

                // ✅ Logger les erreurs CinetPay
                Log::error('CinetPay Error Response', [
                    'code' => $result['code'],
                    'message' => $result['message'] ?? 'Aucun message',
                    'data' => $result['data'] ?? null
                ]);

                throw new Exception($result['message'] ?? 'Erreur CinetPay : ' . $result['code']);
            }

            // ✅ Erreur HTTP
            Log::error('CinetPay HTTP Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            throw new Exception('Erreur HTTP ' . $response->status() . ' : ' . $response->body());
        } catch (Exception $e) {
            Log::error('CinetPay Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Vérifier le statut d'une transaction
     */
    public function checkTransactionStatus(string $transactionId): array
    {
        try {
            $payload = [
                'apikey' => $this->apiKey,
                'site_id' => $this->siteId,
                'transaction_id' => $transactionId
            ];

            Log::info('CinetPay Check Status Request', [
                'transaction_id' => $transactionId
            ]);

            $response = Http::timeout(30)->post(
                'https://api-checkout.cinetpay.com/v2/payment/check',
                $payload
            );

            Log::info('CinetPay Check Status Response', [
                'status' => $response->status(),
                'body' => $response->json()
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            throw new Exception('Erreur lors de la vérification du paiement : ' . $response->body());
        } catch (Exception $e) {
            Log::error('CinetPay Check Status Error', [
                'message' => $e->getMessage()
            ]);

            return [
                'code' => '500',
                'message' => $e->getMessage()
            ];
        }
    }
}
