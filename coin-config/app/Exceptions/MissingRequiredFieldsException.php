<?php

namespace App\Exceptions;

use Exception;

class MissingRequiredFieldsException extends Exception
{
	public array $missing;

	public function __construct(array $missing)
	{
		parent::__construct('Missing required fields');
		$this->missing = $missing;
	}
}