<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $fillable = [
        'name','description','category_id','api_provider_id',
        'api_service_id','rate','min','max','status','type',
        'tier','min_time','max_time',
    ];
    protected $casts = ['rate'=>'float','min'=>'integer','max'=>'integer','min_time'=>'integer','max_time'=>'integer'];
    public function category(): BelongsTo   { return $this->belongsTo(Category::class); }
    public function apiProvider(): BelongsTo{ return $this->belongsTo(ApiProvider::class); }
    public function orders(): HasMany       { return $this->hasMany(Order::class); }
    public function scopeActive($q)         { return $q->where('status','active'); }
}
