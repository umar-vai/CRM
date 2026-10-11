<?php

namespace App\Modules\CRM\Models;

use App\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/** @property string $id */
class Customer extends Model
{
    use BelongsToTenant, HasUlids;

    protected $fillable = ['lead_id', 'name', 'email', 'phone', 'source', 'created_by'];
}
