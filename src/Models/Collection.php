<?php

namespace LdapRecord\Models;

use Closure;
use Tightenco\Collect\Support\Arr;
use LdapRecord\Models\Attributes\DistinguishedName;
use LdapRecord\Query\Collection as QueryCollection;

class Collection extends QueryCollection
{
    /**
     * Determine if the collection contains all of the given models, or any models.
     *
     * @param mixed $models
     *
     * @return bool
     */
    public function exists($models = null)
    {
        $models = $this->getArrayableModels($models);

        if (func_num_args() >= 1 && empty(array_filter($models))) {
            return false;
        }

        if (! $models) {
            return parent::isNotEmpty();
        }

        foreach ($models as $model) {
            $exists = parent::contains(function (Model $related) use ($model) {
                return $this->compareModelWithRelated($model, $related);
            });

            if (! $exists) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if any of the given models are contained in the collection.
     *
     * @param mixed $models
     * @param mixed $operator
     * @param mixed $value
     *
     * @return bool
     */
    public function contains($models, $operator = null, $value = null)
    {
        if (func_num_args() > 1 || $models instanceof Closure) {
            // If we are supplied with more than one argument, or
            // we were passed a closure, we will utilize the
            // parents contains method, for compatibility.
            return parent::contains($models, $operator, $value);
        }

        foreach ($this->getArrayableModels($models) as $model) {
            $exists = parent::contains(function (Model $related) use ($model) {
                return $this->compareModelWithRelated($model, $related);
            });

            if ($exists) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the provided models as an array.
     *
     * @param mixed $models
     *
     * @return array
     */
    protected function getArrayableModels($models = null)
    {
        return $models instanceof QueryCollection
            ? $models->toArray()
            : Arr::wrap($models);
    }

    /**
     * Compare the related model with the given.
     *
     * @param Model|string $model
     * @param Model        $related
     *
     * @return bool
     */
    protected function compareModelWithRelated($model, $related)
    {
        if (is_string($model)) {
            return $this->isValidDn($model)
                ? $related->getDn() == $model
                : $related->getName() == $model;
        }

        return $related->is($model);
    }

    /**
     * Determine if the given string is a valid distinguished name.
     *
     * @param string $dn
     *
     * @return bool
     */
    protected function isValidDn($dn)
    {
        return ! empty((new DistinguishedName($dn))->components());
    }
}