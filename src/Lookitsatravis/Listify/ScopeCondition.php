<?php
/**
 * Created by PhpStorm.
 * User: Kaproid
 * Date: 2/9/2019
 * Time: 11:46 PM
 */

namespace Lookitsatravis\Listify;

use App;
use Config;
use DB;
use Event;
use Lookitsatravis\Listify\Exceptions\InvalidScopeException;
use Lookitsatravis\Listify\Exceptions\NullForeignKeyException;
use Lookitsatravis\Listify\Exceptions\NullScopeException;

trait ScopeCondition
{

    /**
     * Returns the raw WHERE clause to be used as the Listify scope
     * @return string
     * @throws InvalidScopeException
     * @throws NullForeignKeyException
     * @throws NullScopeException
     * @throws \ReflectionException
     */
    private function scopeCondition()
    {
        $theScope = $this->scopeName();

        if ($theScope === NULL)
            throw new NullScopeException('You cannot pass in a null scope into Listify. It breaks stuff.');

        if ($theScope === $this->defaultScope)
            return $theScope;

        if (is_string($theScope)) {
            //Good for you for being brave. Let's hope it'll run in your DB! You sanitized it, right?
            $this->stringScopeValue = $theScope;
            return $theScope;
        }

        if (!is_object($theScope))
            throw new InvalidScopeException('Listify scope parameter must be a String, an Eloquent BelongsTo object, or a Query Builder object.');

        $reflector = new \ReflectionClass($theScope);
        if ($reflector->getName() == 'Illuminate\Database\Eloquent\Relations\BelongsTo') {
            $relationshipId = $this->getAttribute($theScope->getForeignKey());

            if ($relationshipId === NULL)
                throw new NullForeignKeyException('The Listify scope is a "belongsTo" relationship, but the foreign key is null.');

            $theScope = $theScope->getForeignKey() . ' = ' . $this->getAttribute($theScope->getForeignKey());
            return $theScope;
        }
        if ($reflector->getName() != 'Illuminate\Database\Query\Builder')
            throw new InvalidScopeException('Listify scope parameter must be a String, an Eloquent BelongsTo object, or a Query Builder object.');

        $theQuery = $this->getConditionStringFromQueryBuilder($theScope);
        $this->stringScopeValue = $theQuery;
        $theScope = $theQuery;
        return $theScope;
    }

}