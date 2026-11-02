<?php

namespace App\Models;

use App\Models\Concerns\HasReference;
use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    use HasReference;

    protected static string $referencePrefix = 'MSG';

    protected $fillable = ['reference', 'first_name', 'last_name', 'email', 'phone', 'message', 'status'];
}
