<?php

namespace Foostart\Company\Models;

use Foostart\Acl\Authentication\Models\User;
use Foostart\Category\Library\Models\FooModel;
use Foostart\Category\Models\Category;
use Illuminate\Database\Eloquent\Model;
use Foostart\Comment\Models\Comment;

class Company extends FooModel
{

    /**
     * @table categories
     * @param array $attributes
     */
    public $user = NULL;
    public function __construct(array $attributes = array())
    {
        //set configurations
        $this->setConfigs();

        parent::__construct($attributes);
    }

    public function setConfigs()
    {

        //table name
        $this->table = 'company';

        //list of field in table
        $this->fillable = [
            'company_name',
            'company_slug',
            // 'category_id',
            'slideshow_id',
            'user_id',
            'user_full_name',
            'user_email',
            'company_overview',
            'company_description',
            'company_image',
            'company_files',
            'company_status',
        ];

        //list of fields for inserting
        $this->fields = [
            'company_name' => [
                'name' => 'company_name',
                'type' => 'Text',
            ],
            'company_slug' => [
                'name' => 'company_slug',
                'type' => 'Text',
            ],
            // 'category_id' => [
            //     'name' => 'category_id',
            //     'type' => 'Array',
            // ],
            'slideshow_id' => [
                'name' => 'slideshow_id',
                'type' => 'Int',
            ],
            'user_id' => [
                'name' => 'user_id',
                'type' => 'Int',
            ],
            'user_full_name' => [
                'name' => 'user_full_name',
                'type' => 'Text',
            ],
            'user_email' => [
                'name' => 'email',
                'type' => 'Text',
            ],
            'company_overview' => [
                'name' => 'company_overview',
                'type' => 'Text',
            ],
            'company_description' => [
                'name' => 'company_description',
                'type' => 'Text',
            ],
            'company_image' => [
                'name' => 'company_image',
                'type' => 'Text',
            ],
            'company_files' => [
                'name' => 'files',
                'type' => 'Json',
            ],
            'company_status' => [
                'name' => 'company_status',
                'type' => 'Int',
            ],
        ];

        //check valid fields for inserting
        $this->valid_insert_fields = [
            'company_name',
            'company_slug',
            'user_id',
            // 'category_id',
            'slideshow_id',
            'user_full_name',
            'updated_at',
            'company_overview',
            'company_description',
            'company_image',
            'company_files',
            'company_status',
        ];

        //check valid fields for ordering
        $this->valid_ordering_fields = [
            'company_name',
            'updated_at',
            $this->field_status,
        ];
        //check valid fields for filter
        $this->valid_filter_fields = [
            'keyword',
            'status',
            'category',
            '_id',
            'limit',
            'company_id!',
            // 'category_id',
            'user_id',
        ];

        //primary key
        $this->primaryKey = 'company_id';

        //the number of items on page
        $this->perPage = 10;

        //item status
        $this->field_status = 'company_status';
    }

    /**
     * Gest list of items
     * @param type $params
     * @return object list of categories
     */
    public function selectItems($params = array())
    {

        //join to another tables
        $elo = $this->joinTable();

        //search filters
        $elo = $this->searchFilters($params, $elo);

        //select fields
        $elo = $this->createSelect($elo);

        //order filters
        $elo = $this->orderingFilters($params, $elo);

        //paginate items
        if ($this->is_pagination) {
            $items = $this->paginateItems($params, $elo);
        } else {
            $items = $elo->get();
        }

        return $items;
    }

    /**
     * Get a company by {id}
     * @param ARRAY $params list of parameters
     * @return OBJECT company
     */
    public function selectItem($params = array(), $key = NULL)
    {
        if (empty($key)) {
            $key = $this->primaryKey;
        }

        //join to another tables
        $elo = $this->joinTable();

        //search filters
        $elo = $this->searchFilters($params, $elo, FALSE);


        //select fields
        $elo = $this->createSelect($elo);

        //id
        $elo = $elo->where($this->primaryKey, $params['id']);

        //first item
        $item = $elo->first();

        return $item;
    }


