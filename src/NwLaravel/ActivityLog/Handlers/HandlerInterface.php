<?php
namespace NwLaravel\ActivityLog\Handlers;

use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Authenticatable;

interface HandlerInterface
{
    /**
     * Log activity
     *
     * @param string          $action
     * @param string          $description
     * @param \Eloquent       $content
     * @param Authenticatable $user
     * @param Request         $request
     *
     * @return bool
     */
    public function log($action, $description, $content = null, Authenticatable $user = null, Request $request = null);

    /**
     * Clean old log records.
     *
     * @param int $maxAgeInMonths
     *
     * @return integer
     */
    public function cleanLog($maxAgeInMonths);
}