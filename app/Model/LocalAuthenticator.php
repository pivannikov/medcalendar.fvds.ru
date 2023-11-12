<?php

namespace App\Model;

use Nette;
use Nette\Security\SimpleIdentity;

class LocalAuthenticator implements Nette\Security\Authenticator
{
	public function __construct(
		private Nette\Database\Explorer $database,
		private Nette\Security\Passwords $passwords,
	) {
	}

	public function authenticate(string $username, string $password): SimpleIdentity
	{
		$row = $this->database->fetch('SELECT * FROM users WHERE email = ?', $username);


		if (!$row) {
			throw new Nette\Security\AuthenticationException('User not found.');
		}

		if (!$this->passwords->verify($password, $row->password)) {
			throw new Nette\Security\AuthenticationException('Invalid password.');
		}

		return new SimpleIdentity(
			$row->id,
			$row->role, // или массив ролей
			['name' => $row->email],
		);
	}

	public function createUser(string $first_name, string $last_name, string $email, string $phone, string $role = 'member',  string $gender, string $password)
	{
		$this->database->table('users')->insert(['first_name' => $first_name, 'last_name' => $last_name, 'email' => $email, 'phone' => $phone, 'role' => $role, 'gender' => $gender, 'password' => $this->passwords->hash($password)]);

		return $email;
	}
}

