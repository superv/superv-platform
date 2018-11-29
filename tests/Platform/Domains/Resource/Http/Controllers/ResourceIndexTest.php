<?php

namespace Tests\Platform\Domains\Resource\Http\Controllers;

use SuperV\Platform\Domains\Database\Model\Contracts\EntryContract;
use SuperV\Platform\Domains\Database\Schema\Blueprint;
use SuperV\Platform\Domains\Resource\Filter\SearchFilter;
use SuperV\Platform\Domains\Resource\Resource;
use Tests\Platform\Domains\Resource\Fixtures\HelperComponent;
use Tests\Platform\Domains\Resource\ResourceTestCase;

class ResourceIndexTest extends ResourceTestCase
{
    function test__bsmllh()
    {
        $users = $this->schema()->users();

        $page = $this->getPageFromUrl($users->route('index'));
        $table = HelperComponent::from($page->getProp('blocks.0'));

        $this->assertEquals('sv-loader', $table->getName());
        $this->assertEquals(sv_url($users->route('index.table')), $table->getProp('url'));
    }

    function test__index_table_config()
    {
        $users = $this->schema()->users();

        $response = $this->getJsonUser($users->route('index.table'))->assertOk();
        $table = HelperComponent::from($response->decodeResponseJson('data'));

        $this->assertEquals(sv_url($users->route('index.table').'/data'), $table->getProp('config.data_url'));

        $fields = $table->getProp('config.fields');
        $this->assertEquals(3, count($fields));
        foreach ($fields as $key => $field) {
            $this->assertTrue(is_numeric($key));
            $this->assertEquals([
                'uuid',
                'name',
                'label',
            ], array_keys($field));

            $rowActions = $table->getProp('config.row_actions');
            $this->assertEquals(1, count($rowActions));

            $this->assertEquals([
                'name'  => 'view',
                'title' => 'View',
                'url'   => 'sv/res/t_users/{entry.id}/view',
            ], $rowActions[0]['props']);
        }
    }

    function test__index_table_data()
    {
        $this->withoutExceptionHandling();
        $users = $this->schema()->users();
        $userA = $users->fake(['group_id' => 1]);
        $userB = $users->fake(['group_id' => 2]);

        $rows =  $this->getTableRowsOfResource($users);
        $this->assertEquals(2, count($rows));

        $rowA = $rows[0];
        $this->assertEquals($userA->getId(), $rowA['id']);

        $label = $rowA['fields'][0];
        $this->assertEquals(['type', 'name', 'value',], array_keys($label));
        $this->assertEquals('text', $label['type']);
        $this->assertEquals('label', $label['name']);
        $this->assertEquals($users->getEntryLabel($userA), $label['value']);

        $age = $rowA['fields'][1];
        $this->assertEquals('number', $age['type']);
        $this->assertEquals('age', $age['name']);
        $this->assertSame((int)$userA->age, $age['value']);

        $group = $rowA['fields'][2];
        $this->assertEquals('belongs_to', $group['type']);
        $this->assertEquals('group_id', $group['name']);
        $this->assertNull(array_get($group, 'meta.options'));

        $groups = sv_resource('t_groups');
        $usersGroup = $groups->find(1);
        $this->assertSame($usersGroup->title, $group['value']);
        $this->assertEquals($groups->route('view', $usersGroup), $group['meta']['link']);
    }

    function test__fields_extending()
    {
        Resource::extend('t_posts')->with(function (Resource $resource) {
            $resource->getField('user')
                     ->showOnIndex()
                     ->setCallback('table.presenting', function (EntryContract $entry) {
                         return $entry->user->email;
                     });
        });

        $this->withExceptionHandling();
        $users = $this->schema()->users();
        $userA = $users->fake(['group_id' => 1]);

        $posts = $this->schema()->posts();
        $posts->fake(['user_id' => $userA->getId()]);
        $posts->fake(['user_id' => $userA->getId()]);

        $row = $this->getJsonUser($posts->route('index.table').'/data')->decodeResponseJson('data.rows.0');

        $fields = collect($row['fields'])->keyBy('name');
        $this->assertEquals($userA->email, $fields->get('user_id')['value']);
    }

    function test__filters_in_table_config()
    {
        Resource::extend('t_users')->with(function (Resource $resource) {
            $resource->addFilter(new SearchFilter);
        });

        $table = $this->getTableConfigOfResource($this->schema()->users());

        $filter = $table->getProp('config.filters.0');
        $this->assertEquals('search', $filter['name']);
        $this->assertEquals('text', $filter['type']);
    }

    function test__filters_apply()
    {
        $this->withoutExceptionHandling();
        $users = $this->schema()->users();
        Resource::extend('t_users')->with(function (Resource $resource) {
            $resource->searchable(['name']);
        });
        $users->fake(['name' => 'yks']);
        $users->fake(['name' => 'none']);
        $users->fake(['name' => 'none']);

        $this->assertEquals(1, count($this->getTableRowsOfResource($users, 'filters[search]=yks')));
    }

    function test__filters_apply_on_relations()
    {
        $this->withoutExceptionHandling();
        $users = $this->schema()->users();
        Resource::extend('t_users')->with(function (Resource $resource) {
            $resource->searchable(['group.title']);
        });
        $group = sv_resource('t_groups')->create(['title' => 'Ottomans']);
        $users->fake(['group_id' => $group->getId()]);
        $users->fake(['group_id' => $group->getId()]);
        $users->fake();

        $this->assertEquals(2, count($this->getTableRowsOfResource($users, 'filters[search]=tto')));
    }

    function test__builds_search_filter_from_searchable_fields()
    {
        $this->withoutExceptionHandling();
        $users = $this->schema()->users(function(Blueprint $table) {
            $table->getColumn('name')->searchable();
        });
        $users->fake(['name' => 'abced']);
        $users->fake(['name' => 'dcedg']);
        $users->fake(['name' => 'ced4']);
        $users->fake(['name' => 'xywx']);

        $this->assertEquals(3, count($this->getTableRowsOfResource($users, 'filters[search]=ced')));
    }
}



