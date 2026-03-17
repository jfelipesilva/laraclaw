<?php

namespace App\Services;

use App\Tasks\BaseTask;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use ReflectionClass;

class TaskRegistry
{
    protected static ?Collection $tasks = null;

    public static function all(): Collection
    {
        if (static::$tasks === null) {
            static::$tasks = static::discover();
        }

        return static::$tasks;
    }

    public static function find(string $slug): ?BaseTask
    {
        return static::all()->first(fn (BaseTask $task) => $task->getSlug() === $slug);
    }

    public static function slugs(): array
    {
        return static::all()->map(fn (BaseTask $task) => $task->getSlug())->values()->all();
    }

    protected static function discover(): Collection
    {
        $tasksPath = app_path('Tasks');
        $tasks = collect();

        if (!File::isDirectory($tasksPath)) {
            return $tasks;
        }

        foreach (File::files($tasksPath) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $className = 'App\\Tasks\\' . $file->getFilenameWithoutExtension();

            if (!class_exists($className)) {
                continue;
            }

            $reflection = new ReflectionClass($className);

            if ($reflection->isAbstract() || !$reflection->isSubclassOf(BaseTask::class)) {
                continue;
            }

            $tasks->push(new $className());
        }

        return $tasks;
    }

    public static function flush(): void
    {
        static::$tasks = null;
    }
}
