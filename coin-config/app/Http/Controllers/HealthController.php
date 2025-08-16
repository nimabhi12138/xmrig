<?php

namespace App\Http\Controllers;

class HealthController extends Controller
{
	public function healthz()
	{
		return response()->json(['status' => 'ok']);
	}
}