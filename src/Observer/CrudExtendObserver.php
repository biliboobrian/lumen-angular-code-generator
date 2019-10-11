<?php
namespace biliboobrian\lumenAngularCodeGenerator\Observer;

use Illuminate\Support\Facades\Cache;
use biliboobrian\MicroServiceModelUtils\Contracts\Cacheable;
use biliboobrian\MicroServiceModelUtils\Traits\MicroServiceCacheTrait;

/**
 * Observer class to act on CRUD item modifications.
 *
 * @package biliboobrian\lumenAngularCodeGenerator\Observer.
 */
class CrudExtendObserver
{
    use MicroServiceCacheTrait;

    /**
     * Listen to the model saved event.
     *
     * @param Cacheable $model
     * @return void
     */
    public function saved(Cacheable $model)
    {
        $this->cacheForget($model);
        Cache::tags([$model->getTable()])->flush();
    }

    /**
     * Listen to the model deleted event.
     *
     * @param Cacheable $model
     * @return void
     */
    public function deleted(Cacheable $model)
    {
        $this->cacheForget($model);
        Cache::tags([$model->getTable()])->flush();
    }
}
