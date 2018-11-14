<?php

namespace Tests\Platform\Domains\Resource;

use SuperV\Platform\Domains\Database\Schema\Blueprint;
use SuperV\Platform\Domains\Resource\Resource;
use SuperV\Platform\Domains\Resource\ResourceBlueprint;

class ResourceConfigTest extends ResourceTestCase
{
    function test__saves_resource_key()
    {
        $res = $this->create('t_users', function (Blueprint $table , ResourceBlueprint $resource) {
            $table->increments('id');

            $resource->resourceKey('user');
        });

        $this->assertEquals('user', $res->resourceKey());
    }
    /** @test */
    function builds_label_from_table_name()
    {
        $this->create('customers', function (Blueprint $table) {
            $table->increments('id');
        });

        $this->assertEquals('Customers', Resource::of('customers')->getLabel());
        $this->assertEquals('Customer', Resource::of('customers')->singularLabel());
    }

    /** @test */
    function builds_label_from_given()
    {
        $this->create('customers', function (Blueprint $table, ResourceBlueprint $resource) {
            $table->increments('id');

            $resource->label('SuperV Customers');
            $resource->singularLabel('Customer');
        });

        $this->assertEquals('SuperV Customers', Resource::of('customers')->getLabel());
        $this->assertEquals('Customer', Resource::of('customers')->singularLabel());
    }

    /** @test */
    function builds_label_for_resource_entry()
    {
        $res = $this->create('customers', function (Blueprint $table, ResourceBlueprint $resource) {
            $table->increments('id');
            $table->string('first_name');
            $table->string('last_name');

            $resource->entryLabel('{last_name}, {first_name}');
        });

        $entry = $res->fake(['first_name' => 'Nicola', 'last_name' => 'Tesla']);

        $this->assertEquals('Tesla, Nicola', $entry->getLabel());
    }

    /** @test */
    function makes_entry_label_from_marked_column()
    {
        $res = $this->create('customers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('first_name');
            $table->string('last_name')->entryLabel();
        });

        $entry = $res->fake();

        $this->assertEquals($entry->getAttribute('last_name'), $entry->getLabel());
    }

    /** @test */
    function guesses_entry_label_from_string_columns()
    {
        $this->makeResource('A_users', ['name']);
        $this->assertEquals('{name}', Resource::of('A_users')->entryLabelTemplate());

        $this->makeResource('B_users', ['address', 'age:integer', 'title']);
        $this->assertEquals('{title}', Resource::of('B_users')->entryLabelTemplate());

        $this->makeResource('C_users', ['height:decimal', 'age:integer', 'address']);
        $this->assertEquals('{address}', Resource::of('C_users')->entryLabelTemplate());

        $this->makeResource('customers', ['height:decimal', 'age:integer']);
        $this->assertEquals('Customer #{id}', Resource::of('customers')->entryLabelTemplate());
    }
}