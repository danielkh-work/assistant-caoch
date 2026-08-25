<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PendingUser extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'pending_user_requests';
    protected $guarded = [];

    /**
     * pending_user_requests.email has a hard DB-level unique index that a soft-delete
     * does not release, so without this a coach could never re-attempt signup with the
     * same email after their first pending request is consumed/soft-deleted. Reversible:
     * prefixed with the row's own id, mirroring User::mangleEmailForDelete().
     */
    public function mangleEmailForDelete(): void
    {
        $prefix = 'deleted_'.$this->id.'_';
        if (! str_starts_with($this->email, $prefix)) {
            $this->email = $prefix.$this->email;
            $this->save();
        }
    }
}
