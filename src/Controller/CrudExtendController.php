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
        $sort = json_decode($request->input('sort'));
        $sortColumn = $request->input('sort-column');
        $sortDirection = $request->input('sort-direction', 'asc'); // default value asc
        $perPage = (int)$request->input('per-page', 10); // default value 10
        $currentPage = (int)$request->input('current-page', 1); // default value 1

        if ($perPage > 100) {
            $perPage = 100;
        }

        if ($filters) {
            $this->createSQLFilter($filters, $query);
        }

        // sort

        if ($sort) {
            $this->createSorting($query, $sort);
        }

        //$page = $query->paginate($perPage);
        $b64 = base64_encode(serialize($request->all()));
        $offset = ($currentPage - 1) * $perPage;

        $page = Cache::tags($tagList)->rememberForever($this->modelTableName . ':search:' . $b64, function () use ($query, $perPage, $offset) {
            return array(
                'count' => $query->count(),
                'result' => $query->skip($offset)->take($perPage)->get()
            );
        });

        return $this->generatePaginatedResponse(
            new Paginator(
                $page['count'],
                $perPage,
                $currentPage
            ),
            $this->modelTableName,
            $page['result']->toArray()
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
        $item = Cache::tags($tagList)->rememberForever($this->modelTableName . ':' . $id . ':' . $b64, function () use ($id, $list, $relations) {
            if ($list) {
                $obj = call_user_func([$this->getModelClass(), 'with'], $list)->findOrFail($id);
                $obj = $this->sortcollections($obj, $relations);
                $returnObj = $this->getSotedRelationship($obj, $relations);
                
                return $returnObj;
            } else {
                return call_user_func([$this->getModelClass(), 'findOrFail'], $id)->toArray();
            }
        });

        return $this->generateResponse($this->modelTableName, $item);
    }

    public function getSotedRelationship($obj, $relations) {
        $returnObj = $obj->toArray();
        
        foreach ($relations as $relation) {
            $relationship = str_replace('-', '_', $relation->table);

            if(isset($obj->$relationship)) {
                $returnObj[$relationship] = $this->getSotedRelationship($obj->$relationship, $relation->relations);
            }
        }

        return $returnObj;
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
            if (array_search($key, $item->getDates()) !== false) {
                $itemData[$key] = Carbon::createFromTimestampMs($value);
            }
        }

        // Update the item.
        $item->fill($itemData);
        $item->save();

        return $this->generateResponse($this->modelTableName, $item->toArray());
    }

    private function sortcollections($obj, array $relations)
    {
        foreach ($relations as $relation) {
            $table = str_replace('-', '_', $relation->table);
            $getTable = lcfirst(str_replace('-', '', ucwords($relation->table, '-')));
            
            if(isset($relation->sortColumn)) {
                if(isset($relation->sortDesc) && $relation->sortDesc === true) {
                    $obj->$table = $obj->$getTable->sortByDesc($relation->sortColumn)->values();
                } else {
                    $obj->$table = $obj->$getTable->sortBy($relation->sortColumn)->values();
                }
            }
            if (property_exists($obj, $table)) { 
                $obj->$table = $this->sortcollections($obj->$getTable, $relation->relations);
            } 
        }

        return $obj;
    }

    private function getTagsList(array $relations)
    {
        $list = array();

        foreach ($relations as $relation) {
            $key = $relation->table;

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

            $list = array_merge($list, $this->getTagsList($relation->relations));
        }

        return $list;
    }

    private function getRelationsWith(array $relations, $prefix = '')
    {
        $list = array();

        foreach ($relations as $relation) {
            $table = lcfirst(str_replace('-', '', ucwords($relation->table, '-')));
            if ($prefix !== '') {
                $table = $prefix . '.' . $table;
            }
            array_push($list, $table);

            $list = array_merge($list, $this->getRelationsWith($relation->relations, $table));
        }
        
        return $list;
    }

    private function createSorting($query, $sort) {
        if(property_exists($sort, 'joinTable')) {
            $query->join($sort->joinTable, $this->modelTableName .'.'. $sort->tableKey, '=', $sort->joinTable .'.'. $sort->joinKey);
        } 
        if(property_exists($sort, 'sortColumn')) {
            $query->orderBy($sort->sortColumn, $sort->sortDirection);
        }
    }

    private function createSQLFilter($filters, &$query)
    {

        if ($filters->relationName) {
            
            $itemRelation = lcfirst(str_replace('-', '', ucwords($filters->relationName, '-')));
            $query->whereHas($itemRelation, function ($query) use ($filters) { 
                $this->applyFilterMembers($filters->members, $filters->andLink, $query);
            });
        } else {
            $this->applyFilterMembers($filters->members, $filters->andLink, $query);  
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

    private function applyFilterMembers($filters, $andLink, &$query) {
        foreach ($filters as $filter) {
           
            if ($andLink) {
                if ($filter->type === 'like') {
                    if(strpos($filter->value, '*') === 0) {
                        $query->where($filter->column, $filter->type, '%' . substr($filter->value, 1) . '%');
                    } else {
                        $query->where($filter->column, $filter->type,  $filter->value . '%');
                    }
                    
                } else {
                    $query->where($filter->column, $filter->type, $filter->value);
                }
            } else {
                if ($filter->type === 'like') {
                    if(strpos($filter->value, '*') === 0) {
                        $query->orWhere($filter->column, $filter->type, '%' . substr($filter->value, 1) . '%');
                    } else {
                        $query->orWhere($filter->column, $filter->type, $filter->value . '%');
                    }
                } else {
                    $query->orWhere($filter->column, $filter->type, $filter->value);
                }
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
        
        foreach ($itemData as $key => $value) {
            if (array_search($key, $item->getDates()) !== false) {
                $itemData[$key] = Carbon::createFromTimestampMs($value);
            }
        }

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
     * Delete a relation between existing object
     *
     * @param Request $request
     * @return Response
     */
    public function deleteRelation(Request $request, $id, $relation, $idRelation)
    {
        $item = call_user_func([$this->getModelClass(), 'findOrFail'], $id);

        $itemRelation = lcfirst(str_replace('-', '', ucwords($relation, '-')));
        $relationQuery = $item->{$itemRelation}();

        $relatedItem = call_user_func([get_class($relationQuery->getRelated()), 'findOrFail'], $idRelation);
        $relationQuery->detach($relatedItem);

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