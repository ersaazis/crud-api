<?php

namespace ersaazis\crudapi\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Pluralizer;
use Illuminate\Support\Str;

class ApiCrudMaker extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:api';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:api
                            {--t|table= : [all | table number] }
                            {--p|path-models=App\Models : Namespace to Models (Directories will be created) }
                            {--r|routes=Y : [Y | N] }
                            {--m|postman=N : [Y | N] }
                            {--b|base-model=N : [Y | N] }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a Webservice REST - API CRUD';

    /**
     * The path of Models
     *
     * @var string
     */
    private $pathModels = 'App\\Models';

    /**
     * [$routes description]
     * @var boolean
     */
    private $routes = true;

        /**
     * [$postman description]
     * @var boolean
     */
    private $postman = true;

    /**
     * [$tables description]
     *
     * @var [type]
     */
    private $tables = [];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * [processRoutes description]
     * @return [type] [description]
     */
    public function processRoutes()
    {
        if ($this->routes) {

            $template = $this->getTemplate('routes');
            $routes = '';


            if (trim($this->option('table')) === 'all') {

                foreach ($this->tables as $table) {
                    $m = [
                        'plural_uc' => ucwords($table->plural),
                        'plural' => $table->plural,
                        'kebab_plural' => str_replace('data-','',Str::kebab($table->plural)),
                    ];
                    $temp = $template;

                    foreach ($this->marks()['routes'] as $mark){
                        $temp = str_replace('{{{' . $mark . '}}}', trim($m[$mark]), $temp);
                    }

                    $routes .= $temp;
                }


            } elseif (trim($this->option('table')) !== '') {

                $tableKey = $this->option('table');

                $table = $this->tables[$tableKey];

                $m = [
                    'plural_uc' => ucwords($table->plural),
                    'plural' => $table->plural,
                    'kebab_plural' => str_replace('data-','',Str::kebab($table->plural)),
                ];

                $temp = $template;

                foreach ($this->marks()['routes'] as $mark){
                    $temp = str_replace('{{{' . $mark . '}}}', trim($m[$mark]), $temp);
                }

                $routes = $temp;

            }

            $fileWeb = fopen(base_path() . '/routes/web.php', 'a+');
            fwrite($fileWeb, $routes);
            fclose($fileWeb);
        }
    }

    /**
     * [processOptionRoutes description]
     * @return [type] [description]
     */
    public function processOptionPostman()
    {
        $this->alert('POSTMAN PROCESS');

        // Verify option TABLE
        if (in_array(strtoupper(trim($this->option('postman'))), ['N','NO','FALSE'])) {
            $this->postman = false;
        }

    }

    /**
     * [processOptionRoutes description]
     * @return [type] [description]
     */
    public function processOptionRoutes()
    {
        $this->alert('ROUTES PROCESS');

        // Verify option TABLE
        if (in_array(strtoupper(trim($this->option('routes'))), ['N','NO','FALSE'])) {
            $this->routes = false;
        }

    }

    /**
     * [processOptionPathModels description]
     * @return [type] [description]
     */
    public function processOptionPathModels()
    {
        $this->alert('PATH MODELS PROCESS');

        // Verify option TABLE
        if (trim($this->option('path-models')) !== '') {
            $this->pathModels = Str::finish($this->option('path-models'), '\\');
        }

    }

    /**
     * [verifyOptionTable description]
     *
     * @return [type] [description]
     */
    public function processOptionTable()
    {
        $this->alert('TABLE PROCESS');

        // Tables
        $tables = DB::select('SHOW TABLES');

        foreach ($tables as $t) {
            if (in_array($t->{'Tables_in_' . env('DB_DATABASE')}, ['migrations', 'password_resets'])) {
                continue;
            }

            // Make the table object
            $objTab = new \stdClass();
            $objTab->name = $t->{'Tables_in_' . env('DB_DATABASE')};
            $objTab->relationTable = false;
            $objTab->singular = Str::camel($this->singular($objTab->name));
            $objTab->plural = Str::camel($this->plural($objTab->name));
            $objTab->snakeSingular = Str::snake($objTab->singular);
            $objTab->snakePlural = Str::snake($objTab->plural);
            $objTab->fieldDisplay = false;
            $objTab->fk = $objTab->snakeSingular . '_id';
            $objTab->fields = [];
            $objTab->belongsTo = [];
            $objTab->hasMany = [];
            $objTab->hasOne = [];
            $objTab->belongsToMany = [];
            $objTab->marks = [];
            $objTab->arqs = [];


            array_push($this->tables, $objTab);
        }

        // Register belongsToMany
        foreach ($this->tables as $table) {
            // $tabs = explode('_', $table->name);
            $tabs = explode('_', str_replace("table_",'',$table->name));

            if (count($tabs) === 2) {
                $tab1 = $this->plural($tabs[0]);
                $tab2 = $this->plural($tabs[1]);
                $rel1 = false;
                $rel2 = false;

                foreach ($this->tables as $t) {
                    if ($t->name == $tab1) {
                        $rel1 = true;
                    }

                    if ($t->name == $tab2) {
                        $rel2 = true;
                    }
                }

                if ($rel1 && $rel2) {
                    foreach ($this->tables as $t) {
                        if ($t->name == $tab1) {
                            $t->belongsToMany[$table->name] = $tab2;
                        }

                        if ($t->name == $tab2) {
                            $t->belongsToMany[$table->name] = $tab1;
                        }
                    }

                    $table->relationTable = true;
                }


            }

        }

        // dump($this->tables);


        // Verify option TABLE
        if (trim($this->option('table')) === '') {

            $this->alert('TABLES');
            foreach ($this->tables as $tableKey => $table) {
                $this->info($tableKey . '->' . $table->name);
            }
            die;

        } else {
            foreach ($this->tables as $tableKey => $table) {
                $this->readTable($tableKey);
            }
        }

        foreach ($this->tables as $table) {
            // Register hasMany and hasOne
            foreach ($this->tables as $t) {
                if ($table->name === $t->name || $t->relationTable === true) {
                    continue;
                }

                if (in_array($table->name, $t->belongsTo)) {
                    foreach ($t->fields as $f) {
                        if ($f->name === $table->fk) {
                            array_push($table->{$f->unique ? 'hasOne' : 'hasMany'}, $t->name);
                        }
                    }
                }
            }


            // Process Marks
            $table->marks = $this->processMarks($table);
        }



        // DUMPS
        // if (trim($this->option('table')) === 'all') {
        //     dd($this->tables);

        // } elseif (trim($this->option('table')) !== '') {
        //     $tableKey = $this->option('table');
        //     dd($this->tables[$tableKey]);
        // }


    }

    /**
     * [readField description]
     *
     * @return [type] [description]
     */
    public function readField($field, $table)
    {
        $objField = new \stdClass();

        //$this->alert($field->Field);
        preg_match('/[a-zA-Z]+/', $field->Type, $type);
        preg_match('/[0-9]+/', $field->Type, $size);
        preg_match('/([a-zA-Z_0-9]+)_id/', $field->Field, $fk2);

        $types = null;
        if ($type[0] === 'enum') {
            // dump($field->Type);
            preg_match('/\([\'0-9,a-zA-Z]+\)/', $field->Type, $types);
            $types = str_replace(['(', "'",')'], '', $types[0]);
        }

        $objField->name = $field->Field;
        $objField->type = $type[0];
        $objField->inTypes = $types;
        $objField->size = isset($size[0]) ? $size[0] : null;
        $objField->unsigned = strpos($field->Type, 'unsigned') !== false ? true : false;
        $objField->required = $field->Null === 'NO' ? true : false;
        $objField->pk = $field->Key === 'PRI' ? true : false;
        $objField->fk = empty($fk) ? (empty($fk2) ? false : $this->plural($fk2[1])) : $this->plural($fk[1]);
        $objField->display = false;
        $objField->unique = $field->Key === 'UNI' ? true : false;
        $objField->default = $field->Default;
        $objField->autoIncrement = strpos($field->Extra, 'auto_increment') !== false ? true : false;
        $objField->validator = $this->generateValidator($objField, $table);


        $displays = [
                        'nama',
                        $table->snakeSingular . '_nama',
                        'nama_' . $table->snakeSingular,
                        'name',
                        $table->snakeSingular . '_name',
                        'name_' . $table->snakeSingular,
                        'judul',
                        $table->snakeSingular . '_judul',
                        'judul_' . $table->snakeSingular,
                        'title',
                        $table->snakeSingular . '_title',
                        'title_' . $table->snakeSingular,
                        'username',
                        'user',
                        'login',
                        'email'
                    ];
        if (!$table->fieldDisplay && in_array($objField->name, $displays)) {
            $table->fieldDisplay = true;
            $objField->display = true;
        }


        return $objField;
    }

    /**
     * [readTable description]
     *
     * @return [type] [description]
     */
    public function readTable(int $tableKey)
    {
        $table = $this->tables[$tableKey];

        // process table
        $this->warn('TABLE ' . $table->name . ':');

        $fields = DB::select('DESC ' . $table->name);

        // dump($fields);

        //dump($fields);
        foreach ($fields as $f) {
            $objField = $this->readField($f, $table);

            array_push($table->fields, $objField);

            // Register BelongsTo
            if ($objField->fk) {
                array_push($table->belongsTo, $objField->fk);
            }
        }


    }

    /**
     * [generateValidator description]
     * @return [type] [description]
     */
    public function generateValidator($objField, $table)
    {

        // Get Field Type
        $funcType = function () use ($objField) {

            switch ($objField->type) {
                case 'int':
                case 'bigint':
                    $type = 'integer';
                    break;

                case 'char':
                case 'varchar':
                case 'text':
                case 'enum':
                    $type = 'string';
                    break;

                default:
                    $type = $objField->type;
            }

            return $type;
        };

        // Get PK name
        foreach ($table->fields as $f) {
            if ($f->pk) {
                $pk = $f->name;
            }
        }

        $validator = $funcType();
        $validator .= $objField->type === 'enum' ? '|in:' . $objField->inTypes : '';
        $validator .= $objField->size && in_array($objField->type, ['char','varchar','text'])
                        ? '|max:' . $objField->size
                        : '';
        $validator .= strpos($objField->name, 'email') !== false ? '|email' : '';
        $validator .= $objField->unique ? '|unique:' . $table->name . ',' . $pk : '';
        $validator .= $objField->required ? '|required' : '';

        return $validator;

    }

    /**
     * getTemplate
     *
     * @param  [type] $type [description]
     * @return string      [description]
     */
    public function getTemplate($type)
    {
        $template = file_get_contents(__DIR__ . '/stubs/' . $type . '.stub');

        if ($template === false) {
            $this->error('CRUD Template [' . $type  . '] not found.');
            die;
        }

       return $template;
    }

    public function getPkDisplay($objTable)
    {
        $pk = null;
        $display = null;

        // Find PK
        foreach ($objTable->fields as $f) {
            if (!$f->pk) {
                continue;
            }

            $pk = $f->name;
        }

        // Find Display
        foreach ($objTable->fields as $f) {
            if (!$f->display) {
                continue;
            }

            $display = $f->name;
        }

        // Verify PK or DISPLAY nulls
        $pk = $pk === null ? ($display === null ? $objTable->fields[0]->name : $display) : $pk;
        $display = $display === null ? $pk : $display;

        return [$pk, $display];
    }


    /**
     * [processMarks description]
     * @param  [type] $objTable [description]
     * @return [type]           [description]
     */
    public function processMarks($objTable)
    {

        // USES
        $prepareUses = function () use ($objTable) {
            $uses = 'use ' . $this->pathModels . ucwords($objTable->singular) . ";\n";
            foreach ($objTable->belongsTo as $b) {
                $uses .= 'use ' . $this->pathModels . ucwords($this->singular($b)) . ";\n";
            }

            return $uses;
        };

        // VALIDATORS
        $prepareValidators = function () use ($objTable) {
            $validators = '';
            foreach ($objTable->fields as $f) {
                if (in_array($f->name, ['id', 'created_at', 'updated_at', 'deleted_at', 'remember_token'])){
                    continue;
                }

                if (empty($validators)) {
                    $validators = "'".$f->name."' => '" . $f->validator . "',\n";
                } else {
                    $validators .= "                '".$f->name."' => '" . $f->validator . "',\n";
                }
            }

            return $validators;
        };

        // PLUCKS
        $preparePlucks = function () use ($objTable) {
            $plucks = '';
            foreach ($objTable->belongsTo as $b) {
                foreach ($this->tables as $t) {
                    if ($b !== $t->name) {
                        continue;
                    }

                    list($pk, $display) = $this->getPkDisplay($t);

                    if (empty($plucks)) {
                        $plucks = "'" . $t->plural . "' => "
                                . ucwords($t->singular) . "::pluck('" . $display . "', '" . $pk . "')"
                                . ",\n";
                    } else {
                        $plucks .= "                '" . $t->plural . "' => "
                                . ucwords($t->singular) . "::pluck('" . $display . "', '" . $pk . "')"
                                . ",\n";
                    }
                }
            }

            return $plucks;
        };

        // PRIMARY KEY
        $preparePrimaryKey = function () use ($objTable) {
            $pk = 'id';
            $incrementing = true;

            foreach ($objTable->fields as $f) {
                if ($f->pk) {
                    $pk = $f->name;
                    $incrementing = $f->autoIncrement ? 'true' : 'false';
                    break;
                }
            }

            return [$pk, $incrementing];
        };
        list($primary, $incrementing) = $preparePrimaryKey();

        $prepareSoftDeletes = function () use ($objTable) {
            $softDeletes = [null, null];

            foreach ($objTable->fields as $f) {
                if (strtolower($f->name) === 'deleted_at') {
                    $softDeletes = [
                        'use Illuminate\Database\Eloquent\SoftDeletes;',
                        'use SoftDeletes;',
                    ];
                    break;
                }
            }

            return $softDeletes;
        };
        list($useSoftDeletes, $traitSoftDeletes) = $prepareSoftDeletes();

        // FILLABLES
        $prepareFillable = function () use ($objTable) {
            $fillable = null;

            foreach ($objTable->fields as $f) {
                if (in_array(strtolower($f->name), ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                    continue;
                }

                $fillable .= "'" . $f->name . "', ";
            }

            return $fillable;
        };

        // WITH
        $prepareWith = function () use ($objTable) {
            $with = null;

            foreach ($objTable->belongsTo as $b) {
                $with .= "'" . $this->singular($b) . "', ";
            }

            return $with;
        };

        // DATES
        $prepareDates = function () use ($objTable) {
            $dates = null;

            foreach ($objTable->fields as $f) {
                if (in_array($f->name, ['created_at', 'updated_at'])) {
                    continue;
                }

                $dates .= in_array(strtolower($f->type), ['date', 'datetime', 'timestamp'])
                            ? "'" . $f->name . "', "
                            : '';
            }

            return $dates;
        };

        // SUBTEMPLATES
        $prepareSubTemplates = function ($type) use ($objTable) {
            $subTemp = null;

            $prepPrimary = function ($table) {
                foreach ($table->fields as $f){
                    if ($f->pk) {
                        return $f->name;
                    }
                }

                return 'id';
            };

            $attr = [];
            switch ($type) {
                case 'belongs':
                    $attr = $objTable->belongsTo;
                    break;

                case 'many':
                    $attr = $objTable->hasMany;
                    break;

                case 'one':
                    $attr = $objTable->hasOne;
                    break;

                case 'belongsMany':
                case 'syncRelationships':
                    $attr = $objTable->belongsToMany;
                    break;

                case 'relationships':
                    $attr = array_merge($objTable->hasMany, $objTable->belongsToMany);

            }


            foreach ($attr as $key => $item) {
                foreach ($this->tables as $t) {
                    if ($t->name !== $item) {
                        continue;
                    }

                    $m = [
                        'plural' => $t->plural,
                        'plural_uc' => ucwords($t->plural),
                        'kebab_plural' => Str::kebab($t->plural),
                        'singular_uc' => ucwords($t->singular),
                        'singular' => $t->singular,
                        'use_model' => $this->pathModels . ucwords($t->singular),
                        'primary_model' => $prepPrimary($t),
                        'fk_model' => $objTable->fk,
                        'relation_table' => $key,
                    ];

                    $temp = $this->getTemplate($type);

                    foreach ($this->marks()[$type] as $mark){
                        $temp = str_replace('{{{' . $mark . '}}}', trim($m[$mark]), $temp);
                    }

                    $subTemp .= $temp;
                }

            }

            return $subTemp;
        };



        // MARKS TO REPLACE
        $marks = [
            // GERAL
            'table_name' => $objTable->name,
            'plural_uc' => ucwords($objTable->plural),
            'plural' => $objTable->plural,
            'kebab_plural' => Str::kebab($objTable->plural),
            'snake_plural' => $objTable->snakePlural,
            'singular_uc' => ucwords($objTable->singular),
            'singular' => $objTable->singular,
            'snake_singular' => $objTable->snakeSingular,

            // Controller
            'uses' => $prepareUses(),
            'validators' => $prepareValidators(),
            'plucks' => $preparePlucks(),

            // Model
            'namespace' => substr($this->pathModels,0,-1),
            'use_soft_deletes' => $useSoftDeletes,
            'trait_soft_deletes' => $traitSoftDeletes,
            'primary_key' => $primary,
            'auto_increment' => $incrementing,
            'fillable' => $prepareFillable(),
            'hidden' => '',
            'with' => $prepareWith(),
            'dates' => $prepareDates(),
            'belongs_to' => $prepareSubTemplates('belongs'),
            'has_one' => $prepareSubTemplates('one'),
            'has_many' => $prepareSubTemplates('many'),
            'belongs_many' => $prepareSubTemplates('belongsMany'),
            'sync_relationships' => $prepareSubTemplates('syncRelationships'),
            'relationships' => $prepareSubTemplates('relationships'),
        ];

        return $marks;
    }

    /**
     * [marks description]
     * @return [type] [description]
     */
    public function marks()
    {
        return [
            'controller' => [
                'plural_uc',
                'plural',
                'kebab_plural',
                'singular_uc',
                'singular',
                'uses',
                'validators',
                'plucks',
            ],

            'model' => [
                'table_name',
                'plural_uc',
                'plural',
                'snake_plural',
                'singular_uc',
                'singular',
                'namespace',
                'use_soft_deletes',
                'trait_soft_deletes',
                'primary_key',
                'auto_increment',
                'fillable',
                'hidden',
                'with',
                'dates',
                'belongs_to',
                'has_one',
                'has_many',
                'belongs_many',
                'sync_relationships',
                'relationships',
            ],

            'pivot' => [
                'plural_uc',
                'plural',
                'singular_uc',
                'singular',
                'namespace',
                'primary_key',
                'auto_increment',
                'fillable',
                'hidden',
                'with',
                'dates',
                'belongs_to',
                'has_one',
                'has_many',
                'belongs_many',
            ],

            'belongs' => [
                'singular_uc',
                'singular',
                'use_model',
                'primary_model',
            ],

            'many' => [
                'plural',
                'singular_uc',
                'singular',
                'use_model',
                'primary_model',
            ],

            'one' => [
                'singular_uc',
                'singular',
                'use_model',
                'primary_model',
            ],

            'belongsMany' => [
                'plural',
                'singular_uc',
                'singular',
                'use_model',
                'fk_model',
                'relation_table',
            ],

            'syncRelationships' => [
                'plural_uc',
                'plural',
            ],

            'relationships' => [
                'plural',
            ],

            'routes' => [
                'kebab_plural',
                'plural_uc',
                'plural',
            ],
        ];
    }

    /**
     * [processFile description]
     * @param  string $type [description]
     * @return [type]       [description]
     */
    public function processFile(string $type)
    {
        $this->alert(strtoupper($type) . ' PROCESS');

        if ($type == 'model' && strtoupper($this->option('base-model')) == 'Y') {
            // Make the model object
            $objMod = new \stdClass();
            $objMod->singular = 'model';
            $objMod->arqs = [
                $type => str_replace('{{{namespace}}}',
                                    trim(substr($this->pathModels,0,-1)),
                                    $this->getTemplate('baseModel')),
            ];

            $this->createFile($type, $objMod);
        }

        foreach ($this->tables as $key => $table) {
            // dump($table->name.' - '.$table->relationTable.' - '.$type);
            // if ($table->relationTable === true && $type !== 'pivot') {
            //     continue;
            // }

            if ($table->relationTable !== true && $type === 'pivot') {
                continue;
            }

            if (trim($this->option('table')) !== 'all') {
                $tableKey = $this->option('table');
                if ((int) $tableKey !== (int) $key) {
                    continue;
                }
            }

            $table->arqs = [
                $type => $this->getTemplate($type),
            ];

            foreach ($this->marks()[$type] as $mark){
                $table->arqs[$type] = str_replace('{{{' . $mark . '}}}',
                                                        trim($table->marks[$mark]),
                                                        $table->arqs[$type]);
            }

            //$this->info($table->arqs[$type]);
            $this->createFile($type, $table);
        }
    }

    /**
     * [createFile description]
     *
     * @param  string $type [description]
     * @param  string $arq  [description]
     * @return [type]       [description]
     */
    public function createFile(string $type, $objTable)
    {
        $pathModels = explode('\\',$this->pathModels);
        unset($pathModels[0]);

        // Paths type
        $paths = [
            'controller' => base_path('app') . '/Http/Controllers/',
            'model' => base_path('app') . '/' . implode('/',$pathModels),
        ];

        // Name Arq
        $prepareNameArq = function ($t) use ($objTable) {
            $nameArq = '';

            switch ($t) {
                case 'controller':
                    $nameArq = ucwords($objTable->plural) . 'Controller.php';
                    break;

                case 'model':
                    $nameArq = ucwords($objTable->singular) . '.php';
                    break;

                default:
                    $nameArq = $t . '.php';
            }

            return $nameArq;
        };

        @mkdir($paths[$type]);
        $file = fopen($paths[$type] . $prepareNameArq($type), 'w');
        fwrite($file, $objTable->arqs[$type]);
        fclose($file);
    }

    /**
     * [processRoutes description]
     * @return [type] [description]
     */
    public function processPostman()
    {
        if ($this->postman) {
            $collectionId=Str::random(10);
            $main = $this->getTemplate('main_postman');
            $request = $this->getTemplate('request_postman');
            $folder = $this->getTemplate('folder_postman');
            $request_postman = '';
            $folder_postman = '';
            $folders_order=[];
            
            foreach ($this->tables as $table) {
                // dump($table);
                array_push($folders_order,'folder-'.Str::kebab($table->plural));
                $m = [
                    'id' => Str::kebab($table->plural),
                    'url' => url(str_replace('data-','',Str::kebab($table->plural))),
                    'collectionId' => $collectionId,
                    'folder' => Str::kebab($table->plural),
                ];
                $temp = $request;
                foreach ($m as $key=>$mark){
                    $temp = str_replace('{{{' . $key . '}}}', trim($mark), $temp);
                }
                $request_postman .= $temp;

                $m = [
                    'id' => Str::kebab($table->plural),
                    'name' => Str::upper($table->name),
                    'collectionId' => $collectionId,
                ];

                $temp = $folder;
                foreach ($m as $key=>$mark){
                    $temp = str_replace('{{{' . $key . '}}}', trim($mark), $temp);
                }
                $folder_postman .= $temp;
            }
            $m = [
                'id' => $collectionId,
                'folders' => substr_replace($folder_postman,'',-3,1),
                'folders_order' => json_encode($folders_order),
                'requests' => substr_replace($request_postman,'',-3,1),
            ];

            foreach ($m as $key=>$mark){
                $main = str_replace('{{{' . $key . '}}}', trim($mark), $main);
            }
            $fileWeb = fopen(base_path() . '/CRUDAPI.json', 'w+');
            fwrite($fileWeb, $main);
            fclose($fileWeb);
        }
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        // Process Routes
        $this->processOptionRoutes();

        // Process Path Models
        $this->processOptionPathModels();

        // Process Postman
        $this->processOptionPostman();

        // Process TABLES
        $this->processOptionTable();

        // Process Controller
        $this->processFile('controller');

        // Process Model
        $this->processFile('model');

        // Process Routes
        $this->processRoutes();

        // Process Routes
        $this->processPostman();

    }

    private function singular($text){
        if(substr($text,0,5) == "data_")
            return str_replace("data_","",$text);
        else
            return $text;
    }
    private function plural($text){
        if(strpos($text,"data_") === false)
            return "data_".$text;
        else
            return $text;
    }
}
