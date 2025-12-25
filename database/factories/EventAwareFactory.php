<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

abstract class EventAwareFactory extends Factory
{
    protected bool $withEvents = false;

    public function withEvents(): static
    {
        $clone = clone $this;
        $clone->withEvents = true;

        return $clone;
    }

    public function create($attributes = [], ?Model $parent = null)
    {
        if ($this->withEvents) {
            return parent::create($attributes, $parent);
        }

        return Model::withoutEvents(
            fn() => parent::create($attributes, $parent)
        );
    }
}
