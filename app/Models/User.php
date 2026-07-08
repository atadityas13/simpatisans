<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'username', 'nama_lengkap', 'role', 'jabatan', 'password', 'foto',
        'is_active', 'plain_password', 'reset_requested_at',
        'security_question', 'security_answer', 'reset_answer_provided'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'reset_requested_at' => 'datetime',
        ];
    }

    public function guru()
    {
        return $this->belongsTo(Guru::class, 'username', 'username');
    }

    public function getNamaLengkapAttribute($value)
    {
        if ($this->isGuru() && $this->guru) {
            return $this->guru->nama_lengkap;
        }
        return $value;
    }

    public function getJabatanAttribute($value)
    {
        if ($this->isGuru() && $this->guru) {
            return $this->guru->jabatan;
        }
        return $value;
    }

    public function isSuperAdmin()
    {
        return $this->role === 'super_admin';
    }

    public function isAdminKurikulum()
    {
        return $this->role === 'admin_kurikulum';
    }

    public function isGuru()
    {
        return $this->role === 'guru';
    }
}
