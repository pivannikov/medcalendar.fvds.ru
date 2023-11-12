<?php
namespace App\Model;

use Nette;

final class SheduleFacade
{
	public function __construct(
		private Nette\Database\Explorer $database,
	) {
	}

	public function getSheduleByMemberId($uid)
	{
		return $this->database
			->table('shedule')
			->where('user_id', $uid);
	}

	public function getSheduleById($sheduleId)
	{
		return $this->database
			->table('shedule')
			->where('id', $sheduleId);
	}

	public function getAllShedules()
	{
		return $this->database
			->table('shedule')
			->order('id');

	}

	public function getAllElements()
	{
		return $this->database
			->table('elements')
			->order('id');
	}
}
