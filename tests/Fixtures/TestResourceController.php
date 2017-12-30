<?php

namespace AlexWells\ApiDocsGenerator\Tests\Fixtures;

use Illuminate\Routing\Controller;

/**
 * @resource Some resource
 */
class TestResourceController extends Controller
{
    public function hasResource() {}

    /**
     * @resource Overwritten resource
     */
    public function overwriteResource() {}

    /**
     * @resource
     */
    public function invalid() {}
}