<?php

namespace SuperV\Platform\Packs\Auth;

use Illuminate\Http\Request;

trait AuthenticatesUsers
{
    protected $redirect = 'dashboard';

      public function login(Request $request)
      {
          $guard = auth()->guard('platform');
          if (! $guard->attempt($request->only(['email', 'password']))) {
              return redirect()->back()
                               ->withInput(request(['email']))
                               ->withErrors([
                                   'email' => 'Invalid credentials',
                               ]);
          }

          return redirect()->to($this->redirectTo());
      }

      public function redirectTo()
      {
          return $this->redirect;
      }
}