<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RumpunMapel extends Model
{
    protected $fillable = ['nama_rumpun'];

    public function mapels() { return $this->hasMany(Mapel::class, 'rumpun_id')->orderBy('id'); }
}
