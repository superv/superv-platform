<?php

namespace SuperV\Platform\Domains\Resource\Relation;

use SuperV\Platform\Domains\Database\Model\Entry;
use SuperV\Platform\Domains\Resource\ResourceModel;

class RelationModel extends Entry
{
    protected $table = 'sv_relations';

    protected $casts = [
        'config' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (RelationModel $entry) {
            $entry->attributes['uuid'] = uuid();
        });
    }

    public function resource()
    {
        return $this->belongsTo(ResourceModel::class, 'resource_id');
    }

    public function getType()
    {
        return $this->type;
    }

    public static function fromCache($resourceHandle, $relationName)
    {
        $cacheKey = 'sv:relations:'.$resourceHandle.':'.$relationName;

        $entry = cache()->rememberForever($cacheKey, function () use ($resourceHandle, $relationName) {
            return static::query()
                         ->whereHas('resource', function ($query) use ($resourceHandle) {
                             $query->where('identifier', $resourceHandle);
                         })
                         ->where('name', $relationName)
                         ->first();
        });

        return $entry;
    }
}
