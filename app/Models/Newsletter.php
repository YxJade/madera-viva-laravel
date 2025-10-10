<?php
// app/Models/Newsletter.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Newsletter extends Model
{
    protected $table = 'newsletter_subscribers';
    
    protected $fillable = ['email', 'is_active'];
    
    protected $casts = [
        'is_active' => 'boolean'
    ];
}