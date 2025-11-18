<?php

namespace App\Http\Controllers\transaction;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionPlanResource;
use App\Http\Resources\SubscriptionResource;
use App\Models\ParentModel;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\CinetPayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    public function __construct(
        private CinetPayService $cinetPay
    ) {}

    /**
     * Liste des plans d'abonnement
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
     * Mes abonnements
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

        $subscriptions = $parent->subscriptions()
            ->with(['plan', 'student'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => SubscriptionResource::collection($subscriptions),
        ]);
    }

    /**
     * Abonnements actifs
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

        $subscriptions = $parent->subscriptions()
            ->active()
            ->with(['plan', 'student'])
            ->get();

        if ($subscriptions->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun abonnement actif',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => SubscriptionResource::collection($subscriptions),
        ]);
    }

    /**
     * Créer plusieurs abonnements (chaque élève avec son propre plan)
     */
    // public function store(Request $request): JsonResponse
    // {
    //     // ✅ Nouvelle validation pour accepter plusieurs paires (student_id, plan_id)
    //     $validated = $request->validate([
    //         'subscriptions' => 'required|array|min:1',
    //         'subscriptions.*.student_id' => 'required|exists:students,id',
    //         'subscriptions.*.plan_id' => 'required|exists:subscription_plans,id',
    //         'phone' => 'required|string|min:8',
    //     ]);

    //     $parent = ParentModel::where('user_id', $request->user()->id)->first();

    //     if (!$parent) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Profil parent non trouvé',
    //         ], 404);
    //     }

    //     // ✅ Récupérer tous les student_ids
    //     $studentIds = collect($validated['subscriptions'])->pluck('student_id')->unique()->toArray();

    //     // Vérifier que tous les students appartiennent au parent
    //     $students = $parent->students()
    //         ->whereIn('id', $studentIds)
    //         ->get();

    //     if ($students->count() !== count($studentIds)) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Certains élèves ne vous appartiennent pas',
    //         ], 422);
    //     }

    //     // ✅ Valider chaque paire (student, plan)
    //     $subscriptionData = [];
    //     $errors = [];

    //     foreach ($validated['subscriptions'] as $index => $subData) {
    //         $student = $students->firstWhere('id', $subData['student_id']);
    //         $plan = SubscriptionPlan::find($subData['plan_id']);

    //         if (!$student) {
    //             $errors[] = "Élève ID {$subData['student_id']} non trouvé";
    //             continue;
    //         }

    //         if (!$plan) {
    //             $errors[] = "Plan ID {$subData['plan_id']} non trouvé";
    //             continue;
    //         }

    //         // Vérifier que le student correspond au groupe d'âge du plan
    //         if ($student->age_group !== $plan->age_group) {
    //             $errors[] = "L'élève {$student->name} (groupe {$student->age_group}) ne correspond pas au plan {$plan->name} (groupe {$plan->age_group})";
    //             continue;
    //         }

    //         // Vérifier si le student a déjà un abonnement actif
    //         if ($student->hasActiveSubscription()) {
    //             $errors[] = "L'élève {$student->name} a déjà un abonnement actif";
    //             continue;
    //         }

    //         $subscriptionData[] = [
    //             'student' => $student,
    //             'plan' => $plan,
    //         ];
    //     }

    //     if (!empty($errors)) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Erreurs de validation',
    //             'errors' => $errors,
    //         ], 422);
    //     }

    //     if (empty($subscriptionData)) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Aucun abonnement valide à créer',
    //         ], 422);
    //     }

    //     try {
    //         DB::beginTransaction();

    //         // ✅ Générer des identifiants uniques
    //         $paymentGroupId = 'PAY-' . strtoupper(Str::random(10)) . '-' . time();
    //         $transactionId = 'SUB-' . strtoupper(Str::random(10)) . '-' . time();

    //         // ✅ Calculer le montant total
    //         $totalAmount = collect($subscriptionData)->sum(fn($data) => $data['plan']->price);

    //         // ✅ Créer un abonnement pour chaque paire (student, plan)
    //         $createdSubscriptions = [];
    //         $subscriptionDetails = [];

    //         foreach ($subscriptionData as $data) {
    //             $student = $data['student'];
    //             $plan = $data['plan'];

    //             $subscription = Subscription::create([
    //                 'parent_model_id' => $parent->id,
    //                 'student_id' => $student->id,
    //                 'subscription_plan_id' => $plan->id,
    //                 'transaction_id' => $transactionId,
    //                 'payment_group_id' => $paymentGroupId,
    //                 'status' => 'pending',
    //                 'amount_paid' => $plan->price,
    //             ]);

    //             $createdSubscriptions[] = $subscription;
    //             $subscriptionDetails[] = [
    //                 'student' => $student->name,
    //                 'plan' => $plan->name,
    //                 'price' => $plan->price,
    //             ];
    //         }

    //         // ✅ Description détaillée pour CinetPay
    //         $description = "Abonnement pour " . count($subscriptionData) . " élève(s): " .
    //             collect($subscriptionDetails)->map(fn($d) => "{$d['student']} ({$d['plan']})")->join(', ');

    //         // Préparer les données pour CinetPay
    //         $paymentData = [
    //             'transaction_id' => $transactionId,
    //             'amount' => $totalAmount,
    //             'description' => $description,
    //             'customer_name' => $parent->name,
    //             'customer_surname' => $parent->name,
    //             'customer_email' => $parent->email,
    //             'customer_phone_number' => $validated['phone'],
    //             'metadata' => [
    //                 'payment_group_id' => $paymentGroupId,
    //                 'parent_id' => $parent->id,
    //                 'subscriptions' => $validated['subscriptions'],
    //                 'subscription_ids' => collect($createdSubscriptions)->pluck('id')->toArray(),
    //             ]
    //         ];

    //         Log::info('Creating Multiple Subscriptions', [
    //             'parent_id' => $parent->id,
    //             'total_amount' => $totalAmount,
    //             'subscriptions_count' => count($createdSubscriptions),
    //             'details' => $subscriptionDetails
    //         ]);

    //         // Initier le paiement
    //         $paymentResult = $this->cinetPay->initiatePayment($paymentData);

    //         if (!$paymentResult['success']) {
    //             DB::rollBack();
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Erreur lors de l\'initialisation du paiement',
    //                 'error' => $paymentResult['message'] ?? 'Erreur inconnue',
    //             ], 500);
    //         }

    //         // Sauvegarder les données CinetPay dans tous les abonnements
    //         foreach ($createdSubscriptions as $subscription) {
    //             $subscription->update([
    //                 'cinetpay_data' => $paymentResult['data']
    //             ]);
    //         }

    //         DB::commit();

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Abonnements créés avec succès',
    //             'data' => [
    //                 'subscriptions' => SubscriptionResource::collection(
    //                     collect($createdSubscriptions)->load(['plan', 'student'])
    //                 ),
    //                 'summary' => $subscriptionDetails,
    //                 'total_amount' => $totalAmount,
    //                 'students_count' => count($createdSubscriptions),
    //                 'payment_url' => $paymentResult['payment_url'],
    //                 'payment_token' => $paymentResult['payment_token'],
    //                 'payment_group_id' => $paymentGroupId,
    //                 'transaction_id' => $transactionId,
    //             ],
    //         ], 201);

    //     } catch (\Exception $e) {
    //         DB::rollBack();

    //         Log::error('Subscription Creation Error', [
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString()
    //         ]);

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Erreur lors de la création des abonnements',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subscriptions' => 'required|array|min:1',
            'subscriptions.*.student_id' => 'required|exists:students,id',
            'subscriptions.*.plan_id' => 'required|exists:subscription_plans,id',
            'phone' => 'required|string|min:8',
        ]);

        $parent = ParentModel::where('user_id', $request->user()->id)->first();

        if (!$parent) {
            return response()->json([
                'success' => false,
                'message' => 'Profil parent non trouvé',
            ], 404);
        }

        $studentIds = collect($validated['subscriptions'])->pluck('student_id')->unique()->toArray();

        $students = $parent->students()
            ->whereIn('id', $studentIds)
            ->get();

        if ($students->count() !== count($studentIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Certains élèves ne vous appartiennent pas',
            ], 422);
        }

        $subscriptionData = [];
        $errors = [];

        foreach ($validated['subscriptions'] as $index => $subData) {
            $student = $students->firstWhere('id', $subData['student_id']);
            $plan = SubscriptionPlan::find($subData['plan_id']);

            if (!$student) {
                $errors[] = "Élève ID {$subData['student_id']} non trouvé";
                continue;
            }

            if (!$plan) {
                $errors[] = "Plan ID {$subData['plan_id']} non trouvé";
                continue;
            }

            if ($student->age_group !== $plan->age_group) {
                $errors[] = "L'élève {$student->name} (groupe {$student->age_group}) ne correspond pas au plan {$plan->name} (groupe {$plan->age_group})";
                continue;
            }

            if ($student->hasActiveSubscription()) {
                $errors[] = "L'élève {$student->name} a déjà un abonnement actif";
                continue;
            }

            $subscriptionData[] = [
                'student' => $student,
                'plan' => $plan,
            ];
        }

        if (!empty($errors)) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $errors,
            ], 422);
        }

        if (empty($subscriptionData)) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun abonnement valide à créer',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $paymentGroupId = 'PAY-' . strtoupper(Str::random(10)) . '-' . time();
            $transactionId = 'SUB-' . strtoupper(Str::random(10)) . '-' . time();

            $totalAmount = collect($subscriptionData)->sum(fn($data) => $data['plan']->price);

            $createdSubscriptions = [];
            $subscriptionDetails = [];

            foreach ($subscriptionData as $data) {
                $student = $data['student'];
                $plan = $data['plan'];

                $subscription = Subscription::create([
                    'parent_model_id' => $parent->id,
                    'student_id' => $student->id,
                    'subscription_plan_id' => $plan->id,
                    'transaction_id' => $transactionId,
                    'payment_group_id' => $paymentGroupId,
                    'status' => 'pending',
                    'amount_paid' => $plan->price,
                ]);

                // ✅ Charger les relations immédiatement
                $subscription->setRelation('plan', $plan);
                $subscription->setRelation('student', $student);

                $createdSubscriptions[] = $subscription;
                $subscriptionDetails[] = [
                    'student' => $student->name,
                    'plan' => $plan->name,
                    'price' => $plan->price,
                ];
            }

            $description = "Abonnement pour " . count($subscriptionData) . " élève(s): " .
                collect($subscriptionDetails)->map(fn($d) => "{$d['student']} ({$d['plan']})")->join(', ');

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
                    'subscriptions' => $validated['subscriptions'],
                    'subscription_ids' => collect($createdSubscriptions)->pluck('id')->toArray(),
                ]
            ];

            Log::info('Creating Multiple Subscriptions', [
                'parent_id' => $parent->id,
                'total_amount' => $totalAmount,
                'subscriptions_count' => count($createdSubscriptions),
                'details' => $subscriptionDetails
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

            foreach ($createdSubscriptions as $subscription) {
                $subscription->update([
                    'cinetpay_data' => $paymentResult['data']
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Abonnements créés avec succès',
                'data' => [
                    'subscriptions' => SubscriptionResource::collection($createdSubscriptions), // ✅ Corrigé
                    'summary' => $subscriptionDetails,
                    'total_amount' => $totalAmount,
                    'students_count' => count($createdSubscriptions),
                    'payment_url' => $paymentResult['payment_url'],
                    'payment_token' => $paymentResult['payment_token'],
                    'payment_group_id' => $paymentGroupId,
                    'transaction_id' => $transactionId,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Subscription Creation Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création des abonnements',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Détails d'un abonnement
     */
    public function show(Request $request, Subscription $subscription): JsonResponse
    {
        $parent = ParentModel::where('user_id', $request->user()->id)->first();

        if (!$parent || $subscription->parent_model_id !== $parent->id) {
            return response()->json([
                'success' => false,
                'message' => 'Abonnement non trouvé',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new SubscriptionResource($subscription->load(['plan', 'student'])),
        ]);
    }

    /**
     * Vérifier le statut
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
            // Activer TOUS les abonnements du même groupe de paiement
            Subscription::where('payment_group_id', $subscription->payment_group_id)
                ->where('status', 'pending')
                ->get()
                ->each(function ($sub) {
                    $sub->activate();
                });
        }

        return response()->json([
            'success' => true,
            'data' => new SubscriptionResource($subscription->fresh(['plan', 'student'])),
            'cinetpay_status' => $status,
        ]);
    }

    /**
     * Annuler un abonnement
     */
    public function destroy(Request $request, Subscription $subscription): JsonResponse
    {
        $parent = ParentModel::where('user_id', $request->user()->id)->first();

        if (!$parent || $subscription->parent_model_id !== $parent->id) {
            return response()->json([
                'success' => false,
                'message' => 'Abonnement non trouvé',
            ], 404);
        }

        if ($subscription->status === 'pending') {
            $subscription->delete();
            return response()->json([
                'success' => true,
                'message' => 'Abonnement annulé avec succès',
            ]);
        }

        $subscription->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Abonnement résilié avec succès',
        ]);
    }
}
