<?php

namespace App\Modules\CRM\Models;

use App\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property string $id
 * @property int $version
 */
class Lead extends Model
{
    use BelongsToTenant, HasUlids;

    protected $fillable = ['name', 'email', 'phone', 'source', 'status', 'notes', 'created_by'];

    protected function casts(): array
    {
        return ['version' => 'integer'];
    }

    /** @return HasOne<Customer, $this> */
    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class);
    }
}
