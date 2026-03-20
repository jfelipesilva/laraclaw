<?php

namespace App\Http\Controllers;

use App\Models\AgentConfig;
use App\Models\CalendarEvent;
use App\Models\ClickupTask;
use App\Models\Execution;
use App\Services\RunnableRegistry;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $devs = collect(config('laraclaw.clickup.devs'));
        $now = Carbon::now();
        $monthStart = $now->copy()->startOfMonth();
        $weekStart = $now->copy()->startOfWeek();

        // KPI: API Placas credits (last execution)
        $plateExec = Execution::where('agent_slug', 'veicoplatescreditsleft')
            ->where('status', 'success')
            ->latest('finished_at')
            ->first();
        $plateCredits = null;
        $plateAlert = false;
        if ($plateExec && $plateExec->output_result) {
            $plateData = json_decode($plateExec->output_result, true);
            $plateCredits = $plateData['credits'] ?? null;
            $plateAlert = $plateData['alert'] ?? false;
        }

        // Pipeline: count tasks by status (exclude closed/done statuses for pipeline view)
        $pipeline = ClickupTask::select('status')
            ->selectRaw('count(*) as total')
            ->whereNull('date_done')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        // Status order for pipeline display
        $pipelineOrder = [
            'backlog' => ['label' => 'BACKLOG', 'color' => 'green', 'text' => 'green-600'],
            'disponíveis' => ['label' => 'DISPONIV', 'color' => 'blue', 'text' => 'blue-400'],
            'na fila' => ['label' => 'NA_FILA', 'color' => 'cyan', 'text' => 'cyan-400'],
            'em execução' => ['label' => 'EXECUCAO', 'color' => 'yellow', 'text' => 'yellow-400'],
            'pausado' => ['label' => 'PAUSADO', 'color' => 'orange', 'text' => 'orange-400'],
            'em revisão' => ['label' => 'REVISAO', 'color' => 'purple', 'text' => 'purple-400'],
            'correções' => ['label' => 'CORRECAO', 'color' => 'red', 'text' => 'red-400'],
        ];

        $pipelineTotal = array_sum($pipeline);

        // KPI: Tasks in execution (WIP)
        $wipCount = $pipeline['em execução'] ?? 0;

        // KPI: Tasks done this week
        $doneThisWeek = ClickupTask::where('date_done', '>=', $weekStart)->count();

        // KPI: Overdue tasks
        $overdueCount = ClickupTask::whereNotNull('due_date')
            ->where('due_date', '<', $now)
            ->whereNull('date_done')
            ->count();

        // Dev panels
        $monthEnd = $now->copy()->endOfMonth();
        $prevMonthStart = $now->copy()->subMonth()->startOfMonth();
        $prevMonthEnd = $now->copy()->subMonth()->endOfMonth();
        $nextMonthStart = $now->copy()->addMonth()->startOfMonth();
        $nextMonthEnd = $now->copy()->addMonth()->endOfMonth();

        $devPanels = $devs->map(function ($dev) use ($now, $monthStart, $monthEnd, $prevMonthStart, $prevMonthEnd, $nextMonthStart, $nextMonthEnd) {
            $assigneeId = $dev['clickup_id'];

            // Tasks em execucao (global, sem filtro de período)
            $inExecution = ClickupTask::where('assignee_id', $assigneeId)
                ->where('status', 'em execução')
                ->whereNull('date_done')
                ->get();

            // Tasks disponiveis / na fila (global)
            $available = ClickupTask::where('assignee_id', $assigneeId)
                ->whereIn('status', ['disponíveis', 'na fila'])
                ->whereNull('date_done')
                ->get();

            // Tasks em revisao / correções (global)
            $inReview = ClickupTask::where('assignee_id', $assigneeId)
                ->whereIn('status', ['em revisão', 'correções'])
                ->whereNull('date_done')
                ->count();

            // Pausadas (global)
            $paused = ClickupTask::where('assignee_id', $assigneeId)
                ->where('status', 'pausado')
                ->whereNull('date_done')
                ->count();

            // --- Barras de progresso filtradas por due_date ---

            // Current month: tasks with due_date in this month
            $doneMonth = ClickupTask::where('assignee_id', $assigneeId)
                ->whereBetween('due_date', [$monthStart, $monthEnd])
                ->whereNotNull('date_done')
                ->count();

            $activeMonth = ClickupTask::where('assignee_id', $assigneeId)
                ->whereBetween('due_date', [$monthStart, $monthEnd])
                ->whereNull('date_done')
                ->count();

            $totalTasks = $doneMonth + $activeMonth;
            $progress = $totalTasks > 0 ? round(($doneMonth / $totalTasks) * 100) : 0;

            // Previous month: tasks with due_date in previous month
            $donePrevMonth = ClickupTask::where('assignee_id', $assigneeId)
                ->whereBetween('due_date', [$prevMonthStart, $prevMonthEnd])
                ->whereNotNull('date_done')
                ->count();

            $activePrevMonth = ClickupTask::where('assignee_id', $assigneeId)
                ->whereBetween('due_date', [$prevMonthStart, $prevMonthEnd])
                ->whereNull('date_done')
                ->count();

            $totalTasksPrev = $donePrevMonth + $activePrevMonth;
            $progressPrev = $totalTasksPrev > 0 ? round(($donePrevMonth / $totalTasksPrev) * 100) : 0;

            // Next month: tasks with due_date in next month
            $doneNextMonth = ClickupTask::where('assignee_id', $assigneeId)
                ->whereBetween('due_date', [$nextMonthStart, $nextMonthEnd])
                ->whereNotNull('date_done')
                ->count();

            $activeNextMonth = ClickupTask::where('assignee_id', $assigneeId)
                ->whereBetween('due_date', [$nextMonthStart, $nextMonthEnd])
                ->whereNull('date_done')
                ->count();

            $totalTasksNext = $doneNextMonth + $activeNextMonth;
            $progressNext = $totalTasksNext > 0 ? round(($doneNextMonth / $totalTasksNext) * 100) : 0;

            // Total active (global, for display)
            $totalActive = ClickupTask::where('assignee_id', $assigneeId)
                ->whereNull('date_done')
                ->count();

            // Overdue
            $overdue = ClickupTask::where('assignee_id', $assigneeId)
                ->whereNotNull('due_date')
                ->where('due_date', '<', $now)
                ->whereNull('date_done')
                ->count();

            // Status badge
            $hasOverdue = $overdue > 0;
            $isWorking = $inExecution->count() > 0;

            return [
                'name' => $dev['name'],
                'clickup_id' => $assigneeId,
                'in_execution' => $inExecution,
                'available' => $available,
                'in_review' => $inReview,
                'paused' => $paused,
                'done_month' => $doneMonth,
                'done_prev_month' => $donePrevMonth,
                'done_next_month' => $doneNextMonth,
                'total_active' => $totalActive,
                'overdue' => $overdue,
                'has_overdue' => $hasOverdue,
                'is_working' => $isWorking,
                'progress' => $progress,
                'progress_prev' => $progressPrev,
                'progress_next' => $progressNext,
                'total_tasks' => $totalTasks,
                'total_tasks_prev' => $totalTasksPrev,
                'total_tasks_next' => $totalTasksNext,
            ];
        });

        // Overdue tasks to archive/complete (due before current month, not archived)
        $overduePendingArchive = ClickupTask::whereNotNull('due_date')
            ->where('due_date', '<', $monthStart)
            ->where('status', '!=', 'arquivado')
            ->orderBy('due_date', 'asc')
            ->get();

        // Runnables table
        $runnables = RunnableRegistry::all()->map(function ($runnable) {
            $config = $runnable->getConfig();
            $lastExec = Execution::where('agent_slug', $runnable->getSlug())
                ->latest('finished_at')
                ->first();

            return [
                'slug' => $runnable->getSlug(),
                'name' => $runnable->getName(),
                'type' => $runnable->getType(),
                'cron' => $runnable->getCronExpression(),
                'active' => $runnable->isActive(),
                'last_run' => $config?->last_run_at,
                'last_status' => $lastExec?->status,
            ];
        });

        // Execution log (last 20)
        $executions = Execution::latest('started_at')->limit(20)->get();

        // Last sync time
        $lastSync = AgentConfig::where('slug', 'clickup-sync')->first()?->last_run_at;

        // Calendar events (today + upcoming)
        $calendarEventsToday = CalendarEvent::where('status', '!=', 'cancelled')
            ->whereDate('start_at', $now->toDateString())
            ->orderBy('all_day', 'desc')
            ->orderBy('start_at')
            ->get();

        $calendarEventsUpcoming = CalendarEvent::where('status', '!=', 'cancelled')
            ->whereDate('start_at', '>', $now->toDateString())
            ->orderBy('start_at')
            ->limit(10)
            ->get();

        $calendarLastSync = AgentConfig::where('slug', 'google-calendar-sync')->first()?->last_run_at;

        return view('dashboard', compact(
            'plateCredits',
            'plateAlert',
            'pipeline',
            'pipelineOrder',
            'pipelineTotal',
            'wipCount',
            'doneThisWeek',
            'overdueCount',
            'overduePendingArchive',
            'devPanels',
            'runnables',
            'executions',
            'lastSync',
            'calendarEventsToday',
            'calendarEventsUpcoming',
            'calendarLastSync',
        ));
    }
}
