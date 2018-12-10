<?php

namespace Tests\Platform\Domains\Resource\Form;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use SuperV\Platform\Domains\Database\Model\Contracts\EntryContract;
use SuperV\Platform\Domains\Database\Model\Contracts\Watcher;
use SuperV\Platform\Domains\Database\Schema\Blueprint;
use SuperV\Platform\Domains\Media\Media;
use SuperV\Platform\Domains\Resource\Field\Contracts\Field;
use SuperV\Platform\Domains\Resource\Field\FieldComposer;
use SuperV\Platform\Domains\Resource\Field\FieldFactory;
use SuperV\Platform\Domains\Resource\Field\Types\FieldType;
use SuperV\Platform\Domains\Resource\Form\Form;
use SuperV\Platform\Domains\Resource\Form\FormConfig;
use Tests\Platform\Domains\Resource\ResourceTestCase;

class FormTest extends ResourceTestCase
{
    /** @var \SuperV\Platform\Domains\Resource\Resource */
    protected $users;

    protected function setUp()
    {
        parent::setUp();

        $this->users = $this->create('t_users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->unsignedInteger('age');
        });
    }

    function test__config()
    {
        $fields = $this->makeFields();
        $watcher = new FormTestUser(['name' => 'Omar', 'age' => 33]);

        $config = FormConfig::make()
                            ->addGroup($fields, $watcher, 'default')
                            ->hideField('age');

        $form = $config->makeForm();
        $this->assertInstanceOf(Form::class, $form);
        $this->assertEquals(2, $form->getFieldsFlat()->count());
        $this->assertEquals($watcher, $form->getEntryForHandle());
        $this->assertEquals(['age'], $config->getHiddenFields());
    }

    function t1qqest_makes_create_form()
    {
        $config = FormConfig::make()
                            ->setUrl(sv_url('url/to/form'))
                            ->addGroup($fields = $this->makeFields(), $testUser = new FormTestUser());

        $form = $config->makeForm();
        $this->assertEquals($fields, $form->getFieldsFlat()->all());
        $this->assertEquals([
            'url'     => sv_url('url/to/form'),
            'method'  => 'post',
            'fields'  => [
                $form->composeField('name')->get(),
                $form->composeField('age')->get(),
            ],
            'actions' => [],
        ], sv_compose($form->compose()));
    }

    function test_makes_update_form()
    {
        $form = FormConfig::make()
                          ->addGroup(
                              $fields = $this->makeFields(),
                              $testUser = new FormTestUser(['name' => 'Omar', 'age' => 33])
                          )
                          ->makeForm();

        $this->assertEquals('Omar', $this->getComposedValue($form->getField('name'), $testUser));
        $this->assertEquals(33, $this->getComposedValue($form->getField('age'), $testUser));
    }

    protected function getComposedValue($field, $entry = null)
    {
        return (new FieldComposer($field))->forForm($entry)->get('value');
    }

    function test__saves_form()
    {
        $form = FormConfig::make()
                          ->addGroup(
                              $fields = $this->makeFields(),
                              $testUser = new FormTestUser(['name' => 'Omar', 'age' => 33])
                          )
                          ->makeForm()
                          ->setRequest($this->makePostRequest(['name' => 'Omar', 'age' => 33]))
                          ->save();

        $this->assertEquals('Omar', $form->composeField('name', $testUser)->get('value'));
        $this->assertEquals(33, $form->composeField('age', $testUser)->get('value'));
    }

    function test__hidden_fields()
    {
        $form = FormConfig::make()
                          ->setUrl('url/to/form')
                          ->addGroup(
                              $fields = $this->makeFields(),
                              $testUser = new FormTestUser(['name' => 'Omar', 'age' => 33])
                          )
                          ->hideField('name')
                          ->makeForm()
                          ->setRequest($this->makePostRequest(['name' => 'Ali', 'age' => 99]))
                          ->save();

        $nameField = $form->getField('name');
        $this->assertTrue($nameField->isHidden());

        $this->assertEquals('Omar', $form->composeField('name', $testUser)->get('value'));
        $this->assertEquals(99, $form->composeField('age', $testUser)->get('value'));

        $composedFields = $form->compose()->get('fields');
        $this->assertEquals(1, count($composedFields));
        $this->assertEquals(array_values($composedFields), $composedFields);
    }

    function test__saves_entry()
    {
        $form = FormConfig::make()
                          ->addGroup($fields = $this->makeFields(), $testUser = new FormTestUser)
                          ->makeForm()->setRequest($this->makePostRequest(['name' => 'Omar', 'age' => 33]))
                          ->save();

        $this->assertEquals('Omar', $form->composeField('name', $testUser)->get('value'));
        $this->assertEquals(33, $form->composeField('age', $testUser)->get('value'));

        $user = $form->getEntryForHandle('default');
        $this->assertEquals('Omar', $user->name);
        $this->assertEquals(33, $user->age);
        $this->assertTrue($user->wasRecentlyCreated);
    }

    function test__returns_watcher()
    {
        $form = FormConfig::make()
                          ->addGroup($fields = $this->makeFields(), $user = new FormTestUser, 'user')
                          ->makeForm();

        $this->assertEquals($user, $form->getEntryForHandle('user'));
    }

    function test__resource_create_over_http()
    {
        $response = $this->getJsonUser($this->users->route('create'));
        $response->assertOk();

        $props = $response->decodeResponseJson('data.props.blocks.0.props');
        $this->assertEquals(['url', 'method', 'fields', 'actions'], array_keys($props));
        $this->assertEquals(2, count($props['fields']));

        $response = $this->postJsonUser($props['url'], [
            'name' => 'Omar',
            'age'  => 33,
        ]);
        $response->assertOk();

        $user = $this->users->first();
        $this->assertEquals('Omar', $user->name);
        $this->assertEquals(33, $user->age);
    }

    function test__saves_multiple_entries()
    {
        $posts = $this->create('test_posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
        });

        $form = FormConfig::make()
                          ->addGroup($this->users, $user = $this->users->newEntryInstance(), 'test_user')
                          ->addGroup($posts,  $post = $posts->newEntryInstance(), 'test_post')
                          ->makeForm()
                          ->setRequest($this->makePostRequest([
                              'name'  => 'Omar',
                              'title' => 'Khalifa',
                              'age'   => 33,
                          ]))
                          ->save();

        $this->assertEquals('Omar', $form->composeField($form->getField('name', 'test_user'), $user)->get('value'));
        $this->assertEquals('Khalifa', $form->composeField($form->getField('title', 'test_post'), $post)->get('value'));

        $user = $form->getEntryForHandle('test_user');
        $this->assertEquals('Omar', $user->name);
        $this->assertTrue($user->wasRecentlyCreated);

        $post = $form->getEntryForHandle('test_post');
        $this->assertEquals('Khalifa', $post->title);
        $this->assertTrue($post->wasRecentlyCreated);
    }

    function test__save_handles_fields_mutating_callbacks()
    {
        $fields = [
            $textField = $this->makeField(['name' => 'name', 'type' => 'text']),
            $ageField = $this->makeField(['name' => 'age', 'type' => 'number']),
            $fileField = $this->makeField(['name' => 'avatar', 'type' => 'file']),
        ];

        $file = new UploadedFile($this->basePath('__fixtures__/square.png'), 'square.png');

        $form = FormConfig::make()
                          ->addGroup($fields, $testUser = new FormTestUser)
                          ->makeForm()
                          ->setRequest($this->makePostRequest(['name'   => 'Omar',
                                                               'age'    => 33,
                                                               'avatar' => $file]));

        $form->getField('avatar');

        $form->save();

        $this->assertNull($testUser->avatar);

        $this->assertEquals(1, Media::count());
    }

    protected function makeFields(): array
    {
        return [
            FieldFactory::createFromArray(['name' => 'name', 'type' => 'text']),
            FieldFactory::createFromArray(['name' => 'age', 'type' => 'number']),
        ];
    }

    protected function makeField(array $params): Field
    {
        return FieldFactory::createFromArray($params);
    }
}

class FormTestUser extends Model implements Watcher, EntryContract
{
    public $timestamps = false;

    protected $table = 't_users';

    protected $guarded = [];

    public function getId()
    {
        return $this->getKey();
    }
}

class TestFileFieldType extends FieldType
{
    protected $hasColumn = false;
}