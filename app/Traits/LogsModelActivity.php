<?php

declare(strict_types=1);

namespace App\Traits;

use App\Support\ModelActivityContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

trait LogsModelActivity
{
    protected static function bootLogsModelActivity(): void
    {
        static::created(function (Model $model): void {
            $model->writeModelActivity('created', array_keys($model->getAttributes()));
        });

        static::updated(function (Model $model): void {
            $changedAttributes = array_keys($model->getChanges());
            $changedAttributes = $model->withoutModelActivityTimestamps($changedAttributes);

            if ($changedAttributes !== []) {
                $model->writeModelActivity('updated', $changedAttributes);
            }
        });

        static::deleted(function (Model $model): void {
            // في الحذف النهائي لـ SoftDeletes سيُسجَّل force_deleted لاحقاً بدلاً من deleted.
            if (method_exists($model, 'isForceDeleting') && $model->isForceDeleting()) {
                return;
            }

            $model->writeModelActivity('deleted', $model->usesSoftDeletes() ? ['deleted_at'] : []);
        });

        // restored وforceDeleted متاحان فقط عندما يكون النموذج يستخدم SoftDeletes.
        // تسجيلهما على نموذج عادي يجعل Eloquent يحاول استدعاء دالة غير موجودة عند الإقلاع.
        if (static::modelActivityUsesSoftDeletes()) {
            static::restored(function (Model $model): void {
                $model->writeModelActivity('restored', ['deleted_at']);
            });

            static::forceDeleted(function (Model $model): void {
                $model->writeModelActivity('force_deleted');
            });
        }
    }

    /**
     * يسجل الحدث من دون تخزين قيم الحقول، حتى لا تتسرّب معلومات شخصية أو أسرار.
     *
     * @param array<int, string> $changedAttributes
     */
    protected function writeModelActivity(string $event, array $changedAttributes = []): void
    {
        if (!config('model-activity.enabled', true)) {
            return;
        }

        $changedAttributes = array_values(array_diff(
            array_unique($changedAttributes),
            $this->modelActivityExcludedAttributes(),
        ));

        $context = [
            'event' => $event,
            'model' => static::class,
            'model_id' => $this->getKey(),
            'table' => $this->getTable(),
            'changed_attributes' => $changedAttributes,
            ...ModelActivityContext::current(),
        ];

        Log::build([
            'driver' => 'daily',
            'path' => storage_path('logs/models/'.$this->modelActivityLogName().'.log'),
            'level' => config('model-activity.level', 'info'),
            'days' => max(1, (int) config('model-activity.days', 30)),
            'replace_placeholders' => true,
        ])->info('Model activity recorded.', $context);
    }

    /** @return array<int, string> */
    protected function modelActivityExcludedAttributes(): array
    {
        return array_values(array_unique(array_merge(
            $this->getHidden(),
            config('model-activity.excluded_attributes', []),
        )));
    }

    /** @param array<int, string> $attributes
     *  @return array<int, string>
     */
    protected function withoutModelActivityTimestamps(array $attributes): array
    {
        return array_values(array_diff($attributes, [
            $this->getCreatedAtColumn(),
            $this->getUpdatedAtColumn(),
        ]));
    }

    protected function modelActivityLogName(): string
    {
        return Str::snake(class_basename(static::class));
    }

    protected function usesSoftDeletes(): bool
    {
        return static::modelActivityUsesSoftDeletes();
    }

    protected static function modelActivityUsesSoftDeletes(): bool
    {
        return in_array(
            'Illuminate\\Database\\Eloquent\\SoftDeletes',
            class_uses_recursive(static::class),
            true,
        );
    }
}
