<?php

namespace App\Http\Controllers\transaction;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionPlanResource;
use App\Http\Resources\SubscriptionResource;
use App\Models\ParentModel;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\CinetPayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubscriptionController extends Controller
{
    public function __construct(
        private CinetPayService $cinetPay
    ) {}

    /**
     * Liste des plans d'abonnement
     * GET /api/subscription-plans
     */
    public function plans(Request $request): JsonResponse
    {
        $query = SubscriptionPlan::active();

        if ($request->has('age_group')) {
            $query->where('age_group', $request->age_group);
        }

        if ($request->has('duration_months')) {
            $query->where('duration_months', $request->duration_months);
        }

        $plans = $query->orderBy('age_group')
            ->orderBy('duration_months')
            ->get();

        return response()->json([
            'success' => true,
            'data' => SubscriptionPlanResource::collection($plans),
        ]);
    }

    /**
     * Mes abonnements (en tant que parent)
     * GET /api/subscriptions
     */
    public function index(Request $request): JsonResponse
    {
        // Récupérer le ParentModel de l'utilisateur connecté
        $parent = ParentModel::where('user_id', $request->user()->id)->first();

        if (!$parent) {
            return response()->json([
                'success' => false,
                'message' => 'Profil parent non trouvé',
            ], 404);
        }

        $subscriptions = $parent->subscriptions()
            ->with(['plan', 'students'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => SubscriptionResource::collection($subscriptions),
        ]);
    }

    /**
     * Abonnement actif
     * GET /api/subscriptions/active
     */
    public function active(Request $request): JsonResponse
    {
        $parent = ParentModel::where('user_id', $request->user()->id)->first();

        if (!$parent) {
            return response()->json([
                'success' => false,
                'message' => 'Profil parent non trouvé',
            ], 404);
        }

        $subscription = $parent->subscriptions()
            ->active()
            ->with(['plan', 'students'])
            ->first();

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun abonnement actif',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new SubscriptionResource($subscription),
        ]);
    }

    /**
     * Initier un nouvel abonnement
     * POST /api/subscriptions
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'required|exists:students,id',
            'phone' => 'required|string|min:8',
        ]);

        $plan = SubscriptionPlan::findOrFail($validated['plan_id']);

        // Récupérer le ParentModel
        $parent = ParentModel::where('user_id', $request->user()->id)->first();

        if (!$parent) {
            return response()->json([
                'success' => false,
                'message' => 'Profil parent non trouvé',
            ], 404);
        }

        // Vérifier que les students appartiennent au parent
        $students = $parent->students()
            ->whereIn('id', $validated['student_ids'])
            ->get();

        if ($students->count() !== count($validated['student_ids'])) {
            return response()->json([
                'success' => false,
                'message' => 'Certains élèves ne vous appartiennent pas',
            ], 422);
        }

        // Vérifier que les students ont le bon age_group
        $invalidStudents = $students->where('age_group', '!=', $plan->age_group);
        if ($invalidStudents->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => "Certains élèves n'appartiennent pas au groupe d'âge {$plan->age_group}",
                'invalid_students' => $invalidStudents->pluck('name'),
            ], 422);
        }

        // ✅ MODIFICATION ICI : Vérifier par groupe d'âge uniquement
        $activeSubscription = $parent->subscriptions()
            ->active()
            ->whereHas('plan', function ($query) use ($plan) {
                $query->where('age_group', $plan->age_group);
            })
            ->first();

        if ($activeSubscription) {
            return response()->json([
                'success' => false,
                'message' => "Vous avez déjà un abonnement actif pour le groupe d'âge {$plan->age_group}",
                'data' => new SubscriptionResource($activeSubscription->load(['plan', 'students'])),
            ], 422);
        }

        // ✅ AJOUT : Vérifier que les élèves n'ont pas déjà un abonnement actif
        $studentsWithActiveSubscription = $students->filter(function ($student) {
            return $student->hasActiveSubscription();
        });

        if ($studentsWithActiveSubscription->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Certains élèves ont déjà un abonnement actif',
                'students' => $studentsWithActiveSubscription->pluck('name'),
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Générer un ID de transaction unique
            $transactionId = 'SUB-' . strtoupper(Str::random(10)) . '-' . time();

            // Créer l'abonnement en attente
            $subscription = Subscription::create([
                'parent_model_id' => $parent->id,
                'subscription_plan_id' => $plan->id,
                'transaction_id' => $transactionId,
                'status' => 'pending',
                'amount_paid' => $plan->price,
            ]);

            // Préparer les données pour CinetPay
            $paymentData = [
                'transaction_id' => $transactionId,
                'amount' => $plan->price,
                'description' => "Abonnement {$plan->name} pour " . $students->count() . " élève(s)",
                'customer_name' => $parent->name,
                'customer_surname' => $parent->name,
                'customer_email' => $parent->email,
                'customer_phone_number' => $validated['phone'],
                'metadata' => [
                    'subscription_id' => $subscription->id,
                    'plan_id' => $plan->id,
                    'parent_id' => $parent->id,
                    'student_ids' => $validated['student_ids'],
                ]
            ];

            // Initier le paiement
            $paymentResult = $this->cinetPay->initiatePayment($paymentData);

            if (!$paymentResult['success']) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de l\'initialisation du paiement',
                    'error' => $paymentResult['message'] ?? 'Erreur inconnue',
                ], 500);
            }

            // Sauvegarder les données CinetPay
            $subscription->update([
                'cinetpay_data' => array_merge(
                    $paymentResult['data'],
                    ['student_ids' => $validated['student_ids']]
                )
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Abonnement créé avec succès',
                'data' => [
                    'subscription' => new SubscriptionResource($subscription->load('plan')),
                    'students' => $students->pluck('name'),
                    'payment_url' => $paymentResult['payment_url'],
                    'payment_token' => $paymentResult['payment_token'],
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'abonnement',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Vérifier le statut d'un abonnement
     * GET /api/subscriptions/{subscription}/status
     */
    public function checkStatus(Request $request, Subscription $subscription): JsonResponse
    {
        $parent = ParentModel::where('user_id', $request->user()->id)->first();

        if (!$parent || $subscription->parent_model_id !== $parent->id) {
            return response()->json([
                'success' => false,
                'message' => 'Abonnement non trouvé',
            ], 404);
        }

        // Vérifier le statut auprès de CinetPay
        $status = $this->cinetPay->checkTransactionStatus($subscription->transaction_id);

        if ($status['code'] === '00' && $subscription->status === 'pending') {
            // Récupérer les student_ids depuis cinetpay_data
            $studentIds = $subscription->cinetpay_data['student_ids'] ?? [];
            $subscription->activate($studentIds);
        }

        return response()->json([
            'success' => true,
            'data' => new SubscriptionResource($subscription->fresh(['plan', 'students'])),
            'cinetpay_status' => $status,
        ]);
    }
}
