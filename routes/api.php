<?php

use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\AssessmentCriteriaController;
use App\Http\Controllers\AssessmentResultController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AvatarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentReviewController;
use App\Http\Controllers\FinalGradeController;
use App\Http\Controllers\IndustrialProjectGroupController;
use App\Http\Controllers\NoticeController;
use App\Http\Controllers\ProjectTitleController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ThesisController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public routes
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login']);

// Avatar images served directly — no auth needed so <img src> works in browser.
Route::get('/avatar/{user}', [AvatarController::class, 'serve']);

// Public repository: guests may browse published student works (SUC-20).
Route::get('/documents', [DocumentController::class, 'index']);
Route::get('/documents/{document}/download', [DocumentController::class, 'download']);

/*
|--------------------------------------------------------------------------
| Authenticated routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Auth ---------------------------------------------------------------
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/dashboard', [DashboardController::class, 'stats']);

    // Profile photo -----------------------------------------------------
    Route::post('/avatar', [AvatarController::class, 'upload']);
    Route::delete('/avatar', [AvatarController::class, 'remove']);

    // Announcements (all authenticated users can view) -------------------
    Route::get('/notices', [NoticeController::class, 'index']);
    Route::post('/notices', [NoticeController::class, 'store'])->middleware('role:UGPC,GPC,SGC,IPC,Administrator,DepartmentHead');
    Route::delete('/notices/{notice}', [NoticeController::class, 'destroy'])->middleware('role:UGPC,GPC,SGC,IPC,Administrator');

    // User management (Administrator) ------------------------------------
    Route::get('/users', [UserController::class, 'index'])->middleware('role:Administrator');
    Route::post('/users', [UserController::class, 'store'])->middleware('role:Administrator');
    Route::get('/users/{user}', [UserController::class, 'show'])->middleware('role:Administrator');
    Route::put('/users/{user}', [UserController::class, 'update'])->middleware('role:Administrator');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('role:Administrator');
    // Admin can upload/remove any user's profile photo
    Route::post('/users/{user}/avatar', [UserController::class, 'uploadAvatar'])->middleware('role:Administrator');
    Route::delete('/users/{user}/avatar', [UserController::class, 'removeAvatar'])->middleware('role:Administrator');

    // Instructor & student lookups (used by assignment forms) -----------
    Route::get('/instructors', [UserController::class, 'instructors'])->middleware('role:UGPC,GPC,SGC,IPC,Administrator,DepartmentHead,Advisor,Examiner');
    Route::get('/students', [UserController::class, 'students'])->middleware('role:Student,UGPC,GPC,SGC,IPC,Administrator,DepartmentHead,Advisor');

    // Academic years (coordinators & admin) ------------------------------
    Route::get('/academic-years', [AcademicYearController::class, 'index']);
    Route::post('/academic-years', [AcademicYearController::class, 'store'])->middleware('role:Administrator,UGPC,GPC');
    Route::put('/academic-years/{academicYear}', [AcademicYearController::class, 'update'])->middleware('role:Administrator,UGPC,GPC');
    Route::delete('/academic-years/{academicYear}', [AcademicYearController::class, 'destroy'])->middleware('role:Administrator');

    // Industrial project groups -----------------------------------------
    Route::get('/project-groups', [IndustrialProjectGroupController::class, 'index']);
    Route::post('/project-groups', [IndustrialProjectGroupController::class, 'store'])->middleware('role:Student,UGPC,Administrator');
    Route::get('/project-groups/{group}', [IndustrialProjectGroupController::class, 'show']);
    Route::post('/project-groups/{group}/assign-advisor', [IndustrialProjectGroupController::class, 'assignAdvisor'])->middleware('role:UGPC,Administrator');

    // Project titles -----------------------------------------------------
    Route::get('/project-titles', [ProjectTitleController::class, 'index']);
    Route::post('/project-titles', [ProjectTitleController::class, 'store'])->middleware('role:Student,UGPC,Administrator');
    Route::put('/project-titles/{title}', [ProjectTitleController::class, 'update'])->middleware('role:IPC,UGPC,Administrator');
    Route::delete('/project-titles/{title}', [ProjectTitleController::class, 'destroy'])->middleware('role:IPC,UGPC,Administrator');

    // Proposals ---------------------------------------------------------
    Route::get('/proposals', [ProposalController::class, 'index']);
    Route::post('/proposals', [ProposalController::class, 'store'])->middleware('role:GradStudent,Administrator');
    Route::get('/proposals/{proposal}', [ProposalController::class, 'show']);
    Route::post('/proposals/{proposal}/assign-advisor', [ProposalController::class, 'assignAdvisor'])->middleware('role:SGC,Administrator');
    Route::post('/proposals/{proposal}/review-result', [ProposalController::class, 'recordReviewResult'])->middleware('role:GPC,SGC,Administrator');

    // Theses ------------------------------------------------------------
    Route::get('/theses', [ThesisController::class, 'index']);
    Route::post('/theses', [ThesisController::class, 'store'])->middleware('role:GradStudent,Administrator');
    Route::get('/theses/{thesis}', [ThesisController::class, 'show']);
    Route::post('/theses/{thesis}/submit', [ThesisController::class, 'submit'])->middleware('role:GradStudent,Administrator');
    Route::post('/theses/{thesis}/assign-advisor', [ThesisController::class, 'assignAdvisor'])->middleware('role:SGC,Administrator');

    // Documents ---------------------------------------------------------
    Route::post('/documents', [DocumentController::class, 'store']);
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy']);

    // Document reviews --------------------------------------------------
    Route::get('/document-reviews', [DocumentReviewController::class, 'index']);
    Route::post('/document-reviews', [DocumentReviewController::class, 'store'])->middleware('role:Advisor,Examiner,UGPC,GPC,Administrator');

    // Schedules (defense / proposal review) -----------------------------
    Route::get('/schedules', [ScheduleController::class, 'index']);
    Route::post('/schedules', [ScheduleController::class, 'store'])->middleware('role:UGPC,GPC,Administrator');
    Route::get('/schedules/{schedule}', [ScheduleController::class, 'show']);
    Route::put('/schedules/{schedule}', [ScheduleController::class, 'update'])->middleware('role:UGPC,GPC,Administrator');
    Route::delete('/schedules/{schedule}', [ScheduleController::class, 'destroy'])->middleware('role:UGPC,GPC,Administrator');

    // Assessment --------------------------------------------------------
    Route::get('/assessment-criteria', [AssessmentCriteriaController::class, 'index']);
    Route::post('/assessment-criteria', [AssessmentCriteriaController::class, 'store'])->middleware('role:Administrator,UGPC,GPC');
    Route::put('/assessment-criteria/{assessmentCriteria}', [AssessmentCriteriaController::class, 'update'])->middleware('role:Administrator,UGPC,GPC');
    Route::delete('/assessment-criteria/{assessmentCriteria}', [AssessmentCriteriaController::class, 'destroy'])->middleware('role:Administrator,UGPC,GPC');

    Route::get('/assessment-results', [AssessmentResultController::class, 'index']);
    Route::post('/assessment-results', [AssessmentResultController::class, 'store'])->middleware('role:Advisor,Examiner,UGPC,GPC,Administrator');
    Route::delete('/assessment-results/{assessmentResult}', [AssessmentResultController::class, 'destroy'])->middleware('role:Administrator,UGPC,GPC');

    // Final grades ------------------------------------------------------
    Route::get('/final-grades', [FinalGradeController::class, 'index']);
    Route::post('/final-grades/compile', [FinalGradeController::class, 'compile'])->middleware('role:UGPC,GPC,Administrator');

    // Reports (Department Head) ----------------------------------------
    Route::get('/reports', [ReportController::class, 'index'])->middleware('role:DepartmentHead,Administrator,UGPC,GPC');
});
