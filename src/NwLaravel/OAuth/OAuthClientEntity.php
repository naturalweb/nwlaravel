<?php

namespace NwLaravel\OAuth;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Class OAuthClient Entity
 *
 * @method OAuthClientEntity where($column, $operator = null, $value = null, $boolean = 'and')
 * @method Eloquent first($columns = ['*'])
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
