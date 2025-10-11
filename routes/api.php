<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CommentController;
use App\Http\Controllers\Admin\SubCategoryController;
use App\Http\Controllers\Api\TwilioDebugController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\VerifyOtpController;
use App\Http\Controllers\Parent\ParentController;
use App\Http\Controllers\Product\ProductController;
use App\Http\Controllers\Product\ProductImageContoller;
use App\Http\Controllers\Profile\ProfileController;
use App\Http\Controllers\Student\StudentController;
use App\Http\Controllers\Trainer\TrainerController;
use App\Http\Controllers\Admin\AvatarController;
use App\Http\Controllers\Admin\LevelController;
use App\Http\Controllers\Admin\SupportController;
use App\Http\Controllers\Admin\ModuleController;
use App\Http\Controllers\Admin\CoursesController;
use App\Http\Controllers\Admin\WorkshopController;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');
Route::post('/register', [RegisterController::class, 'register']);
Route::controller(VerifyOtpController::class)->group(function () {
    Route::post('/verify', 'verify');
    Route::post('/resend', 'resend');
    Route::post('/resend-otp-sms', 'resendSms');
    Route::post('/resend-otp-whatsapp', 'resendWhatsApp');
});
// Route::post('/login', [LoginController::class, 'login']);
Route::controller(LoginController::class)->group(function () {
    Route::post('/login', 'login');
    Route::post('/forgot-password', [LoginController::class, 'forgotPassword']);
    Route::post('/reset-password', [LoginController::class, 'resetPassword']);
});

Route::middleware('auth:sanctum')->group(function () {


    Route::prefix('admin')
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::controller(AdminController::class)->group(function () {
            Route::get('/admins', 'index');
            Route::post('/admins', 'store');
            Route::get('/admins/{admin}', 'show');
            Route::put('/admins/{admin}', 'update');
            Route::delete('/admins/{admin}', 'destroy');
            Route::patch('/admins/status/{admin}', 'status');
        });

        Route::controller(AvatarController::class)->group(function () {
            Route::get('/avatars', 'index');
            Route::post('/avatars', 'store');
            Route::get('/avatars/{avatar}', 'show');
            Route::put('/avatars/{avatar}', 'update');
            Route::delete('/avatars/{avatar}', 'destroy');
        });

        Route::controller(LevelController::class)->group(function () {
            Route::get('/levels', 'index');
            Route::post('/levels', 'store');
            Route::get('/levels/{level}', 'show');
            Route::put('/levels/{level}', 'update');
            Route::delete('/levels/{level}', 'destroy');
        });

        // Route::controller(SupportController::class)->group(function () {
        //     Route::get('/supports', 'index');
        //     Route::post('/supports', 'store');
        //     Route::get('/supports/{support}', 'show');
        //     Route::put('/supports/{support}', 'update');
        //     Route::delete('/supports/{support}', 'destroy');
        // });

        Route::controller(ModuleController::class)->group(function () {
            Route::get('/modules', 'index');
            Route::post('/modules', 'store');
            Route::get('/modules/{module}', 'show');
            Route::put('/modules/{module}', 'update');
            Route::delete('/modules/{module}', 'destroy');
        });

        Route::controller(CoursesController::class)->group(function () {
            Route::get('/courses', 'index');
            Route::post('/courses', 'store');
            Route::get('/courses/{course}', 'show');
            Route::put('/courses/{course}', 'update');
            Route::delete('/courses/{course}', 'destroy');
        });

        Route::controller(WorkshopController::class)->group(function () {
            Route::get('/workshops', 'index');
            Route::post('/workshops', 'store');
            Route::get('/workshops/{workshop}', 'show');
            Route::put('/workshops/{workshop}', 'update');
            Route::delete('/workshops/{workshop}', 'destroy');
        });
    });


    Route::prefix('student')
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::controller(StudentController::class)->group(function () {
            Route::get('/students', 'index');
            Route::get('/students/statistics', 'statistics');
            Route::post('/students', 'store');
            Route::get('/students/{student}', 'show');
            Route::put('/students/{student}', 'update');
            Route::delete('/students/{student}', 'destroy');
            Route::patch('/students/status/{student}', 'status');
        });
    });


    Route::prefix('trainer')
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::controller(TrainerController::class)->group(function () {
            Route::get('/trainers', 'index');
            Route::post('/trainers', 'store');
            Route::get('/trainers/{trainer}', 'show');
            Route::put('/trainers/{trainer}', 'update');
            Route::delete('/trainers/{trainer}', 'destroy');
            Route::patch('/trainers/status/{trainer}', 'status');
        });
    });


    Route::prefix('parent')
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::controller(ParentController::class)->group(function () {
            Route::get('/parents', 'index');
            Route::prefix('students')->group(function () {
                Route::get('/students', 'index');
                // Afficher mes étudiants
                Route::get('/my-students','getMyStudents');

                // Créer un nouvel étudiant
                Route::post('/students', 'storeStudent');

                // Switch vers le profil d'un étudiant
                Route::post('/students/{student}/switch', 'switchToStudentProfile');

                // Mettre à jour un étudiant
                Route::put('/students/{student}', 'updateStudent');
                // Route::patch('/students/{student}', 'updateStudent');

                // Supprimer un étudiant
                Route::delete('/students/{student}', 'destroyStudent');

                Route::patch('/{student}/pin',  'updateStudentPin');
                Route::post('/{student}/reset-pin', 'resetStudentPin');

                // Déconnexion du profil étudiant
                Route::post('/students/logout', 'logoutFromStudentProfile');
            });
        });
        Route::delete('/destroy-parent/{parent}', [ProfileController::class, 'destroy']);


        Route::controller(ProfileController::class)->group(function () {

            Route::get('/users-compte-active', 'getUsersCompteActive');
            Route::get('/users-compte-inactive', 'getUsersCompteInactive');
            Route::get('/parents', 'getAllParents');
            // Route::get('/show-profile/{user}', 'getShowProfile');
            Route::put('/update-profile/{parent}', 'UpdateProfile');
            Route::post('/update-password', 'updatePassword');
            // Route::delete('/destroy-parent/{parent}', 'destroy');
            Route::patch('/compte-status/{user}', 'compteStatus');
            Route::patch('/update-email', 'updateEmail');
            Route::patch('/update-email-with-manual-check', 'updateEmailWithManualCheck');

        });
    });

    Route::post('/logout', [ProfileController::class, 'logout']);
});
