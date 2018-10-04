<?php 

namespace biliboobrian\lumenAngularCodeGenerator\Controller;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use LushDigital\MicroServiceCore\Pagination\Paginator;
use LushDigital\MicroServiceCrud\Http\Controllers\CrudController;

class CrudExtendController extends CrudController
{



    /**
     * Get filtered list of items.
     *
     * @return Response
     */
    public function get(Request $request)
    {
        $relations = json_decode($request->input('relations'));
        $tags = null;
        $list = null;

        if ($relations) {
            $list = $this->getRelationsWith($relations);
            $tags = $this->getTagsList($relations);
        }

        $tagList = [$this->modelTableName];

        if ($tags) {
            $tagList = array_merge([$this->modelTableName], $tags);
        }

        // Check the cache for data. Otherwise get from the db.
        if ($list) {
            $query = call_user_func([$this->getModelClass(), 'with'], $list);
        } else {
            $query = call_user_func([$this->getModelClass(), 'query']);
        }


        $filters = json_decode($request->input('filters'));
        $sortColumn = $request->input('sort-column');
        $sortDirection = $request->input('sort-direction', 'asc'); // default value asc
        $perPage = (int)$request->input('per-page', 10); // default value 10

        if ($perPage > 100) {
            $perPage = 100;
        }

        if ($filters) {
            $this->createSQLFilter($filters, $query);
        }

        // sort

        if ($sortColumn) {
            $query->orderBy($sortColumn, $sortDirection);
        }

        //$page = $query->paginate($perPage);
        $b64 = base64_encode(serialize($request->all()));

        $page = Cache::tags($tagList)->rememberForever($this->modelTableName . ':search:' . $b64, function () use ($query, $perPage) {
            return $query->paginate($perPage);
        });

        return $this->generatePaginatedResponse(
            new Paginator(
                $page->total(),
                $page->perPage(),
                $page->currentPage()
            ),
            $this->modelTableName,
            $page->items()
        );
    }

    /**
     * Get a single item by it's ID.
     *
     * @param int $id
     * @return Response
     */
    public function getById(Request $request, $id)
    {
        $relations = json_decode($request->input('relations'));
        $tags = null;
        $list = null;

        if ($relations) {
            $list = $this->getRelationsWith($relations);
            $tags = $this->getTagsList($relations);
            $b64 = base64_encode(serialize($list));
        } else {
            $b64 = '';
        }
        $tagList = [$this->modelTableName];

        if ($tags) {
            $tagList = array_merge([$this->modelTableName], $tags);
        }

        // Check the cache for item data. Otherwise get from the db.
        $item = Cache::tags($tagList)->rememberForever($this->modelTableName . ':' . $id . ':' . $b64, function () use ($id, $list) {
            if ($list) {
                return call_user_func([$this->getModelClass(), 'with'], $list)->findOrFail($id)->toArray();
            } else {
                return call_user_func([$this->getModelClass(), 'findOrFail'], $id)->toArray();
            }

        });

        return $this->generateResponse($this->modelTableName, $item);
    }

