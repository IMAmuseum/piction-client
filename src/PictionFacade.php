<?php

namespace Imamuseum\PictionClient;

use Illuminate\Support\Facades\Facade;

class PictionFacade extends Facade
{
	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'Imamuseum\PictionClient\Piction'; }

}