    public function getComments($company_id)
    {

        // Get company
        $params = array(
            'id' => $company_id,
        );
        $company = $this->selectItem($params);

        // Get comment by context
        $params = array(
            'context_name' => 'company',
            'context_id' => $company_id,
            'by_status' => true,
        );
        $obj_comment = new Comment();
        $obj_comment->user = $this->user;
        $comments = $obj_comment->selectItems($params);

        $users_comments = $obj_comment->mapCommentArray($comments);
        $company->cache_comments = json_encode($users_comments);
        $company->cache_time = time();
        $company->save();

        return $users_comments;
    }



    /**
     *
     * @param ARRAY $params list of parameters
     * @return ELOQUENT OBJECT
     */
    protected function joinTable(array $params = [])
    {
        return $this;
    }

    /**
     *
     * @param ARRAY $params list of parameters
     * @return ELOQUENT OBJECT
     */
    protected function searchFilters(array $params = [], $elo, $by_status = TRUE)
    {
        //filter
        if ($this->isValidFilters($params) && (!empty($params))) {

            foreach ($params as $column => $value) {

                // dd($elo);
                // dd($this->category_id()->get());
                // dd($elo->where($this->table . '.category_id', '=', $params['category']));

                if ($this->isValidValue($value)) {
                    switch ($column) {
                        case 'category_id':
                            if (!empty($value)) {
                                $elo = Category::find($params['category'])->companies();
                            }
                            break;
                        case 'category':
                            if (!empty($value)) {
                                $elo = Category::find($params['category'])->companies();
                            }
                            break;
                        case 'user_id':
                            if (!empty($value)) {
                                $elo = $elo->where($this->table . '.user_id', '=', $value);
                            }
                            break;
                        case 'limit':
                            if (!empty($value)) {
                                $this->perPage = $value;
                                $elo = $elo->limit($value);
                            }
                            break;
                        case '_id':
                            if (!empty($value)) {
                                $elo = $elo->where($this->table . '.company_id', '!=', $value);
                            }
                            break;
                        case 'status':
                            if (!empty($value)) {
                                $elo = $elo->where($this->table . '.' . $this->field_status, '=', $value);
                            }
                            break;
                        case 'keyword':
                            if (!empty($value)) {
                                $elo = $elo->where(function ($elo) use ($value) {
                                    $elo->where($this->table . '.company_name', 'LIKE', "%{$value}%")
                                        ->orWhere($this->table . '.company_description', 'LIKE', "%{$value}%")
                                        ->orWhere($this->table . '.company_overview', 'LIKE', "%{$value}%");
                                });
                            }
                            break;
                        default:
                            break;
                    }
                }
            }
        } elseif ($by_status) {

            $elo = $elo->where($this->table . '.' . $this->field_status, '=', $this->config_status['publish']);
        }

        return $elo;
    }

    /**
     * Select list of columns in table
     * @param ELOQUENT OBJECT
     * @return ELOQUENT OBJECT
     */
    public function createSelect($elo)
    {

        // for ($i = 0; $i < count($elo); $i++) {
        //     $elo[$i] = $elo[$i]->select(
        //         $this->table . '.*',
        //         $this->table . '.company_id as id'
        //     );
        // }

        // dd($elo);

        $elo = $elo->select(
            $this->table . '.*',
            $this->table . '.company_id as id'
        );

        return $elo;
    }

    /**
     *
     * @param ARRAY $params list of parameters
     * @return ELOQUENT OBJECT
     */
    public function paginateItems(array $params = [], $elo)
    {
        $items = $elo->paginate($this->perPage);

        return $items;
    }

