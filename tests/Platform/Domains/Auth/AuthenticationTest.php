<?php

namespace Tests\Platform\Domains\Auth;

use Auth;
use Illuminate\Auth\AuthManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Schema;
use SuperV\Platform\Domains\Auth\Concerns\AuthenticatesUsers;
use SuperV\Platform\Domains\Auth\PlatformUserProvider;
use Tests\Platform\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    function authenticates_valid_user_successfully()
    {
        $this->setUpPort('web', 'localhost', null, ['client']);
        $this->makeRoute('web');
        $user = $this->newUser();
        $user->assign('client');

        $response = $this->login('user@superv.io', 'secret');

        $response->assertStatus(302);
        $response->assertRedirect((new LoginControllerStub)->redirectTo());
        $this->assertAuthenticatedAs($user);
    }

    function only_authenticates_allowed_roles()
    {
        $this->setUpPort('acp', 'localhost', null, ['admin']);
        $this->makeRoute('acp');
        $this->newUser()->assign('client');

        $response = $this->login('user@superv.io', 'secret');

        $response->assertStatus(302);
        $response->assertRedirect('login');
        $this->assertNotAuthenticated();
    }

    function resolves_port_model_upon_authentication()
    {
        Schema::create('test_clients', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
        });
        $this->setUpPort('web', 'localhost', null, ['client'], Client::class);
        $this->makeRoute('web');

        $user = $this->newUser();
        $user->assign('client');
        Client::create(['user_id' => $user->id]);

        $response = $this->login('user@superv.io', 'secret');

        // Reset AuthManager
        $this->app->instance('auth', $auth = new AuthManager($this->app));
        $auth->provider('platform', function ($app) {
            return new PlatformUserProvider($app['hash'], config('superv.auth.user.model'));
        });

        $this->assertAuthenticated();
        $this->assertInstanceOf(Client::class, $auth->user());
        $this->assertEquals($user->id, $auth->user()->user->id);
    }

    function does_not_authenticate_a_user_with_invalid_credentials()
    {
        $this->setUpPort('web', 'localhost', null, ['client']);
        $this->makeRoute('web');
        $this->newUser();

        $response = $this->login('user@superv.io', 'not-the-right-password');

        $response->assertStatus(302);
        $response->assertRedirect('login');
        $this->assertNotAuthenticated();
    }

    function test__can_authenticate_multiple_user_types()
    {
        $this->setUpPort('api', 'localhost', null, ['client', 'admin']);
        $this->makeRoute('api');
        $user = $this->newUser();
        $user->assign('client');

        $admin = $this->newUser(['email' => 'admin@superv.io']);
        $admin->assign('admin');

        $response = $this->login($user->getEmail(), 'secret');
        $response->assertRedirect();

        $this->assertAuthenticatedAs($user, 'sv-api');

        Auth::logout();

        $response = $this->login('admin@superv.io', 'secret');
        $response->assertRedirect();

        $this->assertAuthenticatedAs($admin, 'sv-api');
    }

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function login($email, $password)
    {
        return $this->from('/login')->post('/login', [
            'email'    => $email,
            'password' => $password,
        ]);
    }

    protected function assertNotAuthenticated()
    {
        $this->assertNull(Auth::user());
        $this->assertFalse(Auth::check());
    }

    protected function makeRoute($port)
    {
        $this->route('post@login', LoginControllerStub::class.'@login', $port);
    }
}

class Client extends Model
{
    public $timestamps = false;

    protected $table = 'test_clients';

    protected $guarded = [];
}

class LoginControllerStub
{
    use AuthenticatesUsers;
}