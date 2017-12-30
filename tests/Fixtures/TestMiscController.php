<?php

namespace AlexWells\ApiDocsGenerator\Tests\Fixtures;

use Illuminate\Routing\Controller;

class TestMiscController extends Controller
{
    /**
     * Endpoint title
     *
     * And a long description
     * on many,
     * many,
     * many lines.
     */
    public function titleAndDescription() {}

    public function automaticTitle() {}

    /**
     * Hidden from docs
     *
     * @docsHide
     */
    public function hidden() {}
}