<?php

namespace NwLaravel\OAuth;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Class OAuthClient Entity
 *
 * @package Titan\OAuth
 */
class OAuthClientEntity extends Eloquent
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'oauth_clients';
}
