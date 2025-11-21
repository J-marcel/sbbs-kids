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


    // Après la méthode plans() existante, ajoutez :

    /**
     * Plans groupés par âge
     */
    public function plansByAgeGroup(Request $request): JsonResponse
    {
        $plans = SubscriptionPlan::active()
            ->orderBy('age_group')
            ->orderBy('duration_months')
            ->get()
            ->groupBy('age_group');

        $result = [];

        foreach ($plans as $ageGroup => $groupPlans) {
            $result[] = [
                'age_group' => $ageGroup,
                'age_group_label' => $this->getAgeGroupLabel($ageGroup),
                'plans_count' => $groupPlans->count(),
                'plans' => SubscriptionPlanResource::collection($groupPlans),
                'price_range' => [
                    'min' => $groupPlans->min('price'),
                    'max' => $groupPlans->max('price'),
                ],
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Obtenir le label d'un groupe d'âge
     */
    private function getAgeGroupLabel(string $ageGroup): string
    {
        return match ($ageGroup) {
            '4-7' => 'Enfants (4-7 ans)',
            '8-12' => 'Pré-adolescents (8-12 ans)',
            '13-17' => 'Adolescents (13-17 ans)',
            default => $ageGroup,
        };
    }

    /**
     * Plans recommandés
     */
    public function recommendedPlans(Request $request): JsonResponse
    {
        $parent = ParentModel::where('user_id', $request->user()->id)->first();

        if (!$parent) {
            return response()->json([
                'success' => false,
                'message' => 'Profil parent non trouvé',
            ], 404);
        }

        // Récupérer tous les élèves du parent
        $students = $parent->students;

        if ($students->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Aucun élève enregistré',
                'data' => [],
            ]);
        }

        // Grouper les élèves par age_group
        $studentsByAgeGroup = $students->groupBy('age_group');

        $recommendations = [];

        foreach ($studentsByAgeGroup as $ageGroup => $studentsInGroup) {
            // Récupérer les plans pour ce groupe d'âge
            $plans = SubscriptionPlan::active()
                ->where('age_group', $ageGroup)
                ->orderBy('duration_months')
                ->get();

            // Vérifier quels élèves ont déjà un abonnement actif
            $studentsWithSubscription = $studentsInGroup->filter(function ($student) {
                return $student->hasActiveSubscription();
            });

            $studentsWithoutSubscription = $studentsInGroup->filter(function ($student) {
                return !$student->hasActiveSubscription();
            });

            $recommendations[] = [
                'age_group' => $ageGroup,
                'age_group_label' => $this->getAgeGroupLabel($ageGroup),
                'students_count' => $studentsInGroup->count(),
                'students_with_subscription' => $studentsWithSubscription->count(),
                'students_without_subscription' => $studentsWithoutSubscription->count(),
                'students' => [
                    'with_subscription' => $studentsWithSubscription->map(fn($s) => [
                        'id' => $s->id,
                        'name' => $s->name,
                        'active_subscription' => new SubscriptionResource($s->activeSubscription()),
                    ]),
                    'without_subscription' => $studentsWithoutSubscription->map(fn($s) => [
                        'id' => $s->id,
                        'name' => $s->name,
                    ]),
                ],
                'available_plans' => SubscriptionPlanResource::collection($plans),
                'recommended_plan' => $plans->where('duration_months', 3)->first()
                    ? new SubscriptionPlanResource($plans->where('duration_months', 3)->first())
                    : ($plans->first() ? new SubscriptionPlanResource($plans->first()) : null),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $recommendations,
            'summary' => [
                'total_students' => $students->count(),
                'students_with_subscription' => $students->filter(fn($s) => $s->hasActiveSubscription())->count(),
                'students_without_subscription' => $students->filter(fn($s) => !$s->hasActiveSubscription())->count(),
                'age_groups_represented' => $studentsByAgeGroup->keys()->toArray(),
            ],
        ]);
    }

    public function popularPlans(Request $request): JsonResponse
    {
        $plans = SubscriptionPlan::active()
            ->withCount(['subscriptions' => function ($query) {
                $query->where('status', 'active');
            }])
            ->orderBy('age_group')
            ->orderBy('subscriptions_count', 'desc')
            ->get()
            ->groupBy('age_group');

        $result = [];

        foreach ($plans as $ageGroup => $groupPlans) {
            $mostPopular = $groupPlans->first(); // Le premier est le plus populaire
            $totalSubscriptions = $groupPlans->sum('subscriptions_count');

            $result[] = [
                'age_group' => $ageGroup,
                'age_group_label' => $this->getAgeGroupLabel($ageGroup),
                'total_subscriptions' => $totalSubscriptions,
                'most_popular_plan' => $mostPopular ? [
                    'id' => $mostPopular->id,
                    'name' => $mostPopular->name,
                    'price' => $mostPopular->price,
                    'duration_months' => $mostPopular->duration_months,
                    'subscriptions_count' => $mostPopular->subscriptions_count,
                ] : null,
                'all_plans' => $groupPlans->map(function ($plan) {
                    return [
                        'id' => $plan->id,
                        'name' => $plan->name,
                        'price' => $plan->price,
                        'duration_months' => $plan->duration_months,
                        'subscriptions_count' => $plan->subscriptions_count,
                        'popularity_rank' => $plan->subscriptions_count > 0
                            ? '⭐ ' . $plan->subscriptions_count . ' abonnement(s)'
                            : 'Nouveau',
                    ];
                }),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }


    public function planStatistics(Request $request): JsonResponse
    {
        $ageGroups = ['4-7', '8-12', '13-17'];
        $statistics = [];

        foreach ($ageGroups as $ageGroup) {
            $plans = SubscriptionPlan::active()
                ->where('age_group', $ageGroup)
                ->withCount(['subscriptions as active_subscriptions_count' => function ($query) {
                    $query->where('status', 'active');
                }])
                ->get();

            $totalActiveSubscriptions = $plans->sum('active_subscriptions_count');
            $totalRevenue = Subscription::whereHas('plan', function ($query) use ($ageGroup) {
                $query->where('age_group', $ageGroup);
            })
                ->where('status', 'active')
                ->sum('amount_paid');

            $statistics[] = [
                'age_group' => $ageGroup,
                'age_group_label' => $this->getAgeGroupLabel($ageGroup),
                'plans_count' => $plans->count(),
                'active_subscriptions' => $totalActiveSubscriptions,
                'total_revenue' => (float) $totalRevenue,
                'average_price' => $plans->avg('price'),
                'plans' => SubscriptionPlanResource::collection($plans),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $statistics,
            'summary' => [
                'total_plans' => array_sum(array_column($statistics, 'plans_count')),
                'total_active_subscriptions' => array_sum(array_column($statistics, 'active_subscriptions')),
                'total_revenue' => array_sum(array_column($statistics, 'total_revenue')),
            ],
        ]);
    }
}
