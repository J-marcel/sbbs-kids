<?php

namespace App\Http\Controllers\transaction;

use App\Http\Controllers\Controller;
use App\Http\Resources\WorkshopPurchaseResource;
use App\Http\Resources\WorkshopResource;
use App\Models\ParentModel;
use App\Models\Student;
use App\Models\Workshop;
use App\Models\WorkshopPurchase;
use App\Services\CinetPayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class WorkshopPurchaseController extends Controller
{
    public function __construct(
        private CinetPayService $cinetPay
    ) {}

    /**
     * Liste de tous les workshops disponibles
     * GET /api/workshops
     */
    public function workshops(Request $request): JsonResponse
    {
        $workshops = Workshop::with('course')
            ->orderBy('title')
            ->get();

        return response()->json([
            'success' => true,
            'data' => WorkshopResource::collection($workshops),
        ]);
    }

    /**
     * Détails d'un workshop
     * GET /api/workshops/{workshop}
     */
    public function showWorkshop(Workshop $workshop): JsonResponse
    {
        $workshop->load('course');

        return response()->json([
            'success' => true,
            'data' => new WorkshopResource($workshop),
        ]);
    }

    /**
     * Mes achats de workshops
     * GET /api/workshop-purchases
     */
    public function index(Request $request): JsonResponse
    {
        $parent = ParentModel::where('user_id', $request->user()->id)->first();

        if (!$parent) {
            return response()->json([
                'success' => false,
                'message' => 'Profil parent non trouvé',
            ], 404);
        }

        $purchases = $parent->workshopPurchases()
            ->with(['workshop', 'student'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => WorkshopPurchaseResource::collection($purchases),
        ]);
    }

    /**
     * Acheter un ou plusieurs workshops
     * POST /api/workshop-purchases
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'purchases' => 'required|array|min:1',
            'purchases.*.student_id' => 'required|exists:students,id',
            'purchases.*.workshop_id' => 'required|exists:workshops,id',
            'phone' => 'required|string|min:8',
        ]);

        $parent = ParentModel::where('user_id', $request->user()->id)->first();

        if (!$parent) {
            return response()->json([
                'success' => false,
                'message' => 'Profil parent non trouvé',
            ], 404);
        }

        $studentIds = collect($validated['purchases'])->pluck('student_id')->unique()->toArray();

        // Vérifier que tous les students appartiennent au parent
        $students = $parent->students()
            ->whereIn('id', $studentIds)
            ->get();

        if ($students->count() !== count($studentIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Certains élèves ne vous appartiennent pas',
            ], 422);
        }

        // Valider chaque achat
        $purchaseData = [];
        $errors = [];

        foreach ($validated['purchases'] as $index => $purchaseInfo) {
            $student = $students->firstWhere('id', $purchaseInfo['student_id']);
            $workshop = Workshop::find($purchaseInfo['workshop_id']);

            if (!$student) {
                $errors[] = "Élève ID {$purchaseInfo['student_id']} non trouvé";
                continue;
            }

            if (!$workshop) {
                $errors[] = "Workshop ID {$purchaseInfo['workshop_id']} non trouvé";
                continue;
            }

            // Vérifier si le student a déjà acheté ce workshop
            if ($student->hasPurchasedWorkshop($workshop)) {
                $errors[] = "L'élève {$student->name} a déjà acheté le workshop '{$workshop->title}'";
                continue;
            }

            $purchaseData[] = [
                'student' => $student,
                'workshop' => $workshop,
            ];
        }

        if (!empty($errors)) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $errors,
            ], 422);
        }

        if (empty($purchaseData)) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun achat valide à effectuer',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $paymentGroupId = 'WRK-PAY-' . strtoupper(Str::random(10)) . '-' . time();
            $transactionId = 'WRK-' . strtoupper(Str::random(10)) . '-' . time();

            $totalAmount = collect($purchaseData)->sum(fn($data) => $data['workshop']->price);

            $createdPurchases = [];
            $purchaseDetails = [];

            foreach ($purchaseData as $data) {
                $student = $data['student'];
                $workshop = $data['workshop'];

                $purchase = WorkshopPurchase::create([
                    'parent_model_id' => $parent->id,
                    'student_id' => $student->id,
                    'workshop_id' => $workshop->id,
                    'transaction_id' => $transactionId,
                    'payment_group_id' => $paymentGroupId,
                    'status' => 'pending',
                    'amount_paid' => $workshop->price,
                ]);

                $purchase->setRelation('workshop', $workshop);
                $purchase->setRelation('student', $student);

                $createdPurchases[] = $purchase;
                $purchaseDetails[] = [
                    'student' => $student->name,
                    'workshop' => $workshop->title,
                    'price' => $workshop->price,
                ];
            }

            $description = "Achat de " . count($purchaseData) . " workshop(s): " .
                collect($purchaseDetails)->map(fn($d) => "{$d['workshop']} pour {$d['student']}")->join(', ');

            $paymentData = [
                'transaction_id' => $transactionId,
                'amount' => $totalAmount,
                'description' => $description,
                'customer_name' => $parent->name,
                'customer_surname' => $parent->name,
                'customer_email' => $parent->email,
                'customer_phone_number' => $validated['phone'],
                'metadata' => [
                    'payment_group_id' => $paymentGroupId,
                    'parent_id' => $parent->id,
                    'purchases' => $validated['purchases'],
                    'purchase_ids' => collect($createdPurchases)->pluck('id')->toArray(),
                    'type' => 'workshop_purchase',
                ]
            ];

            Log::info('Creating Workshop Purchases', [
                'parent_id' => $parent->id,
                'total_amount' => $totalAmount,
                'purchases_count' => count($createdPurchases),
                'details' => $purchaseDetails
            ]);

            $paymentResult = $this->cinetPay->initiatePayment($paymentData);

            if (!$paymentResult['success']) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de l\'initialisation du paiement',
                    'error' => $paymentResult['message'] ?? 'Erreur inconnue',
                ], 500);
            }

            foreach ($createdPurchases as $purchase) {
                $purchase->update([
                    'cinetpay_data' => $paymentResult['data']
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Achats créés avec succès',
                'data' => [
                    'purchases' => WorkshopPurchaseResource::collection($createdPurchases),
                    'summary' => $purchaseDetails,
                    'total_amount' => $totalAmount,
                    'workshops_count' => count($createdPurchases),
                    'payment_url' => $paymentResult['payment_url'],
                    'payment_token' => $paymentResult['payment_token'],
                    'payment_group_id' => $paymentGroupId,
                    'transaction_id' => $transactionId,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Workshop Purchase Creation Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création des achats',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Détails d'un achat
     * GET /api/workshop-purchases/{purchase}
     */
    public function show(Request $request, WorkshopPurchase $purchase): JsonResponse
    {
        $parent = ParentModel::where('user_id', $request->user()->id)->first();

        if (!$parent || $purchase->parent_model_id !== $parent->id) {
            return response()->json([
                'success' => false,
                'message' => 'Achat non trouvé',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new WorkshopPurchaseResource($purchase->load(['workshop', 'student'])),
        ]);
    }

    /**
     * Vérifier le statut d'un achat
     * GET /api/workshop-purchases/{purchase}/status
     */
    public function checkStatus(Request $request, WorkshopPurchase $purchase): JsonResponse
    {
        $parent = ParentModel::where('user_id', $request->user()->id)->first();

        if (!$parent || $purchase->parent_model_id !== $parent->id) {
            return response()->json([
                'success' => false,
                'message' => 'Achat non trouvé',
            ], 404);
        }

        // Vérifier le statut auprès de CinetPay
        $status = $this->cinetPay->checkTransactionStatus($purchase->transaction_id);

        if ($status['code'] === '00' && $purchase->status === 'pending') {
            // Compléter TOUS les achats du même groupe de paiement
            WorkshopPurchase::where('payment_group_id', $purchase->payment_group_id)
                ->where('status', 'pending')
                ->get()
                ->each(function($p) {
                    $p->complete();
                });
        }

        return response()->json([
            'success' => true,
            'data' => new WorkshopPurchaseResource($purchase->fresh(['workshop', 'student'])),
            'cinetpay_status' => $status,
        ]);
    }
}
