<?php 

namespace biliboobrian\lumenAngularCodeGenerator\Controller;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use LushDigital\MicroServiceCore\Pagination\Paginator;
use LushDigital\MicroServiceCrud\Http\Controllers\CrudController;

class CrudExtendController extends CrudController {

    

    /**
     * Get filtered list of items.
     *
     * @return Response
     */
    public function get(Request $request)
    {
        // Check the cache for data. Otherwise get from the db.
        $query = call_user_func([$this->getModelClass(), 'query']);
        
        $filters = $request->input('filters');
        $sortColumn = $request->input('sort-column');
        $sortDirection = $request->input('sort-direction', 'asc'); // default value asc
        $perPage = (int)$request->input('per-page', 10); // default value 10

        if($perPage > 50) {
            $perPage = 50;
        }

        if ($filters) {

            $query->where(function ($query) use ($filters) {

                forEach ($filters as $filter) {
                    $column = strstr($filter, '=', true); // left hand side of =
                    $value = substr(strstr($filter, '='), 1); // right hand side of =
                    $value = strtolower(trim($value));

                    if (strpos($column, '.') !== false) {
                        // There is no filter on relationships currently for address

                    } else {
                        $query->orWhereRaw('LOWER('.$column.') LIKE ?', array('%'.strtolower($value).'%'));
                    }
                }

            });
        }

        // sort

        if ($sortColumn) {
            if (strpos($sortColumn, '.') !== false) {
                $relationship = strstr($sortColumn, '.', true); // left hand side of .
                $relationshipColumn = substr(strstr($sortColumn, '.'), 1); // right hand side of .

                // There is no sort on relationships currently for initiator

            } else {
                $query->orderBy($sortColumn, $sortDirection);
            }
        }

        //$page = $query->paginate($perPage);
        $b64 = base64_encode(serialize ($request->all()));
        
        $page = Cache::tags([$this->modelTableName])->rememberForever($this->modelTableName . ':search:' . $b64, function () use ($query, $perPage) {
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

        $newItem = call_user_func([$this->getModelClass(), 'find'], $item->getPrimaryKeyValue())->toArray();
        return $this->generateResponse($this->modelTableName, $newItem);
    }

    
    
    /**
     * Create a new item.
     *
     * @param Request $request
     * @return Response
     */
    public function getRelation(Request $request, $id, $relation)
    {
        $item = call_user_func([$this->getModelClass(), 'findOrFail'], $id);
        $relation = lcfirst(str_replace('-', '', ucwords($relation, '-')));
        $relations = $item->$relation;
        



        return $this->generateResponse($relation , $relations->toArray());
    }
}