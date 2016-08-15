<?php
namespace NwLaravel\ActivityLog\Handlers;

use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Authenticatable;
use NwLaravel\ActivityLog\ActivityLog;
use Carbon\Carbon;

class DefaultHandler implements HandlerInterface
{
    /**
     * @var ActivityLog
     */
    protected $model;

    /**
     * Construct
     *
     * @param ActivityLog $model
     */
    public function __construct(ActivityLog $model)
    {
        $this->model = $model;
    }

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
    public function log($action, $description, $content = null, Authenticatable $user = null, Request $request = null)
    {
        $user_id = null;
        $user_name = '';
        if ($user) {
            $user_id = $user->getAuthIdentifier();
            $field_username = config('nwlaravel.activity.field_username', 'username');
            $user_name = ($field_username && isset($user->{$field_username})) ? (string) $user->{$field_username} : '';
        }
        
        $content_type = is_object($content) ? get_class($content) : null;
        $content_id = is_object($content) ? $content->id : null;

        $data = [
            'action' => $action,
            'user_id' => $user_id,
            'user_name' => $user_name,
            'description' => $description,
            'ip_address' => $request->ip(),
            'content_type' => $content_type,
            'content_id' => $content_id,
        ];

        return (bool) $this->model->create($data);
    }

    /**
     * Clean old log records.
     *
     * @param int $maxAgeInMonths
     *
     * @return integer
     */
    public function cleanLog($maxAgeInMonths)
    {
        $maxAgeInMonths = 4;
        $date = Carbon::now()->subMonths($maxAgeInMonths);

        return $this->model->where('created_at', '<=', $date->format('Y-m-d'))->delete();
    }
}