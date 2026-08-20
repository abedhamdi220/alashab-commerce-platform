<?php

namespace App\Models;
use App\Traits\LogsModelActivity;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use LogsModelActivity;
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'store_slug',              // معرّف رابط المتجر العام: /stores/{store_slug}/...
        'meta_phone_id',
        'meta_page_id',
        'delivery_driver_number',
        'whatsapp_access_token',
        'messenger_access_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'whatsapp_access_token',
        'messenger_access_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'whatsapp_access_token' => 'encrypted',
            'messenger_access_token' => 'encrypted',
        ];
    }

    // علاقات مساعدة بصفة User "التاجر" المالك لكل هذي البيانات
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'merchant_id');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'merchant_id');
    }

    public function settings(): HasMany
    {
        return $this->hasMany(Setting::class, 'merchant_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'merchant_id');
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'merchant_id');
    }
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'merchant_id');
    }
}
