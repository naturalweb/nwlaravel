<?php
namespace NwLaravel\ActivityLog;

use NwLaravel\Entities\AbstractEntity;

class ActivityLog extends AbstractEntity
{
    const CREATED  = 'created';
    const UPDATED  = 'updated';
    const DELETED  = 'deleted';
    const VIEW     = 'view';
    const LOGIN    = 'login';
    const LOGOUT   = 'logout';
    const EXECUTED = 'executed';
    const SEND     = 'send';
    const UPLOAD   = 'upload';
    const DOWNLOAD = 'download';
    const INFO     = 'info';

    /**
     * @var string
     */
    protected $table = "activity_log";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'action',
        'user_id',
        'user_type',
        'description',
        'details',
        'ip_address',
        'content_type',
        'content_id',
    ];

    /**
     * @var array
     */
    protected $columns = [
        'action',
        'user_id',
        'user_type',
        'description',
        'details',
        'ip_address',
        'content_type',
        'content_id',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the icon class name for the log entry's action.
     *
     * @return string
     */
    public function getIcon()
    {
        $actionIcons = (array) config('nwlaravel.activity.action_icon');
        $icons = $actionIcons['icons'];
        $action = $this->getAttribute('action');
        
        if (empty($action) || !isset($icons[$action])) {
            return $icons['default'];
        }

        return $icons[$action];
    }

    /**
     * Get the markup for the log entry's icon.
     *
     * @return string
     */
    public function getIconMarkup()
    {
        $actionIcons = (array) config('nwlaravel.activity.action_icon');
        $iconElement = $actionIcons['element'];
        $iconPrefix = $actionIcons['class_prefix'];
        return sprintf('<%s class="%s%s"></%s>', $iconElement, $iconPrefix, $this->getIcon(), $iconElement);
    }
}
