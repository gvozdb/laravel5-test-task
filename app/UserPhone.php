<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserPhone extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
	protected $fillable = [
		'phone', 'confirmed', 'code', 'request_at',
	];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'token',
    ];

    protected $guarded = ['user_id'];

	public $timestamps = false;

    public function user()
    {
        return $this->belongsTo('App\User');
    }
}
