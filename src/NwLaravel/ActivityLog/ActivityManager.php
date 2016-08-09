<?php
namespace NwLaravel\ActivityLog;

use NwLaravel\ActivityLog\Handlers\HandlerInterface;
use Illuminate\Config\Repository as Config;
use Illuminate\Auth\AuthManager;

class ActivityManager
{
    /**
     * @var HandlerInterface
     */
    protected $handler;

    /**
     * @var \Illuminate\Auth\AuthManager
     */
    protected $auth;

    /**
     * @var \Illuminate\Config\Repository
     */
    protected $config;

    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $user;

    /**
     * Create the logsupervisor using a default Handler
     * Also register Laravels Log Handler if needed.
     *
     * @param HandlerInterface $handler
     * @param AuthManager      $auth
     * @param Config           $config
     */
    public function __construct(HandlerInterface $handler, AuthManager $auth, Config $config)
    {
        $this->handler = $handler;
        $this->config = $config;
        $this->auth = $auth;
    }

    /**
     * Log activity
     *
     * @param string $action
     * @param string $description
     * @param \Eloquent  $model
     *
     * @return bool
     */
    public function log($action, $description, $model = null)
    {
        return $this->handler->log($action, $description, $model, $this->getUser(), request());
    }

    /**
     * Clean old log records.
     *
     * @return integer
     */
    public function cleanLog()
    {
        return $this->handler->cleanLog($this->config->get('nwlaravel.activity.deleteOlderThanMonths'));
    }

    /**
     * Get User
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable
     */
    protected function getUser()
    {
        if (!$this->user) {
            $authGuard = $this->config->get('nwlaravel.activity.auth_guard') ?: $this->auth->getDefaultDriver();
            $this->user = $this->auth->guard($authGuard)->user();
        }

        return $this->user;
    }
}
