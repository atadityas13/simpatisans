<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rumpun extends Model
{
    protected $fillable = ['nama_rumpun'];

    public function mapels()
    {
        return $this->belongsToMany(Mapel::class, 'mapel_rumpun')->orderBy('mapels.id');
    }
}
