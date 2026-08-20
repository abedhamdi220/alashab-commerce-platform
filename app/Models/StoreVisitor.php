<?php

namespace App\Models;
use App\Traits\LogsModelActivity;

use App\Traits\BelongsToMerchant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StoreVisitor extends Model
{
    use LogsModelActivity;
    use BelongsToMerchant;

    protected $fillable = [
        'visitor_identifier',
        'display_name',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    protected $hidden = [
        'merchant_id',
        'visitor_identifier',
        'updated_at',
    ];

    protected $appends = ['display_label'];

    public static function fromRequest(Request $request): self
    {
        $visitorIdentifier = strtolower(trim((string) $request->header('X-Visitor-ID')));

        if (!Str::isUuid($visitorIdentifier)) {
            throw ValidationException::withMessages([
                'visitor_id' => ['تعذر التحقق من جلسة الزائر. يرجى تحديث الصفحة ثم المحاولة مرة أخرى.'],
            ]);
        }

        if (!app()->bound('current_merchant_id')) {
            throw ValidationException::withMessages([
                'store' => ['تعذر تحديد المتجر المرتبط بهذه الزيارة.'],
            ]);
        }

        $visitor = static::query()->firstOrCreate([
            'merchant_id' => app('current_merchant_id'),
            'visitor_identifier' => $visitorIdentifier,
        ]);

        $visitor->forceFill(['last_seen_at' => now()])->saveQuietly();

        return $visitor;
    }

    public function rememberDisplayName(string $displayName): void
    {
        $displayName = trim($displayName);

        if ($displayName === '' || $this->display_name === $displayName) {
            return;
        }

        $this->forceFill(['display_name' => $displayName])->saveQuietly();
    }

    public function getDisplayLabelAttribute(): string
    {
        if ($this->display_name) {
            return $this->display_name;
        }

        return 'زائر #' . (string) str($this->visitor_identifier)->replace('-', '')->substr(0, 8)->upper();
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
