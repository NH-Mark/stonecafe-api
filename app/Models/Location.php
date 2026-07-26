<?php
namespace App\Models;


use Illuminate\Database\Eloquent\Model;


class Location extends Model
{

protected $fillable=[
        'name',
        'code',
        'address',
        'phone',
        'status'
];


public function users()
{
    return $this->hasMany(User::class);
}


}