<?php namespace Cartalyst\Sentry\Model;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Cartalyst\Sentry\UserInterface;


class EloquentUser extends EloquentModel implements UserInterface
{
	protected $table = 'users';

	protected $hidden = array('password');

	public $loginColumn = 'email';

	/**
	 * -----------------------------------------
	 * UserInterface Methods
	 * -----------------------------------------
	 */

	public function findByLogin($login)
	{
		$user = $this->where($this->loginColumn, '=', $login)->first();

		return ($user) ?: false;
	}

	public function findByCredentials($login, $password)
	{
		$user = $this->findByLogin($login);

		if ($user and $password === $user->password)
		{
			return $user;
		}

		return false;
	}

	public function activate($login, $activationCode)
	{
		$user = $this->findByLogin($login);

		if ($user and $activationCode === $user->activation_hash)
		{
			$user->activation_hash = null;
			$user->activated = 1;
			$user->save();

			return true;
		}

		return false;
	}

	public function resetPassword($login, $password)
	{
		$user = $this->findByLogin($login);

		if ($user)
		{
			$resetCode = $this->randomString();

			$user->temp_password = $password;
			$user->reset_password_hash = $resetCode;
			$user->save();

			return $resetCode;
		}

		return false;
	}

	public function confirmResetPassword($login, $resetCode)
	{
		$user = $this->findByLogin($login);

		if ($user and $resetCode === $user->reset_password_hash)
		{
			$user->password = $user->temp_password;
			$user->temp_password = null;
			$user->reset_password_hash = null;
			$user->save();

			return true;
		}

		return false;
	}

	public function clearResetPassword($user)
	{
		if ($user->temp_password or $user->reset_password_hash)
		{
			$user->temp_password = null;
			$user->reset_password_hash = null;
			$user->save();
		}

		return $user;
	}

	protected function randomString()
	{
		$pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

		return substr(str_shuffle(str_repeat($pool, 5)), 0, 40);
	}
}