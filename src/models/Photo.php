<?php

namespace Hscomp\Photoable\Models;

use Hscomp\Photoable\Traits\Photoable;
use Illuminate\Database\Eloquent\Model;

class Photo extends Model
{
    use Photoable;

    /**
     * Db table name.
     *
     * @var string
     */
    protected $table = 'photos';

    /**
     * Guarded attributes.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Get all of the owning photoable models.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function photoable()
    {
        return $this->morphTo();
    }

}