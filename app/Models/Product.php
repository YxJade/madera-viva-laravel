<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Product extends Model
{
    protected $fillable = [
        'name','description','price','old_price','discount_percentage',
        'brand','image_url','category_id','active','features'
    ];
    protected $casts = [
        'active' => 'boolean',
        'discount_percentage' => 'float',
        'features' => 'array'
    ];
    public function category(){
        return $this->belongsTo(Category::class);
    }
    /* precio final con descuento */
    public function getFinalPriceAttribute(){
        return $this->old_price && $this->discount_percentage > 0
            ? round($this->old_price * (1 - $this->discount_percentage/100), 2)
            : $this->price;
    }
}