    /**
     *
     * @param ARRAY $params list of parameters
     * @param INT $id is primary key
     * @return type
     */
    public function updateItem($params = [], $id = NULL)
    {

        if (empty($id)) {
            $id = $params['id'];
        }
        $field_status = $this->field_status;

        //get company item by conditions
        $_params = [
            'id' => $id,
        ];

        $company = $this->selectItem($_params);

        $trainer = new Trainer();

        // dd($params['trainer']);
        $category_id = "category_id";

        if (!empty($company)) {

            $category_id = "category_id";
            $company->$category_id()->detach();

            if (!empty($params[$category_id])) {
                $company->$category_id()->attach($params[$category_id]);
            }

            $dataFields = $this->getDataFields($params, $this->fields);

            foreach ($dataFields as $key => $value) {
                $company->$key = $value;
            }

            $company->save();

            return $company;
        } else {
            return NULL;
        }
    }

    /**
     *
     * @param ARRAY $params list of parameters
     * @return OBJECT company
     */
    public function insertItem($params = [])
    {

        $dataFields = $this->getDataFields($params, $this->fields);

        $dataFields[$this->field_status] = $this->config_status['publish'];

        $trainer = new Trainer();

        $trainers = User::find($params['trainer']);

        $item = self::create($dataFields);

        !isset($params["category_id"]) ?: $item->category_id()->attach($params["category_id"]);


        if (count($trainer->selectItems($params['trainer']))) {
            foreach ($trainers as $key => $value) {
                $_params = [
                    "user_id" => $value['id'],
                    "company_id" => $item['company_id'],
                ];
                $trainer->insertItem($_params);
            }
        }

        dd($trainer->selectItems($params['trainer']), 1);

        $key = $this->primaryKey;
        $item->id = $item->$key;

        return $item;
    }

    public function getArray($params, $key)
    {
        $value = NULL;

        if (isset($params[$key])) {
            $value = $params[$key];
        }

        return $value;
    }

    public function category_id()
    {
        return $this->belongsToMany('Foostart\Category\Models\Category', 'company_category', 'category_id', "company_id");
    }

    public function trainer()
    {
        return $this->belongsTo(Trainer::class, 'company_id', 'company_id');
    }

    /**
     *
     * @param ARRAY $input list of parameters
     * @return boolean TRUE incase delete successfully otherwise return FALSE
     */
    public function deleteItem($input = [], $delete_type)
    {

        $item = $this->find($input['id']);
        $category_id = "category_id";

        if ($item) {
            switch ($delete_type) {
                case 'delete-trash':
                    return $item->fdelete($item);
                    break;
                case 'delete-forever':
                    $item->$category_id()->detach();
                    return $item->delete();
                    break;
            }
        }

        return false;
    }

    public function getCoursesByCategoriesRoot($categories)
    {
        $this->is_pagination = false;

        if (!empty($categories)) {

            //get courses of category root
            $_params = [
                'limit' => 9,
                'category' => $categories->category_id,
                'is_pagination' => false
            ];
            $categories->courses = $this->selectItems($_params);

            //get courses of category childs
            foreach ($categories->childs as $key => $category) {
                $ids = [$category->category_id => 1];
                if (!empty($category->category_id_child_str)) {
                    $ids += (array)json_decode($category->category_id_child_str);;
                }
                $ids = array_keys($ids);

                //error
                $_temp = $categories->childs[$key];
                $_temp->courses = $this->getCouresByCategoryIds($ids);
            }
        }
        return $categories;
    }

    public function getCouresByCategoryIds($ids)
    {

        $courses = self::whereIn('category_id', $ids)
            ->paginate($this->perPage);
        return $courses;
    }


    public function getItemsByCategories($categories)
    {

        $items = [];
        $ids = [];


        foreach ($categories as $category) {
            $ids += [$category->category_id => 1];

            if (!empty($category->category_id_child_str)) {
                $ids += (array) json_decode($category->category_id_child_str);
            }
        }

        //Get list of items by ids
        $items = $this->getCouresByCategoryIds(array_keys($ids));

        return $items;
    }
}
