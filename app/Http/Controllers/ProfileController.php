<?php

namespace App\Http\Controllers;

use App\User;
use App\UserPhone;
use Validator;

use App\Http\Requests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    protected $saved = false;
    protected $deleted = false;
    protected $phone_blank = ['','+7(___)___-__-__'];
    protected $send_interval = 60;

    /**
     * Create a new controller instance.
     *
     * @param  User  $user
     * @param  UserPhone  $phone
     * @return void
     */
    public function __construct(User $user, UserPhone $phone)
    {
        $this->middleware('auth');
        if (Auth::guest()) {
            return redirect('/login');
        }

        User::saved(function($user) {
            $this->saved = true;
        });
        UserPhone::saved(function($phone) {
            $this->saved = true;
        });
        UserPhone::deleting(function($phone) {
            $this->deleted = true;
        });

        $this->modelUser = $user;
        $this->modelPhone = $phone;
        $this->user = Auth::user();
        $this->phones = $this->user->phones();
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $phones = $this->phones->count()
            ? $this->phones->get()
            : '';

        return view('profile.index', ['phones' => $phones]);
    }

    /**
     * Get a validator for an incoming save request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        Validator::extend('phone_regex', function ($attr, $val, $params) {
            if ($attr == 'phone') {
                if (in_array($val,$this->phone_blank)) {
                    return 1;
                }
                if (!preg_match('/^\+7\([0-9]{3}\)[0-9]{3}\-[0-9]{2}\-[0-9]{2}$/', $val)) {
                    return 0;
                }
                return 1;
            }
        });
        Validator::extend('phone_already', function ($attr, $val, $params) {
            if ($attr == 'phone') {
                if (in_array($val,$this->phone_blank)) {
                    return 1;
                }
                return !$this->modelPhone
                    ->where('phone','=',$val)
                    ->where(function($q) {
                        return $q
                            ->where(function($q) {
                                return $q
                                ->where('confirmed','=',1);
                            })
                            ->orWhere(function($q) {
                                return $q
                                    ->where('user_id','=',$this->user->id)
                                    ->where('confirmed','=',0);
                            });
                    })
                    ->exists();
            }
        });

        return Validator::make($data, [
            'name' => 'required|max:255',
            'skype' => 'max:255',
            'phone' => 'phone_regex|phone_already',
        ], [
            'phone_regex' => 'The phone format is invalid.',
            'phone_already' => 'This phone number is already in the database.',
        ]);
    }

    /**
     * Save user profile.
     *
     * @param  Request  $request
     * @return Response
     */
    public function update(Request $request)
    {
        $this->saved = false;

        // Проверяем ошибки заполнения полей
        $validator = $this->validator($request->all());
        if ($validator->fails()) {
            $this->throwValidationException(
                $request, $validator
            );
        }

        if ($this->user->name != $request->input('name') || $this->user->skype != $request->input('skype'))
        {
            $this->user->update([
                'name' => $request->input('name'),
                'skype' => $request->input('skype'),
            ]);
        }

        if (!in_array($request->input('phone'),$this->phone_blank))
        {
            $this->modelPhone
                ->where('phone','=',$request->input('phone'))
                ->where('confirmed','=',0)
                ->delete();

            $this->phones->create([
                'user_id' => $this->user->id,
                'phone' => $request->input('phone'),
                'code' => rand(1000000,9999999),
                'request_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $return = redirect()->back();
        if ($this->saved) {
            return $return->with('msg.ok', 'Profile updated!');
        }
        return $return;
    }

    /**
     * Remove user phone.
     *
     * @param  int  $id
     * @return Response
     */
    public function phoneDelete($id)
    {
        $this->deleted = false;

        if ($this->phones->find($id)) {
            $this->phones->find($id)->delete();
        }

        $return = redirect()->back();
        if ($this->deleted) {
            return $return->with('msg.ok', 'Phone deleted!');
        }
        return $return;
    }

    /**
     * Confirm user phone.
     *
     * @param  Request  $request
     * @return Response
     */
    public function phoneConfirm(Request $request)
    {
        $id = $request->route('id') ?: $request->input('id');

        if ($phone = $this->phones->find($id))
        {
            $phone['timestamp'] = strtotime($phone['request_at']) + $this->send_interval - time();

            if ($request->method() == 'GET') {
                return response()->json($phone);
            }
            else if ($request->method() == 'POST')
            {
                if ($phone['code'] == $request->input('code'))
                {
                    $this->phones->find($id)->update([
                        'code' => '',
                        'confirmed' => 1,
                    ]);

                    return response()->json(['success' => true]);
                }
                else {
                    return response()->json(['success' => false, 'msg' => 'Invalid verification code.']);
                }
            }
        }
        abort(404);
    }

    /**
     * Send code for confirmation user phone.
     *
     * @param  int  $id
     * @return Response
     */
    public function phoneConfirmSend($id)
    {
        if ($phone = $this->phones->find($id))
        {
            if ((strtotime($phone['request_at']) + $this->send_interval - time()) > 0)
            {
                return response()->json(['success' => false]);
            }
            else {
                $phone->update([
                    'request_at' => date('Y-m-d H:i:s'),
                ]);

                $phone['timestamp'] = strtotime($phone['request_at']) + $this->send_interval - time();

                return response()->json($phone);
            }
        }
        abort(404);
    }
}