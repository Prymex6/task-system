<?php

use App\Http\Controllers\Tenant\Client\PortalController;
use App\Http\Controllers\Tenant\Manager\ActivityController;
use App\Http\Controllers\Tenant\Manager\AnnouncementController;
use App\Http\Controllers\Tenant\Manager\AttendanceController;
use App\Http\Controllers\Tenant\Manager\AuditLogController;
use App\Http\Controllers\Tenant\Manager\AuthController;
use App\Http\Controllers\Tenant\Manager\AutomationController;
use App\Http\Controllers\Tenant\Manager\BillingController;
use App\Http\Controllers\Tenant\Manager\BudgetController;
use App\Http\Controllers\Tenant\Manager\CannedResponseController;
use App\Http\Controllers\Tenant\Manager\ClientContactController;
use App\Http\Controllers\Tenant\Manager\ClientController;
use App\Http\Controllers\Tenant\Manager\ClientGroupController;
use App\Http\Controllers\Tenant\Manager\ContractController;
use App\Http\Controllers\Tenant\Manager\ContractTypeController;
use App\Http\Controllers\Tenant\Manager\CreditNoteController;
use App\Http\Controllers\Tenant\Manager\CurrencyController;
use App\Http\Controllers\Tenant\Manager\CustomFieldController;
use App\Http\Controllers\Tenant\Manager\DashboardController;
use App\Http\Controllers\Tenant\Manager\DealController;
use App\Http\Controllers\Tenant\Manager\DealStageController;
use App\Http\Controllers\Tenant\Manager\DepartmentController;
use App\Http\Controllers\Tenant\Manager\DepartmentHrController;
use App\Http\Controllers\Tenant\Manager\EmailTemplateController;
use App\Http\Controllers\Tenant\Manager\EstimateController;
use App\Http\Controllers\Tenant\Manager\ExpenseController;
use App\Http\Controllers\Tenant\Manager\ExpenseReportController;
use App\Http\Controllers\Tenant\Manager\FinanceController;
use App\Http\Controllers\Tenant\Manager\HolidayController;
use App\Http\Controllers\Tenant\Manager\HubImpersonateController;
use App\Http\Controllers\Tenant\Manager\IntegrationController;
use App\Http\Controllers\Tenant\Manager\InvitationController;
use App\Http\Controllers\Tenant\Manager\InvoiceController;
use App\Http\Controllers\Tenant\Manager\InvoicePaymentController;
use App\Http\Controllers\Tenant\Manager\KbArticleController;
use App\Http\Controllers\Tenant\Manager\KbCategoryController;
use App\Http\Controllers\Tenant\Manager\LeadActivityController;
use App\Http\Controllers\Tenant\Manager\LeadController;
use App\Http\Controllers\Tenant\Manager\LeaveBalanceController;
use App\Http\Controllers\Tenant\Manager\LeaveRequestController;
use App\Http\Controllers\Tenant\Manager\LeaveTypeController;
use App\Http\Controllers\Tenant\Manager\MessageController;
use App\Http\Controllers\Tenant\Manager\NoteController;
use App\Http\Controllers\Tenant\Manager\NotificationController;
use App\Http\Controllers\Tenant\Manager\PaymentMethodController;
use App\Http\Controllers\Tenant\Manager\PerformanceReviewController;
use App\Http\Controllers\Tenant\Manager\PositionController;
use App\Http\Controllers\Tenant\Manager\ProjectController;
use App\Http\Controllers\Tenant\Manager\ProjectDiscussionController;
use App\Http\Controllers\Tenant\Manager\ProjectFileController;
use App\Http\Controllers\Tenant\Manager\ProjectMilestoneController;
use App\Http\Controllers\Tenant\Manager\ProjectTemplateController;
use App\Http\Controllers\Tenant\Manager\ProposalController;
use App\Http\Controllers\Tenant\Manager\PushSubscriptionController;
use App\Http\Controllers\Tenant\Manager\RecurringInvoiceController;
use App\Http\Controllers\Tenant\Manager\ReportClientController;
use App\Http\Controllers\Tenant\Manager\ReportController;
use App\Http\Controllers\Tenant\Manager\ReportFinanceController;
use App\Http\Controllers\Tenant\Manager\ReportProjectController;
use App\Http\Controllers\Tenant\Manager\ReportStaffController;
use App\Http\Controllers\Tenant\Manager\ReportTimeController;
use App\Http\Controllers\Tenant\Manager\SearchController;
use App\Http\Controllers\Tenant\Manager\SettingsController;
use App\Http\Controllers\Tenant\Manager\SlaPolicyController;
use App\Http\Controllers\Tenant\Manager\SprintController;
use App\Http\Controllers\Tenant\Manager\SprintTaskController;
use App\Http\Controllers\Tenant\Manager\StaffController;
use App\Http\Controllers\Tenant\Manager\StaffProfileController;
use App\Http\Controllers\Tenant\Manager\SubtaskController;
use App\Http\Controllers\Tenant\Manager\TagController;
use App\Http\Controllers\Tenant\Manager\TaskAttachmentController;
use App\Http\Controllers\Tenant\Manager\TaskChecklistController;
use App\Http\Controllers\Tenant\Manager\TaskCommentController;
use App\Http\Controllers\Tenant\Manager\TaskController;
use App\Http\Controllers\Tenant\Manager\TaskDependencyController;
use App\Http\Controllers\Tenant\Manager\TaskLabelController;
use App\Http\Controllers\Tenant\Manager\TaskRecurringController;
use App\Http\Controllers\Tenant\Manager\TaskStatusController;
use App\Http\Controllers\Tenant\Manager\TaskTemplateController;
use App\Http\Controllers\Tenant\Manager\TaskTimerController;
use App\Http\Controllers\Tenant\Manager\TaxRateController;
use App\Http\Controllers\Tenant\Manager\TicketController;
use App\Http\Controllers\Tenant\Manager\TicketMessageController;
use App\Http\Controllers\Tenant\Manager\TimeEntryController;
use App\Http\Controllers\Tenant\Manager\TimerController;
use App\Http\Controllers\Tenant\Manager\TimesheetApprovalController;
use App\Http\Controllers\Tenant\Manager\TimesheetController;
use App\Http\Controllers\Tenant\Manager\WebhookController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes (TaskSystem Manager & Client Portal)
|--------------------------------------------------------------------------
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->name('tenant.')->group(function () {

    Route::get('/', fn () => redirect()->route(
        auth('tenant')->check() ? 'tenant.manager.dashboard' : 'tenant.login'
    ))->name('root');

    /*
    |--------------------------------------------------------------------------
    | Manager Auth
    |--------------------------------------------------------------------------
    */
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post')->middleware('throttle:20,1');
    Route::get('/hub-impersonate/{token}', [HubImpersonateController::class, 'handle'])->name('hub-impersonate');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

    // Invitation acceptance
    Route::get('/invitation/{token}', [InvitationController::class, 'show'])->name('invitation.show');
    Route::post('/invitation/{token}/accept', [InvitationController::class, 'accept'])->name('invitation.accept');

    /*
    |--------------------------------------------------------------------------
    | Manager Panel (auth:tenant guard)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth:tenant'])->name('manager.')->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        /* --- Projects --- */
        Route::prefix('projects')->name('projects.')->group(function () {
            Route::get('/', [ProjectController::class, 'index'])->name('index');
            Route::get('/create', [ProjectController::class, 'create'])->name('create');
            Route::post('/', [ProjectController::class, 'store'])->name('store');
            Route::get('/{project}', [ProjectController::class, 'show'])->name('show');
            Route::get('/{project}/edit', [ProjectController::class, 'edit'])->name('edit');
            Route::put('/{project}', [ProjectController::class, 'update'])->name('update');
            Route::delete('/{project}', [ProjectController::class, 'destroy'])->name('destroy');
            Route::post('/{project}/archive', [ProjectController::class, 'archive'])->name('archive');

            // Zadania projektu
            Route::get('/{project}/tasks', [TaskController::class, 'index'])->name('tasks.index');
            Route::get('/{project}/tasks/create', [TaskController::class, 'create'])->name('tasks.create');

            // Board view
            Route::get('/{project}/board', [ProjectController::class, 'board'])->name('board');

            // Project members
            Route::get('/{project}/members', [ProjectController::class, 'members'])->name('members');
            Route::post('/{project}/members', [ProjectController::class, 'addMember'])->name('members.add');
            Route::put('/{project}/members/{member}', [ProjectController::class, 'updateMember'])->name('members.update');
            Route::delete('/{project}/members/{member}', [ProjectController::class, 'removeMember'])->name('members.remove');

            // Milestones
            Route::get('/{project}/milestones', [ProjectMilestoneController::class, 'index'])->name('milestones.index');
            Route::post('/{project}/milestones', [ProjectMilestoneController::class, 'store'])->name('milestones.store');
            Route::put('/{project}/milestones/{milestone}', [ProjectMilestoneController::class, 'update'])->name('milestones.update');
            Route::delete('/{project}/milestones/{milestone}', [ProjectMilestoneController::class, 'destroy'])->name('milestones.destroy');

            // Files
            Route::get('/{project}/files', [ProjectFileController::class, 'index'])->name('files.index');
            Route::post('/{project}/files', [ProjectFileController::class, 'store'])->name('files.store');
            Route::delete('/{project}/files/{file}', [ProjectFileController::class, 'destroy'])->name('files.destroy');

            // Discussions
            Route::get('/{project}/discussions', [ProjectDiscussionController::class, 'index'])->name('discussions.index');
            Route::post('/{project}/discussions', [ProjectDiscussionController::class, 'store'])->name('discussions.store');
            Route::put('/{project}/discussions/{discussion}', [ProjectDiscussionController::class, 'update'])->name('discussions.update');
            Route::delete('/{project}/discussions/{discussion}', [ProjectDiscussionController::class, 'destroy'])->name('discussions.destroy');
            Route::post('/{project}/discussions/{discussion}/comments', [ProjectDiscussionController::class, 'storeComment'])->name('discussions.comments.store');
        });

        // Project templates
        Route::prefix('project-templates')->name('project-templates.')->group(function () {
            Route::get('/', [ProjectTemplateController::class, 'index'])->name('index');
            Route::post('/', [ProjectTemplateController::class, 'store'])->name('store');
            Route::post('/{template}/use', [ProjectTemplateController::class, 'createProject'])->name('use');
            Route::delete('/{template}', [ProjectTemplateController::class, 'destroy'])->name('destroy');
        });

        /* --- Tasks --- */
        Route::prefix('tasks')->name('tasks.')->group(function () {
            Route::get('/', [TaskController::class, 'index'])->name('index');
            Route::get('/my', [TaskController::class, 'myTasks'])->name('my');
            Route::get('/kanban', [TaskController::class, 'kanban'])->name('kanban');
            Route::get('/calendar', [TaskController::class, 'calendar'])->name('calendar');
            Route::get('/gantt', [TaskController::class, 'gantt'])->name('gantt');
            Route::get('/create', [TaskController::class, 'create'])->name('create');
            Route::post('/', [TaskController::class, 'store'])->name('store');
            Route::get('/{task}', [TaskController::class, 'show'])->name('show');
            Route::get('/{task}/edit', [TaskController::class, 'edit'])->name('edit');
            Route::put('/{task}', [TaskController::class, 'update'])->name('update');
            Route::delete('/{task}', [TaskController::class, 'destroy'])->name('destroy');
            Route::post('/{task}/move', [TaskController::class, 'move'])->name('move');
            Route::post('/{task}/duplicate', [TaskController::class, 'duplicate'])->name('duplicate');

            // Comments
            Route::post('/{task}/comments', [TaskCommentController::class, 'store'])->name('comments.store');
            Route::put('/{task}/comments/{comment}', [TaskCommentController::class, 'update'])->name('comments.update');
            Route::delete('/{task}/comments/{comment}', [TaskCommentController::class, 'destroy'])->name('comments.destroy');

            // Attachments
            Route::post('/{task}/attachments', [TaskAttachmentController::class, 'store'])->name('attachments.store');
            Route::delete('/{task}/attachments/{attachment}', [TaskAttachmentController::class, 'destroy'])->name('attachments.destroy');

            // Checklists
            Route::post('/{task}/checklists', [TaskChecklistController::class, 'store'])->name('checklists.store');
            Route::put('/{task}/checklists/{checklist}', [TaskChecklistController::class, 'update'])->name('checklists.update');
            Route::delete('/{task}/checklists/{checklist}', [TaskChecklistController::class, 'destroy'])->name('checklists.destroy');
            Route::post('/{task}/checklists/{checklist}/items', [TaskChecklistController::class, 'storeItem'])->name('checklists.items.store');
            Route::put('/{task}/checklists/{checklist}/items/{item}', [TaskChecklistController::class, 'updateItem'])->name('checklists.items.update');
            Route::delete('/{task}/checklists/{checklist}/items/{item}', [TaskChecklistController::class, 'destroyItem'])->name('checklists.items.destroy');

            // Time logged against a task
            Route::post('/{task}/timer/start', [TaskTimerController::class, 'start'])->name('timer.start');
            Route::post('/{task}/timer/stop', [TaskTimerController::class, 'stop'])->name('timer.stop');
            Route::post('/{task}/time-log', [TaskTimerController::class, 'logManual'])->name('time-log.store');
            Route::delete('/{task}/time-log/{log}', [TaskTimerController::class, 'destroyLog'])->name('time-log.destroy');
        });

        // Labels
        Route::resource('task-labels', TaskLabelController::class)->except(['show']);

        // Task statuses
        Route::resource('task-statuses', TaskStatusController::class)->except(['show', 'create', 'edit']);
        Route::post('/task-statuses/reorder', [TaskStatusController::class, 'reorder'])->name('task-statuses.reorder');

        /* --- Sprints --- */
        Route::prefix('sprints')->name('sprints.')->group(function () {
            Route::get('/', [SprintController::class, 'index'])->name('index');
            Route::post('/', [SprintController::class, 'store'])->name('store');
            Route::get('/{sprint}', [SprintController::class, 'show'])->name('show');
            Route::put('/{sprint}', [SprintController::class, 'update'])->name('update');
            Route::delete('/{sprint}', [SprintController::class, 'destroy'])->name('destroy');
            Route::post('/{sprint}/start', [SprintController::class, 'start'])->name('start');
            Route::post('/{sprint}/complete', [SprintController::class, 'complete'])->name('complete');
            Route::post('/{sprint}/tasks', [SprintTaskController::class, 'store'])->name('tasks.add');
            Route::delete('/{sprint}/tasks/{task}', [SprintTaskController::class, 'destroy'])->name('tasks.remove');
        });

        /* --- Time tracking --- */
        Route::prefix('time')->name('time.')->group(function () {
            Route::get('/', [TimeEntryController::class, 'index'])->name('index');
            Route::get('/team', [TimeEntryController::class, 'team'])->name('team');
            Route::post('/', [TimeEntryController::class, 'store'])->name('store');
            Route::put('/{entry}', [TimeEntryController::class, 'update'])->name('update');
            Route::delete('/{entry}', [TimeEntryController::class, 'destroy'])->name('destroy');
            Route::get('/reports', [TimesheetController::class, 'reports'])->name('reports');
            Route::get('/approvals', [TimesheetApprovalController::class, 'index'])->name('approvals');
            Route::post('/approvals/{approval}/approve', [TimesheetApprovalController::class, 'approve'])->name('approvals.approve');
            Route::post('/approvals/{approval}/reject', [TimesheetApprovalController::class, 'reject'])->name('approvals.reject');
        });

        /* --- Timer globalny (Time/Index.vue) --- */
        Route::prefix('timer')->name('timer.')->group(function () {
            Route::post('/stop', [TimerController::class, 'stop'])->name('stop');
            Route::post('/store-entry', [TimerController::class, 'storeEntry'])->name('store-entry');
            Route::delete('/entries/{entry}', [TimerController::class, 'destroyEntry'])->name('destroy-entry');
        });

        /* --- CRM --- */
        Route::prefix('crm')->group(function () {
            // Clients
            Route::resource('clients', ClientController::class);
            Route::get('/clients/{client}/contacts', [ClientContactController::class, 'index'])->name('clients.contacts.index');
            Route::post('/clients/{client}/contacts', [ClientContactController::class, 'store'])->name('clients.contacts.add');
            Route::put('/clients/{client}/contacts/{contact}', [ClientContactController::class, 'update'])->name('clients.contacts.update');
            Route::delete('/clients/{client}/contacts/{contact}', [ClientContactController::class, 'destroy'])->name('clients.contacts.remove');

            // Client groups
            Route::resource('client-groups', ClientGroupController::class)->except(['show', 'create', 'edit']);

            // Leads
            Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');
            Route::get('/leads/create', [LeadController::class, 'create'])->name('leads.create');
            Route::post('/leads', [LeadController::class, 'store'])->name('leads.store');
            Route::get('/leads/{lead}', [LeadController::class, 'show'])->name('leads.show');
            Route::get('/leads/{lead}/edit', [LeadController::class, 'edit'])->name('leads.edit');
            Route::put('/leads/{lead}', [LeadController::class, 'update'])->name('leads.update');
            Route::delete('/leads/{lead}', [LeadController::class, 'destroy'])->name('leads.destroy');
            Route::post('/leads/{lead}/convert', [LeadController::class, 'convert'])->name('leads.convert');
            Route::post('/leads/{lead}/activities', [LeadActivityController::class, 'store'])->name('leads.activity');

            // Deal pipeline
            Route::get('/deals', [DealController::class, 'index'])->name('deals.index');
            Route::get('/deals/pipeline', [DealController::class, 'pipeline'])->name('deals.pipeline');
            Route::get('/deals/create', [DealController::class, 'create'])->name('deals.create');
            Route::post('/deals', [DealController::class, 'store'])->name('deals.store');
            Route::get('/deals/{deal}', [DealController::class, 'show'])->name('deals.show');
            Route::get('/deals/{deal}/edit', [DealController::class, 'edit'])->name('deals.edit');
            Route::put('/deals/{deal}', [DealController::class, 'update'])->name('deals.update');
            Route::delete('/deals/{deal}', [DealController::class, 'destroy'])->name('deals.destroy');
            Route::post('/deals/{deal}/move-stage', [DealController::class, 'move'])->name('deals.move-stage');
            Route::post('/deals/{deal}/won', [DealController::class, 'won'])->name('deals.won');
            Route::post('/deals/{deal}/lost', [DealController::class, 'lost'])->name('deals.lost');
            Route::post('/deals/{deal}/activity', [DealController::class, 'addActivity'])->name('deals.activity');
            Route::prefix('deals/stages')->name('deals.stages.')->group(function () {
                Route::get('/', [DealStageController::class, 'index'])->name('index');
                Route::post('/', [DealStageController::class, 'store'])->name('store');
                Route::put('/{stage}', [DealStageController::class, 'update'])->name('update');
                Route::delete('/{stage}', [DealStageController::class, 'destroy'])->name('destroy');
                Route::post('/reorder', [DealStageController::class, 'reorder'])->name('reorder');
            });

            // Notes, attached to any record
            Route::post('/notes', [NoteController::class, 'store'])->name('notes.store');
            Route::put('/notes/{note}', [NoteController::class, 'update'])->name('notes.update');
            Route::delete('/notes/{note}', [NoteController::class, 'destroy'])->name('notes.destroy');
        });

        /* --- Finance (no name prefix: the pages use invoices.*, estimates.*, expenses.*) --- */
        Route::prefix('finance')->group(function () {
            Route::get('/overview', [FinanceController::class, 'overview'])->name('finance.overview');

            // Invoices
            Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
            Route::get('/invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
            Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
            Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
            Route::get('/invoices/{invoice}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit');
            Route::put('/invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
            Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
            Route::post('/invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
            Route::post('/invoices/{invoice}/mark-paid', [InvoiceController::class, 'markPaid'])->name('invoices.mark-paid');
            Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
            Route::post('/invoices/{invoice}/payment', [InvoicePaymentController::class, 'store'])->name('invoices.payment');
            Route::delete('/invoices/{invoice}/payments/{payment}', [InvoicePaymentController::class, 'destroy'])->name('invoices.payments.destroy');

            // Recurring invoices
            Route::get('/recurring-invoices', [RecurringInvoiceController::class, 'index'])->name('recurring-invoices.index');
            Route::get('/recurring-invoices/create', [RecurringInvoiceController::class, 'create'])->name('recurring-invoices.create');
            Route::post('/recurring-invoices', [RecurringInvoiceController::class, 'store'])->name('recurring-invoices.store');
            Route::get('/recurring-invoices/{recurringInvoice}', [RecurringInvoiceController::class, 'show'])->name('recurring-invoices.show');
            Route::get('/recurring-invoices/{recurringInvoice}/edit', [RecurringInvoiceController::class, 'edit'])->name('recurring-invoices.edit');
            Route::put('/recurring-invoices/{recurringInvoice}', [RecurringInvoiceController::class, 'update'])->name('recurring-invoices.update');
            Route::delete('/recurring-invoices/{recurringInvoice}', [RecurringInvoiceController::class, 'destroy'])->name('recurring-invoices.destroy');
            Route::post('/recurring-invoices/{recurringInvoice}/generate', [RecurringInvoiceController::class, 'generate'])->name('recurring-invoices.generate');

            // Expense reports
            Route::get('/expense-reports', [ExpenseReportController::class, 'index'])->name('expense-reports.index');
            Route::post('/expense-reports/{report}/review', [ExpenseReportController::class, 'approve'])->name('expense-reports.review');

            // Estimates
            Route::get('/estimates', [EstimateController::class, 'index'])->name('estimates.index');
            Route::get('/estimates/create', [EstimateController::class, 'create'])->name('estimates.create');
            Route::post('/estimates', [EstimateController::class, 'store'])->name('estimates.store');
            Route::get('/estimates/{estimate}', [EstimateController::class, 'show'])->name('estimates.show');
            Route::get('/estimates/{estimate}/edit', [EstimateController::class, 'edit'])->name('estimates.edit');
            Route::put('/estimates/{estimate}', [EstimateController::class, 'update'])->name('estimates.update');
            Route::delete('/estimates/{estimate}', [EstimateController::class, 'destroy'])->name('estimates.destroy');
            Route::post('/estimates/{estimate}/send', [EstimateController::class, 'send'])->name('estimates.send');
            Route::post('/estimates/{estimate}/convert', [EstimateController::class, 'convertToInvoice'])->name('estimates.convert');
            Route::get('/estimates/{estimate}/pdf', [EstimateController::class, 'pdf'])->name('estimates.pdf');

            // Expenses
            Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
            Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
            Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
            Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');
            Route::post('/expenses/{expense}/approve', [ExpenseController::class, 'approve'])->name('expenses.approve');
            Route::post('/expenses/{expense}/reject', [ExpenseController::class, 'reject'])->name('expenses.reject');

            // Extras
            Route::resource('tax-rates', TaxRateController::class)->except(['show', 'create', 'edit']);
            Route::resource('currencies', CurrencyController::class)->except(['show', 'create', 'edit']);
        });

        /* --- Credit notes --- */
        Route::prefix('credit-notes')->name('credit-notes.')->group(function () {
            Route::get('/', [CreditNoteController::class, 'index'])->name('index');
            Route::post('/', [CreditNoteController::class, 'store'])->name('store');
            Route::get('/{creditNote}/pdf', [CreditNoteController::class, 'pdf'])->name('pdf');
        });

        /* --- Contracts --- */
        Route::prefix('contracts')->name('contracts.')->group(function () {
            // Declared before the wildcard routes below: Laravel matches in
            // order, and /{model} would otherwise swallow these paths and
            // answer 404 looking for a record whose id is "types".
            Route::resource('types', ContractTypeController::class)->except(['show', 'create', 'edit']);
            Route::get('/', [ContractController::class, 'index'])->name('index');
            Route::get('/create', [ContractController::class, 'create'])->name('create');
            Route::post('/', [ContractController::class, 'store'])->name('store');
            Route::get('/{contract}', [ContractController::class, 'show'])->name('show');
            Route::put('/{contract}', [ContractController::class, 'update'])->name('update');
            Route::delete('/{contract}', [ContractController::class, 'destroy'])->name('destroy');
            Route::get('/{contract}/pdf', [ContractController::class, 'pdf'])->name('pdf');
            Route::post('/{contract}/send', [ContractController::class, 'send'])->name('send');
            Route::post('/{contract}/activate', [ContractController::class, 'activate'])->name('activate');
            Route::post('/{contract}/terminate', [ContractController::class, 'terminate'])->name('terminate');
        });

        /* --- Proposals --- */
        Route::prefix('proposals')->name('proposals.')->group(function () {
            Route::get('/', [ProposalController::class, 'index'])->name('index');
            Route::get('/create', [ProposalController::class, 'create'])->name('create');
            Route::post('/', [ProposalController::class, 'store'])->name('store');
            Route::get('/{proposal}', [ProposalController::class, 'show'])->name('show');
            Route::put('/{proposal}', [ProposalController::class, 'update'])->name('update');
            Route::delete('/{proposal}', [ProposalController::class, 'destroy'])->name('destroy');
            Route::get('/{proposal}/pdf', [ProposalController::class, 'pdf'])->name('pdf');
            Route::post('/{proposal}/send', [ProposalController::class, 'send'])->name('send');
            Route::patch('/{proposal}/status', [ProposalController::class, 'changeStatus'])->name('status');
            Route::post('/{proposal}/convert', [ProposalController::class, 'convertToInvoice'])->name('convert');
        });

        /* --- Support Tickets --- */
        Route::prefix('support')->name('support.')->group(function () {
            // Declared before the wildcard routes below: Laravel matches in
            // order, and /{model} would otherwise swallow these paths and
            // answer 404 looking for a record whose id is "types".
            Route::resource('departments', DepartmentController::class)->except(['show']);
            Route::resource('sla-policies', SlaPolicyController::class)->except(['show', 'create', 'edit']);
            Route::resource('canned-responses', CannedResponseController::class)->except(['show']);
            // Routes aligned with Vue: support.index, support.store, support.show, support.reply
            Route::get('/', [TicketController::class, 'index'])->name('index');
            Route::post('/', [TicketController::class, 'store'])->name('store');
            Route::get('/{ticket}', [TicketController::class, 'show'])->name('show');
            Route::put('/{ticket}', [TicketController::class, 'update'])->name('update');
            Route::delete('/{ticket}', [TicketController::class, 'destroy'])->name('destroy');
            Route::post('/{ticket}/reply', [TicketMessageController::class, 'store'])->name('reply');
            Route::post('/{ticket}/close', [TicketController::class, 'close'])->name('close');
            Route::post('/{ticket}/reopen', [TicketController::class, 'reopen'])->name('reopen');
        });

        /* --- Knowledge base --- */
        Route::prefix('knowledge-base')->name('kb.')->group(function () {
            // Declared before the wildcard routes below: Laravel matches in
            // order, and /{model} would otherwise swallow these paths and
            // answer 404 looking for a record whose id is "types".
            Route::resource('categories', KbCategoryController::class)->except(['show']);
            Route::get('/', [KbArticleController::class, 'index'])->name('index');
            Route::get('/create', [KbArticleController::class, 'create'])->name('create');
            Route::post('/', [KbArticleController::class, 'store'])->name('store');
            Route::get('/{article}', [KbArticleController::class, 'show'])->name('show');
            Route::get('/{article}/edit', [KbArticleController::class, 'edit'])->name('edit');
            Route::put('/{article}', [KbArticleController::class, 'update'])->name('update');
            Route::delete('/{article}', [KbArticleController::class, 'destroy'])->name('destroy');
            Route::post('/{article}/publish', [KbArticleController::class, 'publish'])->name('publish');
        });

        /* --- HR --- */
        Route::prefix('hr')->group(function () {
            // Team
            Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
            Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
            Route::get('/staff/{staff}', [StaffController::class, 'show'])->name('staff.show');
            Route::put('/staff/{staff}', [StaffController::class, 'update'])->name('staff.update');
            Route::delete('/staff/{staff}', [StaffController::class, 'destroy'])->name('staff.destroy');
            Route::post('/staff/{staff}/deactivate', [StaffController::class, 'deactivate'])->name('staff.deactivate');
            Route::post('/staff/{staff}/activate', [StaffController::class, 'activate'])->name('staff.activate');

            // Invitations
            Route::get('/invitations', [InvitationController::class, 'index'])->name('invitations.index');
            Route::post('/invitations', [InvitationController::class, 'store'])->name('invitations.store');
            Route::delete('/invitations/{invitation}', [InvitationController::class, 'destroy'])->name('invitations.destroy');
            Route::post('/invitations/{invitation}/resend', [InvitationController::class, 'resend'])->name('invitations.resend');

            // Roles and permissions
            Route::get('/permissions', [SettingsController::class, 'rolePermissions'])->name('permissions.index');
            Route::put('/permissions', [SettingsController::class, 'updateRolePermissions'])->name('permissions.update');

            // Departments
            Route::resource('departments', DepartmentHrController::class)->except(['show']);
            Route::resource('positions', PositionController::class)->except(['show', 'create', 'edit']);

            // Frekwencja (hr.attendance, hr.clock-in, etc.)
            Route::name('hr.')->group(function () {
                Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance');
                Route::get('/attendance/reports', [AttendanceController::class, 'reports'])->name('attendance.reports');
                Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('clock-in');
                Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('clock-out');

                // Urlopy
                Route::get('/leaves', [LeaveRequestController::class, 'index'])->name('leave');
                Route::get('/leaves/calendar', [LeaveRequestController::class, 'calendar'])->name('leave.calendar');
                Route::post('/leaves', [LeaveRequestController::class, 'store'])->name('request-leave');
                Route::post('/leaves/{request}/approve', [LeaveRequestController::class, 'approve'])->name('approve-leave');
                Route::post('/leaves/{request}/reject', [LeaveRequestController::class, 'reject'])->name('reject-leave');
            });
            Route::resource('leave-types', LeaveTypeController::class)->except(['show', 'create', 'edit']);

            // Announcements
            Route::resource('announcements', AnnouncementController::class);
            Route::post('/announcements/{announcement}/read', [AnnouncementController::class, 'markRead'])->name('announcements.read');

            // Performance reviews
            Route::resource('performance', PerformanceReviewController::class)->except(['show']);
        });

        /* --- Internal chat --- */
        Route::prefix('messages')->name('messages.')->group(function () {
            Route::get('/', [MessageController::class, 'index'])->name('index');
            Route::get('/{conversation}', [MessageController::class, 'show'])->name('show');
            Route::post('/', [MessageController::class, 'createConversation'])->name('store');
            Route::post('/{conversation}/messages', [MessageController::class, 'store'])->name('send');
            Route::post('/{conversation}/read', [MessageController::class, 'markRead'])->name('read');
        });

        /* --- Raporty --- */
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', fn () => redirect()->route('tenant.manager.reports.time'))->name('index');
            Route::get('/time', [ReportTimeController::class, 'index'])->name('time');
            Route::get('/finance', [ReportFinanceController::class, 'index'])->name('finance');
            Route::get('/projects', [ReportProjectController::class, 'index'])->name('projects');
            Route::get('/staff', [ReportStaffController::class, 'index'])->name('staff');
            Route::get('/clients', [ReportClientController::class, 'index'])->name('clients');
            Route::post('/export', [ReportController::class, 'export'])->name('export');
            Route::get('/export-csv', [ReportController::class, 'exportCsv'])->name('export-csv');
        });

        /* --- Automatyzacje --- */
        Route::resource('automations', AutomationController::class)->except(['show']);
        Route::post('/automations/{automation}/toggle', [AutomationController::class, 'toggle'])->name('automations.toggle');

        /* --- Webhooks --- */
        Route::resource('webhooks', WebhookController::class)->except(['show']);
        Route::post('/webhooks/{webhook}/test', [WebhookController::class, 'test'])->name('webhooks.test');

        /* --- Powiadomienia --- */
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
        Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store'])->name('push-subscriptions.store');

        /* --- Ustawienia --- */
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [SettingsController::class, 'index'])->name('index');
            Route::post('/upload', [SettingsController::class, 'upload'])->name('upload');
            Route::post('/role-permissions', [SettingsController::class, 'updateRolePermissions'])->name('role-permissions.update');
            Route::get('/company', [SettingsController::class, 'company'])->name('company');
            Route::post('/company', [SettingsController::class, 'updateCompany'])->name('company.update');
            Route::get('/finance', [SettingsController::class, 'finance'])->name('finance');
            Route::post('/finance', [SettingsController::class, 'updateFinance'])->name('finance.update');
            Route::get('/email-templates', [EmailTemplateController::class, 'index'])->name('email-templates.index');
            Route::put('/email-templates/{template}', [EmailTemplateController::class, 'update'])->name('email-templates.update');
            Route::post('/email-templates/{template}/reset', [EmailTemplateController::class, 'reset'])->name('email-templates.reset');
            Route::get('/custom-fields', [CustomFieldController::class, 'index'])->name('custom-fields.index');
            Route::post('/custom-fields', [CustomFieldController::class, 'store'])->name('custom-fields.store');
            Route::put('/custom-fields/{field}', [CustomFieldController::class, 'update'])->name('custom-fields.update');
            Route::delete('/custom-fields/{field}', [CustomFieldController::class, 'destroy'])->name('custom-fields.destroy');
            Route::get('/integrations', [IntegrationController::class, 'index'])->name('integrations.index');
            Route::post('/integrations', [IntegrationController::class, 'store'])->name('integrations.connect');
            Route::delete('/integrations/{type}', [IntegrationController::class, 'destroy'])->name('integrations.disconnect');
            Route::get('/notifications', [SettingsController::class, 'notifications'])->name('notifications');
            Route::post('/notifications', [SettingsController::class, 'updateNotifications'])->name('notifications.update');
            Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log');
            Route::get('/billing', [BillingController::class, 'index'])->name('billing');
            Route::post('/billing/change-plan', [BillingController::class, 'changePlan'])->name('billing.change-plan');
        });

        /* --- Globalne wyszukiwanie --- */
        Route::get('/search', [SearchController::class, 'index'])->name('search');

        /* --- User profile --- */
        Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
        Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');

        /* --- Task dependencies --- */
        Route::post('/tasks/{task}/dependencies', [TaskDependencyController::class, 'store'])->name('tasks.dependencies.store');
        Route::delete('/tasks/{task}/dependencies/{dependency}', [TaskDependencyController::class, 'destroy'])->name('tasks.dependencies.destroy');
        Route::get('/tasks/{task}/can-start', [TaskDependencyController::class, 'canStart'])->name('tasks.can-start');

        /* --- Recurring tasks --- */
        Route::get('/tasks/recurring', [TaskRecurringController::class, 'index'])->name('tasks.recurring.index');
        Route::post('/tasks/{task}/recurring', [TaskRecurringController::class, 'store'])->name('tasks.recurring.store');
        Route::delete('/tasks/{task}/recurring', [TaskRecurringController::class, 'destroy'])->name('tasks.recurring.destroy');

        /* --- Podzadania --- */
        Route::post('/tasks/{task}/subtasks', [SubtaskController::class, 'store'])->name('tasks.subtasks.store');
        Route::put('/tasks/{task}/subtasks/{subtask}', [SubtaskController::class, 'update'])->name('tasks.subtasks.update');
        Route::delete('/tasks/{task}/subtasks/{subtask}', [SubtaskController::class, 'destroy'])->name('tasks.subtasks.destroy');

        /* --- Task templates --- */
        Route::prefix('task-templates')->name('task-templates.')->group(function () {
            Route::get('/', [TaskTemplateController::class, 'index'])->name('index');
            Route::post('/', [TaskTemplateController::class, 'store'])->name('store');
            Route::put('/{template}', [TaskTemplateController::class, 'update'])->name('update');
            Route::delete('/{template}', [TaskTemplateController::class, 'destroy'])->name('destroy');
        });

        /* --- Dni wolne --- */
        Route::prefix('hr/holidays')->name('hr.holidays.')->group(function () {
            Route::get('/', [HolidayController::class, 'index'])->name('index');
            Route::post('/', [HolidayController::class, 'store'])->name('store');
            Route::put('/{holiday}', [HolidayController::class, 'update'])->name('update');
            Route::delete('/{holiday}', [HolidayController::class, 'destroy'])->name('destroy');
        });

        /* --- Leave balances --- */
        Route::prefix('hr/leave-balances')->name('hr.leave-balances.')->group(function () {
            Route::get('/', [LeaveBalanceController::class, 'index'])->name('index');
            Route::post('/', [LeaveBalanceController::class, 'store'])->name('store');
            Route::post('/requests/{leaveRequest}/approve', [LeaveBalanceController::class, 'approve'])->name('approve');
            Route::post('/requests/{leaveRequest}/reject', [LeaveBalanceController::class, 'reject'])->name('reject');
        });

        /* --- Tagi --- */
        Route::prefix('tags')->name('tags.')->group(function () {
            Route::get('/', [TagController::class, 'index'])->name('index');
            Route::post('/', [TagController::class, 'store'])->name('store');
            Route::put('/{tag}', [TagController::class, 'update'])->name('update');
            Route::delete('/{tag}', [TagController::class, 'destroy'])->name('destroy');
        });

        /* --- CRM activity --- */
        Route::prefix('crm/activities')->name('crm.activities.')->group(function () {
            Route::get('/', [ActivityController::class, 'index'])->name('index');
            Route::post('/', [ActivityController::class, 'store'])->name('store');
            Route::delete('/{activity}', [ActivityController::class, 'destroy'])->name('destroy');
        });

        /* --- Project budget --- */
        Route::get('/projects/{project}/budget', [BudgetController::class, 'show'])->name('projects.budget');
        Route::post('/projects/{project}/budget', [BudgetController::class, 'store'])->name('projects.budget.store');
        Route::put('/projects/{project}/budget', [BudgetController::class, 'update'])->name('projects.budget.update');

        /* --- Payment methods --- */
        Route::resource('finance/payment-methods', PaymentMethodController::class)
            ->except(['show', 'create', 'edit'])->names('payment-methods');

        /* --- Employee profiles --- */
        Route::get('/hr/staff/{user}/profile', [StaffProfileController::class, 'show'])->name('hr.staff.profile');
        Route::put('/hr/staff/{user}/profile', [StaffProfileController::class, 'update'])->name('hr.staff.profile.update');
        Route::put('/hr/staff/{user}/role', [StaffProfileController::class, 'updateRole'])->name('hr.staff.role');
        Route::post('/hr/staff/{user}/reset-password', [StaffProfileController::class, 'resetPassword'])->name('hr.staff.reset-password');
        Route::delete('/hr/staff/{user}/delete', [StaffProfileController::class, 'destroy'])->name('hr.staff.delete');

        /* --- Burndown sprintu --- */
        Route::get('/sprints/{sprint}/burndown', [SprintController::class, 'burndown'])->name('sprints.burndown');
    });

    /*
    |--------------------------------------------------------------------------
    | Portal Klienta (auth:customer guard)
    |--------------------------------------------------------------------------
    */
    Route::prefix('portal')->group(function () {
        // Auth portalu (client.*)
        Route::name('client.')->group(function () {
            Route::get('/logowanie', [App\Http\Controllers\Tenant\Client\AuthController::class, 'showLogin'])->name('login');
            Route::post('/logowanie', [App\Http\Controllers\Tenant\Client\AuthController::class, 'login'])->name('login')->middleware('throttle:20,1');
            Route::post('/wylogowanie', [App\Http\Controllers\Tenant\Client\AuthController::class, 'logout'])->name('logout');
            Route::get('/rejestracja', [App\Http\Controllers\Tenant\Client\AuthController::class, 'showRegister'])->name('register');
            Route::post('/rejestracja', [App\Http\Controllers\Tenant\Client\AuthController::class, 'register'])->name('register')->middleware('throttle:10,1');
            Route::get('/reset-hasla', [App\Http\Controllers\Tenant\Client\AuthController::class, 'forgotPassword'])->name('password.request');
            Route::post('/reset-hasla', [App\Http\Controllers\Tenant\Client\AuthController::class, 'sendResetLink'])->name('password.email');
        });

        Route::middleware(['auth:customer'])->name('portal.')->group(function () {
            Route::get('/dashboard', [PortalController::class, 'dashboard'])->name('dashboard');

            // Projects, read only
            Route::get('/projects', [PortalController::class, 'projects'])->name('projects');
            Route::get('/projects/{project}', [PortalController::class, 'projectShow'])->name('projects.show');

            // Tasks, read only plus comments
            Route::get('/tasks', [PortalController::class, 'tasks'])->name('tasks');

            // Invoices
            Route::get('/invoices', [PortalController::class, 'invoices'])->name('invoices');
            Route::get('/invoices/{invoice}', [PortalController::class, 'invoiceShow'])->name('invoices.show');
            Route::get('/invoices/{invoice}/pdf', [PortalController::class, 'invoicePdf'])->name('invoices.pdf');

            // Estimates
            Route::get('/estimates', [PortalController::class, 'estimates'])->name('estimates');
            Route::post('/estimates/{estimate}/accept', [PortalController::class, 'estimateAccept'])->name('estimates.accept');
            Route::post('/estimates/{estimate}/reject', [PortalController::class, 'estimateReject'])->name('estimates.reject');

            // Kontrakty
            Route::get('/contracts', [PortalController::class, 'contracts'])->name('contracts');
            Route::post('/contracts/{contract}/sign', [PortalController::class, 'contractSign'])->name('contracts.sign');

            // Propozycje
            Route::get('/proposals', [PortalController::class, 'proposals'])->name('proposals');
            Route::get('/proposals/{proposal}', [PortalController::class, 'proposalShow'])->name('proposals.show');
            Route::post('/proposals/{proposal}/accept', [PortalController::class, 'proposalAccept'])->name('proposals.accept');
            Route::post('/proposals/{proposal}/reject', [PortalController::class, 'proposalReject'])->name('proposals.reject');
            Route::get('/proposals/{proposal}/pdf', [PortalController::class, 'proposalPdf'])->name('proposals.pdf');

            // Support Tickets
            Route::get('/support', [PortalController::class, 'tickets'])->name('tickets');
            Route::get('/support/create', [PortalController::class, 'ticketCreate'])->name('tickets.create');
            Route::post('/support', [PortalController::class, 'ticketStore'])->name('tickets.store');
            Route::get('/support/{ticket}', [PortalController::class, 'ticketShow'])->name('tickets.show');
            Route::post('/support/{ticket}/messages', [PortalController::class, 'ticketReply'])->name('tickets.messages.store');

            // Baza Wiedzy
            Route::get('/knowledge-base', [PortalController::class, 'kb'])->name('kb');
            Route::get('/knowledge-base/{article}', [PortalController::class, 'kbShow'])->name('kb.show');

            // Powiadomienia
            Route::get('/notifications', [PortalController::class, 'notifications'])->name('notifications');

            // Konto
            Route::get('/account', [PortalController::class, 'account'])->name('account');
            Route::put('/account', [PortalController::class, 'updateAccount'])->name('account.update');
            Route::put('/account/password', [PortalController::class, 'changePassword'])->name('account.password');
        });

        // Proposal open tracking, public and unauthenticated
        Route::get('/proposals/{token}/view', [App\Http\Controllers\Tenant\Client\ProposalController::class, 'trackView'])->name('portal.proposals.track');
    });

    /*
    |--------------------------------------------------------------------------
    | Portal Klienta — explicit client.* routes (używane przez Vue pages)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth:customer'])->prefix('portal')->name('client.')->group(function () {
        // Zadania klienta
        Route::get('/my-tasks', [PortalController::class, 'tasks'])->name('tasks.index');
        Route::get('/my-tasks/{task}', [PortalController::class, 'taskShow'])->name('tasks.show');
        Route::post('/my-tasks/{task}/comments', [PortalController::class, 'taskComment'])->name('tasks.comment');

        // Propozycje klienta
        Route::get('/my-proposals', [PortalController::class, 'proposals'])->name('proposals.index');
        Route::get('/my-proposals/{proposal}', [PortalController::class, 'proposalShow'])->name('proposals.show');
        Route::post('/my-proposals/{proposal}/accept', [PortalController::class, 'proposalAccept'])->name('proposals.accept');
        Route::post('/my-proposals/{proposal}/reject', [PortalController::class, 'proposalReject'])->name('proposals.reject');
        Route::get('/my-proposals/{proposal}/pdf', [PortalController::class, 'proposalPdf'])->name('proposals.pdf');
    });
});
