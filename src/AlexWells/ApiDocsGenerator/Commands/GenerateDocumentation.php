<?php

namespace AlexWells\ApiDocsGenerator\Commands;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use AlexWells\ApiDocsGenerator\Parsers\RouteWrapper;
use AlexWells\ApiDocsGenerator\Postman\CollectionGenerator;
use AlexWells\ApiDocsGenerator\Exceptions\RouteGenerationError;

class GenerateDocumentation extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api-docs:generate
                            {--o|output=public/docs : The output path for the generated documentation}
                            {--m|masks=* : Route masks to check}
                            {--noPostmanGeneration : Disable Postman collection generation}
                            {--noTypeChecks : Skip \'no type specified\' parameter exceptions}
                            {--traces : Show full exception traces (non-standard only)}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate your API documentation from existing Laravel routes.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->addStyles();

        if (! count($this->option('masks'))) {
            $this->error('You must provide at least one route mask.');

            return;
        }

        $parsedRoutes = $this->processRoutes();

        $this->writeAll($parsedRoutes);
    }

    /**
     * Process routes.
     *
     * @return array[]
     */
    private function processRoutes()
    {
        $parsedRoutes = [];

        foreach ($this->getRoutes() as $route) {
            $label = '<red>[' . implode(',', $route->getMethods()) . '] ' . $route->getUri() . ' at ' . $route->getActionSafe() . '</red>';

            $this->overwrite("Processing route $label", 'info');

            if(
                // Does this route match route mask
                ! $route->matchesAnyMask($this->option('masks')) ||
                // Is it valid
                ! $route->isSupported() ||
                // Should it be skipped
                $route->isHiddenFromDocs()
            ) {
                $this->overwrite("Skipping route $label", 'warn');
                continue;
            }

            try {
                $parsedRoutes[] = $route->getSummary();
                $this->overwrite("Processed route $label", 'info');
            } catch (RouteGenerationError $exception) {
                $this->output->writeln('');
                $this->warn($exception->getMessage());
            } catch (\Exception $exception) {
                $this->output->writeln('');
                $exceptionStr = $this->option('traces') ? $exception : $exception->getMessage();
                $this->error('Failed to process: ' . $exceptionStr);
                continue;
            }
            $this->info('');
        }
        $this->info('');

        return $parsedRoutes;
    }

    /**
     * Get all routes wrapped in helper class.
     *
     * @return RouteWrapper[]
     */
    private function getRoutes()
    {
        return array_map(function ($route) {
            return new RouteWrapper($route, $this->options());
        }, Route::getRoutes()->get());
    }

    /**
     * Writes parsed routes into everything needed (html, postman collection).
     *
     * @param  RouteWrapper[] $parsedRoutes
     *
     * @return void
     */
    private function writeAll($parsedRoutes)
    {
        $outputPath = $this->option('output');

        if(! is_dir($outputPath)) {
            mkdir($outputPath, 0777, true);
        }

        $documentation = view('api-docs::documentation', compact('parsedRoutes'));

        file_put_contents($outputPath . DIRECTORY_SEPARATOR . 'index.html', $documentation);

        // broken :(
        /*if ($this->option('noPostmanGeneration') !== true) {
            $collection = (new CollectionGenerator($parsedRoutes))->getCollection();

            file_put_contents($outputPath . DIRECTORY_SEPARATOR . 'collection.json', $collection);
        }*/
    }
}
