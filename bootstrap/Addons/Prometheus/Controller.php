<?php
declare(strict_types=1);
namespace Framework\Addons\Prometheus;

use Framework\Http\AbstractController;
use Framework\Http\Objects\Request;
use Framework\Http\Objects\Response;
use Framework\Http\ViewEngine\ViewEngineInterface;

class Controller extends AbstractController
{
    public function __construct(ViewEngineInterface $viewEngine, protected MetricRegistry $metricRegistry)
    {
        parent::__construct($viewEngine);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function metrics(Request $request): Response
    {
        return $this->text($this->metricRegistry->render());
    }
}