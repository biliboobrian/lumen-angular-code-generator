<?php
if (! function_exists('app_path')) {
	/**
	 * Get the path to the application folder.
	 *
	 * @param  string  $path
	 * @return string
	 */
	function app_path($path = '')
	{
		return app('path').($path ? DIRECTORY_SEPARATOR.$path : $path);
	}
}

return [
    'namespace'       => 'App\Models',
    'base_class_name' => \Illuminate\Database\Eloquent\Model::class,
    'output_path'     => app_path() . '/Models',
    'no_timestamps'   => null,
    'date_format'     => null,
    'connection'      => null,
];