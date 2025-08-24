<?php

namespace App\Auth;

use App\Models\User;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;

class CookieGuard implements Guard
{
    protected $provider;

    protected $request;

    protected $user;

    public function __construct(UserProvider $provider, Request $request)
    {
        $this->provider = $provider;
        $this->request  = $request;
    }

    public function user()
    {
        if ($this->user) {
            return $this->user;
        }

        $user = (new User())->getAuth($this->request);

        return $this->user = $user;
    }

    public function check()
    {
        return !is_null($this->user());
    }

    public function guest()
    {
        return !$this->check();
    }

    public function id()
    {
        return $this->user()?->id;
    }

    public function validate(array $credentials = [])
    {
        return false; // для логина не нужен
    }

    public function setUser(\Illuminate\Contracts\Auth\Authenticatable $user)
    {
        $this->user = $user;

        return $this;
    }

    public function hasUser(): bool
    {
        return $this->user !== null;
    }
}
