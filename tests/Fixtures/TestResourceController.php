<?php

namespace AlexWells\ApiDocsGenerator\Tests\Fixtures;

use Illuminate\Routing\Controller;

/**
 * @resource Some resource
 */
class TestResourceController extends Controller
{
    public function has() {}

    /**
     * @resource Overwritten resource
     */
    public function overwritten() {}

    /**
     * @resource "General", "Sub-category", "Last one"
     */
    public function nested() {}

    /**
     * @resource
     */
    public function invalid() {}
}