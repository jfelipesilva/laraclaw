<?php

namespace App\Http\Controllers;

use App\Models\AgentConfig;
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
        $prevMonthStart = $now->copy()->subMonth()->startOfMonth();
        $prevMonthEnd = $now->copy()->subMonth()->endOfMonth();

        $devPanels = $devs->map(function ($dev) use ($now, $monthStart, $prevMonthStart, $prevMonthEnd) {
            $assigneeId = $dev['clickup_id'];

            // Tasks em execucao
            $inExecution = ClickupTask::where('assignee_id', $assigneeId)
                ->where('status', 'em execução')
                ->whereNull('date_done')
                ->get();

            // Tasks disponiveis / na fila
            $available = ClickupTask::where('assignee_id', $assigneeId)
                ->whereIn('status', ['disponíveis', 'na fila'])
                ->whereNull('date_done')
                ->get();

            // Tasks em revisao / correções
            $inReview = ClickupTask::where('assignee_id', $assigneeId)
                ->whereIn('status', ['em revisão', 'correções'])
                ->whereNull('date_done')
                ->count();

            // Pausadas
            $paused = ClickupTask::where('assignee_id', $assigneeId)
                ->where('status', 'pausado')
                ->whereNull('date_done')
                ->count();

            // Done this month
            $doneMonth = ClickupTask::where('assignee_id', $assigneeId)
                ->where('date_done', '>=', $monthStart)
                ->count();

            // Done previous month
            $donePrevMonth = ClickupTask::where('assignee_id', $assigneeId)
                ->whereBetween('date_done', [$prevMonthStart, $prevMonthEnd])
                ->count();

            // Total assigned (not done)
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

            $totalTasks = $doneMonth + $totalActive;
            $progress = $totalTasks > 0 ? round(($doneMonth / $totalTasks) * 100) : 0;

            // Previous month: tasks done prev month / (done prev month + done this month + total active)
            $totalTasksPrev = $donePrevMonth + $totalActive + $doneMonth;
            $progressPrev = $totalTasksPrev > 0 ? round(($donePrevMonth / $totalTasksPrev) * 100) : 0;

            return [
                'name' => $dev['name'],
                'clickup_id' => $assigneeId,
                'in_execution' => $inExecution,
                'available' => $available,
                'in_review' => $inReview,
                'paused' => $paused,
                'done_month' => $doneMonth,
                'done_prev_month' => $donePrevMonth,
                'total_active' => $totalActive,
                'overdue' => $overdue,
                'has_overdue' => $hasOverdue,
                'is_working' => $isWorking,
                'progress' => $progress,
                'progress_prev' => $progressPrev,
                'total_tasks' => $totalTasks,
                'total_tasks_prev' => $totalTasksPrev,
            ];
        });

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

        return view('dashboard', compact(
            'plateCredits',
            'plateAlert',
            'pipeline',
            'pipelineOrder',
            'pipelineTotal',
            'wipCount',
            'doneThisWeek',
            'overdueCount',
            'devPanels',
            'runnables',
            'executions',
            'lastSync',
        ));
    }
}
