<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Auth\RefreshToken;
use App\Models\Auth\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Two\User as SocialiteUser;
use Laravel\Socialite\Contracts\Factory as Socialite;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function username()
    {
        return 'name';
    }
    public function showLoginForm()
    {
        return view('auth.login');
    }
    public function redirectToProvider(Socialite $social)
    {
        return $social->driver('eveonline')->scopes(['publicData'])
            ->redirect();
    }

    public function handleProviderCallback(Socialite $social)
    {

        $eve_data = $social->driver('eveonline')->user();

        //logger()->debug(dd($eve_data));
        // Get or create the User bound to this login.
        $user = $this->findOrCreateUser($eve_data);
        logger()->debug('Found user.');
        // Update the refresh token for this character.
        $this->updateRefreshToken($eve_data);
        //Try to log in the user
        if (! $this->loginUser($user))
            return redirect()->route('auth.fail')
                ->with('error', 'Login failed. Please contact your administrator.');
       // dd([$eve_data, $user, RefreshToken::where('character_id', $user->id)->get()]);
        return redirect()->route('home');
    }

    public function findOrCreateUser(SocialiteUser $eve_user): User
    {
        if($existing = User::find($eve_user->character_id)) {
            logger()->debug('Found User '.$existing->name);
            return $existing;
        }
        logger()->debug('Creating User '.$eve_user->name);
        return User::forceCreate([
            'id'            => $eve_user->character_id,
            'name'          => $eve_user->name,
            'active'        => true
        ]);

    }
    public function updateRefreshToken(SocialiteUser $eve_data): void
    {
        RefreshToken::withTrashed()->firstOrNew(['character_id' => $eve_data->character_id])
            ->fill([
                'refresh_token' => $eve_data->refresh_token,
                'scopes'        => explode(' ', $eve_data->scopes),
                'token'         => $eve_data->token,
                'expires_on'    => $eve_data->expires_on,
            ])
            ->save();
        // restore soft deleted token if any
        RefreshToken::onlyTrashed()->where('character_id', $eve_data->character_id)->restore();
    }

    /**
     * @param User $user
     * @return bool
     */
    public function loginUser(User $user)
    {
        if(!$user->active)
        {
            return false;
        }
        //dd($user);
        logger()->debug('Logging in');
        Auth::login($user, true);

        return true;
    }


}