    /**
     * Update the specified item.
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        // Get the item.
        $item = call_user_func([$this->getModelClass(), 'findOrFail'], $id);

        // Validate the request.
        $this->validate($request, $item->getValidationRules('update', $id));
        $itemData = $request->all();

        foreach ($itemData as $key => $value) {
            if (DB::connection()->getDoctrineColumn($item->getTable(), $key)->getType()->getName() === 'datetime') {
                $itemData[$key] = Carbon::createFromTimestampMs($value);
            }
        }

        // Update the item.
        $item->fill($itemData);
        $item->save();

        return $this->generateResponse($this->modelTableName, $item->toArray());
    }

    private function getTagsList(array $relations)
    {
        $list = array();


        foreach ($relations as $relation) {
            foreach ($relation as $key => $value) {
                if (strpos($key, '.') !== false) {
                    $key = substr($key, strpos($key, '.') + 1, strlen($key));
                }

                if (substr($key, -1, 1) === 's') {
                    $key = substr(str_replace('-', '_', ($key)), 0, -1);
                } else {
                    $key = str_replace('-', '_', ($key));
                }


                if (in_array($key, $list) === false) {
                    array_push($list, $key);
                }

                $list = array_merge($list, $this->getTagsList($value));
            }

        }
        return $list;
    }

    private function getRelationsWith(array $relations, $prefix = '')
    {
        $list = array();


        foreach ($relations as $relation) {

            foreach ($relation as $key => $value) {
                $key = lcfirst(str_replace('-', '', ucwords($key, '-')));
                if ($prefix !== '') {
                    $key = $prefix . '.' . $key;
                }
                array_push($list, $key);


                $list = array_merge($list, $this->getRelationsWith($value, $key));
            }

        }
        return $list;
    }

    private function createSQLFilter($filters, &$query)
    {

        if ($filters->relationName) {
            
            $itemRelation = lcfirst(str_replace('-', '', ucwords($filters->relationName, '-')));
            $query->whereHas($itemRelation, function ($query) use ($filters) {
                foreach ($filters->childrens as $child) {
                    $this->createSQLFilter($child, $query);
                }
            });
        } else {
        
        foreach ($filters->members as $filter) {
           
                if ($filters->andLink) {
                    if ($filter->type === 'like') {
                        $query->where($filter->column, $filter->type, '%' . $filter->value . '%');
                    } else {
                        $query->where($filter->column, $filter->type, $filter->value);
                    }
                } else {
                    if ($filter->type === 'like') {
                        $query->orWhere($filter->column, $filter->type, '%' . $filter->value . '%');
                    } else {
                        $query->orWhere($filter->column, $filter->type, $filter->value);
                    }
                }
            }
        }

        if ($filters->childrens && sizeOf($filters->childrens) > 0 && $filters->relationName === null) {
            if ($filters->andLink) {
                $query->where(function ($query) use ($filters) {
                    foreach ($filters->childrens as $child) {
                        $this->createSQLFilter($child, $query);
                    }
                });
            } else {
                $query->orWhere(function ($query) use ($filters) {
                    foreach ($filters->childrens as $child) {
                        $this->createSQLFilter($child, $query);
                    }
                });
            }
        }
    }

    /**
     * Create a new item.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        // Instantiate a new model item.
        $modelClass = $this->getModelClass();
        $item = new $modelClass;

        // Validate the request.
        $this->validate($request, $item->getValidationRules());

        // Create the new item.
        $itemData = $request->all();
        $item->fill($itemData);
        $item->save();

        return $this->getById($request, $item->getPrimaryKeyValue());
    }

    /**
     * Create a relation between existing object
     *
     * @param Request $request
     * @return Response
     */
    public function createRelation(Request $request, $id, $relation, $idRelation)
    {
        $item = call_user_func([$this->getModelClass(), 'findOrFail'], $id);
        $itemRelation = lcfirst(str_replace('-', '', ucwords($relation, '-')));
        $relationQuery = $item->{$itemRelation}();

        $relatedItem = call_user_func([get_class($relationQuery->getRelated()), 'findOrFail'], $idRelation);
        $relationQuery->save($relatedItem);

        // Update the item.
        return $this->getById($request, $item->getPrimaryKeyValue());
    }

    /**
     * Create a relation with a fresh Object
     *
     * @param Request $request
     * @return Response
     */
    public function createRelationWithObject(Request $request, $id, $relation)
    {
        $item = call_user_func([$this->getModelClass(), 'findOrFail'], $id);
        $itemRelation = lcfirst(str_replace('-', '', ucwords($relation, '-')));
        $relationQuery = $item->{$itemRelation}();

        // Instantiate a new model item.
        $modelClass = get_class($relationQuery->getRelated());
        $relatedItem = new $modelClass;

        // Validate the request.
        $this->validate($request, $relatedItem->getValidationRules());

        // Create the new item.
        $itemData = $request->all();
        $relatedItem->fill($itemData);

        $relatedItem->save();
        $relationQuery->save($relatedItem);

        return $this->getById($request, $item->getPrimaryKeyValue());
    }



    /**
     * get all relation linked to model
     *
     * @param Request $request
     * @return Response
     */
    public function getRelation(Request $request, $id, $relation)
    {
        $item = call_user_func([$this->getModelClass(), 'findOrFail'], $id);
        $tableName = substr(str_replace('-', '_', $relation), 0, -1);
        $relation = lcfirst(str_replace('-', '', ucwords($relation, '-')));
        $relations = $item->$relation;

        return $this->generateResponse($tableName, $relations->toArray());
    }
}