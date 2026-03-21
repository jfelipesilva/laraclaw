<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LaraClaw Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600;700&display=swap');
        body { font-family: 'JetBrains Mono', monospace; }
        .scanline { background: repeating-linear-gradient(0deg, transparent, transparent 2px, rgba(0,255,0,0.015) 2px, rgba(0,255,0,0.015) 4px); pointer-events: none; position: fixed; inset: 0; z-index: 50; }
        .blink { animation: blink 1s infinite; }
        @keyframes blink { 0%,50% { opacity: 1; } 51%,100% { opacity: 0; } }
        .glow-green { text-shadow: 0 0 8px rgba(34,197,94,0.5); }
        @keyframes border-blink-red { 0%,50% { border-color: rgb(239,68,68); } 51%,100% { border-color: rgb(30,10,10); } }
        @keyframes border-blink-yellow { 0%,50% { border-color: rgb(234,179,8); } 51%,100% { border-color: rgb(30,25,10); } }
        .blink-border-red { animation: border-blink-red 1s infinite; }
        .blink-border-yellow { animation: border-blink-yellow 1s infinite; }
    </style>
</head>
<body class="bg-black text-green-400 min-h-screen p-4">
    <div class="scanline"></div>

    <div class="mx-auto" style="max-width: 1900px;">
        {{-- Header --}}
        <div class="border border-green-900 rounded p-3 mb-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
            <div class="flex items-center gap-3">
                <span class="text-green-500 glow-green font-bold">[LARACLAW]</span>
                <span class="text-green-700">v0.1.0</span>
                <span class="text-green-900 hidden sm:inline">|</span>
                <span class="text-green-600 hidden sm:inline">SYSTEM DASHBOARD</span>
            </div>
            <div class="flex items-center gap-4 text-xs">
                @if($lastSync)
                    <span class="text-green-700">last_sync: {{ $lastSync->format('H:i:s') }}</span>
                @endif
                <span class="text-green-700 hidden sm:inline">{{ now()->format('Y-m-d H:i:s') }}</span>
                <span class="blink text-green-400">_</span>
            </div>
        </div>

        {{-- Status Bar (KPIs) --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mb-4">
            <div class="border border-green-900 rounded p-3">
                <div class="text-[10px] text-green-700 uppercase">api.placas.saldo</div>
                <div class="text-2xl font-bold {{ $plateAlert ? 'text-red-500' : 'glow-green' }}">
                    {{ $plateCredits !== null ? number_format($plateCredits, 0, ',', '.') : '---' }}
                </div>
                <div class="text-xs {{ $plateAlert ? 'text-red-600' : 'text-green-600' }}">
                    [{{ $plateAlert ? 'WARN' : 'OK' }}] threshold: 100
                </div>
            </div>
            <div class="border border-green-900 rounded p-3">
                <div class="text-[10px] text-green-700 uppercase">clickup.tasks.wip</div>
                <div class="text-2xl font-bold text-yellow-400">{{ $wipCount }}</div>
                <div class="text-xs text-yellow-600">
                    [ACTIVE] {{ $devPanels->filter(fn($d) => $d['is_working'])->count() }} devs
                </div>
            </div>
            <div class="border border-green-900 rounded p-3">
                <div class="text-[10px] text-green-700 uppercase">clickup.tasks.done.week</div>
                <div class="text-2xl font-bold glow-green">{{ $doneThisWeek }}</div>
                <div class="text-xs text-green-600">[WEEK] {{ now()->startOfWeek()->format('d/m') }} - {{ now()->format('d/m') }}</div>
            </div>
            <div class="border {{ $overdueCount > 0 ? 'border-red-900' : 'border-green-900' }} rounded p-3">
                <div class="text-[10px] {{ $overdueCount > 0 ? 'text-red-700' : 'text-green-700' }} uppercase">clickup.tasks.overdue</div>
                <div class="text-2xl font-bold {{ $overdueCount > 0 ? 'text-red-500' : 'glow-green' }}">{{ $overdueCount }}</div>
                <div class="text-xs {{ $overdueCount > 0 ? 'text-red-600' : 'text-green-600' }}">
                    [{{ $overdueCount > 0 ? 'WARN' : 'OK' }}] {{ $overdueCount > 0 ? 'requires_action' : 'all_on_track' }}
                </div>
            </div>
        </div>

        {{-- Agenda (Google Calendar) --}}
        <div class="border border-green-900 rounded p-4 mb-4">
            <div class="flex items-center justify-between mb-3">
                <div class="text-xs text-green-700">$ laraclaw agenda --view=today+upcoming</div>
                <div class="flex items-center gap-3">
                    @if($calendarLastSync)
                        <span class="text-[10px] text-green-800">synced: {{ $calendarLastSync->format('H:i') }}</span>
                    @endif
                    <button onclick="openCalendarModal()" class="text-[10px] text-green-600 border border-green-800 rounded px-2 py-0.5 hover:bg-green-950 hover:text-green-400 cursor-pointer">[calendarios]</button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Hoje --}}
                <div>
                    <div class="text-xs text-cyan-500 mb-2 font-bold">&#9654; HOJE ({{ now()->format('d/m') }}) — {{ $calendarEventsToday->count() }} evento(s)</div>
                    @forelse($calendarEventsToday as $event)
                        @php
                            $isNow = $event->isNow();
                            $borderClass = $isNow ? 'border-cyan-700 bg-cyan-950/20' : 'border-green-950';
                            $timeLabel = $event->all_day
                                ? 'DIA TODO'
                                : $event->start_at->format('H:i') . ($event->end_at ? ' - ' . $event->end_at->format('H:i') : '');
                        @endphp
                        <div class="border {{ $borderClass }} rounded p-2 mb-1">
                            <div class="flex items-center justify-between">
                                <span class="text-xs {{ $isNow ? 'text-cyan-300 font-bold' : 'text-green-300' }} truncate max-w-[70%]">
                                    @if($isNow)<span class="text-cyan-400 blink">&#9679;</span> @endif
                                    @if($event->html_link)
                                        <a href="{{ $event->html_link }}" target="_blank" class="hover:underline">{{ $event->title }}</a>
                                    @else
                                        {{ $event->title }}
                                    @endif
                                </span>
                                <span class="text-[10px] {{ $isNow ? 'text-cyan-500' : 'text-green-700' }}">{{ $timeLabel }}</span>
                            </div>
                            @if($event->location || $event->calendar_name)
                                <div class="text-[10px] text-green-800 mt-1 truncate">
                                    @if($event->calendar_name)<span style="{{ $event->color ? 'color:'.$event->color : '' }}">&#9632;</span> {{ $event->calendar_name }}@endif
                                    @if($event->location) &#9872; {{ $event->location }}@endif
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-[10px] text-green-800 italic">nenhum evento hoje</div>
                    @endforelse
                </div>

                {{-- Proximos dias --}}
                <div>
                    <div class="text-xs text-green-500 mb-2 font-bold">&#9670; PROXIMOS DIAS</div>
                    @forelse($calendarEventsUpcoming as $event)
                        @php
                            $dayLabel = $event->start_at->isToday() ? 'HOJE' : ($event->start_at->isTomorrow() ? 'AMANHA' : $event->start_at->format('D d/m'));
                            $timeLabel = $event->all_day
                                ? 'DIA TODO'
                                : $event->start_at->format('H:i');
                        @endphp
                        <div class="border border-green-950 rounded p-2 mb-1">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-green-300 truncate max-w-[60%]">
                                    @if($event->html_link)
                                        <a href="{{ $event->html_link }}" target="_blank" class="hover:underline">{{ $event->title }}</a>
                                    @else
                                        {{ $event->title }}
                                    @endif
                                </span>
                                <span class="text-[10px] text-green-700">{{ $dayLabel }} {{ $timeLabel }}</span>
                            </div>
                            @if($event->location || $event->calendar_name)
                                <div class="text-[10px] text-green-800 mt-1 truncate">
                                    @if($event->calendar_name)<span style="{{ $event->color ? 'color:'.$event->color : '' }}">&#9632;</span> {{ $event->calendar_name }}@endif
                                    @if($event->location) &#9872; {{ $event->location }}@endif
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-[10px] text-green-800 italic">nenhum evento nos proximos dias</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Pipeline --}}
        <div class="border border-green-900 rounded p-4 mb-4">
            <div class="text-xs text-green-700 mb-3">$ laraclaw pipeline --space="producao"</div>
            <div class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-7 gap-2 text-center text-xs">
                @foreach($pipelineOrder as $status => $meta)
                    @php
                        $count = $pipeline[$status] ?? 0;
                        $pct = $pipelineTotal > 0 ? round(($count / $pipelineTotal) * 100) : 0;
                        $isExecution = $status === 'em execução';
                    @endphp
                    <div class="border border-{{ $meta['color'] }}-900{{ $isExecution ? '/50' : '' }} rounded p-2 {{ $isExecution ? 'bg-yellow-950/20' : '' }}">
                        <div class="text-{{ $meta['color'] }}-{{ $isExecution ? '600' : '700' }}">{{ $meta['label'] }}</div>
                        <div class="text-lg font-bold text-{{ $meta['text'] }}">{{ $count }}</div>
                        <div class="mt-1 h-1 bg-{{ $meta['color'] }}-900 rounded">
                            <div class="h-1 bg-{{ $meta['color'] }}-{{ $meta['color'] === 'green' ? '700' : '600' }} rounded" style="width:{{ $pct }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Overdue Pending Archive --}}
        @if($overduePendingArchive->count() > 0)
        <div class="border border-red-900 rounded p-4 mb-4">
            <div class="flex items-center justify-between mb-3">
                <div class="text-xs text-red-500">$ laraclaw tasks --overdue --pending-archive <span class="text-red-700">(vencidas antes de {{ now()->format('M/Y') }} | não arquivadas)</span></div>
                <span class="text-red-400 text-xs font-bold blink">{{ $overduePendingArchive->count() }} PENDENTES</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="text-red-700 border-b border-red-900">
                            <th class="text-left pb-1">TASK</th>
                            <th class="text-left pb-1">RESPONSAVEL</th>
                            <th class="text-left pb-1">STATUS</th>
                            <th class="text-left pb-1">PROJETO</th>
                            <th class="text-left pb-1">VENCIMENTO</th>
                            <th class="text-left pb-1">ATRASO</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($overduePendingArchive as $task)
                            @php
                                $daysLate = (int) $task->due_date->diffInDays(now());
                                $urgencyClass = $daysLate > 60 ? 'text-red-400' : ($daysLate > 30 ? 'text-orange-400' : 'text-yellow-400');
                            @endphp
                            <tr class="border-b border-red-950/50 hover:bg-red-950/20">
                                <td class="py-1.5 pr-2 text-red-300 max-w-[300px] truncate">
                                    @if($task->url)
                                        <a href="{{ $task->url }}" target="_blank" class="hover:underline">{{ $task->name }}</a>
                                    @else
                                        {{ $task->name }}
                                    @endif
                                </td>
                                <td class="py-1.5 pr-2 text-red-600">{{ $task->assignee_name ? explode(' ', $task->assignee_name)[0] : '-' }}</td>
                                <td class="py-1.5 pr-2 text-red-600">{{ $task->status }}</td>
                                <td class="py-1.5 pr-2 text-red-700">{{ $task->project ?? '-' }}</td>
                                <td class="py-1.5 pr-2 text-red-600">{{ $task->due_date->format('d/m/Y') }}</td>
                                <td class="py-1.5 {{ $urgencyClass }} font-bold">{{ $daysLate }}d</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Dev Panels --}}
        <div class="text-xs text-green-700 mb-2">$ laraclaw team --view=detailed --month=current</div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            @foreach($devPanels as $dev)
                @php
                    $firstName = explode(' ', $dev['name'])[0];
                    $progressBar = str_repeat('█', (int)($dev['progress'] / 5)) . str_repeat('░', 20 - (int)($dev['progress'] / 5));
                    $progressColor = $dev['progress'] >= 60 ? 'text-green-600' : ($dev['progress'] >= 40 ? 'text-yellow-500' : 'text-red-500');
                    $progressBarPrev = str_repeat('█', (int)($dev['progress_prev'] / 5)) . str_repeat('░', 20 - (int)($dev['progress_prev'] / 5));
                    $progressColorPrev = $dev['progress_prev'] >= 60 ? 'text-green-600' : ($dev['progress_prev'] >= 40 ? 'text-yellow-500' : 'text-red-500');
                    $progressBarNext = str_repeat('█', (int)($dev['progress_next'] / 5)) . str_repeat('░', 20 - (int)($dev['progress_next'] / 5));
                    $progressColorNext = $dev['progress_next'] >= 60 ? 'text-green-600' : ($dev['progress_next'] >= 40 ? 'text-yellow-500' : 'text-red-500');

                    $noExecution = $dev['in_execution']->count() === 0;
                    $noAvailable = $dev['available']->count() === 0;

                    $borderClass = 'border-green-900';
                    $blinkClass = '';
                    if ($noExecution) {
                        $borderClass = '';
                        $blinkClass = 'blink-border-red';
                    } elseif ($noAvailable) {
                        $borderClass = '';
                        $blinkClass = 'blink-border-yellow';
                    } elseif ($dev['has_overdue']) {
                        $borderClass = 'border-red-900/50';
                    }
                @endphp
                <div class="{{ $borderClass }} {{ $blinkClass }} border rounded p-4">
                    {{-- Header --}}
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-green-400 font-bold">{{ strtolower($firstName) }}</span>
                            @if($dev['has_overdue'])
                                <span class="text-red-400 text-[10px] border border-red-900 rounded px-1">OVERDUE</span>
                            @elseif($dev['is_working'])
                                <span class="text-yellow-400 text-[10px] border border-yellow-900 rounded px-1">WORKING</span>
                            @else
                                <span class="text-green-700 text-[10px] border border-green-900 rounded px-1">IDLE</span>
                            @endif
                        </div>
                        <span class="text-green-800 text-[10px]">
                            active: <span class="text-green-400">{{ $dev['total_active'] }}</span>
                        </span>
                    </div>

                    {{-- Progresso mensal --}}
                    <div class="mb-3">
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-green-700">{{ now()->subMonth()->format('M/Y') }} <span class="text-green-800">({{ $dev['done_prev_month'] }}/{{ $dev['total_tasks_prev'] }} tasks)</span></span>
                            <span class="{{ $progressColorPrev }}">{{ $progressBarPrev }} {{ $dev['progress_prev'] }}%</span>
                        </div>
                        <div class="flex justify-between text-xs mb-1">
                            <span>{{ now()->format('M/Y') }} <span class="text-green-700">({{ $dev['done_month'] }}/{{ $dev['total_tasks'] }} tasks)</span></span>
                            <span class="{{ $progressColor }}">{{ $progressBar }} {{ $dev['progress'] }}%</span>
                        </div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-green-700">{{ now()->addMonth()->format('M/Y') }} <span class="text-green-800">({{ $dev['done_next_month'] }}/{{ $dev['total_tasks_next'] }} tasks)</span></span>
                            <span class="{{ $progressColorNext }}">{{ $progressBarNext }} {{ $dev['progress_next'] }}%</span>
                        </div>
                        <div class="flex gap-3 text-[9px] text-green-800">
                            <span>done:<span class="text-green-500">{{ $dev['done_month'] }}</span></span>
                            <span>wip:<span class="text-yellow-500">{{ $dev['in_execution']->count() }}</span></span>
                            <span>overdue:<span class="{{ $dev['overdue'] > 0 ? 'text-red-500' : 'text-green-700' }}">{{ $dev['overdue'] }}</span></span>
                            <span>review:<span class="text-purple-400">{{ $dev['in_review'] }}</span></span>
                            <span>paused:<span class="text-orange-400">{{ $dev['paused'] }}</span></span>
                        </div>
                    </div>

                    {{-- Em execucao --}}
                    <div class="mb-2">
                        <div class="text-[10px] text-yellow-600 mb-1">&#9658; EM EXECUCAO ({{ $dev['in_execution']->count() }})</div>
                        @forelse($dev['in_execution'] as $task)
                            @php
                                $isOverdue = $task->due_date && $task->due_date->isPast();
                                $bgClass = $isOverdue ? 'border-red-900/50 bg-red-950/20' : 'border-yellow-900/50 bg-yellow-950/20';
                                $textClass = $isOverdue ? 'text-red-300' : 'text-yellow-300';
                            @endphp
                            <div class="{{ $bgClass }} border rounded p-2 mb-1">
                                <div class="text-xs {{ $textClass }} truncate">{{ $task->name }}</div>
                                <div class="flex justify-between text-[10px] mt-1">
                                    <span class="{{ $isOverdue ? 'text-red-700' : 'text-yellow-700' }}">{{ $task->project ?? '-' }} &middot; {{ $task->priority ?? '-' }}</span>
                                    @if($task->due_date)
                                        @if($isOverdue)
                                            <span class="text-red-500">venc: {{ $task->due_date->format('d/m') }} ({{ $task->due_date->diffInDays(now()) }}d atraso)</span>
                                        @else
                                            <span class="text-yellow-600">venc: {{ $task->due_date->format('d/m') }}</span>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="text-[10px] text-green-800 italic">nenhuma task em execucao</div>
                        @endforelse
                    </div>

                    {{-- Disponiveis / na fila --}}
                    <div>
                        <div class="text-[10px] text-blue-600 mb-1">&#9670; DISPONIVEIS / FILA ({{ $dev['available']->count() }})</div>
                        @forelse($dev['available']->take(3) as $task)
                            <div class="border border-green-950 rounded p-2 mb-1">
                                <div class="text-xs text-blue-300 truncate">{{ $task->name }}</div>
                                <div class="flex justify-between text-[10px] mt-1">
                                    <span class="text-green-800">{{ $task->project ?? '-' }} &middot; {{ $task->priority ?? '-' }}</span>
                                    @if($task->due_date)
                                        <span class="text-green-700">venc: {{ $task->due_date->format('d/m') }}</span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="text-[10px] text-green-800 italic">nenhuma task disponivel</div>
                        @endforelse
                        @if($dev['available']->count() > 3)
                            <div class="text-[10px] text-green-800">... +{{ $dev['available']->count() - 3 }} mais</div>
                        @endif
                    </div>

                    {{-- Stats footer --}}
                    <div class="mt-3 pt-2 border-t border-green-950 flex justify-between text-[10px] text-green-800">
                        <span>done_mes: <span class="text-green-500">{{ $dev['done_month'] }}</span></span>
                        <span>review: <span class="text-purple-400">{{ $dev['in_review'] }}</span></span>
                        <span>overdue: <span class="{{ $dev['overdue'] > 0 ? 'text-red-400' : 'text-green-500' }}">{{ $dev['overdue'] }}</span></span>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            {{-- Runnables --}}
            <div class="border border-green-900 rounded p-4">
                <div class="text-xs text-green-700 mb-3">$ laraclaw:schedule:list</div>
                <div class="overflow-x-auto">
                <table class="w-full text-xs min-w-[500px]">
                    <thead>
                        <tr class="text-green-700 border-b border-green-900">
                            <th class="text-left pb-1">SLUG</th>
                            <th class="text-left pb-1">TYPE</th>
                            <th class="text-left pb-1">CRON</th>
                            <th class="text-left pb-1">STATUS</th>
                            <th class="text-left pb-1">LAST_RUN</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($runnables as $r)
                            <tr class="border-b border-green-950">
                                <td class="py-1">{{ $r['slug'] }}</td>
                                <td class="{{ $r['type'] === 'agent' ? 'text-purple-400' : 'text-blue-400' }}">{{ strtoupper($r['type']) }}</td>
                                <td class="text-green-700">{{ $r['cron'] ?? '-' }}</td>
                                <td class="{{ $r['last_status'] === 'error' ? 'text-red-400' : ($r['last_status'] === 'success' ? 'text-green-400' : 'text-green-800') }}">
                                    @if(!$r['active'])
                                        OFF
                                    @elseif($r['last_status'] === 'success')
                                        OK
                                    @elseif($r['last_status'] === 'error')
                                        ERR
                                    @else
                                        IDLE
                                    @endif
                                </td>
                                <td class="text-green-700">{{ $r['last_run'] ? $r['last_run']->format('H:i:s') : '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            </div>

            {{-- Team Summary --}}
            <div class="border border-green-900 rounded p-4">
                <div class="text-xs text-green-700 mb-3">$ laraclaw team --period=month --summary</div>
                <div class="space-y-3">
                    @foreach($devPanels as $dev)
                        @php
                            $firstName = strtolower(explode(' ', $dev['name'])[0]);
                            $bar = str_repeat('█', (int)($dev['progress'] / 5)) . str_repeat('░', 20 - (int)($dev['progress'] / 5));
                            $barColor = $dev['progress'] >= 60 ? 'text-green-600' : ($dev['progress'] >= 40 ? 'text-yellow-500' : 'text-red-500');
                        @endphp
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span>{{ $firstName }} <span class="text-green-700">({{ $dev['done_month'] }} done | {{ $dev['in_execution']->count() }} wip)</span></span>
                                <span class="{{ $barColor }}">{{ $bar }} {{ $dev['progress'] }}%</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 pt-3 border-t border-green-900">
                    <div class="text-xs text-green-700 mb-2">metrics:</div>
                    <div class="text-xs space-y-1">
                        <div>throughput_week: <span class="text-green-400">{{ $doneThisWeek }} tasks</span></div>
                        <div>overdue_total: <span class="{{ $overdueCount > 0 ? 'text-red-400' : 'text-green-400' }}">{{ $overdueCount }}</span></div>
                        <div>pipeline_wip: <span class="text-yellow-400">{{ $wipCount }}</span></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Execution Log --}}
        <div class="border border-green-900 rounded p-4">
            <div class="text-xs text-green-700 mb-3">$ tail -f /var/log/laraclaw/executions.log</div>
            <div class="text-xs space-y-1 text-green-600 overflow-x-auto">
                @forelse($executions as $exec)
                    <div class="whitespace-nowrap">
                        [{{ $exec->started_at?->format('Y-m-d H:i:s') ?? '-' }}]
                        <span class="{{ $exec->status === 'success' ? 'text-green-400' : ($exec->status === 'error' ? 'text-red-400' : 'text-yellow-400') }}">{{ strtoupper($exec->status) }}</span>
                        {{ $exec->agent_slug }}
                        | {{ $exec->duration_ms ?? '?' }}ms
                        | ${{ number_format($exec->cost_usd ?? 0, 3) }}
                        @if($exec->status === 'error' && $exec->error_log)
                            | err: {{ Str::limit($exec->error_log, 60) }}
                        @elseif($exec->output_result)
                            | output: {{ Str::limit($exec->output_result, 80) }}
                        @endif
                    </div>
                @empty
                    <div class="text-green-800">-- no executions yet --</div>
                @endforelse
            </div>
        </div>

        {{-- Footer --}}
        <div class="mt-4 text-center text-[10px] text-green-900">
            LaraClaw v0.1.0 | auto-refresh: 5min | data from clickup_tasks + executions
        </div>
    </div>
    {{-- Modal Calendarios --}}
    <div id="calendarModal" class="fixed inset-0 bg-black/80 z-50 hidden items-center justify-center">
        <div class="border border-green-700 rounded bg-black p-6 w-full max-w-md">
            <div class="flex items-center justify-between mb-4">
                <span class="text-green-400 font-bold text-sm">[SELECIONAR CALENDARIOS]</span>
                <button onclick="closeCalendarModal()" class="text-green-700 hover:text-green-400 cursor-pointer">&#10005;</button>
            </div>
            <div id="calendarList" class="space-y-2 mb-4 max-h-60 overflow-y-auto">
                <div class="text-green-800 text-xs">carregando...</div>
            </div>
            <div class="flex justify-between items-center">
                <span id="calendarStatus" class="text-[10px] text-green-800"></span>
                <div class="flex gap-2">
                    <button onclick="closeCalendarModal()" class="text-xs text-green-700 border border-green-900 rounded px-3 py-1 hover:bg-green-950 cursor-pointer">cancelar</button>
                    <button onclick="saveCalendars()" class="text-xs text-green-400 border border-green-700 rounded px-3 py-1 hover:bg-green-950 cursor-pointer">salvar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        setTimeout(function() { location.reload(); }, 5 * 60 * 1000);

        function openCalendarModal() {
            const modal = document.getElementById('calendarModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            loadCalendars();
        }

        function closeCalendarModal() {
            const modal = document.getElementById('calendarModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function loadCalendars() {
            const list = document.getElementById('calendarList');
            list.innerHTML = '<div class="text-green-800 text-xs">carregando...</div>';

            fetch('/api/google/calendars')
                .then(r => r.json())
                .then(calendars => {
                    list.innerHTML = calendars.map(c => `
                        <label class="flex items-center gap-2 cursor-pointer p-2 rounded hover:bg-green-950/30">
                            <input type="checkbox" value="${c.id}" ${c.selected ? 'checked' : ''}
                                class="accent-green-500">
                            <span style="color:${c.color || '#22c55e'}">&#9632;</span>
                            <span class="text-xs text-green-300">${c.name}</span>
                        </label>
                    `).join('');
                })
                .catch(() => {
                    list.innerHTML = '<div class="text-red-500 text-xs">erro ao carregar calendarios</div>';
                });
        }

        function saveCalendars() {
            const checkboxes = document.querySelectorAll('#calendarList input[type="checkbox"]:checked');
            const ids = Array.from(checkboxes).map(cb => cb.value);
            const status = document.getElementById('calendarStatus');

            if (ids.length === 0) {
                status.textContent = 'selecione ao menos 1 calendario';
                status.className = 'text-[10px] text-red-500';
                return;
            }

            status.textContent = 'salvando e sincronizando...';
            status.className = 'text-[10px] text-yellow-500';

            fetch('/api/google/calendars', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ calendar_ids: ids })
            })
            .then(r => r.json())
            .then(data => {
                status.textContent = `salvo! ${data.selected} calendario(s) selecionado(s)`;
                status.className = 'text-[10px] text-green-400';
                setTimeout(() => location.reload(), 1500);
            })
            .catch(() => {
                status.textContent = 'erro ao salvar';
                status.className = 'text-[10px] text-red-500';
            });
        }

        // Fechar modal ao clicar fora
        document.getElementById('calendarModal').addEventListener('click', function(e) {
            if (e.target === this) closeCalendarModal();
        });
    </script>
</body>
</html>